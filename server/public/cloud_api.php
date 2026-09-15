<?php

/**
 * 远程服务器接口 (提供文件列表和下载) - 优化版
 * 部署在: 云端/远程服务器 /public/cloud_api.php
 */

// ================= 配置区域 =================
$SECRET_TOKEN = '74678d4960dab0b44b3a99a9c85064e7470771282ab70bed8c0bbd1cb6709620';

// 需要预打包的覆盖目录，与本地 direct_overwrite 配置保持一致
$OVERWRITE_DIRS = [
    'public/admin/',
    'public/pc/',
    'extend/miniprogram-ci/mp-weixin/',

];

// 可选：IP 白名单（留空表示不限制）
$IP_WHITELIST = [];

// 单 IP 每分钟最大请求数
$RATE_LIMIT_PER_MIN = 120000;

// 日志文件（设为空字符串可关闭日志）
$LOG_FILE = __DIR__ . '/.sync_access.log';
// ============================================

$baseDir = realpath(__DIR__ . '/..');

// 解析项目根目录 .env
$envFile = $baseDir . '/.env';
if (is_file($envFile)) {
    $section = '';
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if ($line[0] === '[' && str_ends_with($line, ']')) {
            $section = strtoupper(trim(substr($line, 1, -1)));
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = strtoupper(trim($key));
            $val = trim($val, " \t\"'");
            $envKey        = $section !== '' ? "{$section}.{$key}" : $key;
            $_ENV[$envKey] = $val;
            $_ENV[$key]    = $val;
        }
    }
}

$VERSION_DB = [
    'host'    => $_ENV['DATABASE.HOSTNAME'] ?? '127.0.0.1',
    'port'    => (int)($_ENV['DATABASE.HOSTPORT'] ?? 3306),
    'user'    => $_ENV['DATABASE.USERNAME']  ?? '',
    'pass'    => $_ENV['DATABASE.PASSWORD']  ?? '',
    'name'    => $_ENV['DATABASE.DATABASE']  ?? '',
    'charset' => $_ENV['DATABASE.CHARSET']   ?? 'utf8mb4',
    'prefix'  => $_ENV['DATABASE.PREFIX']    ?? 'la_',
];

$defaultIgnore = [
    '.git/',
    '.idea/',
    '.vscode/',
    'node_modules/',
    'vendor/',
    '.user.ini/',
    'public/update_notice.json',
    'public/cloud_api.php',
    'public/local_updater.php',
    'public/.sync_access.log',
    'public/.syncignore',
    'public/overwrite_zips/',
    '.env',
    '.env.local',
    '.env.*',
    'config.php',
    'config.local.php',
    'runtime/',
    'public/uploads/',
    'public/upload/',
    'public/qrcode/',
    'logs/',
    'log/',
    'cache/',
    'tmp/',
    'temp/',
    '*.log',
    '*.swp',
    '*.bak',
    '.DS_Store',
    'Thumbs.db',
    '*.归档.zip',
    '.well-known/',
];

$ignoreList       = $defaultIgnore;
$customIgnoreFile = $baseDir . '/.syncignore';
if (is_file($customIgnoreFile)) {
    foreach (file($customIgnoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $ignoreList[] = $line;
    }
}

// ===== 工具函数 =====

function isIgnored(string $relativePath, array $rules): bool
{
    foreach ($rules as $rule) {
        if ($rule === '') continue;
        if (str_ends_with($rule, '/')) {
            if (str_starts_with($relativePath, $rule)) return true;
            continue;
        }
        if (strpbrk($rule, '*?') !== false) {
            if (fnmatch($rule, $relativePath)) return true;
            if (fnmatch($rule, basename($relativePath))) return true;
            continue;
        }
        if ($relativePath === $rule) return true;
    }
    return false;
}

function logAccess(string $msg): void
{
    global $LOG_FILE;
    if (!$LOG_FILE) return;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
    $line = sprintf("[%s] %s %s %s\n", date('Y-m-d H:i:s'), $ip, $_SERVER['REQUEST_METHOD'] ?? '-', $msg);
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

function rateLimitCheck(int $limit): bool
{
    $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key   = sys_get_temp_dir() . '/sync_rl_' . md5($ip) . '_' . date('YmdHi');
    $count = is_file($key) ? (int) file_get_contents($key) : 0;
    if ($count >= $limit) return false;
    @file_put_contents($key, $count + 1);
    if (rand(1, 100) === 1) {
        foreach (glob(sys_get_temp_dir() . '/sync_rl_*') as $f) {
            if (filemtime($f) < time() - 120) @unlink($f);
        }
    }
    return true;
}

function fail(string $message, int $code = 403): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $message]);
    logAccess("FAIL[$code]: $message");
    exit;
}

