<?php
namespace app\adminapi\controller\wechat;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wechat\WechatChatLists;

/**
 * 聊天管理
 * Class ChatController
 * @package app\adminapi\controller
 */
class ChatController extends BaseAdminController
{
    /**
     * @notes 列表
     */
    public function lists()
    {
        return $this->dataLists(new WechatChatLists());
    }
}
                        