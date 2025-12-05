<?php
namespace app\adminapi\controller\lianlian;

use app\adminapi\lists\lianlian\LlSceneLists;
use app\adminapi\logic\lianlian\LlSceneLogic;
use app\adminapi\validate\lianlian\LlSceneValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;


/**
 * 场景管理
 * Class LlSceneController
 * @package app\adminapi\controller
 */
class LlSceneController extends BaseAdminController
{

    /**
     * @notes 列表
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists()
    {
        return $this->dataLists(new LlSceneLists());
    }


    /**
     * @notes 添加
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function add():Json
    {
        $params = (new LlSceneValidate())->post()->goCheck('add');
        return LlSceneLogic::add($params) ? $this->success(data: LlSceneLogic::getReturnData()) : $this->fail(LlSceneLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function delete():Json
    {
        $params = (new LlSceneValidate())->post()->goCheck('delete');
        return LlSceneLogic::delete($params) ? $this->success() : $this->fail(LlSceneLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function detail():Json
    {
        $data = (new LlSceneValidate())->get()->goCheck('detail');
        return LlSceneLogic::detail($data['id']) ? $this->data(LlSceneLogic::getReturnData()) : $this->fail(LlSceneLogic::getError());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function edit():Json
    {
        $data = (new LlSceneValidate())->post()->goCheck('edit');
        return LlSceneLogic::edit($data) ? $this->success(data: LlSceneLogic::getReturnData()) : $this->fail(LlSceneLogic::getError());
    }

    /**
     * 更换状态
     * @return Json
     * @author L
     * @data 2024/7/5 10:25
     */
    public function changeStatus():Json
    {
        $data = (new LlSceneValidate())->post()->goCheck('changeStatus');
        return LlSceneLogic::changeStatus($data['id']) ? $this->success() : $this->fail(LlSceneLogic::getError());
    }
}
                        