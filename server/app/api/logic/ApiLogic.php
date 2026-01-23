<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;

class ApiLogic extends BaseLogic
{
    public static $uid = 0;


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
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'publish_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceSetting::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceSetting::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'add_wechat_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
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
