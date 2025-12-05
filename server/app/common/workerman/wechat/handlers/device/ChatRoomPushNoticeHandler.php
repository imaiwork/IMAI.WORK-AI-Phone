<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Google\Protobuf\Internal\RepeatedField;
use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ChatRoomPushNoticeMessage, ChatRoomMessage, ChatRoomMessage\DisplayNameMessage};

/**
 * 群聊推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ChatRoomPushNoticeHandler extends BaseHandler
{
    /**
     * 处理群聊推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ChatRoomPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ChatroomPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ChatRoomPushNoticeMessage $request 群聊推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ChatRoomPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\ChatRoomMessage[]|\Google\Protobuf\Internal\RepeatedField $ChatRooms
         *           好友信息模型 多个
         *     @type int $Size
         *     @type int $Count
         *     @type int $Page
         *     @type int|string $TaskId
         */

        // 获取群列表信息
        $chatRooms = [];
        /**
         * 
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
        foreach ($request->getChatRooms() as $chatRoom) {
            $chatRooms[] = [
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
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'ChatRooms' => $chatRooms,
            'Size' => $request->getSize(),
            'Count' => $request->getCount(),
            'Page' => $request->getPage(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Chatroom push notice', array_merge($content, [
            'ChatRooms' => count($chatRooms),
        ]));

        return $content;
    }

    /**
     * 解析群聊成员显示名
     *     Optional. Data for populating the Message object.
     *
     *     @type string $UserName
     *           群成员
     *     @type string $ShowName
     *           群显示名
     *     @type string $Inviter
     *           邀请者
     *     @type int $Flag
     *           &2048 群管理员，其他未知
     * 
     * @param DisplayNameMessage[]|RepeatedField $names
     * @return array
     */
    // private function parseChatRoomShowNameList(DisplayNameMessage|RepeatedField $names): array
    // {
    //     $content = [];

    //     foreach ($names as $name) {
    //         $content[] = [
    //             'UserName' => $name->getUserName(),
    //             'ShowName' => $name->getShowName(),
    //             'Inviter' => $name->getInviter(),
    //             'Flag' => $name->getFlag(),
    //         ];
    //     }

    //     return $content;
    // }
}
