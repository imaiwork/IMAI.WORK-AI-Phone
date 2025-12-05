<?php


namespace app\common\command;

use app\api\logic\sv\PublishLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 知识库文件状态更新
 */
class NotePublishCron extends Command
{
    protected function configure()
    {
        $this->setName('note_publish_cron')
            ->setDescription('定时推送笔记');
    }

    protected function execute(Input $input, Output $output)
    {
        //PublishLogic::aiNotePushCron(0);
        return true;
    }
}
