<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\MaterialUseLogLists;
use app\api\logic\aiPersona\MaterialUseLogLogic;
use app\api\validate\aiPersona\MaterialUseLogValidate;
use think\exception\HttpResponseException;

class MaterialUseLogController extends BaseApiController
{
    public function update()
    {
        try {
            $params = (new MaterialUseLogValidate())->post()->goCheck('update');
            $result = MaterialUseLogLogic::update($params);
            if ($result) {
                return $this->success(data: MaterialUseLogLogic::getReturnData());
            }
            return $this->fail(MaterialUseLogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new MaterialUseLogValidate())->get()->goCheck('detail');
            $result = MaterialUseLogLogic::detail($params);
            if ($result) {
                return $this->data(MaterialUseLogLogic::getReturnData());
            }
            return $this->fail(MaterialUseLogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new MaterialUseLogLists());
    }
}
