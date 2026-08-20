<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * GEO 投稿回执与效果回收同步:AI 手机投稿回执回填 + 投稿互动数据回收。
 * (媒体代发已下线,原发稿平台回执段已移除)
 * 由后台「定时任务」调度(建议每 30 分钟):php think geo_publish_sync
 * GEO 中台密钥未配置时效果回收空转,无副作用。
 */
class GeoPublishSync extends Command
{
    protected function configure()
    {
        $this->setName('geo_publish_sync')
            ->setDescription('GEO 投稿回执与效果回收同步');
    }

    protected function execute(Input $input, Output $output)
    {
        // AI 手机投稿的回执:读设备侧 sv_publish_setting_detail 的执行结果,
        // 回填 geo_publish 的状态与链接。
        try {
            $n = \app\common\service\geo\GeoPhonePublishService::syncBack();
            if ($n > 0) {
                $msg = "GEO AI手机投稿回执: 回填{$n}条";
                $output->writeln($msg);
                Log::info($msg);
            }
        } catch (\Throwable $e) {
            $output->writeln('GEO AI手机回执异常: ' . $e->getMessage());
            Log::error('geo_publish_sync(phone): ' . $e->getMessage());
        }
        // 投稿效果回收:按 published_url 反查各平台互动数据,
        // 让台账从"发了"变成"发了有效果"。中台密钥未配置时空转。
        try {
            $t = \app\common\service\geo\GeoPublishStatsService::syncStats();
            if ($t['scanned'] > 0) {
                $msg = sprintf(
                    'GEO投稿效果回收: 扫描%d 成功%d 失败%d 跳过%d%s',
                    $t['scanned'], $t['ok'], $t['failed'], $t['skipped'],
                    // 触顶要显式说出来,否则"只处理了200条"会被误读成"只有200条"
                    $t['capped'] ? ' (已达单轮上限,剩余下轮继续)' : ''
                );
                $output->writeln($msg);
                Log::info($msg);
            }
        } catch (\Throwable $e) {
            $output->writeln('GEO投稿效果回收异常: ' . $e->getMessage());
            Log::error('geo_publish_sync(stats): ' . $e->getMessage());
        }
        return 0;
    }
}
