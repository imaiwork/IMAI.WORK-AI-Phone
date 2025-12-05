<?php


namespace app\common\command;

use app\api\logic\KnowledgeLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 知识库文件状态更新
 */
class FileChunksPullCron extends Command
{
    protected function configure()
    {
        $this->setName('file_chunks_pull_cron')
            ->setDescription('知识库文件切片拉取');
    }

    protected function execute(Input $input, Output $output)
    {
        KnowledgeLogic::fileChunksPull();
        return true;
    }
}
