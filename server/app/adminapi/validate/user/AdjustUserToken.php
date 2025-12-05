<?php


namespace app\adminapi\validate\user;

use app\common\enum\user\AccountLogEnum;
use app\common\model\user\User;
use app\common\validate\BaseValidate;

/**
 * 调整用户钱包验证器
 * Class AdjustUserMoney
 * @package app\adminapi\validate\user
 */
class AdjustUserToken extends BaseValidate
{

    protected $rule = [
        'user_id' => 'require',
        'action'  => 'require|in:' . AccountLogEnum::INC . ',' . AccountLogEnum::DEC,
        'num'     => 'require|gt:0|checkToken',
        'remark'  => 'max:128',
    ];

    protected $message = [
        'id.require'     => '请选择用户',
        'action.require' => '请选择调整类型',
        'action.in'      => '调整类型错误',
        'num.require'    => '请输入调整数量',
        'num.gt'         => '调整算力必须大于零',
        'remark'         => '备注不可超过128个符号',
    ];


    protected function checkToken($vaule, $rule, $data)
    {
        $user = User::find($data['user_id']);
        if (empty($user)) {
            return '用户不存在';
        }

        if (1 == $data['action']) {
            return true;
        }

        $surplusMoeny = $user->tokens - $vaule;
        if ($surplusMoeny < 0) {
            return '用户可用算力仅剩' . $user->tokens;
        }

        return true;
    }
}
