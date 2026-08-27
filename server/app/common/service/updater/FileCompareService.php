<?php

namespace app\common\service\updater;

class FileCompareService
{
    protected string $remoteApiUrl;
    protected string $secretToken;
    protected string $localDir;
    protected array  $ignoreRules;
    protected array  $directOverwriteDirs;

    public function __construct()
    {
        $this->remoteApiUrl        = config('updater.remote_api_url');
        $this->secretToken         = config('updater.secret_token');
        $this->localDir            = rtrim(config('updater.local_dir'), DIRECTORY_SEPARATOR);
        $this->ignoreRules         = config('updater.ignore', []);
        $this->directOverwriteDirs = config('updater.direct_overwrite', []);

        // ✅ 自动忽略 zip 包目录，不参与文件对比
        if (!in_array('public/overwrite_zips/', $this->ignoreRules, true)) {
            $this->ignoreRules[] = 'public/overwrite_zips/';
        }
    }

    /**
     * 比对差异，返回需要显示给用户的差异列表（不含直接覆盖目录）
     */
    public function compare(): array
    {
        $remote = $this->fetchRemoteList();
        $diffs  = [];

        foreach ($remote as $file => $meta) {
            if ($this->isIgnored($file)) continue;
            if ($this->isDirectOverwrite($file)) continue;

            $localPath = $this->safeResolve($file);
            if ($localPath === null) continue;

            $remoteMd5 = is_array($meta) ? ($meta['md5'] ?? '') : $meta;
            $size      = is_array($meta) ? ($meta['size'] ?? null) : null;

            if (!file_exists($localPath)) {
                $diffs[] = ['file' => $file, 'type' => 'new',    'size' => $size];
            } elseif (md5_file($localPath) !== $remoteMd5) {
                $diffs[] = ['file' => $file, 'type' => 'modify', 'size' => $size];
            }
        }

        return $diffs;
    }

    /**
     * 同步单个文件（用于差异列表中的文件）
     */
    public function syncFile(string $file): void
    {
        if ($this->isIgnored($file)) {
            throw new \Exception("文件在忽略列表中，拒绝写入：{$file}");
        }
        $this->downloadAndWrite($file);
    }

