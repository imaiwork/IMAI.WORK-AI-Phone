<?php

namespace app\common\service\pay;

use app\api\logic\DeviceAuthLogic;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\logic\PayNotifyLogic;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\pay\PayConfig;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\user\UserAuth;
use app\common\model\user\UserTokensLog;
use app\common\service\wechat\WeChatConfigService;
use app\common\service\wechat\WeChatMnpService;
use think\facade\Db;
use think\facade\Log;

/**
 * 微信小程序虚拟支付
 */
class MnpVirtualPayService extends BaseLogic
{
    private const FROM_TOKENS = 'tokens';
    private const FROM_DEVICE_AUTH = 'device_auth';
    private const SIGN_METHOD = 'requestVirtualPayment';
    private const MNP_PAY_TYPE_VIRTUAL = 2;
    /** 已支付待发货 / 发货中 / 已发货，均可视为支付成功可入账 */
    private const PAID_ORDER_STATUS = [2, 3, 4];

    private static array $virtualConfig = [];

    /**
     * 创建虚拟支付订单并返回 wx.requestVirtualPayment 调起参数
     * from: tokens(算力礼包) | device_auth(CDK套餐)
     */
    public static function prepay(array $params)
    {
        $from = (string)($params['from'] ?? self::FROM_TOKENS);
        if ($from === self::FROM_DEVICE_AUTH) {
            return self::prepayDeviceAuth($params);
        }
        return self::prepayTokens($params);
    }

