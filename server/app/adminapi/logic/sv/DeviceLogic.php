<?php

namespace app\adminapi\logic\sv;

use app\common\logic\BaseLogic;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;
use app\common\model\sv\SvAccountContact;

/**
 * DeviceLogic
 * @desc 设备
 * @author Qasim
 */
class DeviceLogic extends BaseLogic
{

    /**
     * @desc 删除设备
     * @param array $params
     * @return bool
     */
    public static function removeDevice(array $params)
    {
        try {
            $device = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();

            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            // 删除关联的账号
            SvAccount::where('device_code', $device->device_code)->select()->each(function ($account) {
                // 删除AI设置
                SvSetting::where('account', $account->account)->select()->delete();
                // 删除好友
                SvAccountContact::where('account', $account->account)->select()->delete();

                $account->delete();
            });
            //删除设备rpa配置
            SvDeviceRpa::where('device_code', $params['device_code'])->select()->delete();
            $device->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