    /**
     * 直接覆盖所有 direct_overwrite 目录（逐文件模式，兜底用）
     */
    public function syncDirectOverwrite(): array
    {
        $remote = $this->fetchRemoteList();
        $errors = [];
        $count  = 0;

        foreach ($remote as $file => $meta) {
            if (!$this->isDirectOverwrite($file)) continue;
            if ($this->isIgnored($file)) continue;
            if ($this->matchRules($file, ['__MACOSX/', '*.DS_Store', 'Thumbs.db'])) continue;

            try {
                $this->downloadAndWrite($file);
                $count++;
            } catch (\Exception $e) {
                $errors[] = ['file' => $file, 'msg' => $e->getMessage()];
            }
        }

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * 获取直接覆盖目录的变更情况
     * 供控制器 overwriteAllByZip 使用
     *
     * 返回格式：
     * [
     *   'overwrite' => [
     *     [
     *       'dir'        => 'public/admin/',
     *       'has_update' => true,
     *       'files'      => [['file' => 'public/admin/index.html', 'type' => 'modify'], ...]
     *     ],
     *     ...
     *   ]
     * ]
     */
    public function compareWithOverwrite(): array
    {
        $remote = $this->fetchRemoteList();

        // 以 dir 为 key 初始化结构
        $dirMap = [];
        foreach ($this->directOverwriteDirs as $dir) {
            $dir          = rtrim($dir, '/') . '/';
            $dirMap[$dir] = [
                'dir'        => $dir,
                'has_update' => false,
                'files'      => [],
            ];
        }

        foreach ($remote as $file => $meta) {
            if ($this->isIgnored($file)) continue;
            if (!$this->isDirectOverwrite($file)) continue;
            if ($this->matchRules($file, ['__MACOSX/', '*.DS_Store', 'Thumbs.db'])) continue;

            $localPath = $this->safeResolve($file);
            if ($localPath === null) continue;

            $remoteMd5 = is_array($meta) ? ($meta['md5'] ?? '') : $meta;
            $size      = is_array($meta) ? ($meta['size'] ?? null) : null;

            $type = null;
            if (!file_exists($localPath)) {
                $type = 'new';
            } elseif (md5_file($localPath) !== $remoteMd5) {
                $type = 'modify';
            }

            // 找到该文件属于哪个 dir
            foreach ($dirMap as $dir => &$info) {
                $fileNorm = str_replace('\\', '/', $file);
                $dirNorm  = $dir;

                // 兼容云端路径不带 public/ 前缀的情况
                $matchPath     = $fileNorm;
                $matchPathFull = 'public/' . $fileNorm;

                $matched = str_starts_with($matchPath, $dirNorm)
                    || str_starts_with($matchPathFull, $dirNorm);

                if ($matched) {
                    // 无论是否有变更，都记录文件（方便前端展示总文件数）
                    $info['files'][] = [
                        'file' => $file,
                        'type' => $type ?? 'same',
                        'size' => $size,
                    ];
                    if ($type !== null) {
                        $info['has_update'] = true;
                    }
                    break;
                }
            }
            unset($info);
        }

        return ['overwrite' => array_values($dirMap)];
    }

    /**
     * 从远端下载预打包 zip 并解压覆盖本地
     * 对应远端 action=zip&name={zipName}
     *
     * @param  string $dir     覆盖目录，如 "public/admin/"
     * @return array           ['count' => int, 'errors' => array]
     * @throws \Exception
     */
    public function fetchZipAndExtract(string $dir): array
    {
        // zip 包名规则与 build_overwrite_zips.php 保持一致
        // 例：public/admin/ → public_admin
        $zipName = rtrim(str_replace('/', '_', $dir), '_');

        $url      = $this->remoteApiUrl
            . '?action=zip'
            . '&token=' . urlencode($this->secretToken)
            . '&name='  . urlencode($zipName);
        $response = $this->httpGet($url);
        $body     = $response['body'] ?? '';

        // 远端出错时返回 JSON
        $isJson = strlen($body) > 0 && in_array($body[0], ['{', '['], true);

        if ($response['code'] !== 200 || empty($body) || $isJson) {
            throw new \Exception(
                $isJson
                    ? '远端返回错误：' . mb_substr($body, 0, 300)
                    : "HTTP {$response['code']} {$response['error']}"
            );
        }

        // 写入临时 zip
        $tmpZip = sys_get_temp_dir() . '/overwrite_' . md5($dir . microtime()) . '.zip';
        if (file_put_contents($tmpZip, $body) === false) {
            throw new \Exception("临时 zip 写入失败：{$tmpZip}");
        }

        // 解压覆盖
        $zip        = new \ZipArchive();
        $openResult = $zip->open($tmpZip);
        if ($openResult !== true) {
            @unlink($tmpZip);
            throw new \Exception("ZIP 损坏，ZipArchive 错误码：{$openResult}");
        }

        $count   = 0;
        $skipped = 0;
        $errors  = [];
        $dirNorm = rtrim(str_replace('\\', '/', $dir), '/') . '/';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $relativePath = $zip->getNameIndex($i);
            $pathNorm     = str_replace('\\', '/', $relativePath);

            // 目录项跳过
            if (str_ends_with($pathNorm, '/')) {
                continue;
            }

            // 只允许写入本次请求的覆盖目录内（兼容云端路径不带 public/ 前缀）
            if (!str_starts_with($pathNorm, $dirNorm) && !str_starts_with('public/' . $pathNorm, $dirNorm)) {
                $skipped++;
                $errors[] = ['file' => $relativePath, 'msg' => "不在目标目录 {$dirNorm} 内，已拒绝写入"];
                continue;
            }

            // 与逐文件模式保持一致：忽略列表 / 系统垃圾文件不落盘
            if ($this->isIgnored($pathNorm) || $this->matchRules($pathNorm, ['__MACOSX/', '*.DS_Store', 'Thumbs.db'])) {
                $skipped++;
                continue;
            }

            $localPath = $this->safeResolve($relativePath);
            if ($localPath === null) {
                $errors[] = ['file' => $relativePath, 'msg' => '非法路径，已跳过'];
                continue;
            }

            $content  = $zip->getFromIndex($i);
            $localDir = dirname($localPath);

            if (!is_dir($localDir) && !@mkdir($localDir, 0755, true)) {
                $errors[] = ['file' => $relativePath, 'msg' => "目录创建失败：{$localDir}"];
                continue;
            }

            // 原子写入
            $tmp = $localPath . '.sync.' . bin2hex(random_bytes(4));
            if (file_put_contents($tmp, $content) === false) {
                @unlink($tmp);
                $errors[] = ['file' => $relativePath, 'msg' => '临时写入失败'];
                continue;
            }
            if (!@rename($tmp, $localPath)) {
                @unlink($tmp);
                $errors[] = ['file' => $relativePath, 'msg' => '文件替换失败'];
                continue;
            }

            $count++;
        }

        $zip->close();
        @unlink($tmpZip);

        return ['count' => $count, 'skipped' => $skipped, 'errors' => $errors];
    }

