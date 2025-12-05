<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ChatRoomMembersNoticeMessage, StrangerMessage};

/**
 * 群聊成员通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ChatRoomMembersNoticeHandler extends BaseHandler
{

    /**
     * 处理群聊成员通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ChatRoomMembersNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ChatRoomMembersNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ChatRoomMembersNoticeMessage $request 群聊成员通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ChatRoomMembersNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\StrangerMessage[]|\Google\Protobuf\Internal\RepeatedField $Members
         *           联系人信息
         */

        // 获取群成员信息
        $members = [];
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $Wxid
         *           微信号
         *     @type string $Alias
         *           微信别名
         *     @type string $Nickname
         *           好友的微信昵称
         *     @type string $Avatar
         *          微信头像 
         *     @type int $Type
         *           联系人类型，判断是否为好友
         *     @type int $Gender
         *          性别
         *     @type string $Country
         *          国家 (非必传)
         *     @type string $Province
         *          省份
         *     @type string $City
         *          城市
         *     @type string $Memo
         *           机主的备注
         *     @type string $Signature
         *          签名
         * @var StrangerMessage $member
         */
        foreach ($request->getMembers() as $member) {
            $members[] = [
                'Wxid' => $member->getWxid(),
                'Alias' => $member->getAlias(),
                'Nickname' => $member->getNickname(),
                'Avatar' => $member->getAvatar(),
                'Gender' => $member->getGender(),
                'Country' => $member->getCountry(),
                'Province' => $member->getProvince(),
                'City' => $member->getCity(),
                'Memo' => $member->getMemo(),
                'Signature' => $member->getSignature(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Members' => $members
        ];

        $this->logInfo('Chat room members notice', array_merge($content, [
            'Members' => count($members)
        ]));

        return $content;
    }
}
