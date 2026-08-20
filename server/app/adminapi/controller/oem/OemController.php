<?php

namespace app\adminapi\controller\oem;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\oem\OemLists;
use think\exception\HttpResponseException;
use app\adminapi\validate\oem\OemValidate;
use app\adminapi\logic\oem\OemLogic;
use app\adminapi\logic\team\TeamLogic;

/**
 * oem列表
 * Class OemController
 * @package app\adminapi\controller\oem
 */
class OemController extends BaseAdminController
{
    /**
     * @notes 列表
     */
    public function lists()
    {
        return $this->dataLists(new OemLists());
    }
    
    public function getInfo()
    {
        $result = OemLogic::getOemInfo();
        return $this->data($result);
    }


    /**
     * @notes  添加oem
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new OemValidate())->post()->goCheck('add');
        $result = OemLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(OemLogic::getError());
    }

    /**
     * @notes  编辑oem
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new OemValidate())->post()->goCheck('edit');
        $result = OemLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(OemLogic::getError());
    }

    /**
     * @notes  删除资讯
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new OemValidate())->post()->goCheck('delete');
        $result = OemLogic::delete($params);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(OemLogic::getError());
    }

    /**
     * @notes  资讯详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new OemValidate())->goCheck('detail');
        $result = OemLogic::detail($params);
        return $this->data($result);
    }


    /**
     * @notes  更改资讯状态
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        $params = (new OemValidate())->post()->goCheck('status');
        $result = OemLogic::changeStatus($params);
        if (true === $result) {
            return $this->success('修改成功', [], 1, 1);
        }
        return $this->fail(OemLogic::getError());
    }

    /**
     * @notes OEM收费配置读取
     */
    public function oemPricing()
    {
        return $this->data(TeamLogic::oemPricing());
    }

    /**
     * @notes OEM收费配置保存
     */
    public function saveOemPricing()
    {
        $res = TeamLogic::saveOemPricing($this->request->post());
        return $res ? $this->success('保存成功', [], 1, 0) : $this->fail(TeamLogic::getError());
    }
}
