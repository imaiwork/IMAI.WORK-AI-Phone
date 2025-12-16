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
        CrawlingTaskLogic::verifyWeChatCron();
        return true;
    }
}
