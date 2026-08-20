<?php

namespace app\common\model\team;

use app\common\model\BaseModel;
use app\common\model\user\User;
use think\model\concern\SoftDelete;

/**
 * 团队模型
 * Class Team
 * @package app\common\model\team
 */
class Team extends BaseModel
{
    use SoftDelete;

    protected $name = 'team';
    protected $deleteTime = 'delete_time';

    /**
     * 有效团队:delete_time 为 NULL 或 0 都视为未删除。
     */
    protected function withNoTrashed($query): void
    {
        $field = $this->getDeleteTimeField(true);
        if ($field) {
            $query->where(function ($q) use ($field) {
                $q->whereNull($field)->whereOr($field, 0);
            });
        }
    }

    /**
     * @notes 团队主
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
}
