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

            foreach ($refCols as $colName => $refCol) {
                if (!isset($targetCols[$colName])) {
                    $def     = $this->buildColumnDef($refCol);
                    $sqls[]  = "ALTER TABLE `{$targetTable}` ADD COLUMN `{$colName}` {$def};";
                    $diffs[] = ['type' => 'add_column', 'msg' => "新增字段：`{$targetTable}`.`{$colName}`"];
                } elseif ($this->isColumnChanged($refCol, $targetCols[$colName])) {
                    $def     = $this->buildColumnDef($refCol);
                    $sqls[]  = "ALTER TABLE `{$targetTable}` MODIFY COLUMN `{$colName}` {$def};";
                    $diffs[] = ['type' => 'modify_column', 'msg' => "更新字段：`{$targetTable}`.`{$colName}`"];
                }
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
                    $cols    = implode('`,`', $idxDef['columns']);
                    $unique  = $isUnique ? 'UNIQUE ' : '';
                    $sqls[]  = "ALTER TABLE `{$targetTable}` ADD {$unique}INDEX `{$idxName}` (`{$cols}`);";
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

    private function getIndexes(\PDO $pdo, string $table): array
    {
        $indexes = [];
        $stmt    = $pdo->query("SHOW INDEX FROM `{$table}`");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $name = $row['Key_name'];
            if (!isset($indexes[$name])) {
                $indexes[$name] = ['non_unique' => $row['Non_unique'], 'columns' => []];
            }
            $indexes[$name]['columns'][] = $row['Column_name'];
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
            '/ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?\s+ADD\s+UNIQUE\s+(?:INDEX|KEY)\s+`?[A-Za-z0-9_]+`?\s*\(([^)]+)\)/i',
            $sql,
            $m
        )) {
            return null;
        }

        $columns = array_map(
            static fn(string $col): string => trim($col, " `\t\n\r"),
            explode(',', $m[2])
        );
        $columns = array_values(array_filter($columns, static fn(string $col): bool => $col !== ''));
        if ($columns === []) {
            return null;
        }

        return ['table' => $m[1], 'columns' => $columns];
    }

    private function buildColumnDef(array $col): string
    {
        $def = $col['Type'];
        if (!empty($col['Collation'])) {
            $charset = explode('_', $col['Collation'])[0];
            $def    .= " CHARACTER SET {$charset} COLLATE {$col['Collation']}";
        }
        $def .= $col['Null'] === 'NO' ? ' NOT NULL' : ' NULL';
        if ($col['Default'] !== null) {
            $def .= " DEFAULT '{$col['Default']}'";
        } elseif ($col['Null'] === 'YES') {
            $def .= ' DEFAULT NULL';
        }
        if (!empty($col['Extra']))   $def .= " {$col['Extra']}";
        if (!empty($col['Comment'])) $def .= " COMMENT '" . addslashes($col['Comment']) . "'";
        return $def;
    }

    private function isColumnChanged(array $ref, array $target): bool
    {
        return $ref['Type']    !== $target['Type']
            || $ref['Null']    !== $target['Null']
            || $ref['Default'] !== $target['Default']
            || $ref['Extra']   !== $target['Extra'];
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
