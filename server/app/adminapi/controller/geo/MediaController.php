<?php

namespace app\adminapi\controller\geo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\geo\GeoMediaLists;
use app\adminapi\logic\geo\GeoMediaLogic;
use app\adminapi\validate\geo\GeoMediaValidate;
use think\response\Json;

/**
 * GEO 管理 · 媒体库(投放渠道)
 */
class MediaController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new GeoMediaLists());
    }

    /** 表单下拉选项(渠道类型/授权平台/AI手机渠道),避免手输 provider_code 错码 */
    public function options(): Json
    {
        return $this->data(GeoMediaLogic::options());
    }

    public function add(): Json
    {
        $params = (new GeoMediaValidate())->post()->goCheck('add');
        $result = GeoMediaLogic::add($params);
        if ($result === false) {
            return $this->fail(GeoMediaLogic::getError());
        }
        return $this->success('添加成功', GeoMediaLogic::getReturnData());
    }

    public function edit(): Json
    {
        $params = (new GeoMediaValidate())->post()->goCheck('edit');
        $result = GeoMediaLogic::edit($params);
        if ($result === false) {
            return $this->fail(GeoMediaLogic::getError());
        }
        return $this->success('编辑成功', GeoMediaLogic::getReturnData());
    }

    public function status(): Json
    {
        $params = (new GeoMediaValidate())->post()->goCheck('status');
        $result = GeoMediaLogic::status($params);
        if ($result === false) {
            return $this->fail(GeoMediaLogic::getError());
        }
        return $this->success('设置成功');
    }

    public function delete(): Json
    {
        $params = (new GeoMediaValidate())->post()->goCheck('delete');
        $result = GeoMediaLogic::delete($params);
        if ($result === false) {
            return $this->fail(GeoMediaLogic::getError());
        }
        return $this->success('删除成功');
    }
}
