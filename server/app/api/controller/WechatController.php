<?php


namespace app\api\controller;

use app\api\logic\WechatLogic;
use app\api\validate\WechatValidate;


/**
 * 微信
 * Class WechatController
 * @package app\api\controller
 */
class WechatController extends BaseApiController
{
    public array $notNeedLogin = ['jsConfig', 'getMnpCodeUrl'];


    /**
     * @notes 微信JSSDK授权接口
     * @return mixed
     * @author 段誉
     * @date 2023/3/1 11:39
     */
    public function jsConfig()
    {
        $params = (new WechatValidate())->goCheck('jsConfig');
        $result = WechatLogic::jsConfig($params);
        if ($result === false) {
            return $this->fail(WechatLogic::getError(), [], 0, 0);
        }
        return $this->data($result);
    }

    /**
     * 获取小程序码
     */
    public function getMnpCodeUrl()
    {
        $params = $this->request->get();
        $result = WechatLogic::getMnpCodeUrl($params);
        if ($result === false) {
            return $this->fail(WechatLogic::getError());
        }
        return $this->data(WechatLogic::getReturnData());
    }
}
