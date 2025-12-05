<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 视频设置校验
 * Class SvMediaSettingValidate
 * @package app\api\validate\sv
 */
class SvMediaSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'media_type' => 'require|in:1,2',
        'type' => 'require|in:3',
        'media_url' => 'json',
        'title' => 'json',
        'subtitle' => 'json',
        'extra' => 'json',
      
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'type.require' => '请输入视频类型',
        'type.in' => '视频类型值不正确',
        'extra.require' => '请输入附加字段内容',
        'media_type.require' => '请输入媒体类型',
        'media_type.in' => '媒体类型不正确',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only([ 'name',  'type','media_type']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id','name', 'type']);
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