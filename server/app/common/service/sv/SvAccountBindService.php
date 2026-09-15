<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvAccount;

/**
 * 账号占用预检：同一 (type, account) 只能绑一台设备。
 */
class SvAccountBindService
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findOccupiedRow(int $type, string $account, string $currentDevice): ?array
    {
        $account = trim($account);
        $currentDevice = trim($currentDevice);
        if ($type <= 0 || $account === '') {
            return null;
        }

        $row = SvAccount::where('type', $type)->where('account', $account)->findOrEmpty();
        if ($row->isEmpty()) {
            return null;
        }

        $code = trim((string)($row->device_code ?? ''));
        if ($code === '' || $code === $currentDevice) {
            return null;
        }

        return $row->toArray();
    }

    public static function findOccupiedDeviceCode(int $type, string $account, string $currentDevice): string
    {
        $row = self::findOccupiedRow($type, $account, $currentDevice);
        if ($row === null) {
            return '';
        }

        return trim((string)($row['device_code'] ?? ''));
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

    public static function formatSetAccountOccupiedMessage(string $account, string $occupiedDeviceCode): string
    {
        return '账号' . trim($account) . '已绑定在设备：' . trim($occupiedDeviceCode) . '，请先解绑后重试';
    }

    public static function isDuplicateEntry(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'uk_type_account')
            || str_contains($message, 'SQLSTATE[23000]')
            || str_contains($message, 'Duplicate entry');
    }
}
