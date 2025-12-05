<?php

/**
 * Swoole Loader Wizard
 * Swoole Loader 安装助手
 * version : 3.1
 */
if (
    isset($_SERVER['HTTP_HOST']) and ($_SERVER['HTTP_HOST'] == 'compiler.swoole.com'
        or $_SERVER['HTTP_HOST'] == 'business.swoole.local')
) {
    die("access deny\n");
}

// Set debug var
ini_set("display_errors", "On");
error_reporting(E_ALL);
restore_exception_handler();
restore_error_handler();
date_default_timezone_set('Asia/Shanghai');

const EXT_NAME = 'swoole_loader';
const WIZARD_VERSION = '3.1';
const WIZARD_DEFAULT_LANG = 'zh-cn';
const WIZARD_OPTIONAL_LANG = 'zh-cn,en';
const WIZARD_NAME_ZH = 'Swoole Compiler Loader 安装助手';
const WIZARD_NAME_EN = 'Swoole Compiler Loader Wizard';
const WIZARD_DEFAULT_RUN_MODE = 'web';
const WIZARD_OPTIONAL_RUN_MODE = 'cli,web';
const WIZARD_DEFAULT_OS = 'linux';
const WIZARD_OPTIONAL_OS = 'linux,windows';
const WIZARD_BASE_API = 'https://business.swoole.com/compiler.html';
const DOWNLOAD_URL = 'https://down.imai.work/zend/swoole_loader.so';

// Set env variable for current environment
$env = swoole_loader_get_info();

if ('web' == $env['php']['run_mode']) {
    swoole_loader_usage_for_web($env);
} elseif ('cli' == $env['php']['run_mode']) {
    swoole_loader_usage_for_cli($env);
} else {
    echo "未知的运行模式：" . $env['php']['run_mode'] . PHP_EOL;
}

function swoole_loader_usage_for_cli($env)
{
    $list = [
        ['desc' => '操作系统', 'value' => $env['os']['name']],
        ['desc' => 'PHP版本', 'value' => $env['php']['version']],
        ['desc' => 'PHP 运行环境', 'value' => $env['php']['sapi'],],
        ['desc' => 'php.ini 路径', 'value' => $env['php']['ini_loaded_file'],],
        ['desc' => '扫描 ini 路径', 'value' => $env['php']['ini_scanned_files'],],
        ['desc' => 'PHP 扩展安装目录', 'value' => $env['php']['extension_dir'],],
        [
            'desc' => 'swoole_loader 扩展',
            'value' => extension_loaded('swoole_loader') ?
                '已安装，版本 ' . swoole_loader_version()
                : '未安装'
        ],
        ['desc' => 'PHP是否线程安全', 'value' => $env['php']['thread_safety'],],
    ];

    echo "Swoole Loader 安装助手" . PHP_EOL . str_repeat('=', 60) . PHP_EOL;
    echo "检查当前环境" . PHP_EOL . str_repeat('-', 60) . PHP_EOL;
    foreach ($list as $info) {
        echo $info['desc'] . ': ' . $info['value'] . PHP_EOL;
    }

    if (!empty($env['php']['loaded_incompatible_extensions'])) {
        echo "错误信息" . PHP_EOL . str_repeat('-', 60) . PHP_EOL;
        echo "当前 PHP 包含与 swoole_loader 扩展不兼容的扩展：" . implode(', ', $env['php']['loaded_incompatible_extensions']) . "，建议移除。";
    }

    echo "安装和配置" . PHP_EOL . str_repeat('-', 60) . PHP_EOL;
    echo "1. 下载 Swoole Loader\n请下载 "
        . $env['os']['name'] . '系统 '
        . 'PHP-' . $env['php']['version'] . ' 版本 '
        . $env['php']['thread_safety']
        . '的 swoole_loader 扩展，下载地址：' . DOWNLOAD_URL . PHP_EOL . PHP_EOL;

    echo "2. 安装 Swoole Loader\n将刚才下载的 swoole_loader 扩展文件（ swoole_loader." .
        $env['loader_ext'] . " ）上传到当前 PHP 的扩展安装目录中：" . $env['php']['extension_dir'] . PHP_EOL . PHP_EOL;

    echo '3. 修改 php.ini 配置' . PHP_EOL;
    echo '编辑此 PHP 配置文件：' . $env['php']['ini_loaded_file'] . '，在此文件底部结尾处加入如下配置';
    echo ' extension=swoole_loader.' . $env['loader_ext'] . "\n注意：需要名称和刚才上传到当前 PHP 的扩展安装目录中的文件名一致" . PHP_EOL . PHP_EOL;

    echo "4. 重启服务\n重启 PHP 服务 [宝塔 => 软件商店 => PHP => 重启]";

    echo "CopyRight © CopyRight © 京ICP备2021020474号-2 壹件事（深圳）科技有限公司-2" . PHP_EOL;
}

