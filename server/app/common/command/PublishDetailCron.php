<?php


namespace app\common\command;

use app\api\logic\sv\PublishLogic;
use app\api\logic\shanjian\PublishLogic as ShanjianPublishLogic;
use app\api\logic\device\PublishLogic as DevicePublishLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 知识库文件状态更新
 */
class PublishDetailCron extends Command
{
    protected function configure()
    {
        $this->setName('publish_detail_cron')
            ->setDescription('拉取新生成的视频图文信息写入待发布表');
    }

    protected function execute(Input $input, Output $output)
    {
        PublishLogic::setPublishDetail();
        DevicePublishLogic::setPublishDetail();
        ShanjianPublishLogic::setPublishDetail();
        return true;
    }
}
