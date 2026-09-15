<?php

namespace app\api\validate\shanjian;

use app\common\validate\BaseValidate;

class VoiceValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'name' => 'require|max:50',
        //'model' => 'require|max:50',
        'audio_url' => 'require|max:255',
        //'language' => 'require|in:zh-CN,en-US',
        'demo_text' => 'require|max:500',
    ];

    protected $message = [
        'name.require' => '名称是必填项',
        'model.require' => '模型是必填项',
        'audio_url.require' => '音频URL是必填项',
        'language.require' => '语言是必填项',
        'demo_text.max' => '演示文本最多500个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['audio_url']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id','audio_url']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}


