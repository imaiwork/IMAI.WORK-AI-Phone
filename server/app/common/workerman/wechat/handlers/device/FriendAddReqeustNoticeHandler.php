<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, FriendAddReqeustNoticeMessage};

/**
 * 好友添加请求通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class FriendAddReqeustNoticeHandler extends BaseHandler
{

    /**
     * 处理好友添加请求通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new FriendAddReqeustNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::FriendAddReqeustNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param FriendAddReqeustNoticeMessage $request 好友添加请求通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(FriendAddReqeustNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type string $FriendId
         *           请求好友微信内部全局唯一识别码
         *     @type string $FriendNo
         *           微信号
         *     @type string $FriendNick
         *           好友的微信昵称
         *     @type string $Reason
         *           招呼语
         *     @type string $Avatar
         *          微信头像 
         *     @type int $Source
         *           来源 (17: 名片分享）
         *     @type string $SourceUser
         *           来源的微信id（推荐人，群聊房间号）
         *     @type int $Gender
         *          性别
         *     @type string $Province
         *          省份 (国家用不上）
         *     @type string $City
         *          城市
         */
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendInfo' => [
                'FriendId' => $request->getFriendId(),
                'FriendNo' => $request->getFriendNo(),
                'FriendNick' => $request->getFriendNick(),
                'Avatar' => $request->getAvatar(),
                'Reason' => $request->getReason(),
                'Gender' => $request->getGender(),
                'Province' => $request->getProvince(),
                'City' => $request->getCity(),
                'Source' => $request->getSource(),
                'SourceUser' => $request->getSourceUser(),
            ]
        ];

        $this->logInfo('Friend add request notice', $content);

        return $content;
    }
}
