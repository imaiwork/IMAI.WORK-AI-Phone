<?php

namespace app\api\controller\coze;

use app\api\controller\BaseApiController;
use app\api\lists\coze\CozeAgentLists;
use app\api\logic\ChatLogic;
use app\api\logic\coze\CozeAgentLogic;
use app\api\logic\coze\CozeChatLogic;
use app\api\logic\coze\CozeConfigLogic;
use app\api\logic\coze\CozeWorkflowLogic;
use app\api\validate\coze\CozeAgentValidate;
use think\exception\HttpResponseException;

class CozeChatController extends BaseApiController
{
    public function chat()
    {
        try {
            $params = $this->request->post();
            $chat = new CozeChatLogic();
            $result = $chat->chat($params);
            if ($result) {
                return $this->success(data: CozeChatLogic::getReturnData());
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }



    public function retrieve()
    {  try {
        $params = $this->request->get();
        $chat = new CozeChatLogic();
        $result = $chat->retrieve($params);
        if ($result) {
            return $this->success(data: CozeChatLogic::getReturnData());
        }
    } catch (\Exception $e) {
        return $this->fail($e->getMessage());
    }
    }

    public function messagelist()
    {
        try {
            $params = $this->request->get();
            $chat = new CozeChatLogic();
            $result = $chat->chatmessage($params);
            if ($result) {
                return $this->success(data: CozeChatLogic::getReturnData());
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function streamchat(){
        try {
            $params = $this->request->post();
            $chat = new CozeChatLogic();
            $result = $chat->stream($params);
            if ($result) {
                return $this->success(data: CozeChatLogic::getReturnData());
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

}
