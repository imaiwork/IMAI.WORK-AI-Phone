<?php

namespace app\adminapi\controller\coze;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\coze\CozeAgentLists;
use app\adminapi\logic\coze\CozeAgentLogic;
use app\adminapi\validate\coze\CozeAgentValidate;
use think\exception\HttpResponseException;

class CozeAgentController extends BaseAdminController
{
    public function add()
    {
        try {
            $params = (new CozeAgentValidate())->post()->goCheck('add');
            $params['source_id'] = $this->adminId;
            $result = CozeAgentLogic::add($params);
            if ($result) {
                return $this->success(data: CozeAgentLogic::getReturnData());
            }
            return $this->fail(CozeAgentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new CozeAgentValidate())->post()->goCheck('update');
            $result = CozeAgentLogic::update($params);
            if ($result) {
                return $this->success(data: CozeAgentLogic::getReturnData());
            }
            return $this->fail(CozeAgentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params =$this->request->post();
            $result = CozeAgentLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CozeAgentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new CozeAgentValidate())->get()->goCheck('detail');
            $result = CozeAgentLogic::get($params);
            if ($result) {
                return $this->data(CozeAgentLogic::getReturnData());
            }
            return $this->fail(CozeAgentLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new CozeAgentLists());
    }
}
