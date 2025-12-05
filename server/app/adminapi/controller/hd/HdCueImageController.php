<?php



namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdCueImageLists;
use app\adminapi\logic\hd\HdCueImageLogic;
use app\adminapi\validate\hd\HdCueImageValidate;


/**
 * HdCueImage控制器
 * Class HdCueImageController
 * @package app\adminapi\controller\hd
 */
class HdCueImageController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdCueImageLists());
    }

    /**
     * @desc 添加
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function add()
    {
        $params = (new HdCueImageValidate())->post()->goCheck('add');
        $result = HdCueImageLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(HdCueImageLogic::getError());
    }


    /**
     * @desc 编辑
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function edit()
    {
        $params = (new HdCueImageValidate())->post()->goCheck('edit');
        $result = HdCueImageLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(HdCueImageLogic::getError());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new HdCueImageValidate())->post()->goCheck('delete');
        return HdCueImageLogic::delete($params) ? $this->success() : $this->fail(HdCueImageLogic::getError());
    }


    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = (new HdCueImageValidate())->goCheck('detail');
        $result = HdCueImageLogic::detail($params);
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
        $params = (new HdCueImageValidate())->goCheck('updateStatus');
        HdCueImageLogic::updateStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
