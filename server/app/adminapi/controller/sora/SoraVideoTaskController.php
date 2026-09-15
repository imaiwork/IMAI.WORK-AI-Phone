<?php

namespace app\adminapi\controller\sora;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sora\SoraVideoTaskLists;
use app\adminapi\logic\sora\SoraVideoTaskLogic;
use app\adminapi\validate\sora\SoraVideoTaskValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class SoraVideoTaskController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new SoraVideoTaskLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = SoraVideoTaskLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(SoraVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