function swoole_loader_is_thread_safety()
{
    ob_start();
    phpinfo();
    $phpInfo = strip_tags(ob_get_contents());
    ob_end_clean();
    if (php_sapi_name() == 'cli') {
        return !preg_match_all('#Thread\s+Safety\s+\=\>\s+disabled#i', $phpInfo, $match);
    } else {
        return !preg_match_all('#Thread\s+Safety\s+disabled#i', $phpInfo, $match);
    }
}

function swoole_loader_get_info()
{
    $env = [];
    // Check os type
    $env['os'] = [];
    $env['os']['name'] = PHP_OS;
    $env['os']['raw_name'] = php_uname();
    $env['os']['is_win'] = strtolower(substr(PHP_OS, 0, 3)) === 'win';
    $env['loader_ext'] = $env['os']['is_win'] ? 'dll' : 'so';
    // Check php
    $env['php'] = [];
    $env['php']['version'] = phpversion();
    // Check run mode
    $sapi_type = php_sapi_name();
    if ("cli" == $sapi_type) {
        $env['php']['run_mode'] = "cli";
    } else {
        $env['php']['run_mode'] = "web";
    }
    // Check php bit
    if (PHP_INT_SIZE == 4) {
        $env['php']['bit'] = 32;
    } else {
        $env['php']['bit'] = 64;
    }
    $env['php']['sapi'] = $sapi_type;
    $env['php']['ini_loaded_file'] = php_ini_loaded_file() ?: '(none)';
    $env['php']['ini_scanned_files'] = php_ini_scanned_files() ?: '(none)';
    $env['php']['loaded_extensions'] = get_loaded_extensions();
    $env['php']['incompatible_extensions'] = ['xdebug', 'ionCube', 'zend_loader', 'swoole_tracker'];
    $env['php']['loaded_incompatible_extensions'] = [];
    $env['php']['extension_dir'] = ini_get('extension_dir');
    // Check incompatible extensions
    if (is_array($env['php']['loaded_extensions'])) {
        foreach ($env['php']['loaded_extensions'] as $loaded_extension) {
            foreach ($env['php']['incompatible_extensions'] as $incompatible_extension) {
                if (strpos(strtolower($loaded_extension), strtolower($incompatible_extension)) !== false) {
                    $env['php']['loaded_incompatible_extensions'][] = $loaded_extension;
                }
            }
        }
    }
    $env['php']['loaded_incompatible_extensions'] = array_unique($env['php']['loaded_incompatible_extensions']);
    // Check php thread safety
    $env['php']['thread_safety'] = swoole_loader_is_thread_safety() ? '线程安全' : '非线程安全';
    // Check swoole loader installation
    if (extension_loaded(EXT_NAME)) {
        $env['php']['swoole_loader']['status'] = "<span style='color: #007bff;'>已安装 <a href='./install/install.php'> 安装源码 </a></span>";
        $env['php']['swoole_loader']['version'] = "<span style='color: #007bff;'>" . swoole_loader_version() . "</span>";

        // 如果已安装，3秒后跳转到安装界面
        $env['php']['swoole_loader']['status'] .= <<<HTML
            <script>
                setTimeout(function() {
                    window.location.href = './install/install.php';
                }, 3000);
            </script>
            <p style="color: green;">已检测到 Swoole Loader，3秒后将跳转到安装页面...</p>
        HTML;
    } else {
        $env['php']['swoole_loader']['status'] = '未安装';
        $env['php']['swoole_loader']['version'] = '未知';
    }

    return $env;
}

