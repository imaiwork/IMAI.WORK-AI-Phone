<?php

namespace app\common\service\deviceauth;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\service\ConfigService;
use app\common\service\ToolsService;
use Exception;

class DeviceAuthCodeSyncService
{
    public static function syncFromPayload(array $codes): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach ($codes as $item) {
            if (!is_array($item)) {
                $stats['skipped']++;
                continue;
            }
            self::syncOne($item, $stats);
        }
        return $stats;
    }

    public static function pullFromPlatform(array $params = []): array
    {
        $request = [];
        if (!empty($params['updated_since'])) {
            $request['updated_since'] = (int)$params['updated_since'];
        }
        if (!empty($params['status'])) {
            $request['status'] = (int)$params['status'];
        }
        if (!empty($params['device_codes']) && is_array($params['device_codes'])) {
            $request['device_codes'] = array_values(array_filter(array_map('trim', $params['device_codes'])));
        }

        $response = ToolsService::DataCenter()->deviceCdkLists($request);
        if ((int)($response['code'] ?? 0) !== 10000) {
            throw new Exception($response['message'] ?? '中台设备CDK列表返回异常');
        }

        $list = $response['data']['list'] ?? [];
        if (!is_array($list)) {
            throw new Exception('中台设备CDK列表格式异常');
        }

        $normalized = [];
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized[] = self::normalizeRemoteItem($item);
        }

        $stats = self::syncFromPayload($normalized);
        if (empty($request['device_codes'])) {
            ConfigService::set('device_auth', 'last_sync_time', time());
        }
        return $stats;
    }

    private static function normalizeRemoteItem(array $item): array
    {
        $rawTypeCode = $item['type_code'] ?? $item['expire_type'] ?? 0;
        $typeCode = (int)$rawTypeCode;
        if ($typeCode <= 0 && $rawTypeCode !== null) {
            $typeCode = (int)preg_replace('/\D/', '', (string)$rawTypeCode);
        }

        $durationDays = (int)($item['expire_days'] ?? $item['duration_days'] ?? 0);
        $durationDays = ($durationDays < 0) ? 0 : $durationDays;
        $usedTime = isset($item['used_time']) && $item['used_time'] !== null ? (int)$item['used_time'] : 0;
        $boundDeviceCode = trim((string)($item['bound_device_code'] ?? $item['device_code'] ?? ''));

        return [
            'id'            => (int)($item['id'] ?? 0),
            'code'          => trim((string)($item['cdk_code'] ?? $item['code'] ?? '')),
            'type_code'     => $typeCode,
            'device_code'   => $boundDeviceCode,
            'order_no'      => trim((string)($item['order_no'] ?? '')),
            'status'        => (int)($item['status'] ?? $item['is_used'] ?? 0),
            'duration_days' => $durationDays,
            'created_time'  => (int)($item['created_time'] ?? $item['createtime'] ?? 0),
            'used_time'     => $usedTime,
            'project'       => trim((string)($item['project'] ?? '')),
            'batch_no'      => trim((string)($item['batch_no'] ?? '')),
            'remark'        => trim((string)($item['remark'] ?? '')),
        ];
    }

    private static function syncOne(array $item, array &$stats): void
    {
        $code = trim((string)($item['code'] ?? ''));
        $middleLicenseId = (int)($item['id'] ?? $item['middle_license_id'] ?? $item['middle_cdk_id'] ?? 0);
        if ($code === '') {
            $stats['skipped']++;
            return;
        }

        $middleStatus = (int)($item['status'] ?? 1);
        $localStatus = self::mapMiddleStatus($middleStatus);
        $type = (int)($item['type_code'] ?? $item['type'] ?? 0);
        if ($type < 1 || $type > 7) {
            $stats['skipped']++;
            return;
        }

        $durationDays = (int)($item['duration_days'] ?? 0);
        if ($type === DeviceAuthCodeEnum::TYPE_FOREVER) {
            $durationDays = 0;
        } elseif ($type === DeviceAuthCodeEnum::TYPE_CUSTOM && $durationDays <= 0) {
            $stats['skipped']++;
            return;
        }

        $now = time();
        $query = DeviceCdkCode::where(function ($q) use ($code, $middleLicenseId) {
            $q->where('code', $code);
            if ($middleLicenseId > 0) {
                $q->whereOr('middle_license_id', $middleLicenseId);
            }
        });
        $existing = $query->findOrEmpty();

        if (!$existing->isEmpty()) {
            if ($existing->status == DeviceAuthCodeEnum::STATUS_USED && $localStatus == DeviceAuthCodeEnum::STATUS_UNUSED) {
                $stats['skipped']++;
                return;
            }
            $changed = false;
            if ($middleLicenseId > 0 && (int)$existing->middle_license_id !== $middleLicenseId) {
                $existing->middle_license_id = $middleLicenseId;
                $changed = true;
            }
            $orderNo = trim((string)($item['order_no'] ?? ''));
            if ($orderNo !== '' && $existing->middle_order_no !== $orderNo) {
                $existing->middle_order_no = $orderNo;
                $changed = true;
            }
            $middleDeviceCode = trim((string)($item['device_code'] ?? ''));
            if ($middleDeviceCode !== '' && $existing->middle_device_code !== $middleDeviceCode) {
                $existing->middle_device_code = $middleDeviceCode;
                $changed = true;
            }
            if ((int)$existing->type !== $type) {
                $existing->type = $type;
                $changed = true;
            }
            if ($durationDays > 0 && (int)$existing->duration_days !== $durationDays) {
                $existing->duration_days = $durationDays;
                $changed = true;
            }
            if ((int)$existing->status !== $localStatus && $existing->status != DeviceAuthCodeEnum::STATUS_USED) {
                $existing->status = $localStatus;
                $changed = true;
            }
            if (!empty($item['used_time']) && (int)$existing->use_time !== (int)$item['used_time']) {
                $existing->use_time = (int)$item['used_time'];
                $changed = true;
            }
            if (!empty($item['device_code']) && trim((string)$existing->device_code) === '' && $localStatus === DeviceAuthCodeEnum::STATUS_USED) {
                $existing->device_code = trim((string)$item['device_code']);
                $changed = true;
            }
            if ($changed) {
                $existing->update_time = $now;
                $existing->save();
                $stats['updated']++;
            } else {
                $stats['skipped']++;
            }
            return;
        }

        DeviceCdkCode::create([
            'batch_id'           => 0,
            'code'               => $code,
            'type'               => $type,
            'duration_days'      => $durationDays,
            'status'             => $localStatus,
            'source'             => DeviceAuthCodeEnum::SOURCE_PLATFORM,
            'owner_user_id'      => 0,
            'user_id'            => 0,
            'device_code'        => $localStatus === DeviceAuthCodeEnum::STATUS_USED ? trim((string)($item['device_code'] ?? '')) : '',
            'use_time'           => (int)($item['used_time'] ?? 0),
            'auth_start_time'    => 0,
            'auth_expire_time'   => 0,
            'middle_license_id'  => $middleLicenseId,
            'middle_order_no'    => trim((string)($item['order_no'] ?? '')),
            'middle_device_code' => trim((string)($item['device_code'] ?? '')),
            'create_time'        => (int)($item['created_time'] ?? $now),
            'update_time'        => $now,
        ]);
        $stats['created']++;
    }

    public static function mapMiddleStatus(int $middleStatus): int
    {
        return match ($middleStatus) {
            1       => DeviceAuthCodeEnum::STATUS_UNUSED,
            2       => DeviceAuthCodeEnum::STATUS_USED,
            3, 4    => DeviceAuthCodeEnum::STATUS_DISABLED,
            default => DeviceAuthCodeEnum::STATUS_DISABLED,
        };
    }

    public static function bindSyncedCodeToDevice($device): bool
    {
        return false;
    }

    public static function assignOnPlatform(iterable $codes, int $stationUserId, int $purchasedTime): void
    {
        // 新 CDK 模式购买发放只在站长端分配归属用户，不再调用中台 assign。
        return;
    }

    public static function useOnPlatform(DeviceCdkCode $code, string $deviceCode, int $stationUserId): bool
    {
        if (!self::shouldSyncToPlatform($code)) {
            return true;
        }
        $response = ToolsService::DataCenter()->deviceCdkUse([
            'cdk_code'        => $code->code,
            'device_code'     => $deviceCode,
            'station_user_id' => $stationUserId,
        ]);
        $responseCode = (int)($response['code'] ?? 0);
        if ($responseCode === 10000) {
            return true;
        }
        $message = (string)($response['message'] ?? 'unknown');
        throw new Exception($message ?: '中台设备CDK使用失败');
    }

    public static function redeemOnPlatform(DeviceCdkCode $code, string $deviceCode, int $stationUserId): bool
    {
        if (!self::shouldSyncToPlatform($code)) {
            // #region agent log
            @file_put_contents(root_path().'debug-7bb140.log', json_encode(['sessionId'=>'7bb140','runId'=>'run1','hypothesisId'=>'C','location'=>'DeviceAuthCodeSyncService.php:redeemOnPlatform:skip','message'=>'shouldSyncToPlatform=false -> skip platform','data'=>['code_id'=>$code->id,'middle_license_id'=>(int)$code->middle_license_id,'middle_order_no'=>(string)$code->middle_order_no,'middle_device_code'=>(string)$code->middle_device_code],'timestamp'=>round(microtime(true)*1000)],JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
            // #endregion
            return true;
        }
        $reqParams = [
            'cdk_code'        => $code->code,
            'device_code'     => $deviceCode,
            'station_user_id' => $stationUserId,
            'domain'          => self::currentSiteDomain(),
        ];
        // #region agent log
        @file_put_contents(root_path().'debug-7bb140.log', json_encode(['sessionId'=>'7bb140','runId'=>'run1','hypothesisId'=>'B/D','location'=>'DeviceAuthCodeSyncService.php:redeemOnPlatform:beforeCall','message'=>'calling platform deviceCdkRedeem','data'=>['req'=>$reqParams],'timestamp'=>round(microtime(true)*1000)],JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
        // #endregion
        $response = ToolsService::DataCenter()->deviceCdkRedeem($reqParams);
        // #region agent log
        @file_put_contents(root_path().'debug-7bb140.log', json_encode(['sessionId'=>'7bb140','runId'=>'run1','hypothesisId'=>'A/B','location'=>'DeviceAuthCodeSyncService.php:redeemOnPlatform:response','message'=>'platform response','data'=>['resp_code'=>$response['code']??null,'resp_message'=>$response['message']??null],'timestamp'=>round(microtime(true)*1000)],JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
        // #endregion
        $responseCode = (int)($response['code'] ?? 0);
        if ($responseCode === 10000) {
            return true;
        }
        $message = (string)($response['message'] ?? 'unknown');
        throw new Exception($message ?: '中台设备CDK兑换失败');
    }

    private static function currentSiteDomain(): string
    {
        if (function_exists('ToolsGetCurrentSiteInfo')) {
            [$domain] = \ToolsGetCurrentSiteInfo();
            $domain = trim((string)$domain);
            if ($domain !== '') {
                return $domain;
            }
        }

        $host = trim((string)config('app.app_host', ''));
        $domain = parse_url($host, PHP_URL_HOST);
        if ($domain) {
            return $domain;
        }

        return $host !== '' ? trim($host, '/') : ($_SERVER['HTTP_HOST'] ?? '');
    }

    private static function shouldSyncToPlatform(DeviceCdkCode $code): bool
    {
        return (int)$code->middle_license_id > 0
            || trim((string)$code->middle_order_no) !== ''
            || trim((string)$code->middle_device_code) !== '';
    }

    /**
     * 一键双端同步：设备管理系统 → 中台，再增量拉取中台设备CDK。
     */
    public static function syncBothEnds(): array
    {
        $syncResponse = ToolsService::DataCenter()->deviceCdkSync([]);
        if ((int)($syncResponse['code'] ?? 0) !== 10000) {
            throw new Exception($syncResponse['message'] ?? '设备CDK导入中台失败');
        }
        $synced = $syncResponse['data'] ?? [];
        if (!is_array($synced)) {
            $synced = [];
        }

        $pull = self::pullFromPlatform([]);

        return [
            'cdk' => [
                'domain'  => (string)($synced['domain'] ?? ''),
                'pulled'  => (int)($synced['pulled'] ?? 0),
                'created' => (int)($synced['created'] ?? 0),
                'updated' => (int)($synced['updated'] ?? 0),
                'skipped' => (int)($synced['skipped'] ?? 0),
            ],
            'pull' => $pull,
        ];
    }
}
