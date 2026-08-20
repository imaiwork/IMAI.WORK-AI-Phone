<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class MaterialSliceValidate extends BaseValidate
{
    protected $rule = [
        'persona_id' => 'require|number|gt:0',
        'scene' => 'require|in:ai_creation,persona',
        'file_id' => 'require|number|gt:0',
        'batch_no' => 'require|max:64',
    ];

    protected $message = [
        'persona_id.require' => '人设ID不能为空',
        'persona_id.number' => '人设ID必须为数字',
        'persona_id.gt' => '人设ID必须大于0',
        'scene.require' => '素材库场景不能为空',
        'scene.in' => '素材库场景仅支持ai_creation或persona',
        'file_id.require' => '请选择已上传的视频',
        'file_id.number' => '视频ID必须为数字',
        'file_id.gt' => '视频ID必须大于0',
        'batch_no.require' => '批次号不能为空',
        'batch_no.max' => '批次号长度不能超过64个字符',
    ];

    public function sceneQuote(): self
    {
        return $this->only(['persona_id', 'scene', 'file_id']);
    }

    public function sceneConfirm(): self
    {
        return $this->only(['persona_id', 'scene', 'file_id']);
    }

    public function sceneBatchDetail(): self
    {
        return $this->only(['batch_no']);
    }
}
