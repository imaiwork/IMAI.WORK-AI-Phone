<?php

namespace app\adminapi\controller;

use app\common\service\ConfigService;
use app\common\service\station\StationV3MigrationService;
use app\common\service\updater\DbBackupService;
use app\common\service\updater\DbCompareService;
use app\common\service\updater\FileCompareService;
use think\facade\Cache;
use think\facade\Db;

/**
 * 系统更新
 */
class UpdateController extends BaseAdminController
{
    /**
     * 可安全跳过的 DDL 错误码：目标已经处于想要的状态，再执行一次没有意义
     *
     * 1050 表已存在 / 1060 字段已存在 / 1061 索引名已存在 / 1091 要删的字段或索引不存在 / 1826 外键名重复
     */
    private const IDEMPOTENT_DDL_ERRORS = [1050, 1060, 1061, 1091, 1826];

    /**
     * @notes 检查更新
     */
    public function check()
    {
        $version = ConfigService::get('website', 'version', []);

        $response = \app\common\service\ToolsService::Auth()->checkUpdate([
            'version' => $version['version_number'] ?? '100',
        ]);

        return $this->success('success', $response['data'] ?? [], show: 0);
    }

    /**
     * @notes 版本列表
     */
    public function lists()
    {
        $response = \app\common\service\ToolsService::Auth()->versionList();

        return $this->data($response['data'] ?? []);
    }

    /**
     * @notes 执行更新（旧机制，已停用）
     *
     * 旧路径绕过 SQL 执行日志、版本号比较不经归一化、前缀替换为全文 str_replace，
     * 与热更新机制并存会导致状态不一致，故统一改走新版热更新流程。
     */
    public function exec()
    {
        return $this->fail('旧版一键更新已停用，请使用新版热更新（文件同步 → 数据库结构同步 → 版本 SQL → 写入版本号）');
    }

    // ==================== 远端版本 ====================

