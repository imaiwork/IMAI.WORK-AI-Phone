<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvSetting;
use app\common\model\user\User;


/**
 * 设备任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\device
 */
class DeviceLogic extends ApiLogic
{
    public static function detail($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $find['accounts'] = SvAccount::alias('w')
                ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
                ->leftJoin('sv_setting s', 's.account = w.account')
                ->where('w.device_code', '=', $find['device_code'])
                // ->when($params['type'], function ($query) use ($params) {
                //     $query->where('w.type', $params['type']);
                // })
                ->order('w.id', 'desc')
                ->select()
                ->each(function ($item) {
                    if (empty($item['takeover_mode'])) {
                        $item['takeover_mode'] = 0;
                    }

                    if (empty($item['robot_id'])) {
                        $item['robot_id'] = 0;
                    }

                    $item['robot_name'] = \app\common\model\kb\KbRobot::where('id', $item['robot_id'])->where('user_id', self::$uid)->value('name', '');

                    if (!empty($item['extra'])) {
                        $extraArray = json_decode($item['extra'], true);
                    } else {
                        $extraArray = [];
                    }
                    foreach ($extraArray  as $key => $v) {
                        $item[$key] = $v;
                    }

                    return $item;
                })
                ->toArray();
            $find['device_name'] = is_null($find['device_name']) ? $find['device_model'] : $find['device_name'];
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $find->save($params);
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function remove($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            // 删除关联的账号
            SvAccount::where('device_code', $find->device_code)->where('user_id', self::$uid)->select()->each(function ($account) {
                // 删除AI设置
                SvSetting::where('account', $account->account)->select()->delete();
                // 删除好友
                SvAccountContact::where('account', $account->account)->select()->delete();

                $account->delete();
            });
            //删除设备rpa配置
            SvDeviceRpa::where('device_code', $find->device_code)->select()->delete();

            // 删除设备任务
            SvDeviceTask::where('device_code', $find->device_code)->select()->delete();
            // 删除设备接管任务
            SvDeviceTakeOverTask::where('id', 'in', function ($query) use ($find) {
                $query->name('sv_device_take_over_task_account')->field('take_over_id')->where('device_code', $find->device_code);
            })->select()->delete();
            // 删除设备接管任务账号
            SvDeviceTakeOverTaskAccount::where('device_code', $find->device_code)->select()->delete();

            // 删除设备激活任务
            SvDeviceActive::where('id', 'in', function ($query) use ($find) {
                $query->name('sv_device_active_account')->field('active_id')->where('device_code', $find->device_code);
            })->select()->delete();
            // 删除设备激活任务账号
            SvDeviceActiveAccount::where('device_code', $find->device_code)->select()->delete();


            $find->delete();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function bind($params): bool
    {
        try {
            $device = SvDevice::where([
                'device_code' => $params['device_code'],
            ])->findOrEmpty();
            if (!$device->isEmpty()) {
                throw new \Exception('设备已被其他用户绑定');
            }
            $personalDevice = SvDevice::where([
                'device_code' => $params['device_code'],
                'user_id'     => $params['user_id'],
            ])->findOrEmpty();
            if (!$personalDevice->isEmpty()) {
                self::$returnData = ['message' => '设备已绑定此用户'];
            }else{
                $insert = [
                    'device_code'  => $params['device_code'],
                    'user_id'      => $params['user_id'],
                    'device_name'  => $params['device_code'],
                    'device_model' => $params['device_model'],
                    'sdk_version'  => $params['sdk_version'],
                    'status'       => 0,
                    'create_time'  => time(),
                ];
                SvDevice::create($insert);
                self::$returnData = ['message' => '绑定成功'];
            }

            $device_bind_num = SvDevice::where('user_id', $params['user_id'])->count();
            User::update(
                [
                    'device_bind_num'  => $device_bind_num,
                    'device_bind_time' => time()
                ],
                ['id' => $params['user_id']]
            );
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
