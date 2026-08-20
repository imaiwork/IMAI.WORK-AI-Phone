<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\user\UserAuth;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\api\logic\WechatLogic;

use app\common\model\sv\SvDevice;

use app\common\model\sv\SvDeviceTask;
class ApiLogic extends BaseLogic
{
    public static $uid = 0;

    public static function deleteOldPersonaTask(SvDevice $device, string $msg)
    {
        
        $find = SvDeviceTask::where('device_code', $device->device_code)
            ->where('auto_type', 1)
            ->where('day', date('Y-m-d'))
            ->where('status', 1)
            ->findOrEmpty();
        if (!$find->isEmpty()) {

            $payload = array(
                'type' => \app\common\enum\DeviceEnum::TASK_PERSONA_RESET, // 重置人设
                'appType' => 0,
                'content' => json_encode(array(
                    'deviceId' => $find->device_code,
                    'code' => \app\common\enum\DeviceEnum::TASK_PERSONA_ERROR,
                    'msg' => $msg
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $find->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );

            \think\facade\Log::channel('device')->info(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $channel = "device.{$find->device_code}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => json_encode($payload)
            ]);
            $find->delete();
        }
        
        \app\common\model\sv\SvPublishSettingDetail::where('device_code', $device->device_code)
            ->where('auto_type', 1)
            ->where('publish_time', 'between', [date('Y-m-d 00:00:00', time()), date('Y-m-d 23:59:59', time())])
            ->select()->delete();
        \app\common\model\wechat\AiWechatCircleTask::where('device_code', $device->device_code)
            ->where('auto_type', 1)
            ->where('send_time', 'between', [date('Y-m-d 00:00:00', time()), date('Y-m-d 23:59:59', time())])
            ->select()->delete();
        SvDeviceTask::where('device_code', $device->device_code)
            ->where('auto_type', 1)
            ->where('day', date('Y-m-d'))
            ->select()->delete();
    }

    public static function sendNotice(array $payload, $scene_id = 401)
    {
        try {
            $openId = UserAuth::where('user_id', $payload['userId'])->where('terminal', 1)->order('update_time', 'desc')->limit(1)->findOrEmpty()->openid ?? '';
            if (empty($openId)) {
                throw new \think\exception\ValidateException('openId为空');
            }
            $data = [];
            if ($scene_id == 401) {//task
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 401,
                    'name' => $payload['content'].($payload['autoType'] == 1 ? '24h' : '手动'),
                    'start_time' => $payload['startTime'],
                    'end_time' => $payload['endTime'],
                    'status' => \app\common\enum\DeviceEnum::getTaskStatusDesc($payload['status']),
                );
            } else if ($scene_id == 402) {//video
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 402,
                    'name' => $payload['content'],
                    'status' => $payload['status'],
                    'time' => $payload['time'] ?? date('Y-m-d H:i:s', time()),
                );
            } elseif ($scene_id == 403) {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 403,
                    'name' => $payload['content'],
                    'status' => $payload['status'],
                    'time' => $payload['time'] ?? date('Y-m-d H:i:s', time()),
                );
            } elseif ($scene_id == 404) {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 404,
                    'name' => $payload['content'].($payload['autoType'] == 1 ? '24h' : '手动'),
                    'start_time' => $payload['startTime'],
                    'end_time' => $payload['endTime'],
                    'status' => \app\common\enum\DeviceEnum::getTaskStatusDesc($payload['status']),
                );
            }elseif ($scene_id == 405) {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 405,
                    'name' => $payload['name'],
                    'count' => $payload['count'],
                    'time' => $payload['time'] ?? date('Y-m-d H:i:s', time()),
                    'phone_number' => $payload['phone_number'],
                );
            }elseif ($scene_id == 406) {
                $data = array(
                    'openid' => $openId,
                    'scene_id' => 406,
                    'name' => $payload['name'],
                    'time' => $payload['time'] ?? date('Y-m-d H:i:s', time()),
                    'status' => $payload['status'],
                );
            }
            WechatLogic::sendMnpMessage($data);
            //\think\facade\Log::channel('notice')->write(json_encode(['openId' => $openId, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $th) {
            //\think\facade\Log::channel('notice')->write(json_encode(['openId' => $openId, 'payload' => $payload, 'error' => $th->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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

    public static function getPersonaRule(AiPersona $persona)
    {
        $rule = match ((int)$persona->persona_type) {
            1 => AiPersonaIndividual::where('persona_id', $persona->id)->findOrEmpty(),
            2 => AiPersonaEnterprise::where('persona_id', $persona->id)->findOrEmpty(),
            3 => AiPersonaLocal::where('persona_id', $persona->id)->findOrEmpty(),
            default => null,
        };

        if ($rule === null) {
            self::setError('IP人设类型错误');
            return false;
        }

        $rule->clue_content = $rule->getClueContent($persona);
        return $rule;
    }
}
