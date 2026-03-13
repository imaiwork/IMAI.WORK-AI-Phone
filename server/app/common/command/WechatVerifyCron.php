<?php


namespace app\common\command;

use app\api\logic\sv\CrawlingTaskLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * WechatVerifyCron
 * @desc 验证微信用户
 * @author dagouzi
 */
class WechatVerifyCron extends Command
{
    protected function configure()
    {
        $this->setName('wechat_verify')
            ->setDescription('验证微信用户');
    }

    protected function execute(Input $input, Output $output)
    {
        print_r("\n验证微信用户任务1...'\n");
        CrawlingTaskLogic::verifyWeChatCron();
        return true;
    }
}
