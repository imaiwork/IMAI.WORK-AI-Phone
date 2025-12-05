<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 文案校验
 * Class SvCopywritingValidate
 * @package app\api\validate\sv
 */
class SvCopywritingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'status' => 'require|in:0,4',
        'type' => 'require|in:1,2,3',
        'add_type' => 'require|in:0,1,2,3',
        'keyword' => 'require|max:600',
        'name' => 'require|max:255',
        'total_num' => 'require|integer',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'status.require' => '请输入任务状态',
        'status.in' => '输入任务状态不对',
        'type.require' => '请输入平台类型',
        'add_type.require' => '请输入新增类型',
        'add_type.in' => '输入新增类型不对',
        'keyword.require' => '请输入关键词',
        'keyword.max' => '输入关键词最长600',
        'name.require' => '请输入文案',
        'name.max' => '输入文案名称最长255',
        'total_num.require' => '请输入数量',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['type']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id',  'name']);
    }

    // 更新场景
    public function sceneDetail()
    {
        return $this->only(['id']);
    }


    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneAddname()
    {
        return $this->only(['type']);
    }
}