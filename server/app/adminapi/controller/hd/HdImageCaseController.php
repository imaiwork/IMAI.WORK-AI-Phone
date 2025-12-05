<?php

namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdImageCaseLists;
use app\adminapi\logic\hd\HdImageCaseLogic;
use app\adminapi\validate\hd\HdImageCaseValidate;


/**
 * HdImageCases控制器
 * Class HdImageCasesController
 * @package app\adminapi\controller\hd
 */
class HdImageCaseController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdImageCaseLists());
    }

    /**
     * @desc 添加
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function add()
    {
        $params = (new HdImageCaseValidate())->post()->goCheck('add');
        $result = HdImageCaseLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(HdImageCaseLogic::getError());
    }


    /**
     * @desc 编辑
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function edit()
    {
        $params = (new HdImageCaseValidate())->post()->goCheck('edit');

        $result = HdImageCaseLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(HdImageCaseLogic::getError());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new HdImageCaseValidate())->post()->goCheck('delete');
        return HdImageCaseLogic::delete($params) ? $this->success() : $this->fail(HdImageCaseLogic::getError());
    }


    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = (new HdImageCaseValidate())->goCheck('detail');
        $result = HdImageCaseLogic::detail($params['id']);
        return $this->data($result);
    }

    /**
     * @desc 修改状态
     * @return \think\response\Json
     * @date 2024/12/25 15:50
     * @author dagouzi
     */
    public function changeStatus()
    {
        $params = (new HdImageCaseValidate())->post()->goCheck('updateStatus');
        $result = HdImageCaseLogic::changeStatus($params['id'], $params['status']);
        if (true === $result) {
            return $this->success('修改成功', HdImageCaseLogic::getReturnData());
        }
        return $this->fail(HdImageCaseLogic::getError());
    }
}
