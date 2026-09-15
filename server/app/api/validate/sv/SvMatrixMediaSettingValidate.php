<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 矩阵媒体设置校验
 * Class SvMatrixMediaSettingValidate
 * @package app\api\validate\sv
 */
class SvMatrixMediaSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'name' => 'require|max:50',
        'media_type' => 'require|in:1,2',
        'media_count' => 'number|between:1,100',
        'media_url' => 'max:65535',
        'copywriting' => 'max:65535',
        'extra' => 'max:65535',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
        'name.require' => '请输入名称',
        'name.max' => '名称长度不能超过50个字符',
        'media_type.require' => '请输入媒体类型',
        'media_type.in' => '媒体类型只能是1(视频)或2(图片)',
        'media_count.number' => '媒体数量必须是数字',
        'media_count.between' => '媒体数量必须在1-100之间',
        'media_url.max' => '媒体URL长度不能超过65535个字符',
        'copywriting.max' => '文案长度不能超过65535个字符',
        'extra.max' => '附加字段长度不能超过65535个字符',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['media_type', 'media_count', 'media_url', 'copywriting', 'extra']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'media_type', 'media_count', 'media_url', 'copywriting', 'extra']);
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
