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

            if (!self::isRpaDeviceOnline($deviceCode)) {
                self::setError("设备{$deviceCode}不在线,无法获取账号信息");
                return false;
            }

            self::rememberGetUserCaller($deviceCode);

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

    /**
     * 按 Workerman 原始键读取在线状态（不拼 Cache prefix，兼容 PHP 序列化值）。
     * 只看 status，与设备列表 / GUI 口径一致。
     */
    private static function isRpaDeviceOnline(string $deviceCode): bool
    {
        $deviceCode = trim($deviceCode);
        if ($deviceCode === '') {
            return false;
        }

        try {
            $handler = Cache::store('redis')->handler();
            $prevDb = self::currentRedisDb($handler);
            try {
                $handler->select((int)env('redis.WS_SELECT', 8));
                $status = $handler->get("xhs:device:{$deviceCode}:status");
            } finally {
                $handler->select($prevDb);
            }

            return self::decodeDeviceStatus($status) === 'online';
        } catch (\Throwable $e) {
            Log::channel('device')->write('检查设备在线状态失败:' . $e->getMessage(), 'account');
            return false;
        }
    }

    /**
     * 无 prefix 写入调用方 uid，供 Workerman UserHandler 读取。
     */
    private static function rememberGetUserCaller(string $deviceCode): void
    {
        $handler = Cache::store('redis')->handler();
        $prevDb = self::currentRedisDb($handler);
        try {
            $handler->select((int)env('redis.WS_SELECT', 8));
            $handler->set("xhs:getUser:{$deviceCode}", (string)self::$uid);
        } finally {
            $handler->select($prevDb);
        }
    }

    private static function currentRedisDb(object $handler): int
    {
        return method_exists($handler, 'getDbNum')
            ? (int)$handler->getDbNum()
            : (int)env('CACHE.SELECT', 2);
    }

    /**
     * 兼容裸字符串与 PHP 序列化（如 s:6:"online";）。
     */
    private static function decodeDeviceStatus(mixed $status)
    {
        if (!is_string($status)) {
            return $status;
        }

        if ($status === 'online') {
            return $status;
        }

        $decoded = @unserialize($status, ['allowed_classes' => false]);
        if ($decoded !== false || $status === 'b:0;') {
            return $decoded;
        }

        return $status;
    }
}
