<?php

namespace app\api\controller\catering;

use app\api\controller\BaseApiController;
use app\api\lists\catering\CateringFranchiseLists;
use app\api\logic\catering\CateringFranchiseLogic;
use app\api\validate\catering\CateringFranchiseValidate;
use think\exception\HttpResponseException;

class CateringFranchiseController extends BaseApiController
{
    public array $notNeedLogin = ['lists', 'detail'];

    public function lists()
    {
        return $this->dataLists(new CateringFranchiseLists());
    }

    public function detail()
    {
        try {
            $params = (new CateringFranchiseValidate())->get()->goCheck('detail');
            $result = CateringFranchiseLogic::get($params);
            if ($result) {
                return $this->data(CateringFranchiseLogic::getReturnData());
            }
            return $this->fail(CateringFranchiseLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