// ===== 安全检查 =====

if (!empty($IP_WHITELIST)) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($clientIp, $IP_WHITELIST, true)) {
        fail('IP not allowed', 403);
    }
}

if (!rateLimitCheck($RATE_LIMIT_PER_MIN)) {
    fail('Rate limit exceeded', 429);
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (!is_string($token) || !hash_equals($SECRET_TOKEN, $token)) {
    fail('Invalid token', 401);
}

if (
    strlen($SECRET_TOKEN) < 16
    || $SECRET_TOKEN === 'CHANGE_ME_TO_A_LONG_RANDOM_STRING_32_BYTES_OR_MORE'
) {
    fail('Server token is too weak, please change SECRET_TOKEN', 500);
}

$action = $_GET['action'] ?? 'list';

// ===== API: 远端版本号 =====
if ($action === 'version') {
    $cfg = $VERSION_DB;
    try {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $table = $cfg['prefix'] . 'config';
        $stmt  = $pdo->prepare(
            "SELECT `value` FROM `{$table}` WHERE `type` = 'website' AND `name` = 'version' LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) fail('Version config not found in database', 404);

        $versionInfo = json_decode($row['value'], true);
        if (!is_array($versionInfo)) fail('Version config format error', 500);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'data'   => [
                'version_number' => (int)($versionInfo['version_number'] ?? 0),
                'version_name'   => $versionInfo['version_name'] ?? '',
                'update_time'    => $versionInfo['update_time'] ?? '',
            ],
        ]);
        logAccess('VERSION ok: ' . ($versionInfo['version_number'] ?? 'unknown'));
    } catch (PDOException $e) {
        fail('Database error: ' . $e->getMessage(), 500);
    }
    exit;
}

// ===== API: 远端数据库连接配置（AES-256-CBC 加密） =====
if ($action === 'dbconfig') {
    $plain = json_encode([
        'host'    => $_ENV['DATABASE.HOSTNAME'] ?? '127.0.0.1',
        'port'    => (int)($_ENV['DATABASE.HOSTPORT'] ?? 3306),
        'user'    => $_ENV['DATABASE.USERNAME']  ?? '',
        'pass'    => $_ENV['DATABASE.PASSWORD']  ?? '',
        'name'    => $_ENV['DATABASE.DATABASE']  ?? '',
        'charset' => $_ENV['DATABASE.CHARSET']   ?? 'utf8mb4',
        'prefix'  => $_ENV['DATABASE.PREFIX']    ?? 'la_',
    ]);

    $key       = substr(hash('sha256', $SECRET_TOKEN, true), 0, 32);
    $iv        = random_bytes(16);
    $encrypted = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) fail('Encryption failed', 500);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'data'   => [
            'iv'      => base64_encode($iv),
            'payload' => base64_encode($encrypted),
        ],
    ]);
    logAccess('DBCONFIG ok (encrypted)');
    exit;
}

// ===== API: 触发预打包 zip =====
if ($action === 'build') {
    global $OVERWRITE_DIRS;

    $zipOutputDir = __DIR__ . '/overwrite_zips/';
    if (!is_dir($zipOutputDir) && !mkdir($zipOutputDir, 0755, true)) {
        fail('无法创建 overwrite_zips 目录', 500);
    }

    $results = [];
    foreach ($OVERWRITE_DIRS as $relDir) {
        $srcDir  = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rtrim($relDir, '/'));
        $zipName = rtrim(str_replace('/', '_', $relDir), '_') . '.zip';
        $zipPath = $zipOutputDir . $zipName;

        // 强制重新打包：?force=1
        if (($_GET['force'] ?? '0') === '1' && is_file($zipPath)) {
            @unlink($zipPath);
        }

        // 已存在则跳过
        if (is_file($zipPath)) {
            $results[$relDir] = [
                'status' => 'skipped',
                'name'   => $zipName,
                'size'   => filesize($zipPath),
                'mtime'  => date('Y-m-d H:i:s', filemtime($zipPath)),
                'msg'    => '已存在，跳过',
            ];
            continue;
        }

        if (!is_dir($srcDir)) {
            $results[$relDir] = [
                'status' => 'error',
                'name'   => $zipName,
                'msg'    => '目录不存在：' . $relDir,
            ];
            continue;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $results[$relDir] = [
                'status' => 'error',
                'name'   => $zipName,
                'msg'    => '无法创建 zip 文件',
            ];
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $count = 0;
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            // zip 内路径保持与项目根目录的相对路径一致，例：public/admin/index.html
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($baseDir) + 1));
            $zip->addFile($file->getPathname(), $relativePath);
            $count++;
        }
        $zip->close();

        $results[$relDir] = [
            'status' => 'built',
            'name'   => $zipName,
            'count'  => $count,
            'size'   => filesize($zipPath),
            'mtime'  => date('Y-m-d H:i:s', filemtime($zipPath)),
            'msg'    => "打包完成，共 {$count} 个文件",
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'results' => $results]);
    logAccess('BUILD ok');
    exit;
}

