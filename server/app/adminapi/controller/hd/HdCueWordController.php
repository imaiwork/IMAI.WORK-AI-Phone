<?php



namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdCueWordLists;
use app\adminapi\logic\hd\HdCueWordLogic;
use app\adminapi\validate\hd\HdCueWordValidate;


/**
 * HdCueWord控制器
 * Class HdCueWordController
 * @package app\adminapi\controller\hd
 */
class HdCueWordController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdCueWordLists());
    }

    /**
     * @desc 添加
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function add()
    {
        $params = (new HdCueWordValidate())->post()->goCheck('add');
        $result = HdCueWordLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(HdCueWordLogic::getError());
    }


    /**
     * @desc 编辑
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function edit()
    {
        $params = (new HdCueWordValidate())->post()->goCheck('edit');
        $result = HdCueWordLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(HdCueWordLogic::getError());
    }


    /**
     * @desc 删除
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function delete()
    {
        $params = (new HdCueWordValidate())->post()->goCheck('delete');
        return HdCueWordLogic::delete($params) ? $this->success() : $this->fail(HdCueWordLogic::getError());
    }


    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = (new HdCueWordValidate())->goCheck('detail');
        $result = HdCueWordLogic::detail($params);
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
        $params = (new HdCueWordValidate())->goCheck('updateStatus');
        HdCueWordLogic::updateStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
