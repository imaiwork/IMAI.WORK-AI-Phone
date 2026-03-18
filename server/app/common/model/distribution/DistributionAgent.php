<?php

namespace app\common\model\distribution;

use app\common\model\BaseModel;
use app\common\model\user\User;

/**
 * 分销代理模型
 * Class DistributionAgent
 * @package app\common\model\distribution
 */
class DistributionAgent extends BaseModel
{
    protected $name = 'distribution_agent';
    protected $type = [
        'become_time' => 'timestamp',
    ];

    // 关联上级用户
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    // 关联本用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
