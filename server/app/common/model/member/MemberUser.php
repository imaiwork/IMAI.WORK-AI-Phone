<?php

namespace app\common\model\member;

use app\common\model\BaseModel;
use app\common\model\user\UserLevel;
use think\model\concern\SoftDelete;

/**
 * 用户会员记录(每用户一条)
 */
class MemberUser extends BaseModel
{
    use SoftDelete;

    protected $name = 'member_user';
    protected $deleteTime = 'delete_time';

    const STATUS_ACTIVE   = 1;
    const STATUS_EXPIRED  = 2;
    const STATUS_CANCELED = 3;

    const SOURCE_ADMIN    = 1;
    const SOURCE_CARDCODE = 2;
    const SOURCE_PAY      = 3;

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'id');
    }
}
