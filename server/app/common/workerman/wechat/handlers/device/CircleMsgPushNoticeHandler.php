<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleMsgPushNoticeMessage};

/**
 * 朋友圈消息推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CircleMsgPushNoticeHandler extends BaseHandler
{
    /**
     * 处理朋友圈消息推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CircleMsgPushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CircleMsgPushNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CircleMsgPushNoticeMessage $request 朋友圈消息推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CircleMsgPushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleCommentMessage[]|\Google\Protobuf\Internal\RepeatedField $Comments
         *          
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleLikeMessage[]|\Google\Protobuf\Internal\RepeatedField $Likes
         *          
         *     @type int|string $TaskId
         */

        // 获取朋友圈消息
        $comments = $this->parseCircleComments($request->getComments());
        $likes = $this->parseCircleLikes($request->getLikes());

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'TaskId' => $request->getTaskId(),
            'Comments' => $comments,
            'Likes' => $likes
        ];

        $this->logInfo('Circle msg push notice', array_merge($content, [
            'Comments' => count($comments),
            'Likes' => count($likes)
        ]));

        return $content;
    }
}
