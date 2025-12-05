<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ChatRoomAddNoticeMessage, ChatRoomMessage};

/**
 * 群聊添加通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ChatRoomAddNoticeHandler extends BaseHandler
{
    /**
     * 处理群聊添加通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ChatRoomAddNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ChatRoomAddNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ChatRoomAddNoticeMessage $request 群聊添加通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ChatRoomAddNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\ChatRoomMessage $ChatRoom
         */

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $UserName
         *     @type string $NickName
         *     @type string[]|\Google\Protobuf\Internal\RepeatedField $MemberList
         *     @type string $Owner
         *     @type string $Notice
         *     @type \Jubo\JuLiao\IM\Wx\Proto\ChatRoomMessage\DisplayNameMessage[]|\Google\Protobuf\Internal\RepeatedField $ShowNameList
         *     @type string $SelfDisplayName
         *     @type string $Avatar
         *          微信头像 
         *     @type int $Verify
         *           群聊邀请确认
         *     @type bool $MsgSilent
         *           消息免打扰
         *     @type string $Remark
         *           群聊备注，7.0.14版本才有
         *     @type int $Type
         *           type字段，供参考 (4.6.16版本开始：&2048 置顶）
         *     @type bool $IsUnusual
         *           是否异常
         * @var ChatRoomMessage $chatRoom
         */
        $chatRoom = $request->getChatRoom();

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'ChatRoom' => [
                'UserName' => $chatRoom->getUserName(),
                'NickName' => $chatRoom->getNickName(),
                'MemberList' => $chatRoom->getMemberList(),
                'Owner' => $chatRoom->getOwner(),
                'Notice' => $chatRoom->getNotice(),
                'ShowNameList' => $this->parseChatRoomShowNameList($chatRoom->getShowNameList()),
                'SelfDisplayName' => $chatRoom->getSelfDisplayName(),
                'Avatar' => $chatRoom->getAvatar(),
                'Verify' => $chatRoom->getVerify(),
                'MsgSilent' => $chatRoom->getMsgSilent(),
                'Remark' => $chatRoom->getRemark(),
                'Type' => $chatRoom->getType(),
                'IsUnusual' => $chatRoom->getIsUnusual(),
            ]
        ];

        $this->logInfo('Chat room add notice', $content);

        return $content;
    }
}
