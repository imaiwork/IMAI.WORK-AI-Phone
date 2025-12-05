<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 账号关键词校验
 */
class AccountKeywordValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'account' => 'require',
        'match_type' => 'require',
        'keyword' => 'require',
        'reply' => 'require',
        'type' => 'require',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'account.require' => '请输入账号ID',
        'match_type.require' => '请输入匹配模式',
        'keyword.require' => '请输入关键词',
        'reply.require' => '请输入回复内容',
        'type.require' => '请输入账号类型',
    ];

    public function sceneAdd()
    {
        return $this->only(['match_type', 'keyword', 'reply', 'account', 'type']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'match_type', 'keyword', 'reply']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneImport()
    {
        return $this->only(['account','type']);
    }
}