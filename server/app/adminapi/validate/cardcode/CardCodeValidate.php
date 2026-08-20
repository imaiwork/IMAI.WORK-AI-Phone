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
        'balance'           => 'checkBalance',
        'member_level_id'   => 'checkMemberLevel',
        'member_days'       => 'checkMemberDays',
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


    protected function checkType($value, $rule, $data)
    {
        // 后台可生成：算力值 / 会员兑换码（代理算力卡由代理端制卡，不走此处）
        if (!in_array((int)$value, [CardCodeEnum::TYPE_TOKENS, CardCodeEnum::TYPE_MEMBER], true)) {
            return '类型错误';
        }
        return true;
    }

    protected function checkBalance($value, $rule, $data)
    {
        if ((int)($data['type'] ?? 0) !== CardCodeEnum::TYPE_TOKENS) {
            return true;
        }
        if ($value === '' || $value === null || (float)$value <= 0) {
            return '请输入算力值';
        }
        if ((float)$value > 1000000) {
            return '算力值不能大于1000000';
        }
        return true;
    }

    protected function checkMemberLevel($value, $rule, $data)
    {
        if ((int)($data['type'] ?? 0) !== CardCodeEnum::TYPE_MEMBER) {
            return true;
        }
        $levelId = (int)$value;
        if ($levelId <= 0) {
            return '请选择会员等级';
        }
        // 与兑换端 MemberService::grant 同用 iw_user_level,勿用废弃的 iw_member_level
        $exists = \app\common\model\user\UserLevel::where('id', $levelId)
            ->where('status', 1)
            ->findOrEmpty();
        if ($exists->isEmpty()) {
            return '会员等级不存在或已禁用';
        }
        return true;
    }

    protected function checkMemberDays($value, $rule, $data)
    {
        if ((int)($data['type'] ?? 0) !== CardCodeEnum::TYPE_MEMBER) {
            return true;
        }
        $days = (int)$value;
        if ($days < 1 || $days > 3650) {
            return '请输入有效会员天数(1-3650)';
        }
        return true;
    }


}