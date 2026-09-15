<?php



namespace app\common\service\pay;


use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\PayNotifyLogic;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\UserAuth;
use app\common\service\wechat\WeChatConfigService;
use EasyWeChat\Pay\Application;
use EasyWeChat\Pay\Message;
use think\facade\Log;


/**
 * 微信支付
 * Class WeChatPayService
 * @package app\common\server
 */
class WeChatPayService extends BasePayService
{
    /**
     * 授权信息
     * @var UserAuth|array|\think\Model
     */
    protected $auth;


    /**
     * 微信配置
     * @var
     */
    protected $config;


    /**
     * easyWeChat实例
     * @var
     */
    protected $app;


    /**
     * 当前使用客户端
     * @var
     */
    protected $terminal;


    /**
     * 本次下单使用的微信应用 appid
     * @var string
     */
    protected string $appId = '';


    /**
     * 初始化微信支付配置
     * @param $terminal //用户终端
     * @param null $userId //用户id(获取授权openid)
     */
    public function __construct($terminal, $userId = null)
    {
        $this->terminal = $terminal;
        $this->config   = WeChatConfigService::getPayConfigByTerminal($terminal);
        $this->appId    = WeChatConfigService::getPayAppIdByTerminal($terminal);
        $this->app      = new Application($this->config);
        if ($userId !== null) {
            // 必须取「本次下单 appid 名下」的 openid:同一用户在 OEM 独立小程序与主站小程序
            // 是两个 openid,按 user_id+terminal 盲取会命中 OEM 那条,
            // 导致主站 appid + OEM openid 下单,微信报 PARAM_ERROR-appid和openid不匹配
            $this->auth = UserAuth::findByAppId((int)$userId, (int)$terminal, $this->appId);
        }
    }


    /**
     * @notes 发起微信支付统一下单
     * @param $from
     * @param $order
     * @return array|false|string
     * @author 段誉
     * @date 2021/8/4 15:05
     */
    public function pay($from, $order)
    {
        try {
            // appid 统一取自构造函数(与 openid 取号同源),避免两处各查一次配置后不一致
            switch ($this->terminal) {
                case UserTerminalEnum::WECHAT_MMP:
                case UserTerminalEnum::WECHAT_OA:
                    $result = $this->jsapiPay($from, $order, $this->appId);
                    break;
                case UserTerminalEnum::IOS:
                case UserTerminalEnum::ANDROID:
                    $result = $this->appPay($from, $order, $this->appId);
                    break;
                case UserTerminalEnum::H5:
                    $result = $this->mwebPay($from, $order, $this->appId);
                    break;
                case UserTerminalEnum::PC:
                    $result = $this->nativePay($from, $order, $this->appId);
                    break;
                default:
                    throw new \Exception('支付方式错误');
            }

            return [
                'config'  => $result,
                'pay_way' => PayEnum::WECHAT_PAY
            ];
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes jsapiPay
     * @param $from
     * @param $order
     * @param $appId
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 12:12
     */
    public function jsapiPay($from, $order, $appId)
    {
        $openid = (string)($this->auth['openid'] ?? '');
        if ($openid === '') {
            // 只在别处授权过(如 OEM 独立小程序)、或从未微信授权的账号:
            // 与其把不匹配的 appid/openid 丢给微信换一句看不懂的 PARAM_ERROR,不如直接引导重新授权
            throw new \Exception('当前微信账号未在本站授权，请用微信登录后再支付');
        }

        $response = $this->app->getClient()->postJson("v3/pay/transactions/jsapi", [
            "appid"        => $appId,
            "mchid"        => $this->config['mch_id'],
            "description"  => $this->payDesc($from),
            "out_trade_no" => $order['pay_sn'],
            "notify_url"   => $this->config['notify_url'],
            "amount"       => [
                "total" => intval($order['order_amount'] * 100),
            ],
            "payer"        => [
                "openid" => $openid
            ],
            'attach'       => $from
        ]);

        $result = $response->toArray(false);
        $this->checkResultFail($result);
        return $this->getPrepayConfig($result['prepay_id'], $appId);
    }


    /**
     * @notes 网站native
     * @param $from
     * @param $order
     * @param $appId
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 12:12
     */
    public function nativePay($from, $order, $appId)
    {
        $response = $this->app->getClient()->postJson('v3/pay/transactions/native', [
            'appid'        => $appId,
            'mchid'        => $this->config['mch_id'],
            'description'  => $this->payDesc($from),
            'out_trade_no' => $order['pay_sn'],
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total' => intval($order['order_amount'] * 100),
            ],
            'attach'       => $from
        ]);
        $result   = $response->toArray(false);
        $this->checkResultFail($result);
        return $result['code_url'];
    }


    /**
     * @notes appPay
     * @param $from
     * @param $order
     * @param $appId
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 12:12
     */
    public function appPay($from, $order, $appId)
    {
        $response = $this->app->getClient()->postJson('v3/pay/transactions/app', [
            'appid'        => $appId,
            'mchid'        => $this->config['mch_id'],
            'description'  => $this->payDesc($from),
            'out_trade_no' => $order['pay_sn'],
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total' => intval($order['order_amount'] * 100),
            ],
            'attach'       => $from
        ]);
        $result   = $response->toArray(false);
        $this->checkResultFail($result);
        return $result['prepay_id'];
    }


