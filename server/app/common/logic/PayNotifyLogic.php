<?php


namespace app\common\logic;

use app\adminapi\logic\deviceauth\DeviceAuthCodeLogic;
use app\api\logic\DeviceAuthLogic;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\recharge\RechargeOrder;
use app\common\model\sv\SvDevice;
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
        $order = RechargeOrder::where('sn', $orderSn)->lock(true)->findOrEmpty();
        if ($order->isEmpty() || $order->pay_status == PayEnum::ISPAID) {
            return;
        }
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
        $order = GiftPackageOrder::where('sn', $orderSn)->lock(true)->findOrEmpty();
        if ($order->isEmpty()) {
            throw new \Exception('充值订单不存在');
        }
        // 已支付：由调用方补齐缺失流水，避免重复加算力
        if ((int)$order->pay_status === PayEnum::ISPAID) {
            return;
        }

        $packageInfo = GiftPackage::json(['package_info'], true)->findOrEmpty($order['package_id']);
        if ($packageInfo->isEmpty()) {
            throw new \Exception('礼包不存在');
        }
        $packageInfoArr = $packageInfo->package_info ?? [];
        if (!is_array($packageInfoArr)) {
            $packageInfoArr = [];
        }
        $tokensAmount = (float)($packageInfoArr['tokens'] ?? 0);
        if ((int)$packageInfo->type === 1 && $tokensAmount <= 0) {
            throw new \Exception('礼包算力配置异常');
        }

        $user = User::findOrEmpty($order->user_id);
        if ($user->isEmpty()) {
            throw new \Exception('用户不存在');
        }
        // 加油包到账
        if ((int)$packageInfo->type === 1) {
            $user->tokens += $tokensAmount;
            $user->save();
        }

        // 记录账户流水（失败必须抛错回滚，否则订单已支付但 /account_log/lists 无记录）
        // 加油包充值始终入个人算力,显式 team_id=0:成员在企业空间购买时勿被 spender 识别挂到企业流水
        $log = AccountLogLogic::add(
            $order->user_id,
            AccountLogEnum::TOKENS_INC_RECHARGE,
            AccountLogEnum::INC,
            $tokensAmount,
            1,
            (string)$order->sn,
            AccountLogEnum::getChangeTypeDesc(AccountLogEnum::TOKENS_INC_RECHARGE),
            [],
            0
        );
        if ($log === false) {
            throw new \Exception('写入算力流水失败');
        }

        $expiredYears = (int)($packageInfoArr['expired'] ?? 50);
        if ($expiredYears <= 0) {
            $expiredYears = 50;
        }
        $order->transaction_id  = $extra['transaction_id'] ?? '';
        $order->pay_status      = PayEnum::ISPAID;
        $order->pay_time        = time();
        $order->expiration_time = time() + $expiredYears * 31536000;
        $order->save();
    }

    /**
     * @notes 设备CDK支付回调
     */
    public static function deviceAuth($orderSn, array $extra = [])
    {
        Db::startTrans();
        try {
            $order = DeviceAuthOrder::where('sn', $orderSn)->lock(true)->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \Exception('订单不存在');
            }
            if ($order->pay_status == DeviceAuthOrderEnum::PAY_STATUS_PAID) {
                Db::commit();
                return;
            }

            if ($order->biz_type == DeviceAuthOrderEnum::BIZ_TYPE_PURCHASE) {
                DeviceAuthCodeLogic::assignCodesFromPool($order);
            }

            $order->transaction_id = $extra['transaction_id'] ?? '';
            $order->pay_status = DeviceAuthOrderEnum::PAY_STATUS_PAID;
            $order->pay_time = time();
            // 历史/异常单可能未写入 pay_way：回调时兜底，与礼包订单展示保持一致
            if ((int)$order->pay_way <= 0) {
                $order->pay_way = (int)($extra['pay_way'] ?? PayEnum::WECHAT_PAY);
            }
            $order->save();

            if ($order->biz_type == DeviceAuthOrderEnum::BIZ_TYPE_RENEW) {
                $device = SvDevice::findOrEmpty($order->device_id);
                $plan = DeviceAuthPlan::findOrEmpty($order->plan_id);
                if ($device->isEmpty() || $plan->isEmpty()) {
                    throw new \Exception('续费订单关联设备或套餐不存在');
                }
                DeviceAuthLogic::consumeRenewCdk($order, $device, $plan);
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::channel('pay')->error('设备CDK支付回调失败: ' . $e->getMessage() . ' sn=' . $orderSn);
            throw $e;
        }
    }
}
