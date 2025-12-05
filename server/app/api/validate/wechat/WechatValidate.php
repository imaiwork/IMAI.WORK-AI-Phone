<?php

namespace app\api\validate\wechat;

use app\common\validate\BaseValidate;

/**
 * 微信校验
 * Class WechatValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class WechatValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'wechat_id' => 'require',
        'open_ai' => 'in:0,1',
        "takeover_mode" => 'in:0,1',
        "takeover_type" => 'in:0,1,2',
        "sort" => 'number',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备码',
        'wechat_id.require' => '请输入微信ID',
        'wechat_nickname.require' => '请输入微信昵称',
        'wechat_avatar.require' => '请输入微信头像',
        'wechat_status.require' => '请输入微信状态',
        "takeover_mode.in" => '接管模式值只能是0或1',
        "takeover_type.in" => '接管类型值只能是0,1,2',
        "sort.number" => '排序值必须是整数',
        "open_ai.in" => '是否开启AI功能值只能是0或1',
    ];


    /**
     * @notes 添加微信
     * @return WechatValidate
     */
    public function sceneAdd()
    {
        return $this->only(['device_code', 'wechat_id']);
    }

    /**
     * @notes 更新微信
     * @return WechatValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'wechat_id']);
    }

    /**
     * @notes AI
     * @return WechatValidate
     */
    public function sceneAi()
    {
        return $this->only(['wechat_id', 'remark', 'takeover_mode', 'takeover_type', 'sort', 'open_ai']);
    }

    /**
     * @notes 详情
     * @return WechatValidate
     */
    public function sceneDetail()
    {
        return $this->only(['wechat_id']);
    }
}
