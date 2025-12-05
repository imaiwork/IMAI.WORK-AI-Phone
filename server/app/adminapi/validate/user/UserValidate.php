<?php

namespace app\adminapi\validate\user;


use app\common\model\user\User;
use app\common\validate\BaseValidate;

/**
 * 用户验证
 * Class UserValidate
 * @package app\adminapi\validate\user
 */
class UserValidate extends BaseValidate
{
    protected $regex = [
        'password' => '/^.{6,20}$/'
    ];

    protected $rule = [
        'id' => 'require|checkUser',
        'field' => 'require|checkField',
        'value' => 'require',

        'password' => 'require|min:6|max:20|regex:password',

    ];

    protected $message = [
        'id.require' => '请选择用户',
        'field.require' => '请选择操作',
        'value.require' => '请输入内容',

        'password.require' => '请填写登录密码',
        'password.min'     => '登录密码最少6位数',
        'password.max'     => '登录密码最少20位数',
        'password.regex'   => '登录密码须为6到20位',
    ];


    /**
     * @notes 详情场景
     * @return UserValidate
     * @author 段誉
     * @date 2022/9/22 16:35
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 用户信息校验
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/22 17:03
     */
    public function checkUser($value, $rule, $data)
    {
        $userIds = is_array($value) ? $value : [$value];

        foreach ($userIds as $item) {
            if (!User::find($item)) {
                return '用户不存在！';
            }
        }
        return true;
    }


    /**
     * @notes 校验是否可更新信息
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2022/9/22 16:37
     */
    public function checkField($value, $rule, $data)
    {
        $allowField = ['account', 'sex', 'mobile', 'real_name'];

        // if (!in_array($value, $allowField)) {
        //     return '用户信息不允许更新';
        // }
        $notAllowField = ['account', 'password', 'id', 'sn'];
        if (in_array($value, $notAllowField)) {
            return '用户信息不允许更新';
        }

        switch ($value) {
            case 'account':
                //验证手机号码是否存在
                $account = User::where([
                    ['id', '<>', $data['id']],
                    ['account', '=', $data['value']]
                ])->findOrEmpty();

                if (!$account->isEmpty()) {
                    return '账号已被使用';
                }
                break;

            case 'mobile':
                if (false == $this->validate($data['value'], 'mobile', $data)) {
                    return '手机号码格式错误';
                }

                //验证手机号码是否存在
                $mobile = User::where([
                    ['id', '<>', $data['id']],
                    ['mobile', '=', $data['value']]
                ])->findOrEmpty();

                if (!$mobile->isEmpty()) {
                    return '手机号码已存在';
                }
                break;
        }
        return true;
    }

    public function sceneCreate(): UserValidate
    {
        return $this->only(['mobile', 'password']);
    }

    public function sceneSetInfo(): UserValidate
    {
        return $this->only(['id', 'field', 'value']);
    }
}
