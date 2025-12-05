<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\SvMaterialLists;
use app\api\logic\sv\SvMaterialLogic;
use app\api\validate\sv\SvMaterialValidate;
use think\exception\HttpResponseException;

class MaterialController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new SvMaterialValidate())->post()->goCheck('add');
            $result = SvMaterialLogic::addSvMaterial($params);
            if ($result) {
                return $this->success('添加成功');
            }
            return $this->fail(SvMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SvMaterialValidate())->post()->goCheck('update');
            $result = SvMaterialLogic::updateSvMaterial($params);
            if ($result) {
                return $this->success(data: SvMaterialLogic::getReturnData());
            }
            return $this->fail(SvMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvMaterialValidate())->post()->goCheck('delete');
            $result = SvMaterialLogic::deleteSvMaterial($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvMaterialValidate())->get()->goCheck('detail');
            $result = SvMaterialLogic::getSvMaterial( $params);
            if ($result) {
                return $this->data(SvMaterialLogic::getReturnData());
            }
            return $this->fail(SvMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new SvMaterialLists());
    }

}