<?php

namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvDevice;
use app\common\service\device\RpaDeviceDispatchService;
use think\facade\Cache;
use think\facade\Log;

/**
 * 设备账号逻辑
 */
class AccountLogic extends ApiLogic
{
    /**
     * 触发 RPA 602 拉取平台账号（异步，回传后由服务端落库）
     */
    public static function fetch(array $params): bool
    {
        try {
            $deviceCode = trim((string)($params['device_code'] ?? ''));
            $type = (int)($params['type'] ?? 0);

            $device = SvDevice::where('device_code', $deviceCode)
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            if (!RpaDeviceDispatchService::isOnline($deviceCode)) {
                self::setError("设备{$deviceCode}不在线,无法获取账号信息");
                return false;
            }

            $redis = Cache::store('redis');
            $redis->select((int)env('redis.WS_SELECT', 8));
            $redis->set("xhs:getUser:{$deviceCode}", (string)self::$uid);

            $sent = RpaDeviceDispatchService::notifyGetUserInfo($deviceCode, $type);
            if (!$sent) {
                self::setError('下发获取账号指令失败');
                return false;
            }

            self::$returnData = [
                'device_code' => $deviceCode,
                'type'        => $type,
                'dispatched'  => true,
            ];
            return true;
        } catch (\Throwable $e) {
            Log::channel('device')->write('触发拉取平台账号失败:' . $e->getMessage(), 'account');
            self::setError($e->getMessage());
            return false;
        }
    }
}
