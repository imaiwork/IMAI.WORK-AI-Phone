<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\DeleteFriendTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 3、删除朋友圈任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class DeleteFriendTaskHandler extends BaseHandler
{

    /**
     * 3、删除朋友圈任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::DeleteFriendTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return DeleteFriendTaskMessage
     */
    protected function buildRequestContent(array $data): DeleteFriendTaskMessage
    {
        
        
        $request = new DeleteFriendTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