// ===== API: 下载预打包覆盖目录 zip =====
if ($action === 'zip') {
    $name = $_GET['name'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
        fail('Invalid zip name', 400);
    }

    $zipDir = __DIR__ . '/overwrite_zips/';
    if (!is_dir($zipDir) && !mkdir($zipDir, 0755, true)) {
        fail('无法创建 overwrite_zips 目录', 500);
    }

    // ✅ 检查所有 $OVERWRITE_DIRS 对应的 zip，缺失的自动打包
    foreach ($OVERWRITE_DIRS as $relDir) {
        $zipName = rtrim(str_replace('/', '_', $relDir), '_') . '.zip';
        $zipPath = $zipDir . $zipName;

        if (is_file($zipPath)) continue; // 已存在，跳过

        $srcDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rtrim($relDir, '/'));

        if (!is_dir($srcDir)) {
            fail("目录不存在，无法自动打包：{$relDir}", 500);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            fail("无法创建 zip 文件：{$zipName}", 500);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($baseDir) + 1));
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
        logAccess("AUTO BUILD: {$zipName}");
    }

    $zipPath = $zipDir . $name . '.zip';

    if (!is_file($zipPath)) {
        fail("zip 包不存在：{$name}.zip，请确认 name 参数与 OVERWRITE_DIRS 配置一致", 404);
    }

    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipPath));
    header('Content-Disposition: attachment; filename="' . $name . '.zip"');
    readfile($zipPath);
    logAccess("ZIP ok: {$name}.zip");
    exit;
}


// ===== API: 文件列表 =====
if ($action === 'list') {
    $fileList = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($baseDir) + 1));
        if (isIgnored($relativePath, $ignoreList)) continue;
        $fileList[$relativePath] = [
            'md5'   => md5_file($file->getPathname()),
            'size'  => $file->getSize(),
            'mtime' => $file->getMTime(),
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'count' => count($fileList), 'data' => $fileList]);
    logAccess('LIST ok, ' . count($fileList) . ' files');
    exit;
}

// ===== API: 单文件下载 =====
if ($action === 'download') {
    $filePath = $_GET['file'] ?? '';
    if ($filePath === '' || str_contains($filePath, "\0")) fail('Invalid file param', 400);
    if ($filePath[0] === '/' || preg_match('#^[a-zA-Z]:#', $filePath)) fail('Absolute path not allowed', 400);
    if (str_contains($filePath, '..')) fail('Path traversal not allowed', 400);

    $realPath = realpath($baseDir . DIRECTORY_SEPARATOR . $filePath);
    if (
        !$realPath
        || !str_starts_with($realPath, $baseDir . DIRECTORY_SEPARATOR)
        || !is_file($realPath)
    ) {
        fail('File not found or access denied', 404);
    }

    $relCheck = str_replace('\\', '/', substr($realPath, strlen($baseDir) + 1));
    if (isIgnored($relCheck, $ignoreList)) fail('File is in ignore list', 403);

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($realPath));
    header('X-File-MD5: ' . md5_file($realPath));
    readfile($realPath);
    logAccess("DOWNLOAD ok: $relCheck");
    exit;
}

// ===== API: 更新公告 =====
if ($action === 'notice') {
    $noticePath = __DIR__ . '/update_notice.json';

    if (!is_file($noticePath)) fail('update_notice.json not found', 404);

    $raw  = file_get_contents($noticePath);
    $data = json_decode($raw, true);

    if (!is_array($data)) fail('update_notice.json format error', 500);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'data' => $data]);
    logAccess('NOTICE ok');
    exit;
}

fail('Unknown action', 400);
