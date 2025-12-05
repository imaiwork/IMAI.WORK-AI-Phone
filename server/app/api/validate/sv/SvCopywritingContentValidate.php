<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 文案内容校验
 * Class SvCopywritingContentValidate
 * @package app\api\validate\sv
 */
class SvCopywritingContentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'copywriting_id' => 'require',
        'type' => 'require|in:1,2,3',
        'content' => 'require',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'copywriting_id.require' => '请输入文案ID',
        'type.require' => '请输入类型',
        'content.require' => '请输入内容',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['copywriting_id', 'type', 'content']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'content']);
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
}