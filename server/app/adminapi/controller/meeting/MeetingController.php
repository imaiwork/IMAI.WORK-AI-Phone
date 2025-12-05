<?php


namespace app\adminapi\controller\meeting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\meeting\MeetingLists;
use app\adminapi\logic\meeting\MeetingLogic;

/**
 * 会议
 */
class MeetingController extends BaseAdminController
{
    /**
     * @notes 会议列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new MeetingLists());
    }


    /**
     * @notes 删除会议
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return MeetingLogic::delete($params) ? $this->success() : $this->fail(MeetingLogic::getError());
    }
}