    // ===== 私有工具方法 =====

    private function fetchRemoteList(): array
    {
        $url      = $this->remoteApiUrl . '?action=list&token=' . urlencode($this->secretToken);
        $response = $this->httpGet($url);

        if ($response['code'] !== 200 || empty($response['body'])) {
            throw new \Exception("远程连接失败，HTTP {$response['code']}：{$response['error']}");
        }

        $remote = json_decode($response['body'], true);
        if (!$remote || ($remote['status'] ?? '') !== 'success') {
            throw new \Exception('远程响应错误：' . ($remote['message'] ?? '未知'));
        }

        return $remote['data'] ?? [];
    }

    /**
     * 下载并原子写入文件（含 MD5 校验）
     */
    private function downloadAndWrite(string $file): void
    {
        $localPath = $this->safeResolve($file);
        if ($localPath === null) {
            throw new \Exception("非法路径：{$file}");
        }

        $dir = dirname($localPath);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
            throw new \Exception("目录创建失败：{$dir}");
        }
        if (file_exists($localPath)) {
            @chmod($localPath, 0777);
            if (!is_writable($localPath)) {
                throw new \Exception("文件无写权限：{$file}");
            }
        }

        $url      = $this->remoteApiUrl . '?action=download&file=' . urlencode($file)
            . '&token=' . urlencode($this->secretToken);
        $response = $this->httpGet($url, true);

        if ($response['code'] !== 200 || $response['body'] === false) {
            throw new \Exception("下载失败，HTTP {$response['code']}：{$response['error']}");
        }

        $headerSize  = $response['header_size'] ?? 0;
        $rawHeaders  = substr($response['body'], 0, $headerSize);
        $content     = substr($response['body'], $headerSize);
        $expectedMd5 = '';

        foreach (explode("\r\n", $rawHeaders) as $h) {
            if (stripos($h, 'X-File-MD5:') === 0) {
                $expectedMd5 = trim(substr($h, 11));
                break;
            }
        }

        if ($expectedMd5 && md5($content) !== $expectedMd5) {
            throw new \Exception("MD5 校验失败：{$file}");
        }

