<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\user\UserAuth;
use app\api\logic\WechatLogic;

class ApiLogic extends BaseLogic
{
    public static $uid = 0;

    public static function sendNotice(array $payload, $type = 'task')
    {
        try {
            $openId = UserAuth::where('user_id', $payload['userId'])->where('terminal', 1)->order('update_time', 'desc')->limit(1)->findOrEmpty()->openid ?? '';
            if (empty($openId)) {
                throw new \think\exception\ValidateException('openId为空');
            }

            if ($type == 'task') {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 401,
                    'name' => $payload['content'],
                    'start_time' => $payload['startTime'],
                    'end_time' => $payload['endTime'],
                    'status' => \app\common\enum\DeviceEnum::getTaskStatusDesc($payload['status']),
                );
            } else {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 402,
                    'name' => $payload['content'],
                    'status' => $payload['status'],
                    'time' => $payload['time'] ?? date('Y-m-d H:i:s', time()),
                );
            }
            WechatLogic::sendMnpMessage($data);
            \think\facade\Log::channel('notice')->write(json_encode(['openId' => $openId, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $th) {
            \think\facade\Log::channel('notice')->write(json_encode(['openId' => $openId, 'payload' => $payload, 'error' => $th->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }


    public static function  checkAutoDevice(array $payload)
    {
        try {
            if (isset($payload['accounts']) && is_array($payload['accounts'])) {
                foreach ($payload['accounts'] as $account) {
                    $find = \app\common\model\sv\SvAccount::field('id,user_id,device_code')->where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                    if ($find->isEmpty()) {
                        $msg = $account['type'] == 1 ? '微信账号' : '账号';
                        $msg .= '不存在,请在设备关联中添加账号';
                        throw new \Exception($account['account'] . $msg);
                    }
                    $device = \app\common\model\sv\SvDevice::where('device_code', $find->device_code)->where('user_id', self::$uid)->where('auto_type', 1)->limit(1)->findOrEmpty();
                    if (!$device->isEmpty()) {
                        throw new \Exception('设备' . $find->device_code . '已开启24小时自动执行任务，无法创建手动任务');
                    }
                }
            }

            if(isset($payload['wechat_ids']) && is_array($payload['wechat_ids'])){
                foreach ($payload['wechat_ids'] as $wechat_id) {
                    $find = \app\common\model\sv\SvAccount::field('id,user_id,device_code')->where('account', $wechat_id)->where('user_id', self::$uid)->where('type', 1)->limit(1)->findOrEmpty();
                    if ($find->isEmpty()) {
                        throw new \Exception('账号不存在');
                    }
                    $device = \app\common\model\sv\SvDevice::where('device_code', $find->device_code)->where('user_id', self::$uid)->where('auto_type', 1)->limit(1)->findOrEmpty();
                    if (!$device->isEmpty()) {
                        throw new \Exception('设备' . $find->device_code . '已开启24小时自动执行任务，无法创建手动任务');
                    }
                }
            }

            if(isset($payload['device_codes']) && is_array($payload['device_codes'])){
                foreach ($payload['device_codes'] as $device_code) {
                    $find = \app\common\model\sv\SvDevice::where('device_code', $device_code)->where('user_id', self::$uid)->where('auto_type', 1)->limit(1)->findOrEmpty();
                    if (!$find->isEmpty()) {
                        throw new \Exception('设备' . $device_code . '已开启24小时自动执行任务，无法创建手动任务');
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function getAutoConfigStatus($find)
    {
        $setting = array(
            'clues_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'touch_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'takeover_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->where('robot_id', '>', 0)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->where('robot_id', '>', 0)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'publish_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceSetting::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceSetting::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'add_wechat_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'circle_like_reply_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'wechat_circle_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'analysis' => [
                'task_status' => 2,
                'is_config' => !empty($find->analysis) ? self::checkAnalysis($find->analysis) : 0,
            ]
        );
        
        $status = [];
        $isConfig = [];
        foreach ($setting as $key => $value) {
            array_push($status, $value['task_status']);
            array_push($isConfig, $value['is_config']);
        }
        $status = array_values(array_unique($status));
        sort($status);
        $isConfig = array_values(array_unique($isConfig));
        $task_status = function ($status) {
            if (count($status) > 1 && (in_array(0, $status) || in_array(1, $status) || in_array(3, $status))) {
                return $status[0];
            }
            return 2;
        };
        $is_config = count($isConfig) > 1 ? 2 : ($isConfig[0] == 1 ? 1 : 0);
        return [$setting, $task_status($status), $is_config];
    }

    public static function checkAnalysis(string $analysis): int
    {
        $analysis = json_decode($analysis, true);
        $is_config = 1;
        foreach ($analysis as $key => $value) {
            if ($value == '') {
                $is_config = 0;
                break;
            }
        }
        return $is_config;
    }
}
