<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 微信回复策略校验
 * Class StrategyValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class SvAddWechatStrategyValidate extends BaseValidate
{

    protected $rule = [
        'account' => 'require',
        'wechat_enable' => 'in:0,1',
        'wechat_reg_type' => 'in:0,1,2',
        'wechat_id' => 'require',
    ];



    protected $message = [
        'account.require' => '小红书账号不能为空',
        'wechat_enable.in' => '加微策略只能是0,1',
        'wechat_reg_type.in' => '加微匹配规则只能是0,1,2',
        'wechat_id.require' => '请选择添加好友的微信号'
    ];


    /**
     * @notes 回复策略
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['account','wechat_enable', 'wechat_reg_type', 'wechat_id']);
    }

    public function sceneDetail()
    {
        return $this->only(['account']);
    }

    public function sceneRetry()
    {
        return $this->only(['id']);
    }
    
}
