<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\ShanjianVideoTaskLists;
use app\adminapi\logic\shanjian\ShanjianVideoTaskLogic;
use app\adminapi\validate\shanjian\ShanjianVideoTaskValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class ShanjianVideoTaskController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ShanjianVideoTaskLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = ShanjianVideoTaskLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
