<?php


namespace app\api\logic;

use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\User;
use app\common\service\ConfigService;


/**
 * 充值逻辑层
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class RechargeLogic extends BaseLogic
{

    /**
     * @notes 充值
     * @param array $params
     * @return array|false
     * @author 段誉
     * @date 2023/2/24 10:43
     */
    public static function recharge(array $params)
    {
        try {
            $data = [
                'sn' => generate_sn(RechargeOrder::class, 'sn'),
                'order_terminal' => $params['terminal'],
                'user_id' => $params['user_id'],
                'pay_status' => PayEnum::UNPAID,
                'order_amount' => $params['money'],
            ];
            $order = RechargeOrder::create($data);

            return [
                'order_id' => (int)$order['id'],
                'from' => 'recharge'
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 充值配置
     * @param $userId
     * @return array
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public static function config($userId)
    {
        $userMoney = User::where(['id' => $userId])->value('user_money');
        $minAmount = ConfigService::get('recharge', 'min_amount', 0);
        $status = ConfigService::get('recharge', 'status', 0);

        return [
            'status' => $status,
            'min_amount' => $minAmount,
            'user_money' => $userMoney,
        ];
    }
}
