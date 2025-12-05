<?php


namespace app\common\model\kb;

use app\common\enum\user\UserEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 机器人分享模型
 */
class KbRobotShareLog extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';


    public function getChannelDescAttr($value,$data)
    {
        return UserTerminalEnum::getTerminalDesc($data['channel']);
    }
}