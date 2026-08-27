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
        'generation_type' => 'require|integer|in:1,2,3',
        'avatar_id' => 'integer|min:0',
        'voice_id' => 'integer|min:0',
    ];

    protected $message = [
        'id.require' => '主键任务ID不能为空',
        'id.integer' => '任务ID必须为整数',
        'id.gt' => '任务ID必须大于0',
        'rewritten_text.require' => '仿写文案不能为空',
        'publish_text.require' => '发布文案不能为空',
        'image_indexes.require' => '请选择要改写的图片',
        'image_indexes.array' => '图片下标必须为数组',
        'generation_type.require' => '请选择视频类型',
        'generation_type.in' => '视频类型仅支持数字人口播、素材口播或新闻体',
        'avatar_id.integer' => '形象ID格式不正确',
        'voice_id.integer' => '音色ID格式不正确',
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

    public function sceneGenerationOptions()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmRewrittenText()
    {
        return $this->only(['id']);
    }

    public function sceneConfirmGenerationOptions()
    {
        return $this->only(['id', 'generation_type', 'avatar_id', 'voice_id']);
    }
}
