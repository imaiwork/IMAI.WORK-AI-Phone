<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\model\auto\AutoDeviceConfig;
use think\facade\Db;


/**
 * 设备自动任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\auto
 */
class DeviceLogic extends ApiLogic
{
    public static function add($params)
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;

            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status === DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    throw new \Exception('当前设备自动任务正在执行中，请稍后再试');
                }
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->human_image = $params['human_image'];
                $find->clip_material = $params['clip_material'];
                $find->image_material = $params['image_material'];
                $find->clue_theme = $params['clue_theme'];
                $find->video_theme = $params['video_theme'];
                $find->text_theme = $params['text_theme'];
                $find->update_time = time();
                $find->save();
                self::$returnData = $find->toArray();
            } else {
                $params['create_time'] = time();
                $params['update_time'] = time();
                $result = AutoDeviceConfig::create($params);
                self::$returnData = $result->toArray();
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail($params)
    {
        try {
            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::$returnData = $find->toArray();
                self::$returnData['is_empty'] = 0;
                
            } else {
                self::$returnData = [
                    'device_code' => $params['device_code'],
                    'human_image' => [],
                    'clip_material' => [],
                    'image_material' => [],
                    'clue_theme' => '',
                    'video_theme' => '',
                    'text_theme' => '',
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'is_empty' => 1,
                ];
            }
            list($setting, $task_status, $is_config) = self::getAutoConfigStatus($find);
            self::$returnData['auto_setting'] = $setting;
            self::$returnData['task_status'] = $task_status;
            self::$returnData['is_config'] = $is_config;
            self::$returnData['accounts'] = SvAccount::field('id,account,type')->where('type', '<>', 1)->where('user_id', self::$uid)->where('device_code', $params['device_code'])->select();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function getAutoConfigStatus($find)
    {
        $setting = array(
            'clues_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'touch_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'takeover_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            // 'active_setting' => [
            //     'task_status' => ($status = \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
            //     'is_config' => \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            // ],
            'publish_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'add_wechat_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
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
            if(count($status) > 1 && (in_array(0, $status) || in_array(1, $status) || in_array(3, $status))){
                return $status[0];
            }
            return 2;
        };
        $is_config = count($isConfig) > 1 ? 2 : ($isConfig[0] == 1 ? 1 : 0);

        $accountCount = \app\common\model\sv\SvAccount::where('user_id', self::$uid)->where('device_code', $find->device_code)->count();
        $is_config = $accountCount >= 1 ? $is_config : 0;

        return [$setting, $task_status($status), $is_config];
    }
}
