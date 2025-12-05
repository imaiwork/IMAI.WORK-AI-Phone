<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\PullChatRoomQrCodeTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 获取群聊二维码任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class PullChatRoomQrCodeTaskHandler extends BaseHandler
{

    /**
     * 获取群聊二维码任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PullChatRoomQrCodeTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PullChatRoomQrCodeTaskMessage
     */
    protected function buildRequestContent(array $data): PullChatRoomQrCodeTaskMessage
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

        $request = new PullChatRoomQrCodeTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        if (isset($data['ChatRoomId'])) {
            $request->setChatRoomId($data['ChatRoomId']);
        }

        
        

        return $request;
    }
}
