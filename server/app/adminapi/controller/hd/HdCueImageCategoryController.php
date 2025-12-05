<?php



namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdCueImageCategoryLists;
use app\adminapi\logic\hd\HdCueImageCategoryLogic;
use app\adminapi\validate\hd\HdCueImageCategoryValidate;


/**
 * HdCueImageCategory控制器
 * Class HdCueImageCategoryController
 * @package app\adminapi\controller\hd
 */
class HdCueImageCategoryController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdCueImageCategoryLists());
    }

    /**
     * @desc 添加
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function add()
    {
        $params = (new HdCueImageCategoryValidate())->post()->goCheck('add');
        $result = HdCueImageCategoryLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(HdCueImageCategoryLogic::getError());
    }


    /**
     * @desc 编辑
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function edit()
    {
        $params = (new HdCueImageCategoryValidate())->post()->goCheck('edit');
        $result = HdCueImageCategoryLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(HdCueImageCategoryLogic::getError());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new HdCueImageCategoryValidate())->post()->goCheck('delete');
        return HdCueImageCategoryLogic::delete($params) ? $this->success() : $this->fail(HdCueImageCategoryLogic::getError());
    }


    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = (new HdCueImageCategoryValidate())->goCheck('detail');
        $result = HdCueImageCategoryLogic::detail($params);
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
        $params = (new HdCueImageCategoryValidate())->goCheck('updateStatus');
        HdCueImageCategoryLogic::updateStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
