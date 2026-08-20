<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 团队校验
 * Class TeamValidate
 * @package app\api\validate
 */
class TeamValidate extends BaseValidate
{
    protected $rule = [
        'name' => 'require|max:100',
        'code' => 'require|max:32',
        'max_uses' => 'integer|egt:0',
        'expire_time' => 'integer|egt:0',
        'user_id' => 'require|integer|gt:0',
        'tokens' => 'require|float|gt:0',
        'expire' => 'integer|egt:0',
    ];

    protected $message = [
        'name.require' => '请输入团队名称',
        'name.max' => '团队名称最长100字符',
        'code.require' => '请输入邀请码',
    ];

    public function sceneCreate()
    {
        return $this->only(['name']);
    }

    public function sceneInvite()
    {
        return $this->only(['max_uses', 'expire_time']);
    }

    public function sceneJoin()
    {
        return $this->only(['code']);
    }

    public function sceneAllocate()
    {
        return $this->only(['user_id', 'tokens']);
    }

    public function sceneSetExpire()
    {
        return $this->only(['user_id', 'expire']);
    }

    public function sceneRemoveMember()
    {
        return $this->only(['user_id']);
    }
}
