<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\ShanjianVideoSettingLists;
use app\adminapi\logic\shanjian\ShanjianVideoSettingLogic;
use app\adminapi\validate\shanjian\ShanjianVideoSettingValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class ShanjianVideoSettingController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new ShanjianVideoSettingLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = ShanjianVideoSettingLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
