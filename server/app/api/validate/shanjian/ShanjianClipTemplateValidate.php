<?php

namespace app\api\validate\shanjian;

use app\common\validate\BaseValidate;

class ShanjianClipTemplateValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'number',
        'name' => 'require|max:128',
        'cover_url' => 'require|max:255',
        'scene' => 'require|max:32',
        'demo_url' => 'require|max:255',
    ];

    protected $message = [
        'name.require' => '模板名称是必填项',
        'name.max' => '模板名称长度不能超过128',
        'cover_url.require' => '封面图URL是必填项',
        'cover_url.max' => '封面图URL长度不能超过255',
        'scene.require' => '场景标识是必填项',
        'scene.max' => '场景标识长度不能超过32',
        'demo_url.require' => '演示视频URL是必填项',
        'demo_url.max' => '演示视频URL长度不能超过255',
    ];

    public function sceneLists()
    {
        return $this->only(['name', 'scene']);
    }
}
