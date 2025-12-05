<?php

namespace app\common\model\draw;

use app\common\model\BaseModel;
use app\common\model\user\UserTokensLog;
use think\model\concern\SoftDelete;

class DrawVideo extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function userTokensLog()
    {
        return $this->hasOne(UserTokensLog::class, 'task_id', 'task_id')
                    ->where('task_id', '<>', '');
    }
}