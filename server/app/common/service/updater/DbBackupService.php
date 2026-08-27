<?php

namespace app\common\service\updater;

use think\facade\Db;

/**
 * 纯 PHP 数据库备份（不依赖 mysqldump）
 *
 * 输出 gzip 压缩的 SQL 文件到 runtime/backup/，含建表语句与全量数据，
 * 可用 `zcat xxx.sql.gz | mysql -u.. -p.. db` 直接恢复。
 */
class DbBackupService
{
    /** 每批读取行数 */
    private const BATCH_ROWS = 1000;

    /** 每条 INSERT 聚合的最大字节数 */
    private const INSERT_CHUNK_BYTES = 512 * 1024;

    /** 默认保留的备份份数 */
    private const KEEP_COUNT = 5;

    public function backupDir(): string
    {
        return rtrim(runtime_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'backup';
    }

    /**
     * 执行备份
     *
     * @param  string $tag   文件名标记，如 "before_v2.12.1"
     * @return array{file:string, path:string, size:int, tables:int, rows:int, seconds:float}
     * @throws \Exception
     */
    public function backup(string $tag = ''): array
    {
        $dir = $this->backupDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \Exception("备份目录创建失败：{$dir}");
        }

        // 防止他人通过 Web 直接下载（runtime 本就不应暴露，双保险）
        $deny = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($deny)) {
            @file_put_contents($deny, "Deny from all\n");
        }

        $tag      = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $tag);
        $database = Db::getConfig('database');
        $filename = 'db_' . $database . '_' . date('Ymd_His') . ($tag !== '' ? "_{$tag}" : '') . '.sql.gz';
        $path     = $dir . DIRECTORY_SEPARATOR . $filename;
        $tmpPath  = $path . '.tmp';

        // 先写 .tmp，完整关闭后再 rename：进程被超时 kill 留下的残缺文件不会被误认为有效备份
        $gz = @gzopen($tmpPath, 'wb6');
        if ($gz === false) {
            throw new \Exception("备份文件无法写入：{$tmpPath}");
        }

        $start      = microtime(true);
        $tableCount = 0;
        $rowCount   = 0;

        try {
            $this->write($gz, "-- imai database backup\n-- database: {$database}\n-- time: " . date('Y-m-d H:i:s') . "\n\n");
            $this->write($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET AUTOCOMMIT = 0;\n\n");

            $tables = array_map(fn($r) => array_values($r)[0], Db::query('SHOW TABLES'));

            foreach ($tables as $table) {
                $tableCount++;

                $create = Db::query("SHOW CREATE TABLE `{$table}`")[0] ?? [];
                $createSql = $create['Create Table'] ?? ($create['Create View'] ?? null);
                if ($createSql === null) {
                    continue;
                }
                $isView = isset($create['Create View']);

                $this->write($gz, "-- ----------------------------\n-- {$table}\n-- ----------------------------\n");
                $this->write($gz, ($isView ? "DROP VIEW IF EXISTS" : "DROP TABLE IF EXISTS") . " `{$table}`;\n{$createSql};\n\n");

                if ($isView) {
                    continue;
                }

                $rowCount += $this->dumpTableData($gz, $table);
            }

            $this->write($gz, "COMMIT;\nSET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n");
        } catch (\Throwable $e) {
            gzclose($gz);
            @unlink($tmpPath);
            throw new \Exception("备份失败（表 {$tableCount}）：" . $e->getMessage());
        }

        gzclose($gz);
        if (!@rename($tmpPath, $path)) {
            @unlink($tmpPath);
            throw new \Exception("备份文件重命名失败：{$path}");
        }

        $this->prune(self::KEEP_COUNT);

        return [
            'file'    => $filename,
            'path'    => $path,
            'size'    => filesize($path) ?: 0,
            'tables'  => $tableCount,
            'rows'    => $rowCount,
            'seconds' => round(microtime(true) - $start, 2),
        ];
    }

    /**
     * 列出现有备份（新的在前）
     */
    public function lists(): array
    {
        $dir = $this->backupDir();
        if (!is_dir($dir)) {
            return [];
        }
        // 清理被 kill 留下的过期 .tmp（超过 1 天）
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'db_*.sql.gz.tmp') ?: [] as $tmp) {
            if (filemtime($tmp) < time() - 86400) {
                @unlink($tmp);
            }
        }
        $files = glob($dir . DIRECTORY_SEPARATOR . 'db_*.sql.gz') ?: [];
        $list  = array_map(fn($f) => [
            'file'  => basename($f),
            'size'  => filesize($f) ?: 0,
            'mtime' => date('Y-m-d H:i:s', filemtime($f) ?: 0),
        ], $files);
        usort($list, fn($a, $b) => strcmp($b['file'], $a['file']));
        return $list;
    }

    /**
     * 只保留最近 N 份
     */
    public function prune(int $keep): void
    {
        $list = $this->lists();
        foreach (array_slice($list, $keep) as $item) {
            @unlink($this->backupDir() . DIRECTORY_SEPARATOR . $item['file']);
        }
    }

    // ===== 私有 =====

    /**
     * @param resource $gz
     */
    private function dumpTableData($gz, string $table): int
    {
        $total = 0;

        // 优先按主键分页，避免大表 OFFSET 退化
        // 联合主键或无主键回退到 OFFSET 分页
        $pkRows = Db::query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        $pk     = count($pkRows) === 1 ? $pkRows[0]['Column_name'] : null;

        $lastId = null;
        $offset = 0;
        $buffer = '';
        $bufLen = 0;
        $cols   = null;

        while (true) {
            if ($pk !== null) {
                $sql  = "SELECT * FROM `{$table}`" . ($lastId !== null ? " WHERE `{$pk}` > ?" : '') . " ORDER BY `{$pk}` LIMIT " . self::BATCH_ROWS;
                $rows = Db::query($sql, $lastId !== null ? [$lastId] : []);
            } else {
                $rows = Db::query("SELECT * FROM `{$table}` LIMIT {$offset}, " . self::BATCH_ROWS);
                $offset += self::BATCH_ROWS;
            }

            if (empty($rows)) {
                break;
            }

            if ($cols === null) {
                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
            }

            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $v) {
                    $values[] = $this->quote($v);
                }
                $line = '(' . implode(',', $values) . ')';

                if ($bufLen + strlen($line) > self::INSERT_CHUNK_BYTES && $buffer !== '') {
                    $this->write($gz, "INSERT INTO `{$table}` ({$cols}) VALUES\n{$buffer};\n");
                    $buffer = '';
                    $bufLen = 0;
                }

                $buffer .= ($buffer === '' ? '' : ",\n") . $line;
                $bufLen += strlen($line) + 2;
                $total++;
            }

            if ($pk !== null) {
                $lastId = end($rows)[$pk];
            }

            if (count($rows) < self::BATCH_ROWS) {
                break;
            }
        }

        if ($buffer !== '') {
            $this->write($gz, "INSERT INTO `{$table}` ({$cols}) VALUES\n{$buffer};\n");
        }
        $this->write($gz, "\n");

        return $total;
    }

    private function quote($v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string)$v;
        }
        return "'" . str_replace(
            ["\\", "\0", "\n", "\r", "'", "\"", "\x1a"],
            ["\\\\", "\\0", "\\n", "\\r", "\\'", "\\\"", "\\Z"],
            (string)$v
        ) . "'";
    }

    /**
     * @param resource $gz
     */
    private function write($gz, string $s): void
    {
        if (gzwrite($gz, $s) === false) {
            throw new \Exception('写入备份文件失败（磁盘空间不足？）');
        }
    }
}
