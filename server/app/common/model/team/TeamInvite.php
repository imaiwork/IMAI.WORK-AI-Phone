<?php

namespace app\common\model\team;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 团队邀请模型
 * Class TeamInvite
 * @package app\common\model\team
 */
class TeamInvite extends BaseModel
{
    use SoftDelete;

    protected $name = 'team_invite';
    protected $deleteTime = 'delete_time';
}
