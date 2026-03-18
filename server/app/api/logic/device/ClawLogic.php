<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\user\User;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\wechat\AiWechat;
use think\facade\Db;


/**
 * 设备抓取任务逻辑
 * Class ClawLogic    
 * @package app\api\logic\device
 */
class ClawLogic extends ApiLogic
{
    public static function getInfo($params)
    {
        try {
            if(!isset($params['device_code']) || !$params['device_code']){
                throw new \Exception('设备号不能为空');
            }
            $device = SvDevice::field('device_name,device_code,user_id')->where('device_code', $params['device_code'])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $user = User::field('id,real_name,nickname,avatar,mobile')->where('id', $device->user_id)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            self::$returnData = [
                'device' => $device->toArray(),
                'user' => $user->toArray(),
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function getTask($params)
    {
        try {
             if(!isset($params['device_code']) || !$params['device_code']){
                throw new \Exception('设备号不能为空');
            }
            $task = SvDeviceTask::field('id,device_code,task_type,account,account_type,task_name,status,source,start_time,end_time')
                ->where('device_code', $params['device_code'])
                ->where('auto_type', '=', function ($query) use ($params) {
                    $query->name('sv_device')->where('device_code', $params['device_code'])->field('auto_type');
                })
                ->where('day', date('Y-m-d', time()))
                ->order('start_time', 'asc')
                ->select()
                ->each(function ($item) {
                    $item['start_time'] = date('H:i', $item['start_time']);
                    $item['end_time'] = date('H:i', $item['end_time']);
                    $item['task_category'] = !in_array($item['source'], [7, 8])? DeviceEnum::getAccountTypeDesc($item['account_type']) . DeviceEnum::getTaskTypeDesc($item['task_type']) : DeviceEnum::getTaskSceneDesc($item['task_type']);
                    return $item;
                });
            
            self::$returnData = $task->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}