    /**
     * @notes h5
     * @param $from
     * @param $order
     * @param $appId
     * @param $redirectUrl
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 12:13
     */
    public function mwebPay($from, $order, $appId)
    {
        $ip = request()->ip();
        if (!empty(env('project.test_web_ip')) && env('APP_DEBUG')) {
            $ip = env('project.test_web_ip');
        }

        $response = $this->app->getClient()->postJson('v3/pay/transactions/h5', [
            'appid'        => $appId,
            'mchid'        => $this->config['mch_id'],
            'description'  => $this->payDesc($from),
            'out_trade_no' => $order['pay_sn'],
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total' => intval(strval($order['order_amount'] * 100)),
            ],
            'attach'       => $from,
            'scene_info'   => [
                'payer_client_ip' => $ip,
                'h5_info'         => [
                    'type' => 'Wap',
                ]
            ]
        ]);
        $result   = $response->toArray(false);
        $this->checkResultFail($result);

        $domain = request()->domain();
        if (!empty(env('project.test_web_domain')) && env('APP_DEBUG')) {
            $domain = env('project.test_web_domain');
        }
        $redirectUrl = $domain . '/mobile' . $order['redirect_url'] . '?id=' . $order['id'] . '&from=' . $from . '&checkPay=true';
        return $result['h5_url'] . '&redirect_url=' . urlencode($redirectUrl);
    }


    /**
     * @notes 退款
     * @param array $refundData
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 16:53
     */
    public function refund(array $refundData)
    {
        $response = $this->app->getClient()->postJson('v3/refund/domestic/refunds', [
            'transaction_id' => $refundData['transaction_id'],
            'out_refund_no'  => $refundData['refund_sn'],
            'amount'         => [
                'refund'   => intval($refundData['refund_amount'] * 100),
                'total'    => intval($refundData['total_amount'] * 100),
                'currency' => 'CNY',
            ]
        ]);
        $result   = $response->toArray(false);
        $this->checkResultFail($result);
        return $result;
    }


    /**
     * @notes 查询退款
     * @param $refundSn
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/3/1 11:16
     */
    public function queryRefund($refundSn)
    {
        $response = $this->app->getClient()->get("v3/refund/domestic/refunds/{$refundSn}");
        return $response->toArray(false);
    }


    /**
     * @notes 支付描述
     * @param $from
     * @return string
     * @author 段誉
     * @date 2023/2/27 17:54
     */
    public function payDesc($from)
    {
        $desc = [
            'order'    => '商品',
            'recharge' => '充值',
        ];
        return $desc[$from] ?? '商品';
    }


    /**
     * @notes 捕获错误
     * @param $result
     * @throws \Exception
     * @author 段誉
     * @date 2023/2/28 12:09
     */
    public function checkResultFail($result)
    {
        if (!empty($result['code']) || !empty($result['message'])) {
            throw new \Exception('微信:' . $result['code'] . '-' . $result['message']);
        }
    }


    /**
     * @notes 预支付配置
     * @param $prepayId
     * @param $appId
     * @return mixed[]
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @author 段誉
     * @date 2023/2/28 17:38
     */
    public function getPrepayConfig($prepayId, $appId)
    {
        return $this->app->getUtils()->buildBridgeConfig($prepayId, $appId);
    }


    /**
     * @notes 支付回调
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 段誉
     * @date 2023/2/28 14:20
     */
    public function notify()
    {
        try {
            $server = $this->app->getServer();
            // 支付通知
            $server->handlePaid(function (Message $message) {
                Log::write("解密数据-" . $message, "wxPay");
                if ($message['trade_state'] === 'SUCCESS') {
                    $extra['transaction_id'] = $message['transaction_id'];
                    $attach = $message['attach'];
                    $message['out_trade_no'] = mb_substr($message['out_trade_no'], 0, 18);
                    switch ($attach) {
                        case 'recharge':
                            $order = RechargeOrder::where(['sn' => $message['out_trade_no']])->findOrEmpty();
                            if ($order->isEmpty() || $order->pay_status == PayEnum::ISPAID) {
                                return true;
                            }
                            PayNotifyLogic::handle('recharge', $message['out_trade_no'], $extra);
                            break;
                        case 'tokens':
                            $order = GiftPackageOrder::where(['sn' => $message['out_trade_no']])->findOrEmpty();
                            if ($order->isEmpty() || $order->pay_status == PayEnum::ISPAID) {
                                return true;
                            }
                            PayNotifyLogic::handle('tokens', $message['out_trade_no'], $extra);
                            break;
                        case 'device_auth':
                            $order = DeviceAuthOrder::where(['sn' => $message['out_trade_no']])->findOrEmpty();
                            if ($order->isEmpty() || $order->pay_status == PayEnum::ISPAID) {
                                return true;
                            }
                            PayNotifyLogic::handle('deviceAuth', $message['out_trade_no'], $extra);
                            break;
                    }
                }
                return true;
            });

            // 退款通知
            $server->handleRefunded(function (Message $message) {
                return true;
            });
            return $server->serve();
        }catch (\Exception $e) {
            Log::write("回调报错-" . $e->getMessage(), "wxPayerror");
            return false;
        }
    }
}
