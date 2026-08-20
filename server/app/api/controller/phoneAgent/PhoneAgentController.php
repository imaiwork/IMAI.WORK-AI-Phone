<?php

namespace app\api\controller\phoneAgent;

use app\api\controller\BaseApiController;
use app\api\lists\phoneAgent\PhoneAgentHistoryLists;
use app\api\logic\phoneAgent\PhoneAgentLogic;
use app\api\validate\phoneAgent\PhoneAgentValidate;
use think\exception\HttpResponseException;

class PhoneAgentController extends BaseApiController
{
    public function devices()
    {
        try {
            $data = PhoneAgentLogic::devices((int)$this->userId);
            return $this->data($data);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function dispatch()
    {
        try {
            $params = (new PhoneAgentValidate())->post()->goCheck('dispatch');
            $result = PhoneAgentLogic::dispatch($params, (int)$this->userId);
            if ($result) {
                return $this->success(data: PhoneAgentLogic::getReturnData());
            }
            return $this->fail(PhoneAgentLogic::getError(), PhoneAgentLogic::getReturnData() ?: []);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function analyze()
    {
        try {
            $params = (new PhoneAgentValidate())->post()->goCheck('analyze');
            $data = PhoneAgentLogic::analyze($params, (int)$this->userId);
            if ($data === false) {
                return $this->fail(PhoneAgentLogic::getError());
            }
            return $this->data($data);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new PhoneAgentValidate())->get()->goCheck('detail');
            $data = PhoneAgentLogic::detail($params, (int)$this->userId);
            if ($data === false) {
                return $this->fail(PhoneAgentLogic::getError());
            }
            return $this->data($data);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function history()
    {
        return $this->dataLists(new PhoneAgentHistoryLists());
    }

    public function conversationDetail()
    {
        try {
            $params = (new PhoneAgentValidate())->get()->goCheck('conversationDetail');
            $data = PhoneAgentLogic::conversationDetail($params, (int)$this->userId);
            if ($data === false) {
                return $this->fail(PhoneAgentLogic::getError());
            }
            return $this->data($data);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function events()
    {
        try {
            $params = (new PhoneAgentValidate())->get()->goCheck('events');
            $data = PhoneAgentLogic::events($params, (int)$this->userId);
            if ($data === false) {
                return $this->fail(PhoneAgentLogic::getError());
            }
            return $this->data($data);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cancel()
    {
        try {
            $params = (new PhoneAgentValidate())->post()->goCheck('cancel');
            $result = PhoneAgentLogic::cancel($params, (int)$this->userId);
            if ($result) {
                return $this->success(data: PhoneAgentLogic::getReturnData());
            }
            return $this->fail(PhoneAgentLogic::getError(), PhoneAgentLogic::getReturnData() ?: []);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function deleteConversation()
    {
        try {
            $params = (new PhoneAgentValidate())->post()->goCheck('deleteConversation');
            $result = PhoneAgentLogic::deleteConversation($params, (int)$this->userId);
            if ($result) {
                return $this->success('删除成功');
            }
            return $this->fail(PhoneAgentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
