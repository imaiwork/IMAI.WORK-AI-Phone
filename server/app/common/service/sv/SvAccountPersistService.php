<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;
use think\facade\Db;

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
        return SvAccountBindService::isDuplicateEntry($e);
    }

    public static function formatOccupiedMessage(string $account, string $occupiedDeviceCode): string
    {
        return SvAccountBindService::formatOccupiedMessage($account, $occupiedDeviceCode);
    }

    /**
     * 按 (type, account) upsert，占用他设备则失败，不先删后插。
     */
    public static function upsertFromRpa(int $userId, array $postData): bool
    {
        self::clearLastError();

        if ($userId <= 0) {
            self::setLastError('账号落库跳过:userId无效');
            return false;
        }

        $account = trim((string)($postData['account'] ?? ''));
        $deviceCode = trim((string)($postData['device_code'] ?? ''));
        $type = (int)($postData['type'] ?? 0);

        if ($account === '' || $deviceCode === '' || $type <= 0) {
            self::setLastError('账号落库跳过:账号数据不完整');
            return false;
        }

        $occupied = SvAccountBindService::findOccupiedRow($type, $account, $deviceCode);
        if ($occupied !== null) {
            $occupiedDevice = trim((string)($occupied['device_code'] ?? ''));
            self::setLastError(self::formatOccupiedMessage($account, $occupiedDevice));
            SvAccountChangeLog::record(SvAccountChangeLog::SOURCE_RPA, SvAccountChangeLog::ACTION_CONFLICT, [
                'user_id' => $userId,
                'device_code' => $deviceCode,
                'type' => $type,
                'account' => $account,
                'account_id' => (int)($occupied['id'] ?? 0),
                'before_json' => SvAccountChangeLog::snapshot($occupied),
                'msg' => self::$lastError,
            ]);
            return false;
        }

        $extra = $postData['extra'] ?? [];
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?: [];
        }
        if (!is_array($extra)) {
            $extra = [];
        }

        $payload = [
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

        Db::startTrans();
        try {
            $existing = SvAccount::where('type', $type)->where('account', $account)->findOrEmpty();
            if ($existing->isEmpty()) {
                $created = SvAccount::create($payload);
                SvAccountChangeLog::record(SvAccountChangeLog::SOURCE_RPA, SvAccountChangeLog::ACTION_INSERT, [
                    'user_id' => $userId,
                    'device_code' => $deviceCode,
                    'type' => $type,
                    'account' => $account,
                    'account_id' => (int)($created->id ?? 0),
                    'after_json' => SvAccountChangeLog::snapshot(array_merge($payload, ['id' => (int)($created->id ?? 0)])),
                    'msg' => '新增账号',
                ]);
            } else {
                $before = $existing->toArray();
                $changed = self::diffPersistFields($before, $payload);
                if ($changed !== []) {
                    SvAccount::where('id', (int)$existing->id)->update($payload);
                    SvAccountChangeLog::record(SvAccountChangeLog::SOURCE_RPA, SvAccountChangeLog::ACTION_UPDATE, [
                        'user_id' => $userId,
                        'device_code' => $deviceCode,
                        'type' => $type,
                        'account' => $account,
                        'account_id' => (int)$existing->id,
                        'changed_fields' => $changed,
                        'before_json' => SvAccountChangeLog::snapshot($before),
                        'after_json' => SvAccountChangeLog::snapshot(array_merge($before, $payload)),
                        'msg' => '更新账号资料',
                    ]);
                }
            }

            $setting = SvSetting::where('account', $account)->findOrEmpty();
            if ($setting->isEmpty()) {
                SvSetting::create([
                    'takeover_type' => 1,
                    'account'       => $account,
                    'user_id'       => $userId,
                ]);
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            if (self::isDuplicateEntry($e)) {
                $occupiedDevice = SvAccountBindService::findOccupiedDeviceCode($type, $account, $deviceCode);
                self::setLastError(self::formatOccupiedMessage($account, $occupiedDevice));
                SvAccountChangeLog::record(SvAccountChangeLog::SOURCE_RPA, SvAccountChangeLog::ACTION_CONFLICT, [
                    'user_id' => $userId,
                    'device_code' => $deviceCode,
                    'type' => $type,
                    'account' => $account,
                    'msg' => self::$lastError,
                ]);
            } else {
                self::setLastError('账号落库失败:' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private static function diffPersistFields(array $existing, array $payload): array
    {
        $changed = [];
        foreach (['user_id', 'status', 'type', 'is_verified'] as $intField) {
            if ((int)($existing[$intField] ?? 0) !== (int)$payload[$intField]) {
                $changed[] = $intField;
            }
        }
        foreach (['device_code', 'account', 'account_no', 'nickname', 'avatar'] as $strField) {
            if ((string)($existing[$strField] ?? '') !== (string)$payload[$strField]) {
                $changed[] = $strField;
            }
        }

        $existingExtra = $existing['extra'] ?? '';
        if (is_array($existingExtra)) {
            $existingExtra = json_encode($existingExtra, JSON_UNESCAPED_UNICODE);
        }
        if ((string)$existingExtra !== (string)$payload['extra']) {
            $changed[] = 'extra';
        }

        return $changed;
    }
}
