<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\SvMediaMaterialGroupLists;
use app\api\logic\sv\SvMediaMaterialGroupLogic;
use app\api\validate\sv\SvMediaMaterialGroupValidate;
use think\exception\HttpResponseException;

class MediaMaterialGroupController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new SvMediaMaterialGroupValidate())->post()->goCheck('add');
            $result = SvMediaMaterialGroupLogic::addSvMediaMaterialGroup($params);
            if ($result) {
                return $this->data(SvMediaMaterialGroupLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SvMediaMaterialGroupValidate())->post()->goCheck('update');
            $result = SvMediaMaterialGroupLogic::updateSvMediaMaterialGroup($params);
            if ($result) {
                return $this->success(data: SvMediaMaterialGroupLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvMediaMaterialGroupValidate())->post()->goCheck('delete');
            $result = SvMediaMaterialGroupLogic::deleteSvMediaMaterialGroup($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMediaMaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvMediaMaterialGroupValidate())->get()->goCheck('detail');
            $result = SvMediaMaterialGroupLogic::getSvMediaMaterialGroup($params);
            if ($result) {
                return $this->data(SvMediaMaterialGroupLogic::getReturnData());
            }
            return $this->fail(SvMediaMaterialGroupLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new SvMediaMaterialGroupLists());
    }
}