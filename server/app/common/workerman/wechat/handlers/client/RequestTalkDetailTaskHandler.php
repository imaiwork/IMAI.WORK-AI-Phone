<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\RequestTalkDetailTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 请求聊天图片或视频消息的详细内容任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RequestTalkDetailTaskHandler extends BaseHandler
{

    /**
     * 处理请求聊天图片或视频消息的详细内容任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::RequestTalkDetailTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return RequestTalkDetailTaskMessage
     */
    protected function buildRequestContent(array $data): RequestTalkDetailTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type int|string $MsgId
         *          全局消息id
         *     @type string $WeChatId
         *          微信唯一Id
         *     @type string $FriendId
         *          聊天好友微信唯一id
         *     @type string $MsgSvrId
         *          微信设备上消息唯一id(FriendTalkNotice中上传)
         *     @type string $Md5
         *          图片或视频md5(FriendTalkNotice中上传)
         *     @type bool $GetOriginal
         *          获取原图（获取接收到的图片消息的原图时置true，其他false）
         */

        $request = new RequestTalkDetailTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        // if (isset($data['Contact'])) {
        //     $request->setContact($data['Contact']);
        // }

        if (isset($data['MsgId'])) {
            $request->setMsgId($data['MsgId']);
        }
        
        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }
        
        if (isset($data['MsgSvrId'])) {
            $request->setMsgSvrId($data['MsgSvrId']);
        }
        
        if (isset($data['Md5'])) {
            $request->setMd5($data['Md5']);
        }
        
        if (isset($data['GetOriginal'])) {
            $request->setGetOriginal($data['GetOriginal']);
        }

        return $request;
    }
}
