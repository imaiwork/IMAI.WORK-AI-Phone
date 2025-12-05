<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FriendTalkNoticeMessage};

/**
 * 好友聊天通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FriendTalkNoticeHandler extends BaseHandler
{

    /**
     * 处理好友聊天通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FriendTalkNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendTalkNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FriendTalkNoticeMessage $request 好友聊天通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FriendTalkNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type string $FriendId
         *           好友微信内部全局唯一识别码
         *     @type int $ContentType
         *           发送的消息内容类型
         *     @type string $Content
         *           内容 二进制流
         *     @type int|string $MsgId
         *          服务端的主键id
         *     @type int|string $msgSvrId
         *           消息唯一id
         *     @type string $Ext
         *           扩展信息，图片视频{"length":10000, "hdlen":20000, "duration":30} 文件大小，原图大小，视频时长
         *     @type int|string $CreateTime
         *           消息时间
         *     @type string $NickName
         *           发送者昵称
         */

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
            'ContentType' => $request->getContentType(),
            'Content' => base64_decode(base64_encode($request->getContent())),
            'MsgId' => $request->getMsgId(),
            'MsgSvrId' => (string)$request->getMsgSvrId(),
            'Ext' => $request->getExt(),
            'CreateTime' => $request->getCreateTime(),
            'NickName' => $request->getNickName(),
        ];

        $this->logInfo('Friend talk notice', $content);

        return $content;
    }
}
