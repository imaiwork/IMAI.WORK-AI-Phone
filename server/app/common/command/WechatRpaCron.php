<?php


namespace app\common\command;

use app\api\logic\device\WechatLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * WechatRpaCron
 * @desc 验证微信rpa任务
 * @author dagouzi
 */
class WechatRpaCron extends Command
{
    protected function configure()
    {
        $this->setName('wechat_rpa_cron')
            ->setDescription('个微RPA接管空闲时段任务');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n个微RPA接管空闲时段任务1...'\n");
        WechatLogic::wechatRpaCron();
        return true;
    }
}
