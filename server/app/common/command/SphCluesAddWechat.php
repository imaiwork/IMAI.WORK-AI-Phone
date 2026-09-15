<?php


namespace app\common\command;

use app\api\logic\sv\CrawlingTaskLogic;
use app\api\logic\sv\SvAddWechatRecordLogic;
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
        //CrawlingTaskLogic::sphCluesAddWechat();   
        print_r("\n 微信线索获取意图...'\n");
        SvAddWechatRecordLogic::acquiringIntent();
        return true;
    }
}
