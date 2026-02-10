<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 媒体手动设置校验
 * Class SvMediaManualSettingValidate
 * @package app\api\validate\sv
 */
class SvMediaManualSettingValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'user_id' => 'require',
        'media_url' => 'json',
        'copywriting' => 'json',
        'extra' => 'json',
    ];

    protected $message = [
        'id.require' => '请输入主键ID',
        'user_id.require' => '请输入用户ID',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only(['media_url', 'copywriting', 'extra']);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'media_url', 'copywriting', 'extra', 'media_count']);
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