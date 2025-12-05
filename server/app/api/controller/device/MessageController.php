<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\MessageLists;

/**
 * MessageController
 * @desc 设备消息任务
 * @author Qasim
 */
class MessageController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new MessageLists());
    }
}