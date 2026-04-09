<?php
// error_reporting(0);
include "model.php";
include "YxEnv.php";
include "../../app/common/service/ToolsService.php";
require '../../vendor/autoload.php';
define('install', true);
define('INSTALL_ROOT', __DIR__);
define('TESTING_TABLE', 'config');

$step = $_GET['step'] ?? 1;

session_start();

$installDir = "install";
$modelInstall = new installModel();

// Env设置
$yxEnv = new YxEnv();

// 检查是否有安装过
$envFilePath = $modelInstall->getAppRoot() . '/.env';
if ($modelInstall->appIsInstalled() && in_array($step, [1, 2, 3, 4])) {
    die('可能已经安装过本系统了，请删除根目录下面的install.lock文件再尝试');
}

// 加载Example文件
$yxEnv->load($modelInstall->getAppRoot() . '/.example.env');

//尝试生成.env
$yxEnv->makeEnv($modelInstall->getAppRoot() . '/.env');

$post = [
    'host' => trim($_POST['host'] ?? '127.0.0.1'),
    'port' => trim($_POST['port'] ?? '3306'),
    'user' => trim($_POST['user'] ?? 'root'),
    'password' => trim($_POST['password'] ?? ''),
    'name' => trim($_POST['name'] ?? 'Ai系统'),
    'admin_user' => trim($_POST['admin_user'] ?? ''),
    'admin_password' => trim($_POST['admin_password'] ?? ''),
    'cdkey' => trim($_POST['cdkey'] ?? ''),
    'admin_confirm_password' => trim($_POST['admin_confirm_password'] ?? ''),
    'prefix' => trim($_POST['prefix'] ?? 'iw_'),
    'clear_db' => trim($_POST['clear_db'] ?? 'off'),
    'mobile' => trim($_POST['mobile'] ?? ''),
    'code' => trim($_POST['code'] ?? ''),
    'type' => trim($_POST['type'] ?? ''),
    'ai_password' => trim($_POST['ai_password'] ?? $_SERVER['HTTP_HOST']),

    'pg_host' => trim($_POST['pg_host'] ?? '127.0.0.1'),
    'pg_port' => trim($_POST['pg_port'] ?? '5432'),
    'pg_name' => trim($_POST['pg_name'] ?? 'postgres'),
    'pg_user' => trim($_POST['pg_user'] ?? 'postgres'),
    'pg_password' => trim($_POST['pg_password'] ?? 'postgres'),
    'pg_prefix' => trim($_POST['pg_prefix'] ?? 'iw_'),
];

if ($step == 4) {
    $_SESSION['install_data'] = $post; // 保存到 Session
}

if ($step == 5) {
    $post = $_SESSION['install_data']; // 获取 Session
}

// 判断是否 HTTPS
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";

// 获取主机名
$host = $_SERVER['HTTP_HOST'];
$address = [
    'admin' => $protocol . $host . '/admin',
    'ai' => 'https://imai.club/user/login'
];

$message = '';

if ($post['type'] == 'send') {
    $response = \app\common\service\ToolsService::DataCenter()->tokensKey([
        'type' => $post['type'],
        'ym' => $host,
        'mobile' => $post['mobile'],
    ]);

    echo json_encode($response);
    exit;
}

// 检查数据库正确性
if ($step == 4) {
    $canNext = true;
    if (empty($post['prefix'])) {
        $canNext = false;
        $message = '数据表前缀不能为空';
    } elseif ($post['admin_user'] == '') {
        $canNext = false;
        $message = '请填写管理员用户名';
    } elseif (empty(trim($post['admin_password']))) {
        $canNext = false;
        $message = '管理员密码不能为空';
    } elseif ($post['admin_password'] != $post['admin_confirm_password']) {
        $canNext = false;
        $message = '两次密码不一致';
    } elseif (empty($post['mobile'])) {
        $canNext = false;
        $message = '请输入手机号';
    } elseif (empty($post['code'])) {
        $canNext = false;
        $message = '请输入手机验证码';
    } else {
        $keyInfo = [];
        if ($canNext) {
            //自动注册数据中台
            $response = \app\common\service\ToolsService::DataCenter()->tokensKey([
                'type' => 'register',
                'mobile' => $post['mobile'],
                'code' => $post['code'],
                'ym' => $host,
                'password' => $post['ai_password']
            ]);

            if ($response['code'] != 10000 || !isset($response['data']['api_key'])) {
                $canNext = false;
                $message = $response['message'] ?? '注册数据中台失败';
            }

            $keyInfo = $response['data'];
        }

        // if ($canNext) {
        //     //自动注册授权
        //     $response = \app\common\service\ToolsService::Auth($post)->cdkExchange();
        //     if (($response['code'] != 10000 && $response['code'] != 19007) && $post['cdkey'] != "") {
        //         $canNext = false;
        //         $message = $response['message'] ?? '授权卡号验证失败';
        //     }
        // }

        if ($canNext) {
            // 检查 数据库信息、安装初始化SQL
            $result = $modelInstall->checkConfig($post['name'], $post);
            if ($result->result == 'fail') {
                $canNext = false;
                $message = $result->error;
            }

            // 检查PG数据库和安装
            $pgResult = $modelInstall->checkPgConfig($post);
            if ($pgResult->result == 'fail') {
                $canNext = false;
                $message = $pgResult->error;
            }
        }

        // 更新数据
        if ($canNext) {
            if (!$modelInstall->importAIModelData()) {
                $canNext = false;
                $message = '更新基础数据错误';
            }
        }

        // 写配置文件-
        if ($canNext) {

            if (!$keyInfo) {

                $canNext = false;
                $message = '没有获取到中台信息';
            }

            $yxEnv->putEnv($envFilePath, $post, $keyInfo);
            $modelInstall->mkLockFile();

            // 恢复admin和index入口
            $modelInstall->restoreIndexLock();

            //更新授权
            //$modelInstall->updateAuthSql($post['cdkey']);

            //更新安装时间时间
            $modelInstall->updateVersionSql();
        }
    }

    if (!$canNext) {

        $step = 3;
    }
}

// 取得安装成功的表
$successTables = $modelInstall->getSuccessTable();

$nextStep = $step + 1;
$lastStep = $step - 1;
include __DIR__ . "/template/main.php";
