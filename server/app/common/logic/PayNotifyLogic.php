<?php


namespace app\common\logic;

use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\User;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付成功后处理订单状态
 * Class PayNotifyLogic
 * @package app\api\logic
 */
class PayNotifyLogic extends BaseLogic
{

    public static function handle($action, $orderSn, $extra = [])
    {
        Db::startTrans();
        try {
            self::$action($orderSn, $extra);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::write(implode('-', [
                __CLASS__,
                __FUNCTION__,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ]));
            self::setError($e->getMessage());
            return $e->getMessage();
        }
    }


    /**
     * @notes 充值回调
     * @param $orderSn
     * @param array $extra
     * @author 段誉
     * @date 2023/2/27 15:28
     */
    public static function recharge($orderSn, array $extra = [])
    {
        $order = RechargeOrder::where('sn', $orderSn)->findOrEmpty();
        // 增加用户累计充值金额及用户余额
        $user                        = User::findOrEmpty($order->user_id);
        $user->total_recharge_amount += $order->order_amount;
        $user->user_money            += $order->order_amount;
        $user->save();

        // 记录账户流水
        AccountLogLogic::add(
            $order->user_id,
            AccountLogEnum::UM_INC_RECHARGE,
            AccountLogEnum::INC,
            $order->order_amount,
            $order->sn,
            '用户充值'
        );

        // 更新充值订单状态
        $order->transaction_id = $extra['transaction_id'] ?? '';
        $order->pay_status     = PayEnum::ISPAID;
        $order->pay_time       = time();
        $order->save();
    }


    /**
     * @notes 充值算力回调
     * @param $orderSn
     * @param array $extra
     * @author 段誉
     * @date 2023/2/27 15:28
     */
    public static function tokens($orderSn, array $extra = [])
    {
        $order       = GiftPackageOrder::where('sn', $orderSn)->findOrEmpty();
        $packageInfo = GiftPackage::json(['package_info'], true)->findOrEmpty($order['package_id']);
        // 增加用户累计充值金额及用户余额
        $user = User::findOrEmpty($order->user_id);
        //加油包
        if ($packageInfo->type == 1) {
            $user->tokens += $packageInfo->package_info['tokens'];
            $user->save();
        }
        // 记录账户流水
        AccountLogLogic::add(
            $order->user_id,
            AccountLogEnum::TOKENS_INC_RECHARGE,
            AccountLogEnum::INC,
            $packageInfo->package_info['tokens'],
            1,
            $order->sn,
            AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_RECHARGE)
        );
        $packageInfoArr = $packageInfo->package_info ?? [];
        $packageInfoArr['expired'] = $packageInfoArr['expired'] ?? 50;
        $packageInfo->package_info = $packageInfoArr;
        // 更新充值订单状态
        $order->transaction_id  = $extra['transaction_id'] ?? '';
        $order->pay_status      = PayEnum::ISPAID;
        $order->pay_time        = time();
        $order->expiration_time = time() + $packageInfo->package_info['expired'] * 31536000;
        $order->save();
    }
}
