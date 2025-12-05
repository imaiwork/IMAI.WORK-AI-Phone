<?php


namespace app\common\command;

use app\api\logic\sv\CrawlingTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SphCluesAddWechat
 * @desc 线索加微
 * @author dagouzi
 */
class SphCluesAddWechat extends Command
{
    protected function configure()
    {
        $this->setName('sph_clues_add_wechat')
            ->setDescription('线索加微');
    }

    protected function execute(Input $input, Output $output)
    {
        CrawlingTaskLogic::sphCluesAddWechat();
        return true;
    }
}
