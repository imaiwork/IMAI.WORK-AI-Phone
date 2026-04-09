<?php


namespace app\common\command;

use app\api\logic\shanjian\PublishLogic as ShanjianPublishLogic;
use app\api\logic\sora\PublishLogic as SoraPublishLogic;
use app\api\logic\storyboard\PublishLogic as StoryboardPublishLogic;
use app\api\logic\aiPersona\PublishLogic as aiPersonaPublishLogic;
use app\api\logic\sv\PublishLogic;
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
        print_r("\n ip人设待发布表任务0...'\n");
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
    }
}
