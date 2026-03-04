<?php
// public/install/async_webhook.php
// 异步推送企微通知
include "../../app/common/service/ToolsService.php";
require '../../vendor/autoload.php';

// 即使用户触发端断开了连接，脚本也要在后台继续执行完毕
ignore_user_abort(true);
set_time_limit(0); // 取消脚本运行时间限制

$domain = $_POST['domain'] ?? '';
$token = $_POST['auth_token'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$timestamp = (int) ($_POST['timestamp'] ?? 0);

// 验证请求参数
if ($domain == '' || $token == '' || $timestamp == 0) {
    exit('Invalid Parameters');
}

// 执行 Webhook 任务（内部包含鉴权和逻辑处理）
$result = \app\common\service\ToolsService::Notify()->processWebhookTask($domain, $mobile, $timestamp, $token);
echo $result;
