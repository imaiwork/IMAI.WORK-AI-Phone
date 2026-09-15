<?php

namespace app\api\controller\coze;

use app\api\controller\BaseApiController;
use app\api\lists\coze\CozeConfigLists;
use app\api\logic\coze\CozeConfigLogic;
use app\api\validate\coze\CozeConfigValidate;
use think\exception\HttpResponseException;

class CozeConfigController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new CozeConfigValidate())->post()->goCheck('add');
            $result = CozeConfigLogic::add($params);
            if ($result) {
                return $this->success(data: CozeConfigLogic::getReturnData());
            }
            return $this->fail(CozeConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new CozeConfigValidate())->post()->goCheck('update');
            $result = CozeConfigLogic::update($params);
            if ($result) {
                return $this->success(data: CozeConfigLogic::getReturnData());
            }
            return $this->fail(CozeConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new CozeConfigValidate())->post()->goCheck('delete');
            $result = CozeConfigLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CozeConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $result = CozeConfigLogic::get();
            if ($result) {
                return $this->data(CozeConfigLogic::getReturnData());
            }
            return $this->fail(CozeConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}


