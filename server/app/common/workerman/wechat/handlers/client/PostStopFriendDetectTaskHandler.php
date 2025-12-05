<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\PostStopFriendDetectTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 10、终止清粉任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class PostStopFriendDetectTaskHandler extends BaseHandler
{

    /**
     * 10、终止清粉任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PostStopFriendDetectTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PostStopFriendDetectTaskMessage
     */
    protected function buildRequestContent(array $data): PostStopFriendDetectTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $FriendId
         *           好友id
         *     @type int|string $MsgSvrId
         *           消息唯一id
         *     @type int|string $TaskId
         */

        $request = new PostStopFriendDetectTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        
        

        return $request;
    }
}
