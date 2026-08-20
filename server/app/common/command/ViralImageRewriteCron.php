<?php

namespace app\common\command;

use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\sv\SvDeviceViral;
use app\common\model\sv\SvDeviceViralRecord;
use app\common\service\aiPersona\ViralImageRewriteService;
use app\common\service\aiPersona\ViralKeywordService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

class ViralImageRewriteCron extends Command
{
    private const RUNNING_LOCK_KEY = 'viral_image_rewrite_cron:running';
    private const RUNNING_LOCK_TTL = 1800;
    private const BATCH_SIZE = 3;
    private const RECOVER_BATCH_SIZE = 5;

    protected function configure()
    {
        $this->setName('viral_image_rewrite_cron')
            ->setDescription('爆款图文图片改写状态轮询并同步文案（不立刻生成发布记录）');
    }

    protected function execute(Input $input, Output $output)
    {
        return self::runOnce($output);
    }

    public static function runOnce(?Output $output = null): bool
    {
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        if (!self::acquireRunningLock($lockValue)) {
            return true;
        }

        try {
            $recovered = 0;
            $handled = 0;

            // 阶段 A：仅回收超时 PROCESSING，本轮不立刻重提，避免与仍存活的旧 submit 叠跑
            $processingRecords = SvDeviceViralRecord::where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
                ->where('status', 4)
                ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_PROCESSING)
                ->order('id', 'asc')
                ->limit(self::RECOVER_BATCH_SIZE)
                ->select();

            foreach ($processingRecords as $record) {
                if (ViralImageRewriteService::recoverExpired($record)) {
                    $recovered++;
                }
            }

            // 阶段 B：只捞 WAIT 执行 submit，避免 PROCESSING 占满批次导致新任务饿死
            $waitingRecords = SvDeviceViralRecord::where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
                ->where('status', 4)
                ->where('image_rewrite_status', SvDeviceViralRecord::IMAGE_REWRITE_STATUS_WAIT)
                ->order('id', 'asc')
                ->limit(self::BATCH_SIZE)
                ->select();

            $onHeartbeat = static function () use ($lockValue): void {
                self::renewRunningLock($lockValue);
            };

            foreach ($waitingRecords as $record) {
                ViralImageRewriteService::sync($record, $onHeartbeat);
                $record = self::reloadRecord((int)$record->id);

                if (!$record->isEmpty()
                    && self::canSyncCopywriting(
                        (int)$record->publish_media_type,
                        (int)$record->image_rewrite_status
                    )
                ) {
                    self::finalizeSuccess($record);
                }
                self::renewRunningLock($lockValue);
                $handled++;
            }

            if ($output) {
                $output->writeln('爆款图文图片改写已回收: ' . $recovered . '，已提交: ' . $handled);
            }
            return true;
        } finally {
            self::releaseRunningLock($lockValue);
        }
    }

    private static function reloadRecord(int $recordId): SvDeviceViralRecord
    {
        return SvDeviceViralRecord::where('id', $recordId)->findOrEmpty();
    }

    /**
     * 图生图成功后仅同步文案 + 可选消耗关键词，不立刻生成发布记录。
     */
    private static function finalizeSuccess(SvDeviceViralRecord $record): bool
    {
        if (!self::syncCopywriting($record)) {
            return false;
        }

        if ((int)$record->keyword_consumed_at > 0) {
            return true;
        }

        // 手动入库 viral_id=0：跳过关键词消耗，不视为失败
        if ((int)$record->viral_id <= 0) {
            return true;
        }

        $persona = AiPersona::where('id', (int)$record->persona_id)->findOrEmpty();
        $task = SvDeviceViral::where('id', (int)$record->viral_id)->findOrEmpty();
        if ($persona->isEmpty() || $task->isEmpty()) {
            // 人设/任务缺失时只记日志，文案已同步，不阻断库存入池
            Log::channel('auto')->write(
                '图片改写成功后跳过关键词消耗 record_id=' . (int)$record->id
                . ' reason=' . ($persona->isEmpty() ? 'IP人设不存在' : '爆款任务不存在'),
                'img2'
            );
            return true;
        }

        return self::consumeKeywordWithLock($record);
    }

    public static function canSyncCopywriting(int $publishMediaType, int $imageRewriteStatus): bool
    {
        return $publishMediaType === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT
            && $imageRewriteStatus === SvDeviceViralRecord::IMAGE_REWRITE_STATUS_SUCCESS;
    }

    private static function syncCopywriting(SvDeviceViralRecord $record): bool
    {
        if (!self::canSyncCopywriting(
            (int)$record->publish_media_type,
            (int)$record->image_rewrite_status
        )) {
            return false;
        }

        try {
            $copywriting = AiPersonaSynthesisCopywriting::where(
                'sv_device_viral_record_id',
                (int)$record->id
            )
                ->where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
                ->findOrEmpty();
            if (!$copywriting->isEmpty()) {
                return true;
            }

            $copywritingContent = $record->copywriting;
            AiPersonaSynthesisCopywriting::create([
                'user_id' => (int)$record->user_id,
                'device_code' => (string)$record->device_code,
                'persona_id' => (int)$record->persona_id,
                'sv_device_viral_record_id' => (int)$record->id,
                'publish_media_type' => AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT,
                'copywriting' => is_string($copywritingContent)
                    ? $copywritingContent
                    : json_encode($copywritingContent ?: [], JSON_UNESCAPED_UNICODE),
                'status' => AiPersonaSynthesisCopywriting::STATUS_SUCCESS,
                'use_state' => AiPersonaSynthesisCopywriting::USE_STATE_UNUSED,
                'day' => (string)$record->day,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return true;
        } catch (\Throwable $e) {
            $record->publish_create_error = '图文文案同步失败：' . $e->getMessage();
            $record->update_time = time();
            $record->save();
            Log::channel('auto')->write(
                '图片改写成功后同步文案失败 record_id=' . (int)$record->id . ' error=' . $e->getMessage(),
                'img2'
            );
            return false;
        }
    }

    private static function consumeKeywordWithLock(SvDeviceViralRecord $record): bool
    {
        $lockKey = self::RUNNING_LOCK_KEY . ':keyword:' . $record->persona_id . ':' . $record->viral_id;
        return self::runWithLock($lockKey, function () use ($record) {
            $record = self::reloadRecord((int)$record->id);
            if ($record->isEmpty()) {
                return false;
            }
            if ((int)$record->keyword_consumed_at > 0) {
                return true;
            }
            if ((int)$record->viral_id <= 0) {
                return true;
            }

            $persona = AiPersona::where('id', (int)$record->persona_id)->findOrEmpty();
            $task = SvDeviceViral::where('id', (int)$record->viral_id)->findOrEmpty();
            if ($persona->isEmpty() || $task->isEmpty()) {
                return true;
            }

            ViralKeywordService::consumeOnSuccess($persona, $task, (string)$record->keyword);
            return SvDeviceViralRecord::where('id', (int)$record->id)
                    ->where('keyword_consumed_at', 0)
                    ->update([
                        'keyword_consumed_at' => time(),
                        'update_time' => time(),
                    ]) > 0;
        });
    }

    private static function runWithLock(string $lockKey, callable $callback): bool
    {
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        try {
            if (!self::acquireLock($lockKey, $lockValue, self::RUNNING_LOCK_TTL)) {
                return false;
            }
        } catch (\Throwable $e) {
            Log::channel('auto')->write('图片改写收尾任务获取锁失败：' . $e->getMessage(), 'img2');
            return false;
        }

        try {
            return (bool)$callback();
        } finally {
            try {
                self::releaseLock($lockKey, $lockValue);
            } catch (\Throwable $e) {
                Log::channel('auto')->write('图片改写收尾任务释放锁失败：' . $e->getMessage(), 'img2');
            }
        }
    }

    private static function acquireRunningLock(string $lockValue): bool
    {
        try {
            return self::acquireLock(self::RUNNING_LOCK_KEY, $lockValue, self::RUNNING_LOCK_TTL);
        } catch (\Throwable $e) {
            Log::channel('auto')->write('图片改写任务获取运行锁失败：' . $e->getMessage(), 'img2');
            return false;
        }
    }

    private static function renewRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('expire', KEYS[1], ARGV[2])
end
return 0
LUA;
            $redis->eval($script, [self::RUNNING_LOCK_KEY, $lockValue, self::RUNNING_LOCK_TTL], 1);
        } catch (\Throwable $e) {
            Log::channel('auto')->write('图片改写任务续期运行锁失败：' . $e->getMessage(), 'img2');
        }
    }

    private static function releaseRunningLock(string $lockValue): void
    {
        try {
            self::releaseLock(self::RUNNING_LOCK_KEY, $lockValue);
        } catch (\Throwable $e) {
            Log::channel('auto')->write('图片改写任务释放运行锁失败：' . $e->getMessage(), 'img2');
        }
    }

    private static function acquireLock(string $lockKey, string $lockValue, int $ttl): bool
    {
        $redis = Cache::store('redis')->handler();
        return (bool)$redis->set($lockKey, $lockValue, ['nx', 'ex' => $ttl]);
    }

    private static function releaseLock(string $lockKey, string $lockValue): void
    {
        $redis = Cache::store('redis')->handler();
        $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
return 0
LUA;
        $redis->eval($script, [$lockKey, $lockValue], 1);
    }
}
