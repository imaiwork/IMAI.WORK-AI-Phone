<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FriendAddReqListNoticeMessage, FriendReqMessage};

/**
 * 好友添加请求列表通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FriendAddReqListNoticeHandler extends BaseHandler
{

    /**
     * 处理好友添加请求列表通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FriendAddReqListNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendAddReqListNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FriendAddReqListNoticeMessage $request 好友添加请求列表通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FriendAddReqListNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\FriendReqMessage[]|\Google\Protobuf\Internal\RepeatedField $Requests
         *           请求列表，只包含未通过的请求，时间不限，多次消息来往只上传一条
         */
        $requests = [];

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $FriendId
         *           请求好友微信内部全局唯一识别码
         *     @type string $FriendNo
         *     @type string $FriendNick
         *           好友的微信昵称
         *     @type string $Avatar
         *          微信头像 
         *     @type string $Reason
         *           招呼语
         *     @type int $Gender
         *          性别
         *     @type string $Province
         *          省份 (国家用不上）
         *     @type string $City
         *          城市
         *     @type int $Source
         *           来源 (17: 名片分享）
         *     @type string $SourceUser
         *           来源的微信id（推荐人，群聊房间号）
         *     @type int|string $ReqTime
         *          最后请求的时间
         *     @type int $State
         *           状态，0 未添加，1 已添加
         *     @type int|string $FirstReq
         *           第一次发送请求的时间
         * @var FriendReqMessage $friend
         */
        foreach ($request->getRequests() as $friend) {
            $requests[] = [
                'FriendId' => $friend->getFriendId(),
                'FriendNo' => $friend->getFriendNo(),
                'FriendNick' => $friend->getFriendNick(),
                'Avatar' => $friend->getAvatar(),
                'Reason' => $friend->getReason(),
                'Gender' => $friend->getGender(),
                'Province' => $friend->getProvince(),
                'City' => $friend->getCity(),
                'Source' => $friend->getSource(),
                'SourceUser' => $friend->getSourceUser(),
                'ReqTime' => $friend->getReqTime(),
                'State' => $friend->getState(),
                'FirstReq' => $friend->getFirstReq(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Requests' => $requests,
        ];

        $this->logInfo('Friend add req list notice', array_merge($content, [
            'Requests' => count($requests),
        ]));

        return $content;
    }
}
