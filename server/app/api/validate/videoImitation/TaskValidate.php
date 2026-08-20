<?php

namespace app\api\validate\videoImitation;

use app\common\validate\BaseValidate;

/**
 * 视频复刻任务生成校验
 */
class TaskValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'rewritten_text' => 'require',
        'publish_text' => 'require',
        'publish_topic' => 'require',
        'image_indexes' => 'require|array',
    ];

    protected $message = [
        'id.require' => '主键任务ID不能为空',
        'id.integer' => '任务ID必须为整数',
        'id.gt' => '任务ID必须大于0',
        'rewritten_text.require' => '仿写文案不能为空',
        'publish_text.require' => '发布文案不能为空',
        'image_indexes.require' => '请选择要改写的图片',
        'image_indexes.array' => '图片下标必须为数组',
    ];

    public function sceneGenerate()
    {
        return $this->only(['id', 'rewritten_text']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmPublishText()
    {
        return $this->only(['id', 'publish_text', 'publish_topic']);
    }

    public function sceneRegenerateCopywriting()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmImageRewrite()
    {
        return $this->only(['id', 'image_indexes']);
    }
}
