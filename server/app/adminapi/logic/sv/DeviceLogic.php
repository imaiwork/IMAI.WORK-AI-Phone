<?php

namespace app\adminapi\logic\sv;

use app\api\logic\DeviceAuthLogic;
use app\api\logic\sv\DeviceLogic as SvDeviceLogic;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\logic\BaseLogic;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceUsed;
use app\common\model\sv\SvSetting;
use app\common\model\user\User;
use app\common\service\device\RpaDeviceDispatchService;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;
use app\common\service\MemberService;
use think\facade\Db;
use think\facade\Log;

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
        Db::startTrans();
        try {
            $device = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();

            if ($device->isEmpty()) {
                Db::rollback();
                self::setError('设备不存在');
                return false;
            }
            $deviceCode = (string)$device->device_code;
            $userId = (int)($device->user_id ?? 0);
            $deviceId = (int)$device->id;
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

            \app\common\model\sv\SvDeviceTask::where('device_code', $device->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceActiveConfig::where('device_code', $device->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceAddWechatConfig::where('device_code', $device->device_code)->select()->delete();
            // 删除设备线索词配置
            \app\common\model\auto\AutoDeviceClueConfig::where('device_code', $device->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceConfig::where('device_code', $device->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceSetting::where('device_code', $device->device_code)->select()->delete();
            // 删除设备接管任务配置
            \app\common\model\auto\AutoDeviceTakeOverConfig::where('device_code', $device->device_code)->select()->delete();
            // 删除设备截流获客任务配置
            \app\common\model\auto\AutoDeviceTouchConfig::where('device_code', $device->device_code)->select()->delete();
            // 删除设备点赞评论任务配置
            \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('device_code', $device->device_code)->select()->delete();
            // 删除设备点赞评论任务账号
            \app\common\model\auto\AutoDeviceWechatCircleConfig::where('device_code', $device->device_code)->select()->delete();

            \app\common\model\sv\SvDeviceTaskLog::where('device_code', $device->device_code)->select()->delete();
            SvDeviceUsed::deleteByDevice($userId, $deviceCode, $deviceId);
            $device->delete();

            Db::commit();
            RpaDeviceDispatchService::afterServerUnbind(
                $deviceCode,
                'admin_remove',
                ['user_id' => $userId]
            );

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 站长代兑换：仅允许使用设备所属用户未使用的 CDK（单事务）
     */
    public static function redeemCdk(array $params): bool
    {
        Db::startTrans();
        try {
            $device = SvDevice::where('id', (int)$params['device_id'])->lock(true)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            $userId = (int)$device->user_id;
            if ($userId <= 0) {
                throw new \Exception('设备未绑定用户');
            }

            $code = DeviceCdkCode::where('id', (int)$params['cdk_id'])->lock(true)->findOrEmpty();
            if ($code->isEmpty()) {
                throw new \Exception('设备CDK不存在');
            }
            if ((int)$code->status === DeviceAuthCodeEnum::STATUS_USED) {
                throw new \Exception('设备CDK已使用');
            }
            if ((int)$code->status === DeviceAuthCodeEnum::STATUS_DISABLED) {
                throw new \Exception('设备CDK已作废');
            }
            if ((int)$code->status !== DeviceAuthCodeEnum::STATUS_UNUSED) {
                throw new \Exception('仅未使用的设备CDK可兑换');
            }
            if ((int)$code->owner_user_id !== $userId) {
                throw new \Exception('仅可使用该用户的设备CDK');
            }

            self::$returnData = DeviceAuthLogic::applyCdkToDevice($code, $device, $userId);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 站长端设备转移：等价于用户端 remove(A) + bind(B)
     */
    public static function deviceTransfer(array $params): bool
    {
        Db::startTrans();
        try {
            $device = SvDevice::where('id', (int)$params['device_id'])->lock(true)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            $fromUserId = (int)$device->user_id;
            if ($fromUserId <= 0) {
                throw new \Exception('设备未绑定用户');
            }

            $toUserId = (int)$params['to_user_id'];
            $toUser = User::where('id', $toUserId)->findOrEmpty();
            if ($toUser->isEmpty()) {
                throw new \Exception('目标用户不存在');
            }
            if ($fromUserId === $toUserId) {
                throw new \Exception('目标用户不能与当前所属用户相同');
            }

            $deviceCode = (string)$device->device_code;
            $deviceModel = (string)($device->device_model ?? '');
            $sdkVersion = (string)($device->sdk_version ?? '');

            self::removeDeviceForUser($deviceCode, $fromUserId);
            self::bindDeviceForUser([
                'device_code'  => $deviceCode,
                'user_id'      => $toUserId,
                'device_model' => $deviceModel,
                'sdk_version'  => $sdkVersion,
            ]);

            Db::commit();
            // 转移保留手机本地 device_code，仅清理可能残留的待解绑标记，不下发解绑指令
            RpaDeviceDispatchService::clearUnbindState($deviceCode);
            RpaDeviceDispatchService::logUnbind('unbind_transfer_skip', [
                'device_code' => $deviceCode,
                'user_id' => $toUserId,
                'reason' => 'device_transfer',
                'msg' => '设备转移完成，已跳过 1212 下发',
                'result' => 'skip',
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
            ]);
            self::$returnData = [
                'device_code'   => $deviceCode,
                'from_user_id' => $fromUserId,
                'to_user_id'   => $toUserId,
            ];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * copy from api DeviceLogic::remove（无独立事务，user_id 显式传入）
     */
    private static function removeDeviceForUser(string $deviceCode, int $userId): array
    {
        $find = SvDevice::field('*')
            ->where('device_code', $deviceCode)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            throw new \Exception('设备不存在');
        }

        Log::channel('device')->write(
            '站长转移-删除设备：' . json_encode($find->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'deviceTransfer'
        );

        SvAccount::where('device_code', $find->device_code)->where('user_id', $userId)->select()->each(function ($account) {
            SvSetting::where('account', $account->account)->select()->delete();
            SvAccountContact::where('account', $account->account)->select()->delete();
            $account->delete();
        });

        SvDeviceRpa::where('device_code', $find->device_code)->select()->delete();
        SvDeviceTask::where('device_code', $find->device_code)->select()->delete();

        \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceSetting::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', $userId)->where('device_code', $find->device_code)->select()->delete();
        \app\common\model\sv\SvDeviceTaskLog::where('device_code', $find->device_code)->select()->delete();

        SvDeviceUsed::deleteByDevice($userId, $find->device_code, (int)$find->id);
        $data = $find->toArray();
        $find->delete();

        return $data;
    }

    /**
     * copy from api DeviceLogic::bind（无独立事务）
     */
    private static function bindDeviceForUser(array $params): array
    {
        Log::channel('device')->write(date('Y-m-d H:i:s') . ' 站长转移-设备绑定开始：' . time(), 'deviceTransfer');

        $deviceCode = trim((string)($params['device_code'] ?? ''));
        $userId = (int)($params['user_id'] ?? 0);
        if ($deviceCode === '' || $userId <= 0) {
            throw new \Exception('绑定参数错误');
        }

        $used = SvDeviceUsed::where([
            'device_code' => $deviceCode,
            'user_id'     => $userId,
        ])->lock(true)->findOrEmpty();
        if (!$used->isEmpty() && (int)$used->is_used === 1) {
            throw new \Exception('设备已被绑定');
        }

        $device = SvDevice::where('device_code', $deviceCode)->lock(true)->findOrEmpty();
        if (!$device->isEmpty() && (int)$device->user_id !== $userId) {
            throw new \Exception('设备已被其他用户绑定');
        }

        if (!$device->isEmpty()) {
            $device->save([
                'device_model' => $params['device_model'] ?? $device->device_model,
                'sdk_version'  => $params['sdk_version'] ?? $device->sdk_version,
                'update_time'  => time(),
            ]);
        } else {
            $existing = (int)SvDevice::where('user_id', $userId)->count();
            $reason = '';
            if (!MemberService::canBindDevice($userId, $existing, $reason)) {
                throw new \Exception($reason);
            }

            $insert = [
                'device_code'  => $deviceCode,
                'user_id'      => $userId,
                'device_name'  => $deviceCode,
                'device_model' => $params['device_model'] ?? '',
                'sdk_version'  => $params['sdk_version'] ?? '',
                'status'       => 0,
                'auto_type'    => 1,
                'is_first'     => 1,
                'create_time'  => time(),
            ];
            $device = SvDevice::create($insert);

            $res = SvDeviceLogic::applyMiddleDeviceAuthFields($params);
            if (isset($res['error'])) {
                throw new \Exception($res['error']);
            }
            if (($res['auth_status'] ?? 0) == 1) {
                $device->auth_status = $res['auth_status'];
                $device->auth_start_time = $res['auth_start_time'];
                $device->auth_expire_time = $res['auth_expire_time'];
                $device->save();
            }
        }

        SvDeviceUsed::saveRecord($userId, $deviceCode, (int)$device->id, 1);

        $deviceBindNum = SvDevice::where('user_id', $userId)->count();
        User::update(
            [
                'device_bind_num'       => $deviceBindNum,
                'device_bind_time'      => time(),
                'last_bind_device_code' => $deviceCode,
            ],
            ['id' => $userId]
        );

        RpaDeviceDispatchService::clearUnbindState($deviceCode);

        return ['message' => '绑定成功', 'device_id' => (int)$device->id];
    }
}
