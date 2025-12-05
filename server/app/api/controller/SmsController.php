<?php


namespace app\api\controller;


use app\api\logic\SmsLogic;
use app\api\validate\SendSmsValidate;


/**
 * 短信
 * Class SmsController
 * @package app\api\controller
 */
class SmsController extends BaseApiController
{

    public array $notNeedLogin = ['sendCode'];


    /**
     * @notes 发送短信验证码
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/15 16:17
     */
    public function sendCode()
    {
        $params = (new SendSmsValidate())->post()->goCheck();
        $result = SmsLogic::sendCode($params);
        if (true === $result) {
            return $this->success('发送成功');
        }
        return $this->fail(SmsLogic::getError());
    }
}
