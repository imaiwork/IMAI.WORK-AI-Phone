<?php

namespace app\validate;

use app\common\validate\BaseValidate;

class VideoSliceValidate extends BaseValidate
{
    protected $rule = [
        'video_id' => 'require|number',
        'module' => 'require',
        'file_path' => 'require',
        'original_name' => 'require',
        'persona_id' => 'number',
        'user_id' => 'number',
    ];

    protected $message = [
        'video_id.require' => '视频ID不能为空',
        'video_id.number' => '视频ID必须为数字',
        'module.require' => '模块不能为空',
        'file_path.require' => '视频文件路径不能为空',
        'original_name.require' => '原始文件名不能为空',
        'persona_id.number' => '人设ID必须为数字',
        'user_id.number' => '用户ID必须为数字',
    ];

    public function sceneUploaded()
    {
        return $this->only(['video_id', 'module', 'file_path', 'original_name', 'persona_id', 'user_id']);
    }
}
