<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\api\logic\aiPersona\PublishLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceViralRecord;
use think\facade\Cache;
use think\facade\Log;

/**
 * 按当天空闲图文发布时段，从跨天未使用仿写库存（id ASC）填坑生成发布记录。
 */
class ViralImageTextPublishFillService
{
    private const LOCK_PREFIX = 'viral_image_text_publish_fill:';
    private const LOCK_TTL = 120;
    private const MAX_PER_GROUP = 50;

    /**
     * @return array{groups:int,created:int,failed:int,skipped:int,errors:list<string>}
     */
    public static function runOnce(?string $targetPublishDay = null): array
    {
        $targetPublishDay = PublishLogic::normalizeTargetPublishDay($targetPublishDay);
        $result = [
            'groups' => 0,
            'created' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $groups = self::listUnusedInventoryGroups();
        $result['groups'] = count($groups);

        foreach ($groups as $group) {
            $groupResult = self::fillGroup(
                (int)$group['user_id'],
                (string)$group['device_code'],
                (int)$group['persona_id'],
                $targetPublishDay
            );
            $result['created'] += $groupResult['created'];
            $result['failed'] += $groupResult['failed'];
            $result['skipped'] += $groupResult['skipped'];
            if ($groupResult['error'] !== '') {
                $result['errors'][] = $groupResult['error'];
            }
        }

        return $result;
    }

    /**
     * 为指定设备/人设填坑（兜底与定时共用）。
     *
     * @return array{created:int,failed:int,skipped:int,error:string}
     */
    public static function fillGroup(
        int $userId,
        string $deviceCode,
        int $personaId,
        ?string $targetPublishDay = null
    ): array {
        $targetPublishDay = PublishLogic::normalizeTargetPublishDay($targetPublishDay);
        $result = [
            'created' => 0,
            'failed' => 0,
            'skipped' => 0,
            'error' => '',
        ];

        if ($userId <= 0 || $deviceCode === '' || $personaId <= 0) {
            $result['skipped']++;
            $result['error'] = '填坑参数无效';
            return $result;
        }

        $lockKey = self::LOCK_PREFIX . $userId . ':' . $deviceCode . ':' . $personaId . ':' . $targetPublishDay;
        $lockValue = (string)(getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireLock($lockKey, $lockValue)) {
            $result['skipped']++;
            $result['error'] = $deviceCode . '图文发布填坑处理中，跳过';
            return $result;
        }

        try {
            $persona = AiPersona::where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                $result['skipped']++;
                $result['error'] = $deviceCode . 'IP人设不存在';
                return $result;
            }

            $device = SvDevice::where('device_code', $deviceCode)
                ->where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->findOrEmpty();
            if ($device->isEmpty()) {
                $result['skipped']++;
                $result['error'] = $deviceCode . '设备绑定不存在';
                return $result;
            }

            $platform = AiPersona::PUBLISH_PLATFORM_XHS;
            for ($i = 0; $i < self::MAX_PER_GROUP; $i++) {
                if (!PublishLogic::hasAvailableImageTextPublishSlot(
                    $device,
                    $persona,
                    $targetPublishDay,
                    $platform
                )) {
                    break;
                }

                $record = self::claimNextUnusedRecord($userId, $deviceCode, $personaId);
                if ($record === null) {
                    break;
                }

                $ok = PublishLogic::createImageTextPublishFromViralRecord($record, $targetPublishDay);
                if ($ok) {
                    $result['created']++;
                    self::log(
                        "图文发布填坑成功: record_id={$record->id}, device={$deviceCode}, day={$targetPublishDay}"
                    );
                    continue;
                }

                $result['failed']++;
                $error = (string)($record->publish_create_error ?? '未知错误');
                self::log(
                    "图文发布填坑失败: record_id={$record->id}, device={$deviceCode}, day={$targetPublishDay}, error={$error}"
                );

                // 无可用时段时停止本组，避免连刷失败污染后续库存
                if (mb_strpos($error, '无可用发布时段') !== false) {
                    break;
                }
            }

            return $result;
        } catch (\Throwable $th) {
            $result['failed']++;
            $result['error'] = $deviceCode . '图文发布填坑异常：' . $th->getMessage();
            self::log($result['error'] . "\n" . $th->getTraceAsString());
            return $result;
        } finally {
            self::releaseLock($lockKey, $lockValue);
        }
    }

    /**
     * @return list<array{user_id:int,device_code:string,persona_id:int}>
     */
    public static function listUnusedInventoryGroups(): array
    {
        $rows = self::unusedQuery()
            ->field('vr.user_id,vr.device_code,vr.persona_id')
            ->group('vr.user_id,vr.device_code,vr.persona_id')
            ->select()
            ->toArray();

        $groups = [];
        foreach ($rows as $row) {
            $groups[] = [
                'user_id' => (int)($row['user_id'] ?? 0),
                'device_code' => (string)($row['device_code'] ?? ''),
                'persona_id' => (int)($row['persona_id'] ?? 0),
            ];
        }

        return $groups;
    }

    /**
     * 跨天未使用库存，严格 id 升序。
     *
     * @return list<SvDeviceViralRecord>
     */
    public static function listUnusedRecords(
        int $userId,
        string $deviceCode,
        int $personaId,
        int $limit = 0
    ): array {
        $query = self::unusedQuery()
            ->where('vr.user_id', $userId)
            ->where('vr.device_code', $deviceCode)
            ->where('vr.persona_id', $personaId)
            ->order('vr.id', 'asc');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $list = [];
        foreach ($query->select() as $record) {
            if ($record instanceof SvDeviceViralRecord) {
                $list[] = $record;
            }
        }

        return $list;
    }

    private static function claimNextUnusedRecord(
        int $userId,
        string $deviceCode,
        int $personaId
    ): ?SvDeviceViralRecord {
        $record = self::unusedQuery()
            ->where('vr.user_id', $userId)
            ->where('vr.device_code', $deviceCode)
            ->where('vr.persona_id', $personaId)
            ->order('vr.id', 'asc')
            ->findOrEmpty();

        return $record->isEmpty() ? null : $record;
    }

    private static function unusedQuery()
    {
        return SvDeviceViralRecord::alias('vr')
            ->where('vr.publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
            ->where('vr.publish_platform', AiPersona::PUBLISH_PLATFORM_XHS)
            ->where('vr.image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS)
            ->where('vr.publish_detail_id', 0)
            ->where('vr.use_time', 0)
            ->where('vr.status', 4)
            ->where('vr.is_interested', 1)
            // 排除设备绑定已失效的孤儿库存（填坑前过滤，不作废记录）
            ->whereExists(function ($query) {
                $query->name('sv_device')
                    ->alias('d')
                    ->whereColumn('d.device_code', 'vr.device_code')
                    ->whereColumn('d.user_id', 'vr.user_id')
                    ->whereColumn('d.persona_id', 'vr.persona_id');
            });
    }

    private static function acquireLock(string $lockKey, string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            return (bool)$redis->set($lockKey, $lockValue, ['nx', 'ex' => self::LOCK_TTL]);
        } catch (\Throwable $th) {
            self::log('图文发布填坑获取锁失败：' . $th->getMessage());
            return false;
        }
    }

    private static function releaseLock(string $lockKey, string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
return 0
LUA;
            $redis->eval($script, [$lockKey, $lockValue], 1);
        } catch (\Throwable $th) {
            self::log('图文发布填坑释放锁失败：' . $th->getMessage());
        }
    }

    private static function log(string $content): void
    {
        Log::channel('auto')->write($content, 'create');
    }
}
