<?php

namespace app\common\service\updater;

class DbCompareService
{
    protected string $remoteApiUrl;
    protected string $secretToken;
    protected \PDO   $pdoRef;
    protected \PDO   $pdoTarget;
    protected string $refPrefix = 'la_';
    protected string $targetPrefix = 'la_';

    public function __construct()
    {
        $this->remoteApiUrl = config('updater.remote_api_url');
        $this->secretToken  = config('updater.secret_token');

        // reference 库连接信息从远端 API 动态获取，本地 updater.php 无需配置 db.reference
        $ref             = $this->fetchRemoteDbConfig();
        $target          = config('updater.db.target');
        $this->refPrefix    = $this->resolvePrefix($ref, 'la_');
        $this->targetPrefix = $this->resolvePrefix($target, 'la_');
        $this->pdoRef    = $this->makePdo($ref);
        $this->pdoTarget = $this->makePdo($target);
    }

    /**
     * 比对两库结构差异，返回差异描述和待执行 SQL
     */
    public function compare(): array
    {
        $this->verifyToken();

        $diffs = [];
        $sqls  = [];

        $refTables    = $this->getTableMap($this->pdoRef, $this->refPrefix);
        $targetTables = $this->getTableMap($this->pdoTarget, $this->targetPrefix);

        foreach ($refTables as $logicalTable => $refTable) {
            $targetTable = $targetTables[$logicalTable] ?? $this->applyPrefix($logicalTable, $this->targetPrefix);

            // 1. 新增表
            if (!isset($targetTables[$logicalTable])) {
                $sqls[]  = $this->mapCreateTableSql($this->getCreateTable($this->pdoRef, $refTable), $refTable, $targetTable);
                $diffs[] = ['type' => 'new_table', 'msg' => "新增表：`{$targetTable}`"];
                continue;
            }

            // 2. 新增 / 修改字段
            $refCols    = $this->getColumns($this->pdoRef, $refTable);
            $targetCols = $this->getColumns($this->pdoTarget, $targetTable);

            $prevCol = null;
            foreach ($refCols as $colName => $refCol) {
                if (!isset($targetCols[$colName])) {
                    $def = $this->buildColumnDef($refCol);
                    if ($def === null) {
                        $diffs[] = ['type' => 'skip_generated_column', 'msg' => "跳过生成列：`{$targetTable}`.`{$colName}`，需在版本 SQL 中手动添加"];
                    } else {
                        // 尽量保持与参考库一致的列顺序
                        $position = ($prevCol !== null && isset($targetCols[$prevCol])) ? " AFTER `{$prevCol}`" : ($prevCol === null ? ' FIRST' : '');
                        $sqls[]   = "ALTER TABLE `{$targetTable}` ADD COLUMN `{$colName}` {$def}{$position};";
                        $diffs[]  = ['type' => 'add_column', 'msg' => "新增字段：`{$targetTable}`.`{$colName}`"];
                        $targetCols[$colName] = $refCol; // 后续列可以 AFTER 它
                    }
                } elseif ($this->isColumnChanged($refCol, $targetCols[$colName])) {
                    $def = $this->buildColumnDef($refCol);
                    if ($def === null) {
                        $diffs[] = ['type' => 'skip_generated_column', 'msg' => "跳过生成列变更：`{$targetTable}`.`{$colName}`"];
                    } else {
                        $sqls[]  = "ALTER TABLE `{$targetTable}` MODIFY COLUMN `{$colName}` {$def};";
                        $diffs[] = ['type' => 'modify_column', 'msg' => "更新字段：`{$targetTable}`.`{$colName}`"];
                    }
                }
                $prevCol = $colName;
            }

            // 3. 新增索引
            $refIdx    = $this->getIndexes($this->pdoRef, $refTable);
            $targetIdx = $this->getIndexes($this->pdoTarget, $targetTable);

            foreach ($refIdx as $idxName => $idxDef) {
                if (!isset($targetIdx[$idxName]) && $idxName !== 'PRIMARY') {
                    $isUnique = ((int)$idxDef['non_unique'] === 0);
                    if ($isUnique && $this->hasDuplicateValues($this->pdoTarget, $targetTable, $idxDef['columns'])) {
                        $diffs[] = [
                            'type' => 'skip_unique_index',
                            'msg'  => "跳过唯一索引：`{$targetTable}`.`{$idxName}`，存在重复数据，留给版本 SQL 去重后再加",
                        ];
                        continue;
                    }
                    $cols    = implode(',', $idxDef['parts']);
                    $unique  = $isUnique ? 'UNIQUE ' : '';
                    $sqls[]  = "ALTER TABLE `{$targetTable}` ADD {$unique}INDEX `{$idxName}` ({$cols});";
                    $diffs[] = ['type' => 'add_index', 'msg' => "新增索引：`{$targetTable}`.`{$idxName}`"];
                }
            }
        }

        return ['diffs' => $diffs, 'sqls' => $sqls];
    }

