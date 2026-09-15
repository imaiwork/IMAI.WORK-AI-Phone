<?php

namespace app\adminapi\controller\sora;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sora\SoraVideoSettingLists;
use app\adminapi\logic\sora\SoraVideoSettingLogic;
use app\adminapi\validate\sora\SoraVideoSettingValidate;
use think\exception\HttpResponseException;
use think\response\Json;

class SoraVideoSettingController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new SoraVideoSettingLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = SoraVideoSettingLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(SoraVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
