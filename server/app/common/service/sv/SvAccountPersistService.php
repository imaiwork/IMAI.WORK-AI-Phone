<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;
use think\facade\Db;
use think\facade\Log;

/**
 * RPA 回传账号落库（不依赖请求态 uid）
 */
class SvAccountPersistService
{
    public const PERSIST_RESULT_TTL = 180;

    private static string $lastError = '';

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function setLastError(string $error): void
    {
        self::$lastError = $error;
    }

    private static function clearLastError(): void
    {
        self::$lastError = '';
    }

    public static function persistResultKey(string $deviceId, int $appType): string
    {
        return "xhs:{$deviceId}:{$appType}:accountPersist";
    }

    public static function isDuplicateEntry(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'uk_type_account')
            || str_contains($message, 'SQLSTATE[23000]')
            || str_contains($message, 'Duplicate entry');
    }

    public static function formatOccupiedMessage(string $account, string $occupiedDeviceCode): string
    {
        $account = trim($account);
        $occupiedDeviceCode = trim($occupiedDeviceCode);
        if ($occupiedDeviceCode === '') {
            return "账号{$account}已被其他设备绑定";
        }
        return "账号{$account}已被设备{$occupiedDeviceCode}绑定";
    }

    /**
     * 按 userId upsert 平台账号（同设备同类型先删后插，对齐 addSvAccount）
     */
    public static function upsertFromRpa(int $userId, array $postData): bool
    {
        self::clearLastError();

        if ($userId <= 0) {
            self::setLastError('账号落库跳过:userId无效');
            Log::channel('device')->write(self::$lastError, 'account');
            return false;
        }

        $account = trim((string)($postData['account'] ?? ''));
        $deviceCode = trim((string)($postData['device_code'] ?? ''));
        $type = (int)($postData['type'] ?? 0);

        if ($account === '' || $deviceCode === '' || $type <= 0) {
            self::setLastError('账号落库跳过:账号数据不完整');
            Log::channel('device')->write(self::$lastError, 'account');
            return false;
        }

        $occupiedDevice = self::findOccupiedDeviceCode($type, $account, $deviceCode);
        if ($occupiedDevice !== '') {
            self::setLastError(self::formatOccupiedMessage($account, $occupiedDevice));
            Log::channel('device')->write(self::$lastError, 'account');
            return false;
        }

        Db::startTrans();
        try {
            SvAccount::where('device_code', $deviceCode)->where('type', $type)->select()->delete();

            $extra = $postData['extra'] ?? [];
            if (is_string($extra)) {
                $extra = json_decode($extra, true) ?: [];
            }
            if (!is_array($extra)) {
                $extra = [];
            }

            $row = [
                'user_id'     => $userId,
                'device_code' => $deviceCode,
                'account'     => $account,
                'account_no'  => (string)($postData['account_no'] ?? $account),
                'nickname'    => (string)($postData['nickname'] ?? ''),
                'avatar'      => (string)($postData['avatar'] ?? ''),
                'status'      => (int)($postData['status'] ?? 1),
                'type'        => $type,
                'is_verified' => (int)($extra['is_verified'] ?? $postData['is_verified'] ?? 0),
                'extra'       => json_encode($extra, JSON_UNESCAPED_UNICODE),
            ];

            $created = SvAccount::create($row);

            $setting = SvSetting::where('account', $account)->findOrEmpty();
            if ($setting->isEmpty()) {
                SvSetting::create([
                    'takeover_type' => 1,
                    'account'       => $account,
                    'user_id'       => $userId,
                ]);
            }

            Db::commit();
            Log::channel('device')->write(
                "账号落库成功:device={$deviceCode},type={$type},account={$account},id={$created->id}",
                'account'
            );
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            if (self::isDuplicateEntry($e)) {
                $occupiedDevice = self::findOccupiedDeviceCode($type, $account, $deviceCode);
                self::setLastError(self::formatOccupiedMessage($account, $occupiedDevice));
            } else {
                self::setLastError('账号落库失败:' . $e->getMessage());
            }
            Log::channel('device')->write(self::$lastError, 'account');
            return false;
        }
    }

    private static function findOccupiedDeviceCode(int $type, string $account, string $currentDevice): string
    {
        $row = SvAccount::where('type', $type)->where('account', $account)->findOrEmpty();
        if ($row->isEmpty()) {
            return '';
        }
        $code = trim((string)($row->device_code ?? ''));
        if ($code === '' || $code === $currentDevice) {
            return '';
        }
        return $code;
    }
}
