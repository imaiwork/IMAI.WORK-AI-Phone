<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\MaterialLists;
use app\api\logic\aiPersona\MaterialLogic;
use app\api\validate\aiPersona\MaterialValidate;
use think\exception\HttpResponseException;

class MaterialController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new MaterialValidate())->post()->goCheck('add');
            $result = MaterialLogic::add($params);
            if ($result) {
                return $this->success('添加成功');
            }
            return $this->fail(MaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function addBatch()
    {
        try {
            $params = (new MaterialValidate())->post()->goCheck('addBatch');
            $result = MaterialLogic::addBatch($params);
            if ($result) {
                return $this->success('批量添加成功', MaterialLogic::getReturnData());
            }
            return $this->fail(MaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new MaterialValidate())->post()->goCheck('update');
            $result = MaterialLogic::update($params);
            if ($result) {
                return $this->success(data: MaterialLogic::getReturnData());
            }
            return $this->fail(MaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new MaterialValidate())->post()->goCheck('delete');
            $result = MaterialLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new MaterialValidate())->get()->goCheck('detail');
            $result = MaterialLogic::detail($params);
            if ($result) {
                return $this->data(MaterialLogic::getReturnData());
            }
            return $this->fail(MaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new MaterialLists());
    }

    public function updateStatus()
    {
        try {
            $params = $this->request->post();
            $result = MaterialLogic::updateStatus($params);
            if ($result) {
                return $this->success('更新成功');
            }
            return $this->fail(MaterialLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
