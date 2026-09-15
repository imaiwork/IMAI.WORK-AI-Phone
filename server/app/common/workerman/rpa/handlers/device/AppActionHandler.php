<?php

namespace app\common\workerman\rpa\handlers\device;

use app\common\model\sv\SvDeviceLog;
use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\WorkerEnum;

class AppActionHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void {}

    public function sendActionToWeb(array $content, string $action)
    {

        try {

            $userId = $this->service->getRedis()->get("xhs:getUser:" . $this->payload['deviceId']);
            $this->recordDeviceLog((int)$userId, $content, $action);
            $sources = WorkerEnum::WS_SOURCES;
            foreach ($sources as $source) {
                $uid = $this->service->getRedis()->get("xhs:user:{$source}:{$userId}");
                if ($uid) {
                    $message = array(
                        'messageId' => $uid,
                        'type' => $action,
                        'appType' => $this->payload['appType'] ?? 3,
                        'deviceId' => $this->payload['deviceId'],
                        'appVersion' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                        'code' => $this->payload['code'],
                        'reply' => json_encode($content, JSON_UNESCAPED_UNICODE)
                    );
                    $this->setLog($message, 'user');
                    $this->sendResponse($uid,  $message,  $message['reply']);
                }
            }
        } catch (\Exception $e) {
            $this->setLog('_sendWeb' . $e, 'error');
        }
    }

    private function recordDeviceLog(int $userId, array $content, string $action): void
    {
        try {
            $payloadContent = $this->normalizePayloadContent($this->payload['content'] ?? []);
            $appType = (int)($this->payload['appType'] ?? ($payloadContent['appType'] ?? 0));

            SvDeviceLog::create([
                'user_id' => $userId,
                'device_code' => $this->payload['deviceId'] ?? '',
                'app_type' => $appType,
                'content' => [
                    'msg' => $this->buildDeviceLogText($action, $appType, $content),
                    'title' => $this->buildDeviceLogTag($action, $appType),
                    'image' => $payloadContent['image'] ?? $payloadContent['imageUrl'] ?? $content['image'] ?? '',
                ],
                'app_version' => $this->payload['appVersion'] ?? WorkerEnum::APP_VERSION,
                'day' => date('Y-m-d'),
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            $this->setLog('AppActionHandler recordDeviceLog error: ' . $e->getMessage(), 'error');
        }
    }

    private function normalizePayloadContent(array|string $content): array
    {
        if (is_array($content)) {
            return $content;
        }

        $decoded = json_decode((string)$content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function buildDeviceLogText(string $action, int $appType, array $content): string
    {
        $actionTextMap = [
            'appSend' => '指令发送完成',
            'appExec' => '手机处理完成',
            'appOpen' => '打开应用完成',
            'appPersonalCenter' => '进入个人中心完成',
            'appUserInfoDefore' => '账号信息预处理完成',
            'appInfo' => '账号信息获取完成',
            'appDataSend' => '数据发送完成',
            'appCompleted' => '账号获取完成',
        ];

        if ((int)($content['code'] ?? WorkerEnum::SUCCESS_CODE) !== WorkerEnum::SUCCESS_CODE && !empty($content['msg'])) {
            $actionText = $content['msg'];
        } else {
            $actionText = $actionTextMap[$action] ?? ($content['msg'] ?? '账号获取进度更新');
        }
        $appText = WorkerEnum::getAccountTypeDesc($appType) ?: '未知平台';

        return $actionText . ' -- ' . $appText;
    }

    private function buildDeviceLogTag(string $action, int $appType): string
    {
        return WorkerEnum::getAccountTypeDesc($appType) . '账号获取';
    }
}
