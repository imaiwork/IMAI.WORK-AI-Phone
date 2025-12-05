<?php


namespace app\adminapi\controller\human;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\human\HumanVoiceLogic;
use app\adminapi\lists\human\VoiceLists;


/**
 * 音色
 * Class WechatController
 * @package app\api\controller
 */
class VoiceController extends BaseAdminController
{
    /**
     * @notes 助手列表
     * @author 段誉
     * @date 2022/9/20 15:30
     */
    public function lists()
    {
        return $this->dataLists(new VoiceLists());
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
        return HumanVoiceLogic::delete($params) ? $this->success() : $this->fail(HumanVoiceLogic::getError());
    }
}
