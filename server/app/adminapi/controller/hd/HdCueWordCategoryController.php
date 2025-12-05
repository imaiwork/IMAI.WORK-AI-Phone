<?php



namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdCueWordCategoryLists;
use app\adminapi\logic\hd\HdCueWordCategoryLogic;
use app\adminapi\validate\hd\HdCueWordCategoryValidate;


/**
 * HdCueWordCategory控制器
 * Class HdCueWordCategoryController
 * @package app\adminapi\controller\hd
 */
class HdCueWordCategoryController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdCueWordCategoryLists());
    }

    /**
     * @desc 添加
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function add()
    {
        $params = (new HdCueWordCategoryValidate())->post()->goCheck('add');
        $result = HdCueWordCategoryLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(HdCueWordCategoryLogic::getError());
    }


    /**
     * @desc 编辑
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function edit()
    {
        $params = (new HdCueWordCategoryValidate())->post()->goCheck('edit');
        $result = HdCueWordCategoryLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(HdCueWordCategoryLogic::getError());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new HdCueWordCategoryValidate())->post()->goCheck('delete');
        return HdCueWordCategoryLogic::delete($params) ? $this->success() : $this->fail(HdCueWordCategoryLogic::getError());
    }


    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = (new HdCueWordCategoryValidate())->goCheck('detail');
        $result = HdCueWordCategoryLogic::detail($params);
        return $this->data($result);
    }

    /**
     * @desc 修改状态
     * @return \think\response\Json
     * @date 2024/5/23 11:40
     * @author dagouzi
     */
    public function updateStatus()
    {
        $params = (new HdCueWordCategoryValidate())->goCheck('updateStatus');
        HdCueWordCategoryLogic::updateStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
