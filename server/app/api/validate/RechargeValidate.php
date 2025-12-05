<?php


namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 用户验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class RechargeValidate extends BaseValidate
{

    protected $rule = [
        'money' => 'require|gt:0|checkMoney',
    ];


    protected $message = [
        'money.require' => '请填写充值金额',
        'money.gt' => '请填写大于0的充值金额',
    ];


    public function sceneRecharge()
    {
        return $this->only(['money']);
    }



    /**
     * @notes 校验金额
     * @param $money
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2023/2/24 10:42
     */
    protected function checkMoney($money, $rule, $data)
    {
        $status = ConfigService::get('recharge', 'status', 0);
        if (!$status) {
            return '充值功能已关闭';
        }

        $minAmount = ConfigService::get('recharge', 'min_amount', 0);

        if ($money < $minAmount) {
            return '最低充值金额' . $minAmount . "元";
        }

        return true;
    }
}
