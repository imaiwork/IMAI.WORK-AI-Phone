<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\MaterialGroupLists;
use app\api\logic\aiPersona\MaterialGroupLogic;
use app\api\validate\aiPersona\MaterialGroupValidate;
use think\exception\HttpResponseException;

class MaterialGroupController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new MaterialGroupValidate())->post()->goCheck('add');
            $result = MaterialGroupLogic::add($params);
            if ($result) {
                return $this->success('已新建分组', MaterialGroupLogic::getReturnData());
            }
            return $this->fail(MaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new MaterialGroupValidate())->post()->goCheck('update');
            $result = MaterialGroupLogic::update($params);
            if ($result) {
                return $this->success('分组名称已更新', MaterialGroupLogic::getReturnData());
            }
            return $this->fail(MaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new MaterialGroupValidate())->post()->goCheck('delete');
            $result = MaterialGroupLogic::delete((int)$params['id']);
            if ($result) {
                return $this->success('分组已删除，素材回到未分组');
            }
            return $this->fail(MaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new MaterialGroupLists());
    }
}
