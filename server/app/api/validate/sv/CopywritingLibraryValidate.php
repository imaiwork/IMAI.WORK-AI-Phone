<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 文案库校验
 * Class CopywritingLibraryValidate
 */
class CopywritingLibraryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'type' => 'require|in:0,3',
        'copywriting_type' => 'require|in:1,2,3',
        'title' => 'json',
        'described' => 'json',
        'oral_copy' => 'json',
        'extra' => 'json',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'type.require' => '请输入平台类型',
        'type.in' => '平台类型值不正确',
        'copywriting_type.require' => '请输入文案类型',
        'copywriting_type.in' => '文案类型值不正确',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['type', 'copywriting_type']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id']);
    }

    // 详情场景
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }


    public function sceneAddAi()
    {
        return $this->only(['type', 'copywriting_type']);
    }
} 