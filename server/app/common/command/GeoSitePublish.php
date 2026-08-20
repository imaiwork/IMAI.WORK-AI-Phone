<?php

namespace app\common\command;

use app\common\service\geo\GeoSiteService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * AI官网SEO 定时发布:遍历进行中的定时任务,按每日配额把 GEO 内容发到用户站点。
 * 建议 cron 每小时执行:php think geo_site_publish
 */
class GeoSitePublish extends Command
{
    protected function configure()
    {
        $this->setName('geo_site_publish')
            ->setDescription('GEO 官网SEO 定时发布');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $summary = GeoSiteService::runDue();
            $msg = sprintf('GEO官网发布: 处理任务%d 发布%d 失败%d', $summary['tasks'], $summary['published'], $summary['failed']);
            $output->writeln($msg);
            Log::info($msg);
        } catch (\Throwable $e) {
            $output->writeln('GEO官网发布异常: ' . $e->getMessage());
            Log::error('geo_site_publish: ' . $e->getMessage());
        }
        return 0;
    }
}
