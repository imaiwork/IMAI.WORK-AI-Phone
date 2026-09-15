<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\ShanjianAnchorLists;
use app\adminapi\logic\shanjian\ShanjianAnchorLogic;
use app\adminapi\validate\shanjian\ShanjianAnchorValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class ShanjianAnchorController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ShanjianAnchorLists());
    }


    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = ShanjianAnchorLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(ShanjianAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}


