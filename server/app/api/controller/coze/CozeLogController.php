<?php

namespace app\api\controller\coze;

use app\api\controller\BaseApiController;
use app\api\lists\coze\CozeLogLists;
use app\api\logic\coze\CozeLogLogic;
use app\api\validate\coze\CozeLogValidate;
use think\exception\HttpResponseException;

class CozeLogController extends BaseApiController
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
}
