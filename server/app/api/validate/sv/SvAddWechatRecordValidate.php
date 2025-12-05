<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 微信回复策略校验
 * Class StrategyValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class SvAddWechatRecordValidate extends BaseValidate
{

    protected $rule = [
        'account' => 'require',
        'wechat_enable' => 'in:0,1',
        'wechat_reg_type' => 'in:0,1,2',
        'wehcat_no' => 'require',
    ];



    protected $message = [
        'account.require' => '小红书账号不能为空',
        'wechat_enable.in' => '加微策略只能是0,1',
        'wechat_reg_type.in' => '加微匹配规则只能是0,1,2',
        'wehcat_no.require' => '请选择添加好友的微信号'
    ];


    /**
     * @notes 回复策略
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only(['account','wechat_enable', 'wechat_reg_type', 'wehcat_no']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function sceneRetry()
    {
        return $this->only(['id']);
    }
    
}
