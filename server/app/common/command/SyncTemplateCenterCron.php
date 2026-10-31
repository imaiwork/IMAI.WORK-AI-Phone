<?php

namespace app\common\command;

use app\common\service\PlayVideoTemplateSyncService;
use app\common\service\ShanjianClipTemplateSyncService;
use app\common\service\SystemMusicSyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 模板中心同步（全站唯一入口，每 5 分钟）：模板中心 → 本站镜像表。
 *
 * 2026-10 把原先三条独立任务合并成一条：
 *   - 剪辑模板 `la_shanjian_clip_template`：每轮都同步（有内容指纹，稳态几乎零 IO）；
 *   - 公共音乐库 `la_system_music`：每轮都同步（纯文本字段比对，开销可忽略）；
 *   - 爆款玩法模板 `la_play_video_template`：**任务内按小时闸门**，每小时才真正跑一次。
 *     这类模板要转存视频/封面且没有内容指纹，跟着 5 分钟跑等于一天 288 轮素材探活，
 *     与合并前的节奏保持一致（不是漏了，是有意压频）。
 *
 * 三块互不阻塞：某一块抛错只记日志，其余继续跑，避免一个接口抖动拖住全部同步。
 */
class SyncTemplateCenterCron extends Command
{
    private const RUNNING_LOCK_KEY = 'sync_template_center:running';
    private const RUNNING_LOCK_TTL = 1800;

    /** 玩法模板上次成功同步的小时槽（YmdH），用来把重素材同步压到每小时一次 */
    private const PLAY_TEMPLATE_HOUR_KEY = 'sync_template_center:play_hour';
    private const PLAY_TEMPLATE_HOUR_TTL = 7200;

    protected function configure()
    {
        $this->setName('sync_template_center')
            ->setDescription('同步模板中心（剪辑模板/公共音乐库每5分钟，玩法模板每小时）');
    }

    protected function execute(Input $input, Output $output)
    {
        $lockValue = (getmypid() ?: 0) . ':' . time();
        $lockAcquired = false;

        try {
            $lockAcquired = $this->acquireRunningLock($lockValue);
            if (!$lockAcquired) {
                $message = '同步模板中心正在执行，跳过本轮';
                $output->writeln($message);
                Log::channel('crontab')->info($message);
                return true;
            }

            $synced = $this->syncClipTemplates($output);
            $synced = $this->syncSystemMusic($output) && $synced;

            // 玩法模板每轮先判断小时槽，未到点直接跳过（不打远端、不探活素材）
            if ($this->playTemplatesDue()) {
                if ($this->syncPlayTemplates($output)) {
                    $this->markPlayTemplatesSynced();
                } else {
                    $synced = false;
                }
            }

            return $synced;
        } catch (\Throwable $e) {
            $message = '同步模板中心失败: ' . $e->getMessage();
            Log::channel('crontab')->error($message);
            $output->writeln($message);
            return false;
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($lockValue);
            }
        }
    }

    /** 剪辑模板：5 分钟一轮，useCache=false 直接打远端，避免被 Redis 缓存吃掉整个周期 */
    private function syncClipTemplates(Output $output): bool
    {
        try {
            $result = ShanjianClipTemplateSyncService::syncFromPlatform(false);
            $partialFailed = ($result['failed'] ?? 0) > 0;
            $message = ($partialFailed ? '同步剪辑模板部分失败(已跳过失败条目): ' : '同步剪辑模板成功: ')
                . json_encode($result, JSON_UNESCAPED_UNICODE);
            $output->writeln($message);
            if ($partialFailed) {
                Log::channel('crontab')->error($message);
            } else {
                Log::channel('crontab')->info($message);
            }
            return true;
        } catch (\Throwable $e) {
            $message = '同步剪辑模板失败: ' . $e->getMessage();
            $output->writeln($message);
            Log::channel('crontab')->error($message);
            return false;
        }
    }

    /** 公共音乐库：5 分钟一轮，内容未变不写库 */
    private function syncSystemMusic(Output $output): bool
    {
        try {
            $result = SystemMusicSyncService::syncFromTemplateCenter(false);
            $message = '同步系统曲库成功: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $output->writeln($message);
            Log::channel('crontab')->info($message);
            return true;
        } catch (\Throwable $e) {
            $message = '同步系统曲库失败: ' . $e->getMessage();
            $output->writeln($message);
            Log::channel('crontab')->error($message);
            return false;
        }
    }

    /** 爆款玩法模板：含视频/封面转存，按小时跑 */
    private function syncPlayTemplates(Output $output): bool
    {
        try {
            $result = PlayVideoTemplateSyncService::syncFromTemplateCenter(false);
            $partialFailed = ($result['failed'] ?? 0) > 0;
            $message = ($partialFailed ? '同步玩法模板部分失败(已跳过失败条目): ' : '同步玩法模板成功: ')
                . json_encode($result, JSON_UNESCAPED_UNICODE);
            $output->writeln($message);
            if ($partialFailed) {
                Log::channel('crontab')->error($message);
            } else {
                Log::channel('crontab')->info($message);
            }
            return true;
        } catch (\Throwable $e) {
            $message = '同步玩法模板失败: ' . $e->getMessage();
            $output->writeln($message);
            Log::channel('crontab')->error($message);
            return false;
        }
    }

    /**
     * 本轮是否到了玩法模板的同步点。
     * 缓存不可用时返回 true（退化成每轮都跑），同步功能不能因为缓存故障停摆。
     */
    private function playTemplatesDue(): bool
    {
        try {
            return (string)Cache::store('redis')->get(self::PLAY_TEMPLATE_HOUR_KEY) !== date('YmdH');
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('读取玩法模板同步小时槽失败: ' . $e->getMessage());
            return true;
        }
    }

    /** 只在真正跑成功后才落小时槽，失败留给 5 分钟后的下一轮重试 */
    private function markPlayTemplatesSynced(): void
    {
        try {
            Cache::store('redis')->set(self::PLAY_TEMPLATE_HOUR_KEY, date('YmdH'), self::PLAY_TEMPLATE_HOUR_TTL);
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('写入玩法模板同步小时槽失败: ' . $e->getMessage());
        }
    }

    private function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx(self::RUNNING_LOCK_KEY, $lockValue)) {
                return false;
            }

            $redis->expire(self::RUNNING_LOCK_KEY, self::RUNNING_LOCK_TTL);
            return true;
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('同步模板中心获取运行锁失败: ' . $e->getMessage());
            return false;
        }
    }

    private function releaseRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            if ($redis->get(self::RUNNING_LOCK_KEY) === $lockValue) {
                $redis->del(self::RUNNING_LOCK_KEY);
            }
        } catch (\Throwable $e) {
            Log::channel('crontab')->error('同步模板中心释放运行锁失败: ' . $e->getMessage());
        }
    }
}
