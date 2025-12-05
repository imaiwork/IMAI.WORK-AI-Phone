<?php


namespace app\adminapi\controller\human;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\human\AnchorLists;
use app\adminapi\logic\human\HumanAnchorLogic;



/**
 * 形象
 */
class AnchorController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new AnchorLists());
    }

    /**
     * @notes 删除形象
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return HumanAnchorLogic::delete($params) ? $this->success() : $this->fail(HumanAnchorLogic::getError());
    }
}
