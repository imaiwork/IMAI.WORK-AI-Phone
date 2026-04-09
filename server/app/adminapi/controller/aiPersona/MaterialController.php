<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\aiPersona\MaterialLists;
use app\adminapi\logic\aiPersona\MaterialLogic;
use Exception;
use think\response\Json;

class MaterialController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new MaterialLists());
    }

    public function update(): Json
    {
        $params = $this->request->post();
        $result = MaterialLogic::update($params);
        if ($result === false) {
            return $this->fail(MaterialLogic::getError());
        }
        return $this->success('编辑成功', MaterialLogic::getReturnData());
    }

    public function delete(): Json
    {
        $params = $this->request->post();
        $result = MaterialLogic::delete(intval($params['id']));
        if ($result === false) {
            return $this->fail(MaterialLogic::getError());
        }
        return $this->success('删除成功', MaterialLogic::getReturnData());
    }

    public function detail(): Json
    {
        $params = $this->request->get();
        try {
            $result = MaterialLogic::detail(intval($params['id']));
            if ($result === false) {
                return $this->fail(MaterialLogic::getError() ?: '素材不存在');
            }
            return $this->data(MaterialLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
