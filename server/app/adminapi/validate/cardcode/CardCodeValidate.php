<?php

namespace app\adminapi\validate\cardcode;
use app\common\enum\CardCodeEnum;
use app\common\validate\BaseValidate;

/**
 * 卡密验证器类
 * Class CardCodeController
 * @package app\adminapi\validate\cardecode
 */
class CardCodeValidate extends BaseValidate
{

    protected $rule = [
        'id'                => 'require',
        'type'              => 'require|checkType',
        'relation_id'       => 'requireIf:type,1,2',
        'card_num'          => 'require|gt:0|elt:500',
        'valid_start_time'  => 'require|gt:0',
        'valid_end_time'    => 'require|gt:0',
        'rule_type'         => 'require|in:1,2',

    ];

    protected $message = [
        'id.require'                => '请选择卡密',
        'type.require'              => '请选择卡密类型',
        'type.in'                   => '卡密类型错误',
        'relation_id.requireIf'     => '请选择卡密',
        'card_num.require'          => '请输入卡密数量',
        'card_num.gt'               => '卡密数量不能小于0',
        'card_num.elt'               => '卡密数量不能大于500',
        'valid_start_time.require'  => '请选择失效时间',
        'valid_start_time.lt'       => '生效时间错误',
        'valid_end_time.require'    => '请选择生效时间',
        'valid_end_time.lt'         => '生效时间错误',
        'rule_type.require'         => '请选择生成规则',
        'rule_type.in'              => '生成规则值错误',
    ];

    protected function sceneAdd()
    {
        return $this->remove(['id'=>true]);
    }

    protected function sceneId()
    {
        return $this->only(['id']);
    }


    protected function checkType($value,$rule,$data)
    {
        if(!in_array($value,[CardCodeEnum::TYPE_TOKENS])){
            return '类型错误';
        }
        return true;

    }


}