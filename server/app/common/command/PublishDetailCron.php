<?php


namespace app\common\command;

use app\api\logic\shanjian\PublishLogic as ShanjianPublishLogic;
use app\api\logic\sora\PublishLogic as SoraPublishLogic;
use app\api\logic\storyboard\PublishLogic as StoryboardPublishLogic;
use app\api\logic\aiPersona\PublishLogic as aiPersonaPublishLogic;
use app\api\logic\sv\PublishLogic;
use app\common\command\ViralImageRewriteCron;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * 知识库文件状态更新
 */
class PublishDetailCron extends Command
{
    private const RUNNING_LOCK_KEY = 'publish_detail_cron:running';
    private const RUNNING_LOCK_TTL = 900;

    protected function configure()
    {
        $this->setName('publish_detail_cron')
            ->setDescription('拉取新生成的视频图文信息写入待发布表');
    }

    protected function execute(Input $input, Output $output)
    {
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        $lockAcquired = false;

        try {
            if (!$this->acquireRunningLock($lockValue)) {
                return true;
            }
            $lockAcquired = true;

            print_r("\n ip人设待发布表任务0...'\n");
            ViralImageRewriteCron::runOnce($output);
            print_r("\n ip人设图文待发布表任务0.5...'\n");
            aiPersonaPublishLogic::shanjianPersonaPublishCron();
            print_r("\n 待发布表任务1...'\n");
            PublishLogic::setPublishDetail();
            print_r("\n 待发布表任务2...'\n");
            //DevicePublishLogic::setPublishDetail(); 弃用
            print_r("\n 待发布表任务3...'\n");
            ShanjianPublishLogic::setPublishDetail();
            print_r("\n 待发布表任务4...'\n");
            SoraPublishLogic::setPublishDetail();
            print_r("\n 待发布表任务5...'\n");
            StoryboardPublishLogic::setPublishDetail();
            return true;
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($lockValue);
            }
        }
    }

    private function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            if (!$redis->setnx(self::RUNNING_LOCK_KEY, $lockValue)) {
                Log::channel('auto')->write('PublishDetailCron正在执行，跳过并发触发：' . date('Y-m-d H:i:s'), 'create');
                return false;
            }
            $redis->expire(self::RUNNING_LOCK_KEY, self::RUNNING_LOCK_TTL);
            return true;
        } catch (\Throwable $e) {
            Log::channel('auto')->write('PublishDetailCron获取运行锁失败，跳过执行：' . $e->getMessage(), 'create');
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
            Log::channel('auto')->write('PublishDetailCron释放运行锁失败：' . $e->getMessage(), 'create');
        }
    }
}