function swoole_loader_usage_for_web($env)
{
    // Language items
    $languages['zh-cn'] = [
        'title' => 'Swoole Loader 安装助手',
    ];
    $languages['en'] = [
        'title' => 'Swoole Loader Wizard',
    ];
    $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
    if (preg_match("/zh-c/i", $language)) {
        $env['lang'] = "zh-cn";
        $wizard_lang = $env['lang'];
    } else {
        $env['lang'] = "en";
        $wizard_lang = $env['lang'];
    }
    $html = '';
    // Header
    $html_header = '<!doctype html>
	<html lang="en">
	  <head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- Bootstrap CSS -->
		<link href="https://lib.baomitu.com/twitter-bootstrap/4.1.0/css/bootstrap.min.css" rel="stylesheet">
		<title>%s</title>
		<style>
			.list_info {display: inline-block; width: 13rem; font-weight: bold}
			.bold_text {font-weight: bold;}
			.code {color:#007bff; font-size: medium;}
		</style>
	  </head>
	  <body class="bg-light"> 
	  ';
    $html_header = sprintf($html_header, $languages[$wizard_lang]['title']);
    $html_body = '<div class="container">';
    $html_body_nav = '<div class="py-5 text-center"  style="padding-bottom: 1rem!important;">';
    $html_body_nav .= '<h2>Swoole Loader 安装向导</h2>';
    $html_body_nav .= '<p class="lead">Version: ' . WIZARD_VERSION . ' Date: 2022-12-09</p>';
    $html_body_nav .= '</div><hr>';

    // Environment information
    $html_body_environment = '
	<div class="col-12"  style="padding-top: 1rem!important;">
		<h5 class="text-center">检查当前环境</h5>
		<ul class="list-unstyled text-small" style="line-height: 2">';
    $html_body_environment .= '<li><span class="list_info">操作系统 : </span>' . $env['os']['name'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">PHP 版本 : </span>' . $env['php']['version'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">PHP 运行环境 : </span>' . $env['php']['sapi'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">PHP 配置文件 : </span>' . $env['php']['ini_loaded_file'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">PHP 扩展安装目录 : </span>' . $env['php']['extension_dir'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">PHP 是否线程安全 : </span>' . $env['php']['thread_safety'] . '</li>';
    $html_body_environment .= '<li><span class="list_info">是否安装 swoole_loader : </span>' . $env['php']['swoole_loader']['status'] . '</li>';
    if (extension_loaded('swoole_loader')) {
        $html_body_environment .= '<li><span class="list_info">swoole_loader 版本 : </span>' . $env['php']['swoole_loader']['version'] . '</li>';
    }
    if ($env['php']['bit'] == 32) {
        $html_body_environment .= '<li><span style="color:red">温馨提示：当前环境使用的 PHP 为 ' . $env['php']['bit'] . ' 位的 PHP，swoole_loader 目前不支持 Debug 版本或 32 位的 PHP，可在 phpinfo() 中查看对应位数，如果误报请忽略此提示</span></li>';
    }
    $html_body_environment .= '	</ul></div>';

    // Error information
    $html_error = "";
    if (!empty($env['php']['loaded_incompatible_extensions'])) {
        $html_error = '<hr>
		<div class="col-12"  style="padding-top: 1rem!important;">
		<h5 class="text-center" style="color:red">错误信息</h5>
		<p class="text-center" style="color:red">%s</p>
    </div>
		';
        $err_msg = "当前 PHP 包含与 swoole_loader 扩展不兼容的扩展：" . implode(', ', $env['php']['loaded_incompatible_extensions']) . "，建议移除。";
        $html_error = sprintf($html_error, $err_msg);
    }

    // Check Loader Status
    $html_body_loader = '<hr>';
    if (empty($html_error)) {
        $html_body_loader .= '<div class="col-12" style="padding-top: 1rem!important;">';
        $html_body_loader .= '<h5 class="text-center">安装和配置</h5>';
        $html_body_loader .= '<p><span class="bold_text">1 - 下载 Swoole Loader</span></p><p>请下载 '
            . '<strong>' . $env['os']['name'] . '</strong> 系统 '
            . '<strong> PHP-' . $env['php']['version'] . '</strong> 版本 '
            . '<strong>' . $env['php']['thread_safety'] . '</strong> '
            . '的 swoole_loader 扩展，<a target="_blank" href="' . DOWNLOAD_URL . '">点击直达下载页面</a></p>';
        $html_body_loader .= '<p><span class="bold_text">2 - 安装 Swoole Loader</span></p><p>将刚才下载的 swoole_loader 扩展文件（ swoole_loader.' .
            $env['loader_ext'] . ' ）上传到当前 PHP 的扩展安装目录中：<br/><pre class="code">' . $env['php']['extension_dir'] . '</pre></p>';
        $html_body_loader .= '<p><span class="bold_text">3 - 修改 php.ini 配置</span>（如已修改配置，请忽略此步骤，不必重复添加）</p><p>';
        $html_body_loader .= '编辑此 PHP 配置文件：<span class="code">' . $env['php']['ini_loaded_file'] . '</span>，在此文件底部结尾处加入如下配置<br/>';
        $html_body_loader .= '<pre class="code">extension=swoole_loader.' . $env['loader_ext'] . '</pre>注意：需要名称和刚才上传到当前 PHP 的扩展安装目录中的文件名一致';
        $html_body_loader .= '</p>';
        $html_body_loader .= '<p><span class="bold_text">4. 重启服务 [宝塔 => 软件商店 => PHP => 重启]</p>';
        $html_body_loader .= '</div>';
    }

    // Body footer
    $html_body_footer = '<footer class="my-5 pt-5 text-muted text-center text-small">
	<p class="mb-1">CopyRight © 京ICP备2021020474号-2 壹件事（深圳）科技有限公司-2</p>
  </footer>';
    $html_body .= $html_body_nav . '<div class="row">' . $html_body_environment . $html_error . $html_body_loader . '</div>' . $html_body_footer;
    $html_body .= '</div>';
    // Footer
    $html_footer = '
		<script src="https://lib.baomitu.com/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://lib.baomitu.com/axios/0.18.0/axios.min.js"></script>
		<script src="https://lib.baomitu.com/twitter-bootstrap/4.1.0/js/bootstrap.min.js"></script>
		</body>
	</html>';

    echo $html_header . $html_body . $html_footer;
}
