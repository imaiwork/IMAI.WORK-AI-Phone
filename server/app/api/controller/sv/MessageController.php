<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\MessageValidate;
use app\api\logic\sv\MessageLogic;

/**
 * MessageController
 * @desc 消息
 * @author Qasim
 */
class MessageController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 打招呼
     */
    public function greet()
    {
        try {
            $params = (new MessageValidate())->post()->goCheck('greet');
            $result = MessageLogic::greetMessage($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MessageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 发送消息
     */
    public function send()
    {
        try {
            $params = (new MessageValidate())->post()->goCheck('send');
            $result = MessageLogic::sendMessage($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MessageLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
