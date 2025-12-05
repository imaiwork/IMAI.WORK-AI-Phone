<?php

namespace app\api\validate\material;

use app\common\validate\BaseValidate;

class MusicValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'style' => 'in:0,1,2,3,4,5,6',
        'status' => 'in:0,1',
        'name' => 'max:255',
        'url' => 'require|max:200',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'style.in' => '风格值只能是0到6',
        'status.in' => '是否显示只能是0或者1',
        'name.max' => '文件名称不能超过255个字符',
        'url.require' => '文件路径是必填项',
        'url.max' => '文件路径不能超过200个字符',
    ];

    public function sceneAdd()
    {
        return $this->only([ 'name', 'url', 'status']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id','name', 'url']);
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
