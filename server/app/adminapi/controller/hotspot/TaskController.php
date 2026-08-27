<?php

namespace app\adminapi\controller\hotspot;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hotspot\TaskLists;
use app\adminapi\logic\hotspot\TaskLogic;
use app\adminapi\validate\hotspot\HotspotAdminValidate;

class TaskController extends BaseAdminController
{
    public function lists()
    {
        (new HotspotAdminValidate())->get()->goCheck('taskLists');
        return $this->dataLists(new TaskLists());
    }

    public function detail()
    {
        $params = (new HotspotAdminValidate())->get()->goCheck('id');
        $result = TaskLogic::detail((int)$params['id']);
        if (false === $result) {
            return $this->fail(TaskLogic::getError());
        }
        return $this->data($result);
    }

    public function retry()
    {
        $params = (new HotspotAdminValidate())->post()->goCheck('id');
        $result = TaskLogic::retry((int)$params['id']);
        if (false === $result) {
            return $this->fail(TaskLogic::getError());
        }
        return $this->success('操作成功', $result);
    }

    public function delete()
    {
        $params = (new HotspotAdminValidate())->post()->goCheck('id');
        if (true === TaskLogic::delete($params['id'])) {
            return $this->success('操作成功');
        }
        return $this->fail(TaskLogic::getError());
    }
}
