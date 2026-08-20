<?php


namespace app\api\controller;


use app\api\validate\PayValidate;
use app\common\enum\user\UserTerminalEnum;
use app\common\logic\PaymentLogic;
use app\common\service\pay\AliPayService;
use app\common\service\pay\MnpVirtualPayService;
use app\common\service\pay\WeChatPayService;

/**
 * 支付
 * Class PayController
 * @package app\api\controller
 */
class PayController extends BaseApiController
{

    public array $notNeedLogin = ['notifyMnp', 'notifyOa', 'aliNotify', 'mnpVirtualNotify'];

    /**
     * @notes 支付方式
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 17:54
     */
    public function payWay()
    {
        $params = (new PayValidate())->post()->goCheck('payway');
        $result = PaymentLogic::getPayWay($this->userId, $this->userInfo['terminal'], $params);
        if ($result === false) {
            return $this->fail(PaymentLogic::getError());
        }
        return $this->data($result);
    }


    /**
     * @notes 预支付
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/28 14:21
     */
    public function prepay()
    {
        // 仅校验预支付必要字段，避免命中虚拟支付专用的 code 等规则
        $params = (new PayValidate())->post()->goCheck('prepay');
        //订单信息
        $order = PaymentLogic::getPayOrderInfo($params);
        if (false === $order) {
            return $this->fail(PaymentLogic::getError(), $params);
        }
        //支付流程
        $redirectUrl = $params['redirect'] ?? '/pages/payment/payment';
        $result = PaymentLogic::pay($params['pay_way'], $params['from'], $order, $this->userInfo['terminal'], $redirectUrl);
        if (false === $result) {
            return $this->fail(PaymentLogic::getError(), $params);
        }
        return $this->success('', $result);
    }


    /**
     * @notes 获取支付状态
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/3/1 16:23
     */
    public function payStatus()
    {
        $params = (new PayValidate())->goCheck('status', ['user_id' => $this->userId]);
        $result = PaymentLogic::getPayStatus($params);
        if ($result === false) {
            return $this->fail(PaymentLogic::getError());
        }
        return $this->data($result);
    }


    /**
     * @notes 小程序虚拟支付预下单
     * @return \think\response\Json
     */
    public function mnpVirtualPrepay()
    {
        $params = (new PayValidate())->post()->goCheck('mnpVirtualPrepay', [
            'user_id' => $this->userId,
        ]);
        $result = MnpVirtualPayService::prepay($params);
        if ($result === false) {
            return $this->fail(MnpVirtualPayService::getError(), $params);
        }
        return $this->success('', $result);
    }


    /**
     * @notes 小程序虚拟支付结果查询
     * @return \think\response\Json
     */
    public function mnpVirtualConfirm()
    {
        $params = (new PayValidate())->post()->goCheck('mnpVirtualConfirm', [
            'user_id' => $this->userId,
        ]);
        $result = MnpVirtualPayService::confirm($params);
        if ($result === false) {
            return $this->fail(MnpVirtualPayService::getError(), $params);
        }
        return $this->data($result);
    }


    /**
     * @notes 小程序虚拟支付回调（消息推送 xpay_goods_deliver_notify 等）
     * 请在小程序后台「开发管理-开发设置-消息推送」或虚拟支付发货推送中配置为本接口地址
     * @return \think\response\Response
     */
    public function mnpVirtualNotify()
    {
        $params = $this->request->param();
        $raw = $this->request->getInput();
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $params = array_merge($params, $json);
            } else if (stripos($raw, '<xml') !== false) {
                $params['_raw_is_xml'] = true;
                $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml !== false) {
                    $params = array_merge($params, json_decode(json_encode($xml), true) ?: []);
                    $params['_raw_is_xml'] = true;
                }
            }
        }

        $result = MnpVirtualPayService::notify($params);
        return response($result['body'] ?? 'fail', 200, [
            'Content-Type' => $result['content_type'] ?? 'text/plain; charset=utf-8',
        ]);
    }


    /**
     * @notes 小程序支付回调
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 段誉
     * @date 2023/2/28 14:21
     */
    public function notifyMnp()
    {
        return (new WeChatPayService(UserTerminalEnum::WECHAT_MMP))->notify();
    }


    /**
     * @notes 公众号支付回调
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \ReflectionException
     * @throws \Throwable
     * @author 段誉
     * @date 2023/2/28 14:21
     */
    public function notifyOa()
    {
        return (new WeChatPayService(UserTerminalEnum::WECHAT_OA))->notify();
    }

    /**
     * @notes 支付宝回调
     * @author mjf
     * @date 2024/3/18 16:50
     */
    public function aliNotify()
    {
        $params = $this->request->post();
        $result = (new AliPayService())->notify($params);
        if (true === $result) {
            echo 'success';
        } else {
            echo 'fail';
        }
    }
}