    /**
     * @notes 新版本更新提醒
     */
    public function checkVersion()
    {
        try {
            $remote = $this->fetchRemoteVersion();

            $local       = ConfigService::get('website', 'version', []);
            $localTuple  = $this->anyToTuple($local['version_number'] ?? 0, $local['version_name'] ?? '');
            $remoteTuple = $this->anyToTuple($remote['version_number'], $remote['version_name']);

            return $this->success('success', [
                'has_update'         => $this->tupleGt($remoteTuple, $localTuple),
                'local_version'      => $local['version_number'] ?? 0,
                'local_name'         => $this->tupleToName($localTuple),
                'remote_version'     => $remote['version_number'],
                'remote_name'        => $this->tupleToName($remoteTuple),
                'remote_update_time' => $remote['update_time'] ?? '',
            ], 1, 0);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 获取远端版本号
     */
    public function remoteVersion()
    {
        try {
            $remote = $this->fetchRemoteVersion();

            $local       = ConfigService::get('website', 'version', []);
            $localTuple  = $this->anyToTuple($local['version_number'] ?? 0, $local['version_name'] ?? '');
            $remoteTuple = $this->anyToTuple($remote['version_number'], $remote['version_name']);

            return $this->success('success', [
                'remote' => [
                    'version_number' => $remote['version_number'],
                    'version_name'   => $this->tupleToName($remoteTuple),
                    'update_time'    => $remote['update_time'] ?? '',
                ],
                'local'  => [
                    'version_number' => $local['version_number'] ?? 0,
                    'version_name'   => $this->tupleToName($localTuple),
                    'update_time'    => $local['update_time'] ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 写入版本号（热更新全部步骤完成后，前端手动触发）
     *
     * POST /adminapi/update/versionUpdate
     * Body: { "force": 0 }   force=1 跳过"版本 SQL 是否全部执行"的校验（管理员明确知道自己在做什么时使用）
     *
     * 写入前校验：本地版本 → 远端版本之间的所有版本 SQL 文件必须都已执行成功，
     * 否则版本号一旦前移，sqlCompare 的 "文件版本 > 本地版本" 过滤会把没跑的文件永久隐藏。
     */
    public function versionUpdate()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        try {
            $remote = $this->fetchRemoteVersion();
            $force  = (int)$this->request->post('force', 0) === 1;

            $local       = ConfigService::get('website', 'version', []);
            $localTuple  = $this->anyToTuple($local['version_number'] ?? 0, $local['version_name'] ?? '');
            $remoteTuple = $this->anyToTuple($remote['version_number'], $remote['version_name']);

            if (!$this->tupleGt($remoteTuple, $localTuple)) {
                return $this->fail('当前版本已是最新（' . $this->tupleToName($localTuple) . '），无需写入');
            }

            $pending = array_values(array_filter(
                $this->collectPendingSqlTasks($localTuple, $remoteTuple),
                fn($t) => empty($t['skip'])
            ));

            if (!empty($pending) && !$force) {
                $names = array_column($pending, 'file');
                return $this->fail(
                    '还有 ' . count($pending) . ' 个版本 SQL 文件未执行成功，不能写入版本号：' . implode('、', $names)
                        . '。请先执行完成；如确认无需执行，可传 force=1 强制写入。',
                    ['pending' => $names]
                );
            }

            // 统一写入标准格式：version_number 五位数字、version_name vX.Y.Z
            $local['version_number'] = $this->calcVersionNumber(...$remoteTuple);
            $local['version_name']   = $this->tupleToName($remoteTuple);
            $local['update_time']    = date('Y-m-d H:i:s');

            ConfigService::set('website', 'version', $local);

            return $this->success('版本号已更新', [
                'version_number' => $local['version_number'],
                'version_name'   => $local['version_name'],
                'update_time'    => $local['update_time'],
                'forced'         => $force && !empty($pending),
                'skipped_files'  => $force ? array_column($pending, 'file') : [],
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 手动写入版本号
     *
     * POST /adminapi/update/versionManual
     * Body: { "version": "v2.9.4" }
     */
    public function versionManual()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        $versionName = trim($this->request->post('version', ''));

        if (!preg_match('/^v(\d+)\.(\d+)\.(\d+)$/i', $versionName, $m)) {
            return $this->fail('版本号格式错误，必须为 vX.Y.Z，例如：v2.9.4');
        }

        $versionNumber = $this->calcVersionNumber((int)$m[1], (int)$m[2], (int)$m[3]);
        $versionName   = $this->tupleToName([(int)$m[1], (int)$m[2], (int)$m[3]]); // 归一化：小写 v、去前导零

        $local = ConfigService::get('website', 'version', []);

        $local['version_number'] = $versionNumber;
        $local['version_name']   = $versionName;
        $local['update_time']    = date('Y-m-d H:i:s');

        ConfigService::set('website', 'version', $local);

        return $this->success('版本号已手动写入', [
            'version_number' => $versionNumber,
            'version_name'   => $versionName,
            'update_time'    => $local['update_time'],
        ]);
    }

    // ==================== 文件同步 ====================

    /**
     * @notes 单文件并排差异详情
     */
    public function fileDiff()
    {
        $file = $this->request->post('file', '');
        if (empty($file)) {
            return $this->fail('文件路径不能为空');
        }

        try {
            $service = new FileCompareService();
            $result  = $service->diffFile($file);

            unset($result['local'], $result['remote']);

            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 文件差异比对
     */
    public function fileCompare()
    {
        try {
            $service = new FileCompareService();
            $diffs   = $service->compare();

            return $this->success('success', $diffs);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
    /**
     * @notes 全量检测（普通文件差异 + 覆盖目录变更）合并返回
     */
    public function fullCompare()
    {
        try {
            $service = new FileCompareService();

            // 普通文件差异
            $diffs = $service->compare();

            // 覆盖目录变更
            $owResult = $service->compareWithOverwrite();
            $overwrite = array_map(function ($item) {
                $changed = count(array_filter($item['files'], fn($f) => $f['type'] !== 'same'));
                $total   = count($item['files']);
                $size    = array_sum(array_column($item['files'], 'size'));
                // 只把有变更的文件暴露给前端，减少传输量
                $files   = array_values(array_filter($item['files'], fn($f) => $f['type'] !== 'same'));
                return [
                    'dir'        => $item['dir'],
                    'has_update' => $item['has_update'],
                    'changed'    => $changed,
                    'total'      => $total,
                    'size'       => $size,
                    'files'      => $files,
                ];
            }, $owResult['overwrite']);

            return $this->success('success', [
                'diffs'     => $diffs,
                'overwrite' => $overwrite,
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 同步单个文件
     */
    public function fileSync()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        $file = $this->request->post('file', '');
        if (empty($file)) {
            return $this->fail('文件路径不能为空');
        }

        try {
            $service = new FileCompareService();
            $service->syncFile($file);
            if (preg_match('/\.php$/i', $file)) {
                $this->resetOpcache();
            }
            return $this->success('同步成功', [], 1, 0);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 直接覆盖 direct_overwrite 目录
     */
    public function fileSyncSilent()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        try {
            $service = new FileCompareService();
            $result  = $service->syncDirectOverwrite();
            $this->resetOpcache();

            return $this->success('覆盖完成', [
                'count'  => $result['count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 获取 direct_overwrite 目录的变更情况（zip 模式预检）
     */
    public function overwriteCompare()
    {
        try {
            $service = new FileCompareService();
            $result  = $service->compareWithOverwrite();

            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 批量 zip 覆盖 direct_overwrite 目录
     */
    public function overwriteAllByZip()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        $dirs = $this->request->post('dirs', []);
        if (empty($dirs)) {
            $dirs = config('updater.direct_overwrite', []);
        }

        if (empty($dirs)) {
            return $this->fail('未配置 direct_overwrite 目录');
        }

        $service = new FileCompareService();
        $details = [];
        $success = 0;
        $failed  = 0;

        foreach ($dirs as $dir) {
            try {
                $result    = $service->fetchZipAndExtract($dir);
                $details[] = [
                    'dir'    => $dir,
                    'count'  => $result['count'],
                    'errors' => $result['errors'],
                ];
                if (empty($result['errors'])) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $details[] = [
                    'dir'    => $dir,
                    'count'  => 0,
                    'errors' => [['file' => '*', 'msg' => $e->getMessage()]],
                ];
            }
        }

        $data = [
            'total'         => count($dirs),
            'success'       => $success,
            'failed'        => $failed,
            'details'       => $details,
            'opcache_reset' => $this->resetOpcache(),
        ];


        if ($failed > 0) {
            // 汇总所有错误信息给前端展示
            $errorMsgs = [];
            foreach ($details as $d) {
                foreach ($d['errors'] as $e) {
                    $errorMsgs[] = "[{$d['dir']}] {$e['file']}：{$e['msg']}";
                }
            }
            $msg = "覆盖失败（{$failed}/{$data['total']} 个目录）：" . implode('；', $errorMsgs);
            return $this->fail($msg, $data);
        }

        return $this->success('覆盖完成', $data);
    }

    /**
     * @notes 手动清理 OPcache（批量 fileSync 完成后前端可调用一次）
     */
    public function opcacheReset()
    {
        $ok = $this->resetOpcache();
        return $this->success($ok ? 'OPcache 已清理' : 'OPcache 未启用或无法清理（请重启 PHP-FPM）', ['ok' => $ok], 1, 0);
    }

    // ==================== 数据库备份 ====================

    /**
     * @notes 手动备份数据库（纯 PHP 导出到 runtime/backup，库大时耗时较长，请确保 nginx/php-fpm 超时足够）
     *
     * POST /adminapi/update/dbBackup
     * Body: { "tag": "before_v2.12.1" }  可选
     */
    public function dbBackup()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        @set_time_limit(0);

        try {
            $service = new DbBackupService();
            $result  = $service->backup((string)$this->request->post('tag', ''));
            unset($result['path']);
            return $this->success('备份完成', $result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 备份列表
     */
    public function dbBackupList()
    {
        return $this->success('success', (new DbBackupService())->lists(), 1, 0);
    }

    // ==================== 数据库结构同步 ====================

    /**
     * @notes 数据库结构差异比对
     */
    public function dbCompare()
    {
        try {
            $service = new DbCompareService();
            $result  = $service->compare();

            // adminapi 为 token 鉴权，不启用原生 session，session_id() 恒为空串，
            // 改用 管理员ID + 随机串 做键，避免多管理员共用同一份 SQL 列表导致 index 错位
            $cacheKey = 'updater_db_sqls_' . $this->adminId . '_' . bin2hex(random_bytes(8));
            Cache::set($cacheKey, $result['sqls'], 1800);

            return $this->success('success', [
                'diffs'     => $result['diffs'],
                'total'     => count($result['sqls']),
                'cache_key' => $cacheKey,
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 执行第 N 条数据库升级 SQL
     */
    public function dbExecute()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        $cacheKey = $this->request->post('cache_key', '');
        $index    = (int) $this->request->post('index', -1);

        if (empty($cacheKey) || $index < 0) {
            return $this->fail('参数错误');
        }

        // 只允许执行当前管理员自己比对出来的列表
        if (!str_starts_with($cacheKey, 'updater_db_sqls_' . $this->adminId . '_')) {
            return $this->fail('cache_key 无效，请重新比对');
        }

        $sqls = Cache::get($cacheKey);
        if (empty($sqls) || !isset($sqls[$index])) {
            return $this->fail('SQL 缓存已过期或索引无效，请重新比对');
        }

        try {
            $service = new DbCompareService();
            $service->execute($sqls[$index]);
            return $this->success('执行成功', [], 1, 0);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    // ==================== SQL 版本文件升级 ====================

    /**
     * @notes 检测 public/update 目录中待执行的 SQL 版本文件
     *
     * 过滤规则：本地版本 < SQL文件版本 <= 远端版本
     *
     * 版本比较全部通过 anyToTuple() 转为 [major, minor, patch] 三元组，
     * 彻底规避本地/远端/历史数据格式不一致的问题。
     *
     * tasks 返回字段说明：
     *   file      原始文件名，如 "v2.9.40.sql"
     *   kind      "version" | "table"
     *   version   从文件名直接解析的版本字符串，如 "v2.9.40"（显示用，不做任何推断）
     *   name      与 version 相同，由 tuple 生成，双重保障
     *   tuple     [major, minor, patch]，如 [2, 9, 40]
     *   number    标准五位版本数字，如 20940（仅用于排序，不用于显示）
     *   timestamp 时间戳后缀，无则为 0
     *   size      文件大小（字节）
     *   skip      是否跳过
     */
    public function sqlCompare()
    {
        try {
            $localInfo   = ConfigService::get('website', 'version', []);
            $localTuple  = $this->anyToTuple($localInfo['version_number'] ?? 0, $localInfo['version_name'] ?? '');

            $remoteInfo  = $this->fetchRemoteVersion();
            $remoteTuple = $this->anyToTuple($remoteInfo['version_number'], $remoteInfo['version_name']);

            $tasks = $this->collectPendingSqlTasks($localTuple, $remoteTuple);

            return $this->success('success', [
                'local_version'  => $localInfo['version_number'] ?? 0,
                'local_name'     => $this->tupleToName($localTuple),
                'remote_version' => $remoteInfo['version_number'],
                'remote_name'    => $this->tupleToName($remoteTuple),
                'tasks'          => $tasks,
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 收集待执行的版本 SQL 文件任务（本地版本 < 文件版本 <= 远端版本），按版本升序
     *
     * sqlCompare 与 versionUpdate 共用，保证"列出来的"与"写版本号前校验的"是同一批文件
     */
    private function collectPendingSqlTasks(array $localTuple, array $remoteTuple): array
    {
        $updateDir = public_path('update');
        if (!is_dir($updateDir)) {
            return [];
        }

        $files = glob($updateDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];

        // 已成功执行过的文件（防止版本号写入失败后重复执行）
        $executedFiles = $this->getExecutedSqlFiles();

        $versionTasks = [];
        $tableTasks   = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            $parsed = $this->parseVersionFile($filename);
            if ($parsed !== null) {
                $fileTuple = $parsed['tuple'];

                // 跳过：文件版本 <= 本地版本（已执行过）
                if (!$this->tupleGt($fileTuple, $localTuple)) {
                    continue;
                }
                // 跳过：文件版本 > 远端版本（还未发布）
                if ($this->tupleGt($fileTuple, $remoteTuple)) {
                    continue;
                }

                $parsedSql = $this->splitAndFilterSql((string)file_get_contents($filePath));

                $versionTasks[] = [
                    'file'      => $filename,
                    'kind'      => 'version',
                    // 执行前预解析：让管理员提前看到哪些语句会被跳过
                    'statements'    => count($parsedSql['allowed']),
                    'filtered'      => count($parsedSql['filtered']),
                    'filtered_list' => $this->previewStatements($parsedSql['filtered'], $parsedSql['reasons']),
                    // version / name 均直接来自文件名解析，不经过数字反推
                    // v2.9.40.sql → "v2.9.40"，绝不会变成 "v2.9.4"
                    'version'   => $parsed['version'],
                    'name'      => $this->tupleToName($fileTuple),
                    'tuple'     => $fileTuple,
                    // number 仅用于排序，前端显示请用 version / name 字段
                    'number'    => $this->calcVersionNumber(...$fileTuple),
                    'timestamp' => $parsed['timestamp'],
                    'size'      => filesize($filePath),
                    'skip'      => in_array($filename, $executedFiles, true),
                ];
                continue;
            }

            // --- 类型二：建表文件 update_xxx.sql ---
            // $tableInfo = $this->parseTableFile($filename);
            // if ($tableInfo !== null) { ... }
        }

        // 按版本号升序，同版本按时间戳升序
        usort($versionTasks, function ($a, $b) {
            return $a['number'] !== $b['number']
                ? $a['number'] <=> $b['number']
                : $a['timestamp'] <=> $b['timestamp'];
        });

        usort($tableTasks, fn($a, $b) => strcmp($a['file'], $b['file']));

        return array_merge($versionTasks, $tableTasks);
    }

    /**
     * @notes 执行指定 SQL 文件
     */
    public function sqlExecute()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        $filename = $this->request->post('file', '');

        if (empty($filename) || !preg_match('/^[a-zA-Z0-9_.v-]+\.sql$/', $filename)) {
            return $this->fail('非法文件名');
        }

        $filePath = public_path('update') . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($filePath)) {
            return $this->fail("文件不存在：{$filename}");
        }

        $tableInfo = $this->parseTableFile($filename);
        if ($tableInfo !== null && $this->tableExists($tableInfo['table'])) {
            return $this->success('已跳过', [
                'file'    => $filename,
                'skipped' => true,
                'reason'  => "表 `{$tableInfo['table']}` 已存在，已跳过执行",
            ]);
        }

        $sql = file_get_contents($filePath);
        if (empty(trim($sql))) {
            return $this->fail("SQL 文件内容为空：{$filename}");
        }

        // 幂等保障：已成功执行过的文件直接跳过，防止重复执行导致主键/唯一键冲突
        $this->ensureSqlLogTable();
        $log = $this->getSqlLog($filename);
        if ($log !== null && (int)$log['status'] === 1) {
            return $this->success('已跳过', [
                'file'    => $filename,
                'skipped' => true,
                'reason'  => "该文件已于 {$log['execute_time']} 执行成功，已跳过",
            ]);
        }

        $result   = $this->splitAndFilterSql($sql);
        $allowed  = $result['allowed'];
        $filtered = $result['filtered'];
        $md5      = md5($sql);
        $total    = count($allowed);

        // 断点续跑：上次失败且文件内容未变，则跳过已成功提交的语句；文件内容变了从头执行
        $startIndex = 0;
        if ($log !== null && $log['md5'] === $md5) {
            $startIndex = min((int)($log['progress'] ?? 0), $total);
        }

        // 白名单里含 ALTER / CREATE 等 DDL，MySQL 对 DDL 隐式提交，整文件包一个事务并不能回滚。
        // 因此：DDL 逐条单独执行；相邻的 DML 归为一组包事务。每组成功后立即记录进度。
        $chunks  = $this->chunkStatements($allowed, $startIndex);
        $done    = $startIndex;
        $skipped = [];

        foreach ($chunks as $chunk) {
            $isDdl = $chunk['ddl'];
            $from  = $chunk['from'];
            $list  = $chunk['statements'];

            try {
                if ($isDdl) {
                    $skipped = array_merge($skipped, $this->executeDdlStatement($list[0], $from + 1));
                } else {
                    Db::startTrans();
                    foreach ($list as $statement) {
                        Db::execute($statement);
                    }
                    Db::commit();
                }
            } catch (\Exception $e) {
                if (!$isDdl) {
                    Db::rollback();
                }
                $failedAt = $from + 1; // 人类可读的序号（DML 组里具体哪条失败无法精确定位，从组首重跑）
                $msg = "执行失败：第 {$failedAt} 条起的语句出错（前 {$done}/{$total} 条已生效并记录，修正后重试将从第 " . ($done + 1) . " 条继续）：" . $e->getMessage();
                $this->saveSqlLog($filename, $md5, 0, $msg, $done);
                return $this->fail($msg, [
                    'file'      => $filename,
                    'total'     => $total,
                    'done'      => $done,
                    'failed_at' => $failedAt,
                    'statement' => mb_substr(preg_replace('/\s+/', ' ', $list[0]) ?? $list[0], 0, 200),
                ]);
            }

            $done = $from + count($list);
            $this->saveSqlLog($filename, $md5, 0, '', $done);
        }

        $this->saveSqlLog($filename, $md5, 1, '', $total);

        $responseData = [
            'file'           => $filename,
            'skipped'        => false,
            'count'          => count($allowed),
            'resumed_from'   => $startIndex,
            'filtered'       => count($filtered),
            'filtered_types' => array_values(array_unique(array_map(
                fn($s) => strtoupper(strtok(ltrim($s), " \t\n\r(")),
                $filtered
            ))),
            'filtered_list'  => $this->previewStatements($filtered, $result['reasons']),
            // 结构同步已经补过的字段/索引，这里会被逐条跳过，列出来让管理员心里有数
            'skipped_clauses' => $skipped,
        ];

        // SQL 已提交并记录成功，迁移失败不影响 SQL 执行记录（迁移有独立的重试入口）
        if (StationV3MigrationService::isV300SqlFile($filename)) {
            StationV3MigrationService::markSqlExecuted();
            try {
                $responseData['migration'] = StationV3MigrationService::run();
            } catch (\Throwable $e) {
                return $this->fail($e->getMessage(), array_merge($responseData, [
                    'migration' => StationV3MigrationService::getStatus(),
                ]));
            }
        }

        return $this->success('执行成功', $responseData);
    }

    /**
     * @notes 热更新 SQL 解析/执行测试（不改版本号、不跑结构比对、不跑文件同步）
     *
     * POST /adminapi/update/sqlTest
     * Body:
     *   file          文件名，如 v2.12.1.sql；不传则只返回 public/update 文件列表
     *   execute       0 只解析（默认） 1 按 sqlExecute 同一套白名单逐条执行
     *   stop_on_error 1 遇错即停（默认，对齐正式热更新） 0 继续跑完并汇总错误
     */
    public function sqlTest()
    {
        $filename    = trim((string)$this->request->param('file', ''));
        $execute     = (int)$this->request->param('execute', 0) === 1;
        $stopOnError = (int)$this->request->param('stop_on_error', 1) !== 0;

        $updateDir = public_path('update');
        $files     = is_dir($updateDir)
            ? array_values(array_map('basename', glob($updateDir . DIRECTORY_SEPARATOR . '*.sql') ?: []))
            : [];

        if ($filename === '') {
            return $this->success('success', [
                'mode'  => 'list',
                'files' => $files,
                'hint'  => '传入 file=v2.12.1.sql；execute=0 只解析，execute=1 解析并执行',
            ], 1, 0);
        }

        if (!preg_match('/^[a-zA-Z0-9_.v-]+\.sql$/', $filename)) {
            return $this->fail('非法文件名');
        }
        if (!in_array($filename, $files, true)) {
            return $this->fail("文件不存在：{$filename}");
        }

        $filePath = $updateDir . DIRECTORY_SEPARATOR . $filename;
        $sql      = file_get_contents($filePath);
        if ($sql === false || trim($sql) === '') {
            return $this->fail("SQL 文件内容为空：{$filename}");
        }

        try {
            $data = $this->runSqlFileTest($sql, $execute, $stopOnError);
            $data['file'] = $filename;
            $data['size'] = filesize($filePath);
            $data['path'] = 'public/update/' . $filename;

            if ($execute && !$data['execute']['ok']) {
                return $this->fail('SQL 测试执行失败', $data);
            }

            return $this->success($execute ? 'SQL 测试执行完成' : 'SQL 解析完成', $data, 1, 0);
        } catch (\Exception $e) {
            return $this->fail('SQL 测试失败：' . $e->getMessage());
        }
    }

    // ==================== 站长 v2.10.0 中台迁移 ====================

    /**
     * @notes 查询站长 v3 中台迁移状态（控制重试按钮显隐）
     */
    public function stationMigrationV3Status()
    {
        return $this->success('success', StationV3MigrationService::getStatus(), 1, 0);
    }

    /**
     * @notes 手动重试站长 v3 中台迁移（旧中台导出 → 新中台导入）
     */
    public function stationMigrationV3()
    {
        if ($err = $this->acquireUpdateLock()) {
            return $this->fail($err);
        }

        if (StationV3MigrationService::isCompleted()) {
            return $this->fail('站长中台已迁移完成，无需重试', StationV3MigrationService::getStatus());
        }
        if (!StationV3MigrationService::shouldShowRetry()) {
            return $this->fail('请先完成 v2.10.0 数据库升级', StationV3MigrationService::getStatus());
        }

        try {
            $result = StationV3MigrationService::run();
            $status = StationV3MigrationService::getStatus();
            $message = '站长中台已迁移至新版本';
            if (!empty($status['domain'])) {
                $message .= '（' . $status['domain'] . '）';
            }
            $message .= '。请重启 PHP-FPM 或清理 OPcache 使新中台 API 地址生效。';

            return $this->success($message, array_merge($status, [
                'result' => $result,
            ]));
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), StationV3MigrationService::getStatus());
        }
    }

    // ==================== 并发锁 / OPcache ====================

    /** @var resource|null */
    private $lockHandle = null;

    /**
     * 获取全局更新锁（进程级互斥，flock 非阻塞）
     *
     * 所有会写文件 / 写库的更新接口都必须先拿锁，防止多管理员或多标签页并发执行。
     * 锁在请求结束时随句柄释放。
     *
     * @return string|null  拿到锁返回 null，否则返回失败原因
     */
    private function acquireUpdateLock(): ?string
    {
        $lockFile = runtime_path() . 'updater.lock';

        $fp = @fopen($lockFile, 'c+');
        if ($fp === false) {
            return "无法创建锁文件：{$lockFile}";
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $owner = trim((string)stream_get_contents($fp));
            fclose($fp);
            return '另一个更新操作正在执行中' . ($owner !== '' ? "（{$owner}）" : '') . '，请稍后再试';
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, "admin_id={$this->adminId} at " . date('Y-m-d H:i:s'));
        fflush($fp);

        $this->lockHandle = $fp; // 保持句柄直到请求结束
        return null;
    }

    /**
     * 同步 PHP 代码后清理 OPcache，否则新代码可能不会生效
     */
    private function resetOpcache(): bool
    {
        try {
            if (function_exists('opcache_reset')) {
                return (bool)@opcache_reset();
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 从远端拉取版本号，原样返回，不做格式转换
     *
     * @throws \Exception
     */
    private function fetchRemoteVersion(): array
    {
        $remoteApiUrl = config('updater.remote_api_url');
        $secretToken  = config('updater.secret_token');

        $url      = $remoteApiUrl . '?action=version&token=' . urlencode($secretToken);
        $response = $this->httpGet($url);

        if ($response['code'] !== 200 || empty($response['body'])) {
            throw new \Exception("获取远端版本失败，HTTP {$response['code']}：{$response['error']}");
        }

        $result = json_decode($response['body'], true);
        if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
            throw new \Exception('远端版本响应异常：' . ($result['message'] ?? '未知'));
        }

        return [
            'version_number' => $result['data']['version_number'] ?? 0,
            'version_name'   => $result['data']['version_name'] ?? '',
            'update_time'    => $result['data']['update_time'] ?? '',
        ];
    }

    /**
     * 简单 HTTP GET
     */
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

    /**
     * 将任意格式版本号转为 [major, minor, patch] 三元组
     *
     * 优先用 version_name 解析（最准确），失败才回退到 version_number 数字推断。
     *
     * 支持的 version_number 格式：
     *   294      三位旧格式  → [2,9,4]   v2.9.4
     *   2940     四位格式    → [2,9,40]  v2.9.40
     *   20904    五位标准    → [2,9,4]   v2.9.4
     *   20940    五位标准    → [2,9,40]  v2.9.40
     *   290400   六位错误    → [2,9,4]   v2.9.4  (÷100→四位)
     *   2090400  七位错误    → [2,9,4]   v2.9.4  (÷100→五位)
     *
     * @param  int|string $number  version_number 原始值
     * @param  string     $name    version_name，如 "v2.9.40"（有则优先用）
     * @return int[]  [major, minor, patch]
     */
    private function anyToTuple($number, string $name = ''): array
    {
        // 1. 优先从 version_name 解析，最可靠
        if (preg_match('/v?(\d+)\.(\d+)\.(\d+)/i', $name, $m)) {
            return [(int)$m[1], (int)$m[2], (int)$m[3]];
        }

        // 2. 回退到 version_number 数字位数推断
        $n = (int)$number;

        if ($n <= 0) {
            return [0, 0, 0];
        }

        $digits = strlen((string)$n);

        // 七位：2090400 → ÷100 → 20904（五位标准）
        if ($digits === 7) {
            $n      = intdiv($n, 100);
            $digits = 5;
        }

        // 六位：290400 → ÷100 → 2904（四位）
        if ($digits === 6) {
            $n      = intdiv($n, 100);
            $digits = 4;
        }

        // 五位标准：20904→[2,9,4]  20940→[2,9,40]
        if ($digits === 5) {
            return [
                intdiv($n, 10000),
                intdiv($n % 10000, 100),
                $n % 100,
            ];
        }

        // 四位：2904→[2,9,4]  2940→[2,9,40]
        if ($digits === 4) {
            return [
                intdiv($n, 1000),
                intdiv($n % 1000, 100),
                $n % 100,
            ];
        }

        // 三位旧格式：294→[2,9,4]
        if ($digits === 3) {
            return [
                intdiv($n, 100),
                intdiv($n % 100, 10),
                $n % 10,
            ];
        }

        return [0, 0, $n];
    }

    /**
     * 三元组比较：$a > $b 返回 true
     *
     * @param  int[] $a  [major, minor, patch]
     * @param  int[] $b  [major, minor, patch]
     */
    private function tupleGt(array $a, array $b): bool
    {
        if ($a[0] !== $b[0]) return $a[0] > $b[0];
        if ($a[1] !== $b[1]) return $a[1] > $b[1];
        return $a[2] > $b[2];
    }

    /**
     * 三元组转版本名称
     *
     * [2, 9, 4]  → "v2.9.4"
     * [2, 9, 40] → "v2.9.40"
     */
    private function tupleToName(array $tuple): string
    {
        return "v{$tuple[0]}.{$tuple[1]}.{$tuple[2]}";
    }

    /**
     * 计算标准五位版本数字（仅用于排序）
     *
     * v2.9.4  → 20904
     * v2.9.40 → 20940
     */
    private function calcVersionNumber(int $major, int $minor, int $patch): int
    {
        return $major * 10000 + $minor * 100 + $patch;
    }

    /**
     * 解析版本升级文件名
     *
     * 返回字段：
     *   tuple     [major, minor, patch]，直接从文件名正则捕获，不经过数字转换
     *   version   版本字符串，如 "v2.9.40"，直接拼接捕获组，保证与文件名完全一致
     *   timestamp 时间戳后缀，无则为 0
     *
     * 示例：
     *   v2.9.4.sql               → tuple=[2,9,4],  version="v2.9.4",  timestamp=0
     *   v2.9.40.sql              → tuple=[2,9,40], version="v2.9.40", timestamp=0
     *   v2.9.40-202505131541.sql → tuple=[2,9,40], version="v2.9.40", timestamp=202505131541
     *
     * @return array|null  解析成功返回数组，非版本文件返回 null
     */
    private function parseVersionFile(string $filename): ?array
    {
        // 带时间戳：v2.9.40-202505131541.sql
        if (preg_match('/^v(\d+)\.(\d+)\.(\d+)-(\d+)\.sql$/i', $filename, $m)) {
            return [
                'tuple'     => [(int)$m[1], (int)$m[2], (int)$m[3]],
                'version'   => "v{$m[1]}.{$m[2]}.{$m[3]}",
                'timestamp' => (int)$m[4],
            ];
        }
        // 不带时间戳：v2.9.40.sql
        if (preg_match('/^v(\d+)\.(\d+)\.(\d+)\.sql$/i', $filename, $m)) {
            return [
                'tuple'     => [(int)$m[1], (int)$m[2], (int)$m[3]],
                'version'   => "v{$m[1]}.{$m[2]}.{$m[3]}",
                'timestamp' => 0,
            ];
        }
        return null;
    }

    /**
     * 解析建表文件名
     */
    private function parseTableFile(string $filename): ?array
    {
        if (!preg_match('/^update_([a-zA-Z0-9_]+)\.sql$/i', $filename, $m)) {
            return null;
        }
        return ['table' => $m[1]];
    }

    /**
     * 检查数据库中指定表是否存在
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $prefix    = Db::getConfig('prefix') ?? '';
            $fullTable = $prefix . $tableName;
            $database  = Db::getConfig('database');

            $result = Db::query(
                "SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$database, $fullTable]
            );

            return (int)($result[0]['cnt'] ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * SQL 执行日志表名（带前缀）
     */
    private function sqlLogTable(): string
    {
        return (Db::getConfig('prefix') ?? '') . 'system_update_sql_log';
    }

    /**
     * 自举创建 SQL 执行日志表
     *
     * 版本 SQL 文件里只允许 DML，无法通过它建表，故由代码自建
     */
    private function ensureSqlLogTable(): void
    {
        $table = $this->sqlLogTable();
        Db::execute(
            "CREATE TABLE IF NOT EXISTS `{$table}` (" .
                "`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id'," .
                "`file` varchar(191) NOT NULL DEFAULT '' COMMENT 'SQL文件名'," .
                "`md5` char(32) NOT NULL DEFAULT '' COMMENT '文件内容MD5'," .
                "`status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态:1-成功,0-失败'," .
                "`error` text COMMENT '失败原因'," .
                "`progress` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '已成功执行的语句数(断点续跑用)'," .
                "`execute_time` datetime DEFAULT NULL COMMENT '执行时间'," .
                "PRIMARY KEY (`id`)," .
                "UNIQUE KEY `uk_file` (`file`)" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统更新SQL文件执行记录'"
        );

        // 旧表兼容：补 progress 字段
        try {
            $database = Db::getConfig('database');
            $rows = Db::query(
                "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = 'progress'",
                [$database, $table]
            );
            if ((int)($rows[0]['cnt'] ?? 0) === 0) {
                Db::execute("ALTER TABLE `{$table}` ADD COLUMN `progress` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '已成功执行的语句数(断点续跑用)' AFTER `error`");
            }
        } catch (\Throwable $e) {
            // 忽略：加不上字段只会失去断点续跑能力，不影响执行
        }
    }

    /**
     * 语句预览列表（截断到 200 字）
     *
     * @return array<int, array{type:string, reason:string, preview:string}>
     */
    private function previewStatements(array $statements, array $reasons = []): array
    {
        $list = [];
        foreach ($statements as $i => $statement) {
            $preview = preg_replace('/\s+/', ' ', trim($statement)) ?? trim($statement);
            $list[]  = [
                'type'    => strtoupper((string)strtok(ltrim($statement), " \t\n\r(")),
                'reason'  => $reasons[$i] ?? '',
                'preview' => mb_substr($preview, 0, 200),
            ];
        }
        return $list;
    }

    /**
     * 执行单条 DDL，已经生效过的部分跳过
     *
     * 一键更新是「先结构同步、后跑版本 SQL」：步骤一已按参考库补齐字段和索引，
     * 步骤三版本 SQL 里的 ALTER 再跑一遍必然撞 1060/1061。
     * 但整条跳过会连带漏掉同一条 ALTER 里尚未生效的子句（三个 ADD COLUMN 只存在一个时，
     * 另外两个就永远补不上），所以撞到幂等错误后拆成单子句逐条重试，
     * 只放过「已经是目标状态」的子句，其余错误照常抛出中止。
     *
     * @return array<int, array{index:int, clause:string, error:string}> 被跳过的子句
     */
    private function executeDdlStatement(string $statement, int $humanIndex): array
    {
        try {
            Db::execute($statement);
            return [];
        } catch (\Exception $e) {
            // 只有「已经是目标状态」这类错误才值得重试，其余（如 1071 索引过长）直接中止
            if (!$this->isIdempotentDdlError($e)) {
                throw $e;
            }

            $parsed = $this->splitAlterClauses($statement);
            if ($parsed === null) {
                return [$this->describeSkippedClause($humanIndex, $statement, $e)];
            }

            $skipped = [];
            foreach ($parsed['clauses'] as $clause) {
                $single = "ALTER TABLE {$parsed['table']} {$clause}";
                try {
                    Db::execute($single);
                } catch (\Exception $inner) {
                    if (!$this->isIdempotentDdlError($inner)) {
                        throw $inner;
                    }
                    $skipped[] = $this->describeSkippedClause($humanIndex, $single, $inner);
                }
            }
            return $skipped;
        }
    }

    /**
     * @return array{index:int, clause:string, error:string}
     */
    private function describeSkippedClause(int $humanIndex, string $sql, \Throwable $e): array
    {
        return [
            'index'  => $humanIndex,
            'clause' => mb_substr(preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql), 0, 200),
            'error'  => mb_substr($e->getMessage(), 0, 200),
        ];
    }

    /**
     * 把 ALTER TABLE 拆成表名 + 各个子句，非 ALTER TABLE 或拆不出多个子句时返回 null
     *
     * 顶层逗号才算分隔符：引号、反引号、括号内的逗号（COMMENT '一,二'、KEY idx (a,b)）不能切。
     *
     * @return array{table:string, clauses:string[]}|null
     */
    private function splitAlterClauses(string $statement): ?array
    {
        if (!preg_match('/^\s*ALTER\s+(?:ONLINE\s+|IGNORE\s+)?TABLE\s+(`[^`]+`|[A-Za-z0-9_$]+)\s+(.+)$/is', $statement, $m)) {
            return null;
        }

        $table = $m[1];
        $rest  = rtrim(trim($m[2]), ';');

        $clauses  = [];
        $buffer   = '';
        $depth    = 0;
        $inString = null;
        $len      = strlen($rest);

        for ($i = 0; $i < $len; $i++) {
            $ch = $rest[$i];

            if ($inString !== null) {
                $buffer .= $ch;
                if ($ch === '\\' && $inString !== '`') {
                    if ($i + 1 < $len) {
                        $buffer .= $rest[$i + 1];
                        $i++;
                    }
                } elseif ($ch === $inString) {
                    if ($i + 1 < $len && $rest[$i + 1] === $inString) {
                        $buffer .= $rest[$i + 1];
                        $i++;
                    } else {
                        $inString = null;
                    }
                }
                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inString = $ch;
                $buffer  .= $ch;
                continue;
            }
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $clauses[] = trim($buffer);
                $buffer    = '';
                continue;
            }

            $buffer .= $ch;
        }
        $clauses[] = trim($buffer);

        $clauses = array_values(array_filter($clauses, static fn(string $c): bool => $c !== ''));

        // 引号/括号没闭合说明拆错了，宁可不拆；单子句拆了也没意义
        if ($inString !== null || $depth !== 0 || count($clauses) < 2) {
            return null;
        }

        // ALGORITHM=INPLACE, LOCK=NONE 这类是整条语句的执行选项，拆开单跑不合法
        foreach ($clauses as $clause) {
            if (preg_match('/^(ALGORITHM|LOCK)\s*=/i', $clause)) {
                return null;
            }
        }

        return ['table' => $table, 'clauses' => $clauses];
    }

    /**
     * 判断异常是不是「目标已经是想要的状态」那一类
     *
     * ThinkPHP 会把 PDOException 包一层，优先顺着 previous 链取驱动错误码，
     * 取不到再从 "SQLSTATE[42S21]: Column already exists: 1060 ..." 这种消息里抠。
     */
    private function isIdempotentDdlError(\Throwable $e): bool
    {
        for ($cur = $e; $cur !== null; $cur = $cur->getPrevious()) {
            if ($cur instanceof \PDOException && isset($cur->errorInfo[1])) {
                return in_array((int)$cur->errorInfo[1], self::IDEMPOTENT_DDL_ERRORS, true);
            }
        }

        if (preg_match('/SQLSTATE\[\w+\]:.*?:\s*(\d{4})\s/s', $e->getMessage(), $m)) {
            return in_array((int)$m[1], self::IDEMPOTENT_DDL_ERRORS, true);
        }

        return false;
    }

    /**
     * 将语句按「DDL 单独一组 / 相邻 DML 合并一组」切块
     *
     * @return array<int, array{ddl: bool, from: int, statements: string[]}>
     */
    private function chunkStatements(array $statements, int $startIndex = 0): array
    {
        $ddlWords = ['ALTER', 'CREATE', 'DROP', 'TRUNCATE', 'RENAME'];
        $chunks   = [];
        $current  = null;

        for ($i = $startIndex, $n = count($statements); $i < $n; $i++) {
            $statement = $statements[$i];
            $firstWord = strtoupper((string)strtok(ltrim($statement), " \t\n\r("));
            $isDdl     = in_array($firstWord, $ddlWords, true);

            if ($isDdl) {
                if ($current !== null) {
                    $chunks[] = $current;
                    $current  = null;
                }
                $chunks[] = ['ddl' => true, 'from' => $i, 'statements' => [$statement]];
                continue;
            }

            if ($current === null) {
                $current = ['ddl' => false, 'from' => $i, 'statements' => []];
            }
            $current['statements'][] = $statement;
        }

        if ($current !== null) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * 查询单个 SQL 文件的执行记录
     */
    private function getSqlLog(string $filename): ?array
    {
        $table = $this->sqlLogTable();
        $rows  = Db::query("SELECT * FROM `{$table}` WHERE `file` = ? LIMIT 1", [$filename]);
        return $rows[0] ?? null;
    }

    /**
     * 写入/更新 SQL 文件执行记录（失败不阻断主流程）
     */
    private function saveSqlLog(string $filename, string $md5, int $status, string $error = '', int $progress = 0): void
    {
        try {
            $table = $this->sqlLogTable();
            Db::execute(
                "INSERT INTO `{$table}` (`file`, `md5`, `status`, `error`, `progress`, `execute_time`) VALUES (?, ?, ?, ?, ?, NOW()) " .
                    "ON DUPLICATE KEY UPDATE `md5` = VALUES(`md5`), `status` = VALUES(`status`), " .
                    "`error` = VALUES(`error`), `progress` = VALUES(`progress`), `execute_time` = VALUES(`execute_time`)",
                [$filename, $md5, $status, $error, $progress]
            );
        } catch (\Throwable $e) {
            // 兼容 progress 字段缺失的旧表：退回旧写法，至少保住成功/失败记录
            try {
                $table = $this->sqlLogTable();
                Db::execute(
                    "INSERT INTO `{$table}` (`file`, `md5`, `status`, `error`, `execute_time`) VALUES (?, ?, ?, ?, NOW()) " .
                        "ON DUPLICATE KEY UPDATE `md5` = VALUES(`md5`), `status` = VALUES(`status`), " .
                        "`error` = VALUES(`error`), `execute_time` = VALUES(`execute_time`)",
                    [$filename, $md5, $status, $error]
                );
            } catch (\Throwable $e2) {
                // 忽略：日志表异常不应影响 SQL 本身的执行结果
            }
        }
    }

    /**
     * 查询所有已成功执行的 SQL 文件名（表不存在时视为无记录）
     */
    private function getExecutedSqlFiles(): array
    {
        try {
            $table = $this->sqlLogTable();
            $rows  = Db::query("SELECT `file` FROM `{$table}` WHERE `status` = 1");
            return array_column($rows, 'file');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 按热更新同一套拆句/白名单解析，并可逐条执行。
     *
     * @return array{
     *   mode:string,
     *   parse:array,
     *   execute:array
     * }
     */
    private function runSqlFileTest(string $sql, bool $execute, bool $stopOnError): array
    {
        $parsed   = $this->splitAndFilterSql($sql);
        $allowed  = $parsed['allowed'];
        $filtered = $parsed['filtered'];

        $summarize = static function (array $statements): array {
            $items = [];
            foreach ($statements as $i => $statement) {
                $type = strtoupper((string)strtok(ltrim($statement), " \t\n\r("));
                $preview = preg_replace('/\s+/', ' ', trim($statement)) ?? trim($statement);
                if (function_exists('mb_strlen') && mb_strlen($preview) > 180) {
                    $preview = mb_substr($preview, 0, 180) . '...';
                } elseif (strlen($preview) > 180) {
                    $preview = substr($preview, 0, 180) . '...';
                }
                $items[] = [
                    'index'   => $i,
                    'type'    => $type,
                    'preview' => $preview,
                ];
            }
            return $items;
        };

        $allowedItems  = $summarize($allowed);
        $filteredItems = $summarize($filtered);
        $typeCount     = static function (array $items): array {
            $map = [];
            foreach ($items as $item) {
                $type = (string)$item['type'];
                $map[$type] = ($map[$type] ?? 0) + 1;
            }
            ksort($map);
            return $map;
        };

        $result = [
            'mode'  => $execute ? 'execute' : 'parse',
            'parse' => [
                'allowed_count'  => count($allowed),
                'filtered_count' => count($filtered),
                'allowed_types'  => $typeCount($allowedItems),
                'filtered_types' => $typeCount($filteredItems),
                'allowed'        => $allowedItems,
                'filtered'       => $filteredItems,
            ],
            'execute' => [
                'ran'        => false,
                'ok'         => true,
                'success'    => 0,
                'fail'       => 0,
                'stopped_at' => null,
                'statements' => [],
            ],
        ];

        if (!$execute) {
            return $result;
        }

        $result['execute']['ran'] = true;
        foreach ($allowed as $i => $statement) {
            $row = $allowedItems[$i];
            $row['ok'] = false;
            $row['ms'] = 0;
            $row['error'] = '';
            $started = microtime(true);
            try {
                Db::execute($statement);
                $row['ok'] = true;
                $result['execute']['success']++;
            } catch (\Throwable $e) {
                $row['error'] = $e->getMessage();
                $result['execute']['fail']++;
                $result['execute']['ok'] = false;
                $result['execute']['stopped_at'] = $i;
            }
            $row['ms'] = (int)round((microtime(true) - $started) * 1000);
            $result['execute']['statements'][] = $row;

            if (!$row['ok'] && $stopOnError) {
                break;
            }
        }

        return $result;
    }

    /**
     * 将 SQL 文件内容拆分为单条语句。
     * 放行版本升级所需的 DML/DDL；拦截 DROP / TRUNCATE / GRANT 等危险语句。
     *
     * 返回：
     *   allowed   将被执行的语句
     *   filtered  被跳过的语句原文
     *   reasons   与 filtered 一一对应的跳过原因（blocked=危险语句 / unknown=不在白名单）
     */
    private function splitAndFilterSql(string $sql): array
    {
        $envPrefix      = env('DATABASE.PREFIX', '');
        $standardPrefix = 'la_';

        // 注释（-- / # / /* */）在拆分器里按引号状态剥离，字符串里的 "--" 不会被误伤
        $all = $this->splitSqlStatements($sql);

        if (!empty($envPrefix) && $envPrefix !== $standardPrefix) {
            $all = array_map(
                fn($statement) => $this->replacePrefixOutsideStrings($statement, $standardPrefix, $envPrefix),
                $all
            );
        }

        $allowedWords = [
            'INSERT',
            'REPLACE',
            'UPDATE',
            'DELETE',
            'ALTER',
            'CREATE',
            'SET',
            'PREPARE',
            'EXECUTE',
            'DEALLOCATE',
            'DO',
            'CALL',
            'WITH',
        ];
        $blockedWords = ['DROP', 'TRUNCATE', 'GRANT', 'REVOKE'];

        $allowed  = [];
        $filtered = [];
        $reasons  = [];

        foreach ($all as $statement) {
            $firstWord = strtoupper((string)strtok(ltrim($statement), " \t\n\r("));
            if (in_array($firstWord, $blockedWords, true)) {
                $filtered[] = $statement;
                $reasons[]  = 'blocked';
                continue;
            }
            if (in_array($firstWord, $allowedWords, true)) {
                $allowed[] = $statement;
            } else {
                $filtered[] = $statement;
                $reasons[]  = 'unknown';
            }
        }

        return ['allowed' => $allowed, 'filtered' => $filtered, 'reasons' => $reasons];
    }

    /**
     * 在字符串字面量之外替换表前缀（反引号内的标识符也替换）
     *
     * 覆盖 `la_user` 与裸写 la_user；'la_xxx' / "la_xxx" 引号内是数据值（MySQL 默认模式下双引号是字符串），不动。
     */
    private function replacePrefixOutsideStrings(string $statement, string $from, string $to): string
    {
        $out       = '';
        $len       = strlen($statement);
        $inString  = null;
        $segment   = '';
        $strBuffer = '';
        $pattern   = '/(?<![A-Za-z0-9_])' . preg_quote($from, '/') . '(?=[A-Za-z0-9_])/';

        // 字符串里的动态 SQL：PREPARE p FROM 'ALTER TABLE `la_x` ...'、
        // TABLE_NAME = TRIM(BOTH '`' FROM '`la_x`') 这类写法，表名躲在引号里，
        // 不替换的话前缀不是 la_ 的站点会去操作一张不存在的表。
        // 只认「反引号包起来的标识符」，纯文本值（COMMENT '关联la_draw_task.id'）不碰。
        $quotedIdent = '/(?<=`)' . preg_quote($from, '/') . '(?=[A-Za-z0-9_]*`)/';

        $flush = function () use (&$segment, &$out, $pattern, $to) {
            $out    .= preg_replace($pattern, $to, $segment);
            $segment = '';
        };

        for ($i = 0; $i < $len; $i++) {
            $ch = $statement[$i];

            if ($inString !== null) {
                $strBuffer .= $ch;
                if ($ch === '\\' && $inString !== '`') {
                    if ($i + 1 < $len) {
                        $strBuffer .= $statement[++$i];
                    }
                } elseif ($ch === $inString) {
                    if ($i + 1 < $len && $statement[$i + 1] === $inString) {
                        $strBuffer .= $statement[++$i];
                    } else {
                        $inString   = null;
                        $out       .= preg_replace($quotedIdent, $to, $strBuffer);
                        $strBuffer  = '';
                    }
                }
                continue;
            }

            // 反引号内是标识符，需要替换；单/双引号内是值，只替换其中被反引号包住的标识符
            if ($ch === "'" || $ch === '"') {
                $flush();
                $inString  = $ch;
                $strBuffer = $ch;
                continue;
            }

            $segment .= $ch;
        }
        $flush();

        // 引号没闭合说明语句本身有问题，原样吐出，不做替换
        $out .= $strBuffer;

        return $out;
    }

    /**
     * 引号感知的 SQL 语句拆分
     *
     * 处理单引号、双引号、反引号内的分号，以及 \' 反斜杠转义和 '' 双写转义
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer     = '';
        $len        = strlen($sql);
        $inString   = null;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($inString !== null) {
                $buffer .= $ch;
                if ($ch === '\\' && $inString !== '`') {
                    if ($i + 1 < $len) {
                        $buffer .= $sql[$i + 1];
                        $i++;
                    }
                } elseif ($ch === $inString) {
                    if ($i + 1 < $len && $sql[$i + 1] === $inString) {
                        $buffer .= $sql[$i + 1];
                        $i++;
                    } else {
                        $inString = null;
                    }
                }
                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inString = $ch;
                $buffer  .= $ch;
                continue;
            }

            // 行注释：# 或 --
            // MySQL 规范要求 -- 后跟空白，但历史文件里有 "--标题" 这种写法，
            // 因此：-- 后跟空白/行尾，或 -- 位于行首（前面只有空白），都视为注释
            $isDashComment = false;
            if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                if ($i + 2 >= $len || ctype_space($sql[$i + 2])) {
                    $isDashComment = true;
                } else {
                    $lineStart     = strrpos(substr($sql, 0, $i), "\n");
                    $lineStart     = $lineStart === false ? 0 : $lineStart + 1;
                    $isDashComment = trim(substr($sql, $lineStart, $i - $lineStart)) === '';
                }
            }
            if ($ch === '#' || $isDashComment) {
                $nl = strpos($sql, "\n", $i);
                if ($nl === false) {
                    break;
                }
                $i = $nl; // 保留换行本身
                $buffer .= "\n";
                continue;
            }

            // 块注释 /* ... */（含 /*!40101 ... */ 条件注释，一并剥离）
            if ($ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                $close = strpos($sql, '*/', $i + 2);
                if ($close === false) {
                    break; // 未闭合：丢弃剩余内容
                }
                $i = $close + 1;
                $buffer .= ' ';
                continue;
            }

            if ($ch === ';') {
                $statements[] = $buffer;
                $buffer       = '';
                continue;
            }

            $buffer .= $ch;
        }

        $statements[] = $buffer;

        return array_values(array_filter(array_map('trim', $statements), fn($s) => $s !== ''));
    }

    /**
     * @notes 获取远端更新公告
     */
    public function notice()
    {
        try {
            $remoteApiUrl = config('updater.remote_api_url');
            $secretToken  = config('updater.secret_token');

            $url      = $remoteApiUrl . '?action=notice&token=' . urlencode($secretToken);
            $response = $this->httpGet($url);

            if ($response['code'] !== 200 || empty($response['body'])) {
                return $this->fail("获取公告失败，HTTP {$response['code']}：{$response['error']}");
            }

            $result = json_decode($response['body'], true);
            if (($result['status'] ?? '') !== 'success' || !isset($result['data'])) {
                return $this->fail('远端公告响应异常：' . ($result['message'] ?? '未知'));
            }

            return $this->success('success', $result['data'], 1, 0);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
