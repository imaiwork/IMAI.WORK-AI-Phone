<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\SvMediaMaterialLists;
use app\api\logic\sv\SvMediaMaterialLogic;
use app\api\validate\sv\SvMediaMaterialValidate;
use think\exception\HttpResponseException;

class MediaMaterialController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new SvMediaMaterialValidate())->post()->goCheck('add');
            $result = SvMediaMaterialLogic::addSvMediaMaterial($params);
            if ($result) {
                return $this->data(SvMediaMaterialLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SvMediaMaterialValidate())->post()->goCheck('update');
            $result = SvMediaMaterialLogic::updateSvMediaMaterial($params);
            if ($result) {
                return $this->success(data: SvMediaMaterialLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvMediaMaterialValidate())->post()->goCheck('delete');
            $result = SvMediaMaterialLogic::deleteSvMediaMaterial($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMediaMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvMediaMaterialValidate())->get()->goCheck('detail');
            $result = SvMediaMaterialLogic::getSvMediaMaterial( $params);
            if ($result) {
                return $this->data(SvMediaMaterialLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new SvMediaMaterialLists());
    }

}