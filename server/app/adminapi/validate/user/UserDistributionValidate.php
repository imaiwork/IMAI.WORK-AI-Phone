<?php

namespace app\adminapi\validate\user;

use app\adminapi\logic\setting\DistributionAgentConfigLogic;
use app\common\validate\BaseValidate;
use app\common\model\distribution\DistributionAgent;
use app\common\model\user\User;

/**
 * 用户分销信息验证
 * Class UserDistributionValidate
 * @package app\adminapi\validate\user
 */
class UserDistributionValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkUser',
        'field' => 'require|checkField',
        'value' => 'require|integer', // value can be 0, so isset instead of require
    ];

    protected $message = [
        'id.require' => '请选择用户',
        'field.require' => '请选择操作',
        'value.isset' => '请输入内容',
    ];

    public function sceneSetInfo()
    {
        return $this->only(['id', 'field', 'value']);
    }

    public function checkUser($value, $rule, $data)
    {
        if (!User::find($value)) {
            return '用户不存在！';
        }
        return true;
    }

    public function checkField($value, $rule, $data)
    {
        $allowField = ['level', 'parent_id', 'status'];
        if (!in_array($value, $allowField)) {
            return '不允许更新该字段';
        }

        if ($value == 'parent_id' && $data['value'] != 0) {
            if ($data['value'] == $data['id']) {
                return '上级用户不能是自己';
            }
            if (!User::find($data['value'])) {
                return '上级用户不存在';
            }
            // 仅有效代理可作为上级（与邀请码绑定规则一致）
            $parentAgent = DistributionAgent::where('user_id', (int)$data['value'])->findOrEmpty();
            if ($parentAgent->isEmpty() || (int)$parentAgent['status'] !== 1 || (int)$parentAgent['level'] <= 0) {
                return '上级用户必须是有效代理';
            }
        }

        if ($value == 'level') {
            // 0=普通用户，其余等级取后台「代理等级」配置，数量可增删
            $allowLevel = array_merge([0], DistributionAgentConfigLogic::getLevelValues());
            if (!in_array((int)$data['value'], $allowLevel, true)) {
                return '代理等级错误';
            }
        }

        if ($value == 'status') {
            if (!in_array($data['value'], [0, 1])) {
                return '分销状态错误';
            }
        }

        return true;
    }
}
