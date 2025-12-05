<?php

namespace app\adminapi\controller\assistants;


use app\adminapi\lists\assistants\SceneLists;
use app\adminapi\logic\assistants\KnowledgeLogic;
use app\adminapi\validate\assistants\SceneValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;



/**
 *
 * Class KnowledgeController
 * @package app\adminapi\controller
 */
class KnowledgeController extends BaseAdminController
{

    /**
     * @notes 列表
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists()
    {
        return $this->dataLists(new SceneLists());
    }


    /**
     * @notes 启用
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function enable(): Json
    {
        return KnowledgeLogic::enable() ? $this->success() : $this->fail(KnowledgeLogic::getError());
    }


    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        return SceneLogic::delete($params) ? $this->success() : $this->fail(SceneLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function detail(): Json
    {
        $id = $this->request->get('id/d');
        if (empty($id))
        {
            return $this->fail('参数丢失');
        }
        $result = SceneLogic::detail($id);
        return $this->data(SceneLogic::getReturnData());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function edit(): Json
    {
        $postData = (new SceneValidate())->post()->goCheck('edit');
        $edit      = SceneLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(SceneLogic::getError());
    }

    /**
     * 修改状态的、
     * @return Json
     * @author L
     * @data 2024/7/2 15:12
     */
    public function changeStatus(): Json
    {
        $id = $this->request->post('id/d');
        if (empty($id))
        {
            return $this->fail("参数丢失");
        }
        return SceneLogic::changeStatus($id) ? $this->success() : $this->fail(SceneLogic::getError());
    }
}
