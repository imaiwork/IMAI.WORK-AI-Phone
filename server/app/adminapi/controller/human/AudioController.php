<?php


namespace app\adminapi\controller\human;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\human\AudioLists;
use app\adminapi\logic\human\HumanAudioLogic;


/**
 * 音色
 */
class AudioController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new AudioLists());
    }

    /**
     * @notes 删除音色
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return HumanAudioLogic::delete($params) ? $this->success() : $this->fail(HumanAudioLogic::getError());
    }
}