        $tmp = $localPath . '.sync.' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, $content) === false) {
            @unlink($tmp);
            throw new \Exception("临时写入失败：{$file}");
        }
        if (!@rename($tmp, $localPath)) {
            @unlink($tmp);
            throw new \Exception("文件替换失败：{$file}");
        }
    }

    private function isIgnored(string $path): bool
    {
        return $this->matchRules($path, $this->ignoreRules);
    }

    private function isDirectOverwrite(string $path): bool
    {
        return $this->matchRules($path, $this->directOverwriteDirs);
    }

    /**
     * 规则匹配
     * 云端返回路径不带 public/ 前缀（如 qrcode/xxx.png、pc/_nuxt/xxx.js）
     * 但 ignore 规则可能写的是 public/qrcode/，所以同时用两种路径匹配
     */
    private function matchRules(string $path, array $rules): bool
    {
        $path     = str_replace('\\', '/', $path);
        $fullPath = 'public/' . $path;

        foreach ($rules as $rule) {
            if ($rule === '') continue;

            if (str_ends_with($rule, '/')) {
                if (str_starts_with($path, $rule)) return true;
                if (str_starts_with($fullPath, $rule)) return true;
                if (str_contains($path, '/' . $rule)) return true;
                continue;
            }

            if (strpbrk($rule, '*?') !== false) {
                if ($this->fnmatchCompat($rule, $path) || $this->fnmatchCompat($rule, basename($path))) return true;
                continue;
            }

            if ($path === $rule || $fullPath === $rule || basename($path) === $rule) return true;
        }

        return false;
    }

    /**
     * 跨平台通配符匹配，兼容 Windows
     */
    private function fnmatchCompat(string $pattern, string $string): bool
    {
        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $string);
        }
        $regex = preg_quote($pattern, '#');
        $regex = str_replace(['\*', '\?'], ['.*', '.'], $regex);
        return (bool) preg_match('#^' . $regex . '$#', $string);
    }

    private function safeResolve(string $relativePath): ?string
    {
        if ($relativePath === '' || str_contains($relativePath, "\0")) return null;
        if ($relativePath[0] === '/' || $relativePath[0] === '\\') return null;
        if (preg_match('#^[a-zA-Z]:#', $relativePath)) return null;
        foreach (preg_split('#[/\\\\]+#', $relativePath) as $p) {
            if ($p === '..' || $p === '.') return null;
        }
        return $this->localDir . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function httpGet(string $url, bool $withHeader = false): array
    {
        $timeout        = (int)(config('updater.timeout', 300));
        $connectTimeout = (int)(config('updater.connect_timeout', 30));

        $ch = curl_init($url);

        if ($ch === false) {
            return ['code' => 0, 'body' => false, 'error' => 'curl_init 失败', 'header_size' => 0];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'LikeAdminUpdater/1.0',
            CURLOPT_HEADER         => $withHeader,
        ]);
        $body  = curl_exec($ch);
        $info  = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'code'        => $info['http_code'] ?? 0,
            'body'        => $body,
            'error'       => $error,
            'header_size' => $info['header_size'] ?? 0,
        ];
    }

    /**
     * 获取单文件并排差异数据
     */
    public function diffFile(string $file): array
    {
        if ($this->isIgnored($file)) {
            throw new \Exception("文件在忽略列表中：{$file}");
        }

        $remoteContent = $this->fetchRemoteContent($file);

        $localPath = $this->safeResolve($file);
        if ($localPath && file_exists($localPath)) {
            $localContent = file_get_contents($localPath);
            if ($localContent === false) {
                throw new \Exception("本地文件读取失败：{$file}");
            }
        } else {
            $localContent = '';
        }

        $type = $localContent === '' ? 'new' : 'modify';

        $hunks = $this->computeHunks(
            explode("\n", $this->normalizeLineEndings($localContent)),
            explode("\n", $this->normalizeLineEndings($remoteContent))
        );

        return [
            'file'   => $file,
            'type'   => $type,
            'local'  => $localContent,
            'remote' => $remoteContent,
            'hunks'  => $hunks,
        ];
    }

    /**
     * 从远端下载文件原始内容（不写磁盘）
     */
    private function fetchRemoteContent(string $file): string
    {
        $url      = $this->remoteApiUrl
            . '?action=download&file=' . urlencode($file)
            . '&token=' . urlencode($this->secretToken);
        $response = $this->httpGet($url, true);

        if ($response['code'] !== 200 || $response['body'] === false) {
            throw new \Exception("下载失败，HTTP {$response['code']}：{$response['error']}");
        }

        $headerSize  = $response['header_size'] ?? 0;
        $content     = substr($response['body'], $headerSize);
        $rawHeaders  = substr($response['body'], 0, $headerSize);
        $expectedMd5 = '';

        foreach (explode("\r\n", $rawHeaders) as $h) {
            if (stripos($h, 'X-File-MD5:') === 0) {
                $expectedMd5 = trim(substr($h, 11));
                break;
            }
        }
        if ($expectedMd5 && md5($content) !== $expectedMd5) {
            throw new \Exception("MD5 校验失败：{$file}");
        }

        return $content;
    }

    /**
     * 统一换行符为 \n
     */
    private function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * 计算并排 diff hunks（Myers diff 简化版，上下文各 3 行）
     */
    private function computeHunks(array $oldLines, array $newLines): array
    {
        $context = 3;
        $ops     = $this->diffLines($oldLines, $newLines);

        if (empty($ops)) {
            return [];
        }

        $changed = [];
        foreach ($ops as $i => $op) {
            if ($op['op'] !== 'eq') {
                $changed[] = $i;
            }
        }

        if (empty($changed)) {
            return [];
        }

        $ranges = [];
        $start  = max(0, $changed[0] - $context);
        $end    = min(count($ops) - 1, $changed[0] + $context);

        foreach ($changed as $ci) {
            $newStart = max(0, $ci - $context);
            $newEnd   = min(count($ops) - 1, $ci + $context);

            if ($newStart <= $end + 1) {
                $end = max($end, $newEnd);
            } else {
                $ranges[] = [$start, $end];
                $start    = $newStart;
                $end      = $newEnd;
            }
        }
        $ranges[] = [$start, $end];

        $hunks = [];
        foreach ($ranges as [$from, $to]) {
            $lines       = [];
            $localStart  = null;
            $remoteStart = null;

            for ($i = $from; $i <= $to; $i++) {
                $op = $ops[$i];

                if ($localStart === null) {
                    $localStart  = $op['old'] ?? ($op['new'] ?? 1);
                    $remoteStart = $op['new'] ?? ($op['old'] ?? 1);
                }

                $lines[] = [
                    'type'      => $op['op'] === 'eq' ? 'context'
                        : ($op['op'] === 'del' ? 'delete' : 'insert'),
                    'local_no'  => $op['old'],
                    'remote_no' => $op['new'],
                    'text'      => $op['text'],
                ];
            }

            $hunks[] = [
                'local_start'  => $localStart,
                'remote_start' => $remoteStart,
                'lines'        => $lines,
            ];
        }

        return $hunks;
    }

    /**
     * 行级 diff，返回编辑操作序列（LCS）
     */
    private function diffLines(array $oldLines, array $newLines): array
    {
        $m = count($oldLines);
        $n = count($newLines);

        if ($m * $n > 10_000_000) {
            throw new \Exception("文件过大，无法计算 diff（{$m} × {$n} 超出上限）");
        }

        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                if ($oldLines[$i] === $newLines[$j]) {
                    $dp[$i][$j] = 1 + $dp[$i + 1][$j + 1];
                } else {
                    $dp[$i][$j] = max($dp[$i + 1][$j], $dp[$i][$j + 1]);
                }
            }
        }

        $ops = [];
        $i   = 0;
        $j   = 0;
        while ($i < $m || $j < $n) {
            if ($i < $m && $j < $n && $oldLines[$i] === $newLines[$j]) {
                $ops[] = ['op' => 'eq',  'old' => $i + 1, 'new' => $j + 1, 'text' => $oldLines[$i]];
                $i++;
                $j++;
            } elseif ($j < $n && ($i >= $m || $dp[$i][$j + 1] >= $dp[$i + 1][$j])) {
                $ops[] = ['op' => 'ins', 'old' => null,   'new' => $j + 1, 'text' => $newLines[$j]];
                $j++;
            } else {
                $ops[] = ['op' => 'del', 'old' => $i + 1, 'new' => null,   'text' => $oldLines[$i]];
                $i++;
            }
        }

        return $ops;
    }
}
