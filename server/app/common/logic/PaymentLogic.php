<?php


namespace app\common\logic;


use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\YesNoEnum;
use app\common\model\pay\PayWay;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\pay\AliPayService;
use app\common\service\pay\WeChatPayService;


/**
 * 支付逻辑
 * Class PaymentLogic
 * @package app\common\logic
 */
class PaymentLogic extends BaseLogic
{

    /**
     * @notes 支付方式
     * @param $userId
     * @param $terminal
     * @param $params
     * @return array|false
     * @author 段誉
     * @date 2023/2/24 17:53
     */
    public static function getPayWay($userId, $terminal, $params)
    {
        try {
            //            if ($params['from'] == 'recharge') {
            //                // 充值
            //                $order = RechargeOrder::findOrEmpty($params['order_id'])->toArray();
            //            }
            //            if ($params['from'] == 'tokens') {
            //                // 充值 tokens
            //                $order = GiftPackageOrder::findOrEmpty($params['order_id'])->toArray();
            //            }
            //
            //            if (empty($order)) {
            //                throw new \Exception('待支付订单不存在');
            //            }

            //获取支付场景
            $pay_way = PayWay::alias('pw')
                ->join('dev_pay_config dp', 'pw.pay_config_id = dp.id')
                ->where(['pw.scene' => $terminal, 'pw.status' => YesNoEnum::YES])
                ->field('dp.id,dp.name,dp.pay_way,dp.icon,dp.sort,dp.remark,pw.is_default')
                ->order('pw.is_default desc,dp.sort desc,id asc')
                ->select()
                ->toArray();

            foreach ($pay_way as $k => &$item) {
                if ($item['pay_way'] == PayEnum::WECHAT_PAY) {
                    $item['extra'] = '微信快捷支付';
                }
                if ($item['pay_way'] == PayEnum::ALI_PAY) {
                    $item['extra'] = '支付宝快捷支付';
                }
                if ($item['pay_way'] == PayEnum::BALANCE_PAY) {
                    $user_money    = User::where(['id' => $userId])->value('user_money');
                    $item['extra'] = '可用余额:' . $user_money;
                }
                // 充值时去除余额支付
                if ($params['from'] == 'recharge' && $item['pay_way'] == PayEnum::BALANCE_PAY) {
                    unset($pay_way[$k]);
                }
            }

            return [
                'lists' => array_values($pay_way),
                //                'order_amount' => $order['order_amount'],
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取支付状态
     * @param $params
     * @return array|false
     * @author 段誉
     * @date 2023/3/1 16:23
     */
    public static function getPayStatus($params)
    {
        try {
            $order     = [];
            $orderInfo = [];
            switch ($params['from']) {
                case 'recharge':
                    $order     = RechargeOrder::where(['user_id' => $params['user_id'], 'id' => $params['order_id']])
                        ->findOrEmpty();
                    $payTime   = empty($order['pay_time']) ? '' : date('Y-m-d H:i:s', $order['pay_time']);
                    $orderInfo = [
                        'order_id'     => $order['id'],
                        'order_sn'     => $order['sn'],
                        'order_amount' => $order['order_amount'],
                        'pay_way'      => PayEnum::getPayDesc($order['pay_way']),
                        'pay_status'   => PayEnum::getPayStatusDesc($order['pay_status']),
                        'pay_time'     => $payTime,
                    ];
                    break;
                case 'tokens':
                    $order     = GiftPackageOrder::where(['user_id' => $params['user_id'], 'id' => $params['order_id']])
                        ->findOrEmpty();
                    $payTime   = empty($order['pay_time']) ? '' : date('Y-m-d H:i:s', $order['pay_time']);
                    $orderInfo = [
                        'order_id'     => $order['id'],
                        'order_sn'     => $order['sn'],
                        'order_amount' => $order['order_amount'],
                        'pay_way'      => PayEnum::getPayDesc($order['pay_way']),
                        'pay_status'   => PayEnum::getPayStatusDesc($order['pay_status']),
                        'pay_time'     => $payTime,
                    ];
                    break;
            }

            if (empty($order)) {
                throw new \Exception('订单不存在');
            }

            return [
                'pay_status' => $order['pay_status'],
                'pay_way'    => $order['pay_way'],
                'order'      => $orderInfo
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取预支付订单信息
     * @param $params
     * @return RechargeOrder|array|false|\think\Model
     * @author 段誉
     * @date 2023/2/27 15:19
     */
    public static function getPayOrderInfo($params)
    {
        try {
            switch ($params['from']) {
                case 'recharge':
                    $order = RechargeOrder::findOrEmpty($params['order_id']);
                    if ($order->isEmpty()) {
                        throw new \Exception('充值订单不存在');
                    }
                    break;
                case 'tokens':
                    $order = GiftPackageOrder::findOrEmpty($params['order_id']);
                    if ($order->isEmpty()) {
                        throw new \Exception('充值订单不存在');
                    }
                    break;
            }

            if ($order['pay_status'] == PayEnum::ISPAID) {
                throw new \Exception('订单已支付');
            }
            return $order;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * @notes 支付
     * @param $payWay
     * @param $from
     * @param $order
     * @param $terminal
     * @param $redirectUrl
     * @return array|false|mixed|string|string[]
     * @throws \Exception
     * @author mjf
     * @date 2024/3/18 16:49
     */
    public static function pay($payWay, $from, $order, $terminal, $redirectUrl)
    {
        // 支付编号-仅为微信支付预置(同一商户号下不同客户端支付需使用唯一订单号)
        $paySn = $order['sn'];
        if ($payWay == PayEnum::WECHAT_PAY) {
            $paySn = self::formatOrderSn($order['sn'], $terminal);
        }
        //更新支付方式
        switch ($from) {
            case 'recharge':
                RechargeOrder::update(['pay_way' => $payWay, 'pay_sn' => $paySn], ['id' => $order['id']]);
                break;
            case 'tokens':
                GiftPackageOrder::update(['pay_way' => $payWay, 'pay_sn' => $paySn], ['id' => $order['id']]);
                break;
        }

        if ($order['order_amount'] == 0) {
            PayNotifyLogic::handle($from, $order['sn']);
            return ['pay_way' => PayEnum::BALANCE_PAY];
        }
        $payService = null;
        switch ($payWay) {
            case PayEnum::WECHAT_PAY:
                $payService            = (new WeChatPayService($terminal, $order['user_id'] ?? null));
                $order['pay_sn']       = $paySn;
                $order['redirect_url'] = $redirectUrl;
                $result                = $payService->pay($from, $order);
                break;
            case PayEnum::ALI_PAY:
                $payService            = (new AliPayService($terminal));
                $order['redirect_url'] = $redirectUrl;
                $result                = $payService->pay($from, $order);
                break;
            default:
                self::$error = '订单异常';
                $result      = false;
        }

        if (false === $result && !self::hasError()) {
            self::setError($payService->getError());
        }
        return $result;
    }

    /**
     * @notes 设置订单号 支付回调时截取前面的单号 18个
     * @param $orderSn
     * @param $terminal
     * @return string
     * @author 段誉
     * @date 2023/3/1 16:31
     * @remark 回调时使用了不同的回调地址,导致跨客户端支付时(例如小程序,公众号)可能出现201,商户订单号重复错误
     */
    public static function formatOrderSn($orderSn, $terminal)
    {
        $suffix = mb_substr(time(), -4);
        return $orderSn . $terminal . $suffix;
    }


    /**
     * 进行用户算力过期后扣除
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author L
     * @data 2024/8/16 11:03
     */
    public static function ChangeUserTokens(): string
    {
        return "success";
        try {
            $orderList = GiftPackageOrder::where([
                ['type', '=', 1],
                ['pay_status', '=', 1],
                ['change_type', '=', 0]
            ])->field(['id', 'user_id', 'package_id', 'pay_time', 'expiration_time'])->select();
            if ($orderList->isEmpty()) {
                return true;
            }
            $changeTokensList = [];
            foreach ($orderList as $k => $v) {
                $userTokens = User::where('id', $v->user_id)->value('tokens');
                if ($userTokens == 0) {
                    continue;
                }
                $packageInfo   = GiftPackage::json(['package_info'], true)->field(['package_info'])->findOrEmpty($v->package_id);
                $userTokensSum = UserTokensLog::where([
                    ['user_id', '=', $v->user_id],
                    ['action', '=', 2],
                    ['status', '=', 1],
                    ['change_type', '<>', 314],
                ])->whereBetweenTime('create_time', $v->pay_time, $v->expiration_time)->sum('change_amount');
                if ($userTokensSum > $packageInfo->package_info['tokens']) {
                    continue;
                }
                $changeTokensList[$k] = [
                    'id'            => $v->user_id,
                    'order_id'      => $v->id,
                    'change_tokens' => $packageInfo->package_info['tokens'] - $userTokensSum,
                    'tokens'        => $userTokens - ($packageInfo->package_info['tokens'] - $userTokensSum),
                ];
            }
            if (!empty($changeTokensList)) {
                foreach ($changeTokensList as &$userToken) {
                    AccountLogLogic::recordUserTokensLog(true, $userToken['id'], AccountLogEnum::TOKENS_DEC_EXPIRE, $userToken['change_tokens']);
                    GiftPackageOrder::where('id', $userToken['order_id'])->update([
                        'id'          => $userToken['order_id'],
                        'change_type' => 1
                    ]);
                    unset($userToken['change_tokens'], $userToken['order_id']);
                }
                (new User())->saveAll($changeTokensList);
            }
            return "success";
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
