<?php


namespace app\api\validate;


use app\common\model\user\User;
use app\common\validate\BaseValidate;


/**
 * 设置用户信息验证
 * Class SetUserInfoValidate
 * @package app\api\validate
 */
class SetUserInfoValidate extends BaseValidate
{
    protected $rule = [
        'field' => 'require|checkField',
        'value' => 'require',
    ];

    protected $message = [
        'field.require' => '参数缺失',
        'value.require' => '值不存在',
    ];


    /**
     * @notes 校验字段内容
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2022/9/21 17:01
     */
    protected function checkField($value, $rule, $data)
    {
        $allowField = [
            'nickname',
            'account',
            'sex',
            'avatar',
            'real_name',
        ];

        if (!in_array($value, $allowField)) {
            return '参数错误';
        }

        if ($value == 'account') {
            $user = User::where([
                ['account', '=', $data['value']],
                ['id', '<>', $data['id']]
            ])->findOrEmpty();
            if (!$user->isEmpty()) {
                return '账号已被使用!';
            }
        }

        return true;
    }
}
