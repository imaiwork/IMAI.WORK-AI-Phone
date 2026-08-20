<?php

namespace app\api\controller\map;

use app\api\controller\BaseApiController;
use app\api\lists\map\MapLeadConversationLists;
use app\api\lists\map\MapLeadMessageLists;
use app\api\logic\map\LeadLogic;
use app\api\validate\map\LeadValidate;
use think\exception\HttpResponseException;

class LeadController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function chat()
    {
        try {
            $params = (new LeadValidate())->post()->goCheck('chat');
            $result = LeadLogic::chat($params);
            if ($result) {
                return $this->success(data: LeadLogic::getReturnData());
            }
            return $this->fail(LeadLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function conversations()
    {
        return $this->dataLists(new MapLeadConversationLists());
    }

    public function messages()
    {
        try {
            (new LeadValidate())->get()->goCheck('messages');
            return $this->dataLists(new MapLeadMessageLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new LeadValidate())->post()->goCheck('delete');
            $result = LeadLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(LeadLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function export()
    {
        try {
            $params = (new LeadValidate())->get()->goCheck('export');
            $result = LeadLogic::export($params);
            if ($result) {
                return $this->success(data: LeadLogic::getReturnData());
            }
            return $this->fail(LeadLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
