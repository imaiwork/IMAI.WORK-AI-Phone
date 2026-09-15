<?php

namespace app\api\validate\videoImitation;

use app\common\validate\BaseValidate;

/**
 * 视频复刻发布设置校验
 */
class PublishValidate extends BaseValidate
{
    protected $rule = [
        'name' => 'require',
        'video_ids' => 'require|array',
        'accounts' =>  'require|array',
        'publish_frep' => 'require|integer',
        'time_config' => 'require|array',
    ];

    protected $message = [
        'name.require' => '请输入任务名称',
        'video_ids.require' => '请选择要发布的视频',
        'video_ids.array' => '视频格式错误',
        'accounts.require' => '请选择账号',
        'accounts.array' => '账号格式错误',
        'publish_frep.require' => '请设置发布频率',
        'time_config.require' => '请设置发布时间段',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'video_ids', 'accounts', 'publish_frep', 'time_config']);
    }
}
