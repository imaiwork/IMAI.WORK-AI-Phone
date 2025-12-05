<?php

namespace app\api\controller\coze;

use app\api\controller\BaseApiController;
use app\api\lists\coze\CozeAgentLists;
use app\api\logic\coze\CozeAgentLogic;
use app\api\validate\coze\CozeAgentValidate;
use think\exception\HttpResponseException;

class CozeAgentController extends BaseApiController
{
    public array $notNeedLogin = ['commonLists'];

    public function add()
    {
        try {
            $params = (new CozeAgentValidate())->post()->goCheck('add');
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
            $params = $this->request->post();
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


    public function commonLists(){
        try {
            $request = $this->request->get();
            $result = CozeAgentLogic::commonLists($request);
            return $this->data($result);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
