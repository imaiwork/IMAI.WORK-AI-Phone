<?php

namespace app\adminapi\controller\storyboard;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\storyboard\StoryboardVideoSettingLists;
use app\adminapi\logic\storyboard\StoryboardVideoSettingLogic;
use think\exception\HttpResponseException;
use think\response\Json;

class StoryboardVideoSettingController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new StoryboardVideoSettingLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = StoryboardVideoSettingLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(StoryboardVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
