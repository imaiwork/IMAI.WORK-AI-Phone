<?php


namespace app\common\command;

use app\api\logic\wechat\StrategyLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * AiCircleReplyLike
 * @desc 自动微信朋友圈点赞评论
 * @author dagouzi
 */
class AiCircleReplyLike extends Command
{
    protected function configure()
    {
        $this->setName('ai_circle_reply_like')
            ->setDescription('自动微信朋友圈点赞评论');
    }

    protected function execute(Input $input, Output $output)
    {
        StrategyLogic::execCircleReplyLikeStrategy();
        return true;
    }
}
