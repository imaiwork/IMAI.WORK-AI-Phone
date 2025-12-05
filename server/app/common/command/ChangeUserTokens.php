<?php


namespace app\common\command;

use app\api\logic\HdLogic;
use app\common\logic\PaymentLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * SunoStatus
 * @desc 扣除用户过期算力
 * @author dagouzi
 */
class ChangeUserTokens extends Command
{
    protected function configure()
    {
        $this->setName('change_user_tokens')
            ->setDescription('扣除用户未使用过的算力');
    }

    protected function execute(Input $input, Output $output)
    {
        return PaymentLogic::ChangeUserTokens();
    }
}
