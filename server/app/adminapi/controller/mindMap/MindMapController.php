<?php


namespace app\adminapi\controller\mindMap;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\mindMap\MindMapLists;
use app\adminapi\logic\mindMap\MindMapLogic;

/**
 * 思维导图
 */
class MindMapController extends BaseAdminController
{
    /**
     * @notes 思维导图列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new MindMapLists());
    }


    /**
     * @notes 删除思维导图
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return MindMapLogic::delete($params) ? $this->success() : $this->fail(MindMapLogic::getError());
    }
}
