<?php

namespace app\adminapi\controller\assistants;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\assistants\ChatLogLists;
use app\adminapi\logic\assistants\ChatLogLogic;



/**
 * 助手聊天记录
 * Class WechatController
 * @package app\adminapi\controller
 */
class ChatLogController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists()
    {
        return $this->dataLists(new ChatLogLists());
    }


    /**
     * @notes 删除聊天记录
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return ChatLogLogic::delete($params) ? $this->success() : $this->fail(ChatLogLogic::getError());
    }
}
