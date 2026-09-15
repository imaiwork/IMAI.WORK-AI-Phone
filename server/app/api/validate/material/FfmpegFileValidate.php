<?php

namespace app\api\validate\material;

use app\common\validate\BaseValidate;

class FfmpegFileValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'file_id' => 'require|number',
        'user_id' => 'number',
        'type' => 'in:10,20',
        'status' => 'in:0,1,2,3',
        'name' => 'max:255',
        'tries' => 'number|egt:0',
        'remark' => 'max:500',
        'uri' => 'require|max:200',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'file_id.require' => '文件ID是必填项',
        'file_id.number' => '文件ID必须是数字',
        'type.in' => '类型只能是10（图片）或20（视频）',
        'status.in' => '状态只能是0-3',
        'name.max' => '文件名称不能超过255个字符',
        'tries.number' => '尝试次数必须是数字',
        'tries.egt' => '尝试次数不能小于0',
        'remark.max' => '错误原因不能超过500个字符',
        'uri.require' => '文件路径是必填项',
        'uri.max' => '文件路径不能超过200个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['uri']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'uri']);
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