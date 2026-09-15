<?php

namespace app\adminapi\controller\coze;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\coze\CozeLogLists;
use app\adminapi\logic\coze\CozeLogLogic;
use app\adminapi\validate\coze\CozeLogValidate;
use think\exception\HttpResponseException;

class CozeLogController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new CozeLogLists());
    }


    public function delete()
    {
        try {
            $params = (new CozeLogValidate())->post()->goCheck('delete');
            $result = CozeLogLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CozeLogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function reply()
    {

        try {
            $params = (new CozeLogValidate())->get()->goCheck('reply');
            $result = CozeLogLogic::reply($params);
            if ($result) {
                return $this->success('成功' ,CozeLogLogic::getReturnData(),1,0);
            }
            return $this->fail(CozeLogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }

    }
    
}


