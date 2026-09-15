<?php

namespace app\adminapi\controller\coze;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\coze\CozeConfigLists;
use app\adminapi\logic\coze\CozeConfigLogic;
use app\adminapi\validate\coze\CozeConfigValidate;
use think\exception\HttpResponseException;

class CozeConfigController extends BaseAdminController
{
    public function add()
    {
        try {
            $params = (new CozeConfigValidate())->post()->goCheck('add');
            $params['source_id'] = $this->adminId;
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
            return $this->fail(CozeConfigLogic::getError(),[],0,0);
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '',[],0,0);
        }
    }
    
}


