<?php

namespace app\adminapi\controller\storyboard;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\storyboard\StoryboardVideoTaskLists;
use app\adminapi\logic\storyboard\StoryboardVideoTaskLogic;
use think\exception\HttpResponseException;
use think\response\Json;

class StoryboardVideoTaskController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new StoryboardVideoTaskLists());
    }

    public function delete(): Json
    {
        try {
            $params = $this->request->post();
            $result = StoryboardVideoTaskLogic::delete($params['id']);
            return $result ? $this->success('删除成功', [], 1, 1) : $this->fail(StoryboardVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