    /**
     * 算力礼包虚拟支付预下单
     */
    private static function prepayTokens(array $params)
    {
        Db::startTrans();
        try {
            self::checkVirtualPayEnabled();
            if (empty($params['package_id'])) {
                throw new \Exception('礼包参数缺失');
            }
            $wechatSession = (new WeChatMnpService())->getMnpResByCode((string)$params['code']);
            self::checkMnpAuth((int)$params['user_id'], (string)$wechatSession['openid']);

            $package = GiftPackage::json(['package_info'], true)
                ->where('status', 1)
                ->findOrEmpty((int)$params['package_id']);
            if ($package->isEmpty()) {
                throw new \Exception('礼包异常');
            }
            if ((float)$package->price <= 0) {
                throw new \Exception('虚拟支付金额异常');
            }

            $packageInfo = $package->package_info ?: [];
            $mode = (string)self::getConfig('mode', 'short_series_goods');
            // 道具直购：购买数量默认 1；代币充值：可用套餐算力值
            if ($mode === 'short_series_goods') {
                $buyQuantity = (int)($params['buy_quantity'] ?? 1);
            } else {
                $buyQuantity = (int)($params['buy_quantity'] ?? ($packageInfo['tokens'] ?? 0));
            }
            if ($buyQuantity <= 0) {
                throw new \Exception('虚拟商品数量异常');
            }

            $order = GiftPackageOrder::create([
                'sn'             => generate_sn(GiftPackageOrder::class, 'sn'),
                'package_id'     => $package->id,
                'order_terminal' => UserTerminalEnum::WECHAT_MMP,
                'user_id'        => (int)$params['user_id'],
                'pay_way'        => PayEnum::MNP_VIRTUAL_PAY,
                'pay_status'     => PayEnum::UNPAID,
                'order_amount'   => $package->price,
                'type'           => $package->type,
            ]);
            $order->pay_sn = $order->sn;
            $order->save();

            $productId = trim((string)($package->product_id ?? ''));
            if ($productId === '') {
                $productId = trim((string)($params['product_id'] ?? ''));
            }
            $signDataJson = self::buildSignedConfig(
                $productId,
                $buyQuantity,
                (float)$package->price,
                (string)$order->sn,
                self::FROM_TOKENS,
                $mode,
                (string)($wechatSession['session_key'] ?? '')
            );

            Db::commit();
            return [
                'order_id'     => (int)$order->id,
                'order_sn'     => $order->sn,
                'from'         => self::FROM_TOKENS,
                'order_amount' => $order->order_amount,
                'pay_way'      => PayEnum::MNP_VIRTUAL_PAY,
                'config'       => $signDataJson['config'],
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * CDK套餐虚拟支付预下单（购买 / 续费）
     */
    private static function prepayDeviceAuth(array $params)
    {
        Db::startTrans();
        try {
            self::checkVirtualPayEnabled();
            if (empty($params['plan_id'])) {
                throw new \Exception('CDK套餐参数缺失');
            }
            $wechatSession = (new WeChatMnpService())->getMnpResByCode((string)$params['code']);
            self::checkMnpAuth((int)$params['user_id'], (string)$wechatSession['openid']);

            $bizType = (int)($params['biz_type'] ?? DeviceAuthOrderEnum::BIZ_TYPE_PURCHASE);
            if ($bizType === DeviceAuthOrderEnum::BIZ_TYPE_RENEW) {
                $ok = DeviceAuthLogic::renewDevice([
                    'user_id'     => (int)$params['user_id'],
                    'plan_id'     => (int)$params['plan_id'],
                    'pay_type'    => DeviceAuthOrderEnum::PAY_TYPE_ONLINE,
                    'device_id'   => (int)$params['device_id'],
                    'device_code' => (string)($params['device_code'] ?? ''),
                    'terminal'    => UserTerminalEnum::WECHAT_MMP,
                ]);
            } else {
                $ok = DeviceAuthLogic::purchaseCode([
                    'user_id'  => (int)$params['user_id'],
                    'plan_id'  => (int)$params['plan_id'],
                    'quantity' => max(1, (int)($params['quantity'] ?? $params['buy_quantity'] ?? 1)),
                    'pay_type' => DeviceAuthOrderEnum::PAY_TYPE_ONLINE,
                    'terminal' => UserTerminalEnum::WECHAT_MMP,
                ]);
            }
            if ($ok === false) {
                throw new \Exception(DeviceAuthLogic::getError() ?: '创建CDK订单失败');
            }

            $orderId = (int)(DeviceAuthLogic::getReturnData()['order_id'] ?? 0);
            $order = DeviceAuthOrder::where([
                'id'      => $orderId,
                'user_id' => (int)$params['user_id'],
            ])->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \Exception('CDK订单创建失败');
            }

            $plan = DeviceAuthPlan::findOrEmpty((int)$order->plan_id);
            if ($plan->isEmpty()) {
                throw new \Exception('CDK套餐不存在');
            }
            $productId = trim((string)($plan->product_id ?? ''));
            if ($productId === '') {
                $productId = trim((string)($params['product_id'] ?? ''));
            }
            if ($productId === '') {
                throw new \Exception('CDK套餐未配置虚拟支付产品ID');
            }
            if ((float)$order->unit_price <= 0) {
                throw new \Exception('虚拟支付金额异常');
            }

            $order->pay_way = PayEnum::MNP_VIRTUAL_PAY;
            $order->pay_sn = $order->sn;
            $order->save();

            $mode = (string)self::getConfig('mode', 'short_series_goods');
            $buyQuantity = max(1, (int)$order->quantity);
            $signed = self::buildSignedConfig(
                $productId,
                $buyQuantity,
                (float)$order->unit_price,
                (string)$order->sn,
                self::FROM_DEVICE_AUTH,
                $mode,
                (string)($wechatSession['session_key'] ?? '')
            );

            Db::commit();
            return [
                'order_id'     => (int)$order->id,
                'order_sn'     => $order->sn,
                'from'         => self::FROM_DEVICE_AUTH,
                'order_amount' => $order->order_amount,
                'pay_way'      => PayEnum::MNP_VIRTUAL_PAY,
                'config'       => $signed['config'],
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 支付成功后确认：向微信 query_order 核对，已支付则入账并发货确认
     */
    public static function confirm(array $params)
    {
        try {
            $from = (string)($params['from'] ?? self::FROM_TOKENS);
            $orderId = (int)$params['order_id'];
            $userId = (int)$params['user_id'];

            if ($from === self::FROM_DEVICE_AUTH) {
                $order = DeviceAuthOrder::where(['id' => $orderId, 'user_id' => $userId])->findOrEmpty();
                if ($order->isEmpty()) {
                    throw new \Exception('订单不存在');
                }
                if ((int)$order->pay_status !== DeviceAuthOrderEnum::PAY_STATUS_PAID) {
                    self::settlePaidOrderBySn((string)$order->sn, $userId, self::FROM_DEVICE_AUTH);
                }
                $order = DeviceAuthOrder::findOrEmpty($orderId);
                return [
                    'pay_status' => (int)$order->pay_status,
                    'pay_time'   => empty($order->pay_time) ? '' : date('Y-m-d H:i:s', $order->pay_time),
                    'from'       => self::FROM_DEVICE_AUTH,
                ];
            }

            $order = GiftPackageOrder::where(['id' => $orderId, 'user_id' => $userId])->findOrEmpty();
            if ($order->isEmpty()) {
                throw new \Exception('订单不存在');
            }
            if ((int)$order->pay_status !== PayEnum::ISPAID) {
                self::settlePaidOrderBySn((string)$order->sn, $userId, self::FROM_TOKENS);
            }
            // 已支付但缺流水时补齐，保证 /account_log/lists 能查到虚拟支付入账
            self::ensureGiftPackageTokensLog((string)$order->sn);
            $order = GiftPackageOrder::findOrEmpty($orderId);
            return [
                'pay_status' => (int)$order->pay_status,
                'pay_time'   => empty($order->pay_time) ? '' : date('Y-m-d H:i:s', $order->pay_time),
                'from'       => self::FROM_TOKENS,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 虚拟支付回调：
     * 1) 微信消息推送 xpay_goods_deliver_notify / xpay_coin_pay_notify
     * 2) 兼容旧版 signData + paySig
     *
     * @return array{ok:bool,body:string,content_type:string}
     */
    public static function notify(array $params): array
    {
        $event = (string)($params['Event'] ?? $params['event'] ?? '');
        try {
            if (in_array($event, ['xpay_goods_deliver_notify', 'xpay_coin_pay_notify'], true)) {
                self::handleDeliverPush($params);
                return self::notifyResponse(true, $params);
            }

            // 兼容：自定义带签名通知
            if (!empty($params['signData']) || !empty($params['sign_data'])) {
                self::handleLegacySignedNotify($params);
                return self::notifyResponse(true, $params);
            }

            // 无 Event 时尝试按 OutTradeNo 处理（部分网关字段扁平化）
            if (!empty($params['OutTradeNo']) || !empty($params['outTradeNo'])) {
                self::handleDeliverPush($params);
                return self::notifyResponse(true, $params);
            }

            throw new \Exception('无法识别的虚拟支付通知');
        } catch (\Exception $e) {
            Log::write('小程序虚拟支付回调失败-' . $e->getMessage(), 'wxPayerror');
            self::setError($e->getMessage());
            return self::notifyResponse(false, $params, $e->getMessage());
        }
    }

    /**
     * 道具/代币发货推送入账
     */
    private static function handleDeliverPush(array $params): void
    {
        $orderSn = (string)($params['OutTradeNo'] ?? $params['outTradeNo'] ?? $params['out_trade_no'] ?? '');
        if ($orderSn === '') {
            throw new \Exception('订单号缺失');
        }
        $orderSn = mb_substr($orderSn, 0, 64);

        $payInfo = $params['WeChatPayInfo'] ?? $params['weChatPayInfo'] ?? [];
        if (!is_array($payInfo)) {
            $payInfo = [];
        }
        $transactionId = (string)($payInfo['TransactionId']
            ?? $payInfo['transactionId']
            ?? $params['TransactionId']
            ?? $params['transaction_id']
            ?? '');

        $goodsInfo = $params['GoodsInfo'] ?? $params['goodsInfo'] ?? [];
        if (!is_array($goodsInfo)) {
            $goodsInfo = [];
        }
        $attach = (string)($goodsInfo['Attach'] ?? $goodsInfo['attach'] ?? $params['Attach'] ?? $params['attach'] ?? '');
        $resolved = self::resolveOrderBySn($orderSn, $attach);
        if ($resolved === null) {
            throw new \Exception('订单不存在:' . $orderSn);
        }
        if ($resolved['paid']) {
            // 幂等：已支付时仍尝试补齐缺失的算力流水
            if ($resolved['from'] === self::FROM_TOKENS) {
                self::ensureGiftPackageTokensLog($orderSn);
            }
            return;
        }

        self::markOrderPaidByFrom($resolved['from'], $orderSn, $transactionId);
        try {
            self::notifyProvideGoods($orderSn);
        } catch (\Throwable $e) {
            Log::write('虚拟支付通知发货完成失败-' . $e->getMessage(), 'wxPayerror');
        }
    }

    private static function handleLegacySignedNotify(array $params): void
    {
        $signDataJson = (string)($params['signData'] ?? $params['sign_data'] ?? '');
        $paySig = (string)($params['paySig'] ?? $params['pay_sig'] ?? '');
        if ($signDataJson === '' || $paySig === '') {
            throw new \Exception('虚拟支付通知签名参数缺失');
        }

        $expectedPaySig = self::hmacSha256Hex(
            self::getMethod() . '&' . $signDataJson,
            self::getRequiredConfig('app_key')
        );
        if (!hash_equals($expectedPaySig, $paySig)) {
            throw new \Exception('虚拟支付签名错误');
        }

        $signData = json_decode($signDataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($signData)) {
            throw new \Exception('虚拟支付通知数据格式错误');
        }
        $params = array_merge($params, $signData);

        $orderSn = (string)($params['outTradeNo'] ?? $params['out_trade_no'] ?? $params['order_sn'] ?? '');
        if ($orderSn === '') {
            throw new \Exception('订单号缺失');
        }
        $orderSn = mb_substr($orderSn, 0, 64);
        $attach = (string)($params['attach'] ?? '');
        $resolved = self::resolveOrderBySn($orderSn, $attach);
        if ($resolved === null || $resolved['paid']) {
            return;
        }
        self::markOrderPaidByFrom(
            $resolved['from'],
            $orderSn,
            (string)($params['transaction_id'] ?? $params['transactionId'] ?? '')
        );
    }

    /**
     * 通过 query_order 核对后入账
     */
    private static function settlePaidOrderBySn(string $orderSn, int $userId, string $from): void
    {
        $wxOrder = self::queryOrderByUser($orderSn, $userId);
        $status = (int)($wxOrder['status'] ?? 0);
        if (!in_array($status, self::PAID_ORDER_STATUS, true)) {
            return;
        }

        $transactionId = (string)($wxOrder['wxpay_order_id'] ?? $wxOrder['wx_order_id'] ?? $wxOrder['channel_order_id'] ?? '');
        self::markOrderPaidByFrom($from, $orderSn, $transactionId);

        if ($status === 2) {
            try {
                self::notifyProvideGoods($orderSn);
            } catch (\Throwable $e) {
                Log::write('虚拟支付通知发货完成失败-' . $e->getMessage(), 'wxPayerror');
            }
        }
    }

    /**
     * 已支付算力订单若缺少 UserTokensLog，补写流水（不重复加算力）
     */
    private static function ensureGiftPackageTokensLog(string $orderSn): void
    {
        $order = GiftPackageOrder::where('sn', $orderSn)->findOrEmpty();
        if ($order->isEmpty() || (int)$order->pay_status !== PayEnum::ISPAID) {
            return;
        }

        $exists = UserTokensLog::where([
            'user_id'     => $order->user_id,
            'source_sn'   => $order->sn,
            'change_type' => AccountLogEnum::TOKENS_INC_RECHARGE,
        ])->findOrEmpty();
        if (!$exists->isEmpty()) {
            return;
        }

        $package = GiftPackage::json(['package_info'], true)->findOrEmpty($order->package_id);
        $packageInfo = $package->package_info ?? [];
        if (!is_array($packageInfo)) {
            $packageInfo = [];
        }
        $tokensAmount = (float)($packageInfo['tokens'] ?? 0);
        if ($tokensAmount <= 0) {
            Log::write('虚拟支付补写流水跳过：算力数量异常 sn=' . $orderSn, 'wxPayerror');
            return;
        }

        // 加油包充值入个人算力,显式 team_id=0(与 PayNotifyLogic::tokens 一致)
        $log = AccountLogLogic::add(
            (int)$order->user_id,
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
            throw new \Exception('补写算力流水失败');
        }
    }

    /**
     * @return array{from:string,paid:bool}|null
     */
    private static function resolveOrderBySn(string $orderSn, string $attach = ''): ?array
    {
        if ($attach === self::FROM_DEVICE_AUTH || $attach === '') {
            $deviceOrder = DeviceAuthOrder::where('sn', $orderSn)->findOrEmpty();
            if (!$deviceOrder->isEmpty()) {
                return [
                    'from' => self::FROM_DEVICE_AUTH,
                    'paid' => (int)$deviceOrder->pay_status === DeviceAuthOrderEnum::PAY_STATUS_PAID,
                ];
            }
        }
        if ($attach === self::FROM_TOKENS || $attach === '') {
            $giftOrder = GiftPackageOrder::where('sn', $orderSn)->findOrEmpty();
            if (!$giftOrder->isEmpty()) {
                return [
                    'from' => self::FROM_TOKENS,
                    'paid' => (int)$giftOrder->pay_status === PayEnum::ISPAID,
                ];
            }
        }
        return null;
    }

    private static function markOrderPaidByFrom(string $from, string $orderSn, string $transactionId): void
    {
        $action = $from === self::FROM_DEVICE_AUTH ? 'deviceAuth' : self::FROM_TOKENS;
        $extra = [
            'transaction_id' => $transactionId,
        ];
        if ($from === self::FROM_DEVICE_AUTH) {
            $extra['pay_way'] = PayEnum::MNP_VIRTUAL_PAY;
        }
        $result = PayNotifyLogic::handle($action, $orderSn, $extra);
        if ($result !== true) {
            throw new \Exception((string)$result);
        }
        if ($from === self::FROM_TOKENS) {
            self::ensureGiftPackageTokensLog($orderSn);
        }
    }

    /**
     * 组装 wx.requestVirtualPayment 参数
     * @return array{config:array}
     */
    private static function buildSignedConfig(
        string $productId,
        int $buyQuantity,
        float $unitPriceYuan,
        string $outTradeNo,
        string $attach,
        string $mode,
        string $sessionKey
    ): array {
        if ($sessionKey === '') {
            throw new \Exception('微信登录态异常，请重新登录');
        }
        if ($productId === '' && $mode === 'short_series_goods') {
            throw new \Exception('未配置虚拟支付产品ID');
        }

        $signData = [
            'offerId'      => self::getRequiredConfig('offer_id'),
            'buyQuantity'  => $buyQuantity,
            'env'          => (int)self::getConfig('env', 0),
            'currencyType' => (string)self::getConfig('currency_type', 'CNY'),
        ];
        if ($mode === 'short_series_goods') {
            $signData['productId'] = $productId;
            $signData['goodsPrice'] = (int)round($unitPriceYuan * 100);
        }
        $signData['outTradeNo'] = $outTradeNo;
        $signData['attach'] = $attach;

        $signDataJson = json_encode($signData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($signDataJson === false) {
            throw new \Exception('虚拟支付参数生成失败');
        }
        $appKey = self::getRequiredConfig('app_key');
        return [
            'config' => [
                'mode'      => $mode,
                'signData'  => $signDataJson,
                'paySig'    => self::hmacSha256Hex(self::getMethod() . '&' . $signDataJson, $appKey),
                'signature' => self::hmacSha256Hex($signDataJson, $sessionKey),
            ],
        ];
    }

    /**
     * 按用户全部有效小程序 openid 查询订单（避免脏授权导致用错 openid 查不到、无法入账）
     * @return array 微信 order 字段
     */
    private static function queryOrderByUser(string $orderSn, int $userId): array
    {
        $openids = UserAuth::where([
            'user_id'  => $userId,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])
            ->where('openid', 'not like', '%\_retired\_%')
            ->order('id', 'desc')
            ->column('openid');
        $openids = array_values(array_unique(array_filter(array_map('strval', $openids))));
        if (empty($openids)) {
            throw new \Exception('用户小程序openid不存在');
        }

        $lastError = null;
        foreach ($openids as $openid) {
            try {
                return self::queryOrder($orderSn, $openid);
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }
        throw new \Exception($lastError ? $lastError->getMessage() : '查询虚拟支付订单失败');
    }

    /**
     * 查询微信侧现金单状态
     * @return array 微信 order 字段
     */
    private static function queryOrder(string $orderSn, string $openid): array
    {
        $body = [
            'openid'   => $openid,
            'env'      => (int)self::getConfig('env', 0),
            'order_id' => $orderSn,
        ];
        $result = self::callXpayApi('/xpay/query_order', $body);
        if ((int)($result['errcode'] ?? -1) !== 0) {
            throw new \Exception('查询虚拟支付订单失败:' . ($result['errmsg'] ?? json_encode($result, JSON_UNESCAPED_UNICODE)));
        }
        $order = $result['order'] ?? [];
        if (!is_array($order) || empty($order)) {
            throw new \Exception('查询虚拟支付订单为空');
        }
        return $order;
    }

    /**
     * 通知微信已发货完成（推送失败时的补偿）
     */
    private static function notifyProvideGoods(string $orderSn): void
    {
        $body = [
            'order_id' => $orderSn,
            'env'      => (int)self::getConfig('env', 0),
        ];
        $result = self::callXpayApi('/xpay/notify_provide_goods', $body);
        $errcode = (int)($result['errcode'] ?? -1);
        // 0 成功；部分重复通知场景也可忽略
        if ($errcode !== 0) {
            throw new \Exception($result['errmsg'] ?? ('errcode:' . $errcode));
        }
    }

    /**
     * 调用微信 xpay 接口（pay_sig = hex(hmac_sha256(appKey, uri + '&' + body))）
     */
    private static function callXpayApi(string $uri, array $body): array
    {
        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($bodyJson === false) {
            throw new \Exception('虚拟支付请求参数编码失败');
        }

        $appKey = self::getRequiredConfig('app_key');
        $paySig = self::hmacSha256Hex($uri . '&' . $bodyJson, $appKey);
        $accessToken = self::getMnpAccessToken();
        $url = 'https://api.weixin.qq.com' . $uri
            . '?access_token=' . urlencode($accessToken)
            . '&pay_sig=' . urlencode($paySig);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            throw new \Exception('请求微信虚拟支付接口失败:' . $error);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \Exception('微信虚拟支付接口响应异常');
        }
        return $data;
    }

    private static function getMnpAccessToken(): string
    {
        $config = WeChatConfigService::getMnpConfig();
        $appId = (string)($config['app_id'] ?? '');
        $secret = (string)($config['secret'] ?? '');
        if ($appId === '' || $secret === '') {
            throw new \Exception('请先设置小程序配置');
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        $payload = json_encode([
            'grant_type' => 'client_credential',
            'appid'      => $appId,
            'secret'     => $secret,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            throw new \Exception('获取小程序access_token失败:' . $error);
        }
        $data = json_decode($response, true);
        $token = (string)($data['access_token'] ?? '');
        if ($token === '') {
            throw new \Exception('获取小程序access_token失败:' . ($data['errmsg'] ?? $response));
        }
        return $token;
    }

    private static function getUserMnpOpenid(int $userId): string
    {
        // 排除历史退役脏数据，取最新有效小程序授权
        $auth = UserAuth::where([
            'user_id'  => $userId,
            'terminal' => UserTerminalEnum::WECHAT_MMP,
        ])
            ->where('openid', 'not like', '%\_retired\_%')
            ->order('id', 'desc')
            ->findOrEmpty();
        $openid = (string)($auth->openid ?? '');
        if ($openid === '') {
            throw new \Exception('用户小程序openid不存在');
        }
        return $openid;
    }

    /**
     * @return array{ok:bool,body:string,content_type:string}
     */
    private static function notifyResponse(bool $ok, array $params, string $errMsg = ''): array
    {
        $wantsXml = !empty($params['_raw_is_xml']);
        if ($wantsXml) {
            $code = $ok ? 0 : -1;
            $msg = htmlspecialchars($ok ? 'success' : ($errMsg ?: 'fail'), ENT_XML1);
            return [
                'ok'           => $ok,
                'body'         => "<xml><ErrCode>{$code}</ErrCode><ErrMsg><![CDATA[{$msg}]]></ErrMsg></xml>",
                'content_type' => 'application/xml; charset=utf-8',
            ];
        }

        return [
            'ok'           => $ok,
            'body'         => json_encode([
                'ErrCode' => $ok ? 0 : -1,
                'ErrMsg'  => $ok ? 'success' : ($errMsg ?: 'fail'),
            ], JSON_UNESCAPED_UNICODE),
            'content_type' => 'application/json; charset=utf-8',
        ];
    }

    private static function checkMnpAuth(int $userId, string $openid): void
    {
        // 必须以 openid 为准：同用户可能残留多条 terminal=小程序 的脏数据，
        // 仅按 terminal 查询会误命中旧行导致「登录态异常」。
        $auth = UserAuth::where([
            'user_id' => $userId,
            'openid'  => $openid,
        ])->findOrEmpty();
        if ($auth->isEmpty()) {
            throw new \Exception('小程序登录态异常，请重新登录');
        }
        // 纠正历史脏数据的终端标记，便于后续查询
        if ((int)$auth->terminal !== UserTerminalEnum::WECHAT_MMP) {
            $auth->terminal = UserTerminalEnum::WECHAT_MMP;
            $auth->save();
        }
    }

    /**
     * 微信虚拟支付签名：to_hex(hmac_sha256(key, msg))
     */
    private static function hmacSha256Hex(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key);
    }

    private static function getMethod(): string
    {
        // 与 wx.requestVirtualPayment 签名 uri 固定一致
        return self::SIGN_METHOD;
    }

    private static function getRequiredConfig(string $name): string
    {
        $value = (string)self::getConfig($name, '');
        if ($value === '') {
            throw new \Exception('请先设置小程序虚拟支付配置:' . $name);
        }
        return $value;
    }

    private static function getConfig(string $name, $default = '')
    {
        $config = self::getVirtualPayConfig();
        return $config[$name] ?? $default;
    }

    /**
     * 仅限制新发起的虚拟支付，历史订单回调仍按配置密钥验签。
     */
    private static function checkVirtualPayEnabled(): void
    {
        $config = self::getVirtualPayConfig();
        if ((int)($config['mnp_pay_type'] ?? 1) !== self::MNP_PAY_TYPE_VIRTUAL) {
            throw new \Exception('请先在后台切换为小程序虚拟支付');
        }
    }

    /**
     * 从后台微信支付配置读取小程序虚拟支付配置，不再依赖 config/project.php。
     */
    private static function getVirtualPayConfig(): array
    {
        if (!empty(self::$virtualConfig)) {
            return self::$virtualConfig;
        }

        $pay = PayConfig::where(['pay_way' => PayEnum::WECHAT_PAY])->findOrEmpty();
        if ($pay->isEmpty()) {
            throw new \Exception('请先设置微信支付配置');
        }

        $config = $pay['config'] ?? [];
        if (!is_array($config)) {
            $config = [];
        }
        $virtualConfig = $config['mnp_virtual_pay'] ?? [];
        if (!is_array($virtualConfig)) {
            $virtualConfig = [];
        }

        self::$virtualConfig = [
            'mnp_pay_type'  => (int)($config['mnp_pay_type'] ?? 1),
            'offer_id'      => $virtualConfig['offer_id'] ?? '',
            'app_key'       => $virtualConfig['app_key'] ?? '',
            'env'           => (int)($virtualConfig['env'] ?? 0),
            'currency_type' => $virtualConfig['currency_type'] ?? 'CNY',
            'mode'          => $virtualConfig['mode'] ?? 'short_series_goods',
            'method'        => $virtualConfig['method'] ?? self::SIGN_METHOD,
        ];
        return self::$virtualConfig;
    }
}
