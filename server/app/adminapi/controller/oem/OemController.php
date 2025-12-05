<?php

namespace app\adminapi\controller\oem;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\oem\OemLists;
use think\exception\HttpResponseException;
use app\adminapi\validate\oem\OemValidate;
use app\adminapi\logic\oem\OemLogic;

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
}
