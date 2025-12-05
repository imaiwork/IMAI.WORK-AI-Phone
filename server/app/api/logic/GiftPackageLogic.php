<?php

namespace app\api\logic;

use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;


/**
 * logic
 */
class GiftPackageLogic extends BaseLogic
{

    /**
     * @notes 充值
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2023/2/24 10:43
     */
    public static function recharge(array $params): bool
    {
        try {
            $packageInfo = GiftPackage::where('status', 1)->findOrEmpty($params['package_id']);
            if ($packageInfo->isEmpty()) {
                throw new \Exception("礼包异常");
            }
            $data  = [
                'sn'             => generate_sn(GiftPackageOrder::class, 'sn'),
                'package_id'     => $packageInfo->id,
                'order_terminal' => $params['terminal'],
                'user_id'        => $params['user_id'],
                'pay_status'     => PayEnum::UNPAID,
                'order_amount'   => $packageInfo->price,
                'type'           => $packageInfo->type,
            ];
            $order = GiftPackageOrder::create($data);

            self::$returnData = [
                'order_id' => (int)$order['id'],
                'from'     => 'recharge'
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