    /**
     * 执行单条 SQL
     */
    public function execute(string $sql): void
    {
        $this->verifyToken();

        $uniqueInfo = $this->parseAddUniqueIndexSql($sql);
        if ($uniqueInfo !== null && $this->hasDuplicateValues($this->pdoTarget, $uniqueInfo['table'], $uniqueInfo['columns'])) {
            return;
        }

        $this->pdoTarget->exec($sql);
    }

    // ===== 私有工具方法 =====

    /**
     * 从远端 cloud_api.php 拉取并解密 reference 数据库连接配置
     *
     * 云端用 AES-256-CBC 加密，密钥为 sha256(secret_token) 前 32 字节。
     * 响应结构：{ status, data: { iv: base64, payload: base64 } }
     *
     * @throws \Exception 网络失败、解密失败或响应格式错误时抛出
     */
    private function fetchRemoteDbConfig(): array
    {
        $url      = $this->remoteApiUrl . '?action=dbconfig&token=' . urlencode($this->secretToken);
        $response = $this->httpGet($url);

        if ($response['code'] !== 200 || empty($response['body'])) {
            throw new \Exception("获取远端数据库配置失败，HTTP {$response['code']}：{$response['error']}");
        }

        $result = json_decode($response['body'], true);
        if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
            throw new \Exception('远端数据库配置响应异常：' . ($result['message'] ?? '未知'));
        }

        $iv      = base64_decode($result['data']['iv']      ?? '');
        $payload = base64_decode($result['data']['payload'] ?? '');

        if (empty($iv) || empty($payload)) {
            throw new \Exception('远端数据库配置响应缺少加密字段');
        }

        // 密钥与云端保持一致：sha256(secret_token) 前 32 字节
        $key   = substr(hash('sha256', $this->secretToken, true), 0, 32);
        $plain = openssl_decrypt($payload, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new \Exception('数据库配置解密失败，请检查 secret_token 是否与云端一致');
        }

        $cfg = json_decode($plain, true);
        if (!is_array($cfg) || empty($cfg['host'])) {
            throw new \Exception('数据库配置解密后格式错误');
        }

