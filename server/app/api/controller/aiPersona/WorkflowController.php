<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;

use think\exception\HttpResponseException;
use app\api\validate\aiPersona\WorkflowValidate;
use app\api\logic\aiPersona\WorkflowLogic;
use app\api\lists\aiPersona\WorkflowTemplateLists;

class WorkflowController extends BaseApiController
{
    public function detail()
    {
        try {
            $params = (new WorkflowValidate())->get()->goCheck('detail');
            $result = WorkflowLogic::detail($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detailTemplate(){
        try {
            $params = (new WorkflowValidate())->get()->goCheck('detailTemplate');
            $result = WorkflowLogic::detailTemplate($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @notes 可添加的任务场景列表
     */
    public function sceneLists()
    {
        try {
            $result = WorkflowLogic::sceneLists();
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function category()
    {
        try {
            $params = (new WorkflowValidate())->get()->goCheck('category');
            $result = WorkflowLogic::category($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new WorkflowTemplateLists());
    }


    public function add()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('add');
            $result = WorkflowLogic::add($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function update()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('update');
            $result = WorkflowLogic::update($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('delete');
            $result = WorkflowLogic::delete($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function addNode()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('addNode');
            $result = WorkflowLogic::addNode($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function reset()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('reset');
            $result = WorkflowLogic::reset($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function updateNode()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('updateNode');
            $result = WorkflowLogic::updateNode($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function changeStatusNode(){
        try {
            $params = (new WorkflowValidate())->post()->goCheck('changeStatusNode');
            $result = WorkflowLogic::changeStatusNode($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function use()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('use');
            $result = WorkflowLogic::use($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function copyTemplate()
    {
        try {
            $params = (new WorkflowValidate())->post()->goCheck('copyTemplate');
            $result = WorkflowLogic::copyTemplate($params);
            if ($result) {
                return $this->success(data: WorkflowLogic::getReturnData());
            }
            return $this->fail(WorkflowLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