        return $cfg;
    }

    /**
     * 校验 secretToken 与远程是否一致，不一致直接抛出异常
     *
     * 注：云端 cloud_api.php 所有 action 均需 token，
     * 此处用 dbconfig 接口做一次轻量校验（构造函数已调用过一次，可复用结果）
     */
    private function verifyToken(): void
    {
        $url      = $this->remoteApiUrl . '?action=dbconfig&token=' . urlencode($this->secretToken);
        $response = $this->httpGet($url);

        if ($response['code'] !== 200 || empty($response['body'])) {
            throw new \Exception("Token 校验请求失败，HTTP {$response['code']}：{$response['error']}");
        }

        $result = json_decode($response['body'], true);
        if (($result['status'] ?? '') !== 'success') {
            throw new \Exception('Token 校验失败：' . ($result['message'] ?? '未知'));
        }
    }

    private function makePdo(array $cfg): \PDO
    {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
        $pdo = new \PDO($dsn, $cfg['user'], $cfg['pass']);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function getTables(\PDO $pdo): array
    {
        return $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getTableMap(\PDO $pdo, string $prefix): array
    {
        $map = [];
        foreach ($this->getTables($pdo) as $table) {
            $logicalTable = $this->stripPrefix($table, $prefix);
            if ($prefix !== '' && $logicalTable === $table) {
                continue;
            }
            $map[$logicalTable] = $table;
        }
        ksort($map);
        return $map;
    }

    private function resolvePrefix(array $cfg, string $default): string
    {
        return (string)($cfg['prefix'] ?? $cfg['table_prefix'] ?? $cfg['database_prefix'] ?? $default);
    }

    private function stripPrefix(string $table, string $prefix): string
    {
        if ($prefix === '') {
            return $table;
        }
        return substr($table, 0, strlen($prefix)) === $prefix ? substr($table, strlen($prefix)) : $table;
    }

    private function applyPrefix(string $logicalTable, string $prefix): string
    {
        return $prefix . $logicalTable;
    }

    private function getCreateTable(\PDO $pdo, string $table): string
    {
        $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        return $row['Create Table'] . ';';
    }

    private function mapCreateTableSql(string $sql, string $refTable, string $targetTable): string
    {
        if ($this->refPrefix !== '' && $this->refPrefix !== $this->targetPrefix) {
            $sql = str_replace('`' . $this->refPrefix, '`' . $this->targetPrefix, $sql);
        }

        $sql = preg_replace(
            '/^CREATE TABLE `' . preg_quote($refTable, '/') . '`/i',
            "CREATE TABLE `{$targetTable}`",
            $sql,
            1
        ) ?? $sql;

        return $sql;
    }

    private function getColumns(\PDO $pdo, string $table): array
    {
        $cols = [];
        $stmt = $pdo->query("SHOW FULL COLUMNS FROM `{$table}`");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $cols[$row['Field']] = $row;
        }
        return $cols;
    }

    /**
     * 读取表索引
     *
     * columns 只放纯列名（供 hasDuplicateValues 拼 GROUP BY），
     * parts 放建索引用的列表达式，前缀索引会带上长度，如 `remote_url`(191)。
     * 丢掉前缀长度会让 varchar(1024) utf8mb4 整列进索引（4096 字节），超过 InnoDB 3072 上限直接报 1071。
     */
    private function getIndexes(\PDO $pdo, string $table): array
    {
        $indexes = [];
        $stmt    = $pdo->query("SHOW INDEX FROM `{$table}`");
        $rows    = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // SHOW INDEX 的行序不做保证，按 Seq_in_index 排序后再拼，避免复合索引列序错乱
        usort($rows, static fn(array $a, array $b): int => (int)$a['Seq_in_index'] <=> (int)$b['Seq_in_index']);

        foreach ($rows as $row) {
            $name = $row['Key_name'];
            if (!isset($indexes[$name])) {
                $indexes[$name] = ['non_unique' => $row['Non_unique'], 'columns' => [], 'parts' => []];
            }
            $col     = $row['Column_name'];
            $subPart = $row['Sub_part'] ?? null;

            $indexes[$name]['columns'][] = $col;
            $indexes[$name]['parts'][]   = ($subPart !== null && (int)$subPart > 0)
                ? "`{$col}`(" . (int)$subPart . ")"
                : "`{$col}`";
        }
        return $indexes;
    }

    /**
     * 目标表在指定列上是否已有重复值（加 UNIQUE 前必须先排除）
     */
    private function hasDuplicateValues(\PDO $pdo, string $table, array $columns): bool
    {
        $safeCols = [];
        foreach ($columns as $col) {
            $col = trim((string)$col, " `\t\n\r");
            if ($col === '' || !preg_match('/^[A-Za-z0-9_]+$/', $col)) {
                return true;
            }
            $safeCols[] = '`' . $col . '`';
        }
        if ($safeCols === [] || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return true;
        }

        $colList = implode(',', $safeCols);
        $sql     = "SELECT 1 FROM `{$table}` GROUP BY {$colList} HAVING COUNT(*) > 1 LIMIT 1";
        try {
            $stmt = $pdo->query($sql);
            return $stmt !== false && $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * 解析 ADD UNIQUE INDEX / ADD UNIQUE KEY 语句
     *
     * @return array{table:string,columns:string[]}|null
     */
    private function parseAddUniqueIndexSql(string $sql): ?array
    {
        if (!preg_match(
            '/ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?\s+ADD\s+UNIQUE\s+(?:INDEX|KEY)\s+`?[A-Za-z0-9_]+`?\s*\(((?:[^()]|\(\s*\d+\s*\))+)\)/i',
            $sql,
            $m
        )) {
            return null;
        }

        // 前缀索引列形如 `remote_url`(191)，去掉长度再去查重
        $columns = array_map(
            static fn(string $col): string => trim((string)preg_replace('/\(\s*\d+\s*\)\s*$/', '', trim($col)), " `\t\n\r"),
            explode(',', $m[2])
        );
        $columns = array_values(array_filter($columns, static fn(string $col): bool => $col !== ''));
        if ($columns === []) {
            return null;
        }

        return ['table' => $m[1], 'columns' => $columns];
    }

    /**
     * 由 SHOW FULL COLUMNS 的一行拼出列定义
     *
     * 处理要点：
     *  - CURRENT_TIMESTAMP / NOW() 等函数默认值不能加引号
     *  - MySQL 8 的 Extra 含 DEFAULT_GENERATED 标记，需剥离
     *  - 生成列（VIRTUAL/STORED GENERATED）无法从 SHOW COLUMNS 还原表达式，返回 null 由调用方跳过
     */
    private function buildColumnDef(array $col): ?string
    {
        $extra = strtolower(trim((string)($col['Extra'] ?? '')));

        if (str_contains($extra, 'generated')) {
            // "VIRTUAL GENERATED" / "STORED GENERATED"：无表达式信息，不能重建
            if (preg_match('/\b(virtual|stored)\s+generated\b/', $extra)) {
                return null;
            }
            // MySQL 8: "DEFAULT_GENERATED" / "DEFAULT_GENERATED on update CURRENT_TIMESTAMP"
            $extra = trim(str_replace('default_generated', '', $extra));
        }

        $def = $col['Type'];
        if (!empty($col['Collation'])) {
            $charset = explode('_', $col['Collation'])[0];
            $def    .= " CHARACTER SET {$charset} COLLATE {$col['Collation']}";
        }
        $def .= $col['Null'] === 'NO' ? ' NOT NULL' : ' NULL';

        $default = $col['Default'];
        if ($default !== null) {
            if ($this->isExpressionDefault($default)) {
                $def .= " DEFAULT {$default}";
            } else {
                $def .= " DEFAULT '" . addslashes($default) . "'";
            }
        } elseif ($col['Null'] === 'YES') {
            $def .= ' DEFAULT NULL';
        }

        if ($extra !== '') {
            $def .= ' ' . strtoupper($extra);
        }
        if (!empty($col['Comment'])) {
            $def .= " COMMENT '" . addslashes($col['Comment']) . "'";
        }
        return $def;
    }

    /**
     * 默认值是否为表达式（不能加引号）
     */
    private function isExpressionDefault(string $default): bool
    {
        return (bool)preg_match('/^(CURRENT_TIMESTAMP|NOW|LOCALTIME|LOCALTIMESTAMP|CURRENT_DATE|CURRENT_TIME)(\s*\(\d*\))?$/i', trim($default));
    }

    private function isColumnChanged(array $ref, array $target): bool
    {
        $norm = fn($v) => strtolower(trim(str_replace('DEFAULT_GENERATED', '', (string)$v)));

        return $ref['Type']    !== $target['Type']
            || $ref['Null']    !== $target['Null']
            || $ref['Default'] !== $target['Default']
            || $norm($ref['Extra']) !== $norm($target['Extra']);
    }

    private function httpGet(string $url): array
    {
        $timeout        = (int)(config('updater.timeout', 300));
        $connectTimeout = (int)(config('updater.connect_timeout', 30));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'SystemAdminUpdater/1.0',
        ]);
        $body  = curl_exec($ch);
        $info  = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'code'  => $info['http_code'] ?? 0,
            'body'  => $body,
            'error' => $error,
        ];
    }
}
