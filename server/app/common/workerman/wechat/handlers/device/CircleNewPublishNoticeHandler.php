<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleNewPublishNoticeMessage, CircleInformationMessage};

/**
 * 手机上发送了朋友圈通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CircleNewPublishNoticeHandler extends BaseHandler
{

    /**
     * 处理手机上发送了朋友圈通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CircleNewPublishNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CircleNewPublishNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CircleNewPublishNoticeMessage $request 手机上发送了朋友圈通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CircleNewPublishNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage $Circle
         *           朋友圈信息
         */

        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           发布者
         *     @type int|string $CircleId
         *           朋友圈id
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage\CircleContentMessage $Content
         *           朋友圈内容
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleLikeMessage[]|\Google\Protobuf\Internal\RepeatedField $Likes
         *           点赞好友friendid
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleCommentMessage[]|\Google\Protobuf\Internal\RepeatedField $Comments
         *           评论好友
         *     @type int|string $PublishTime
         *           发布时间
         * @var CircleInformationMessage $circle
         */
        $circle = $request->getCircle();
        
        $circle = [
            
            'WeChatId' => $circle->getWeChatId(),
            'CircleId' => (string)$circle->getCircleId(),
            'Content'  => $this->parseCircleContent($circle->getContent()),
            'Likes'     => $this->parseCircleLikes($circle->getLikes()),
            'Comments'  => $this->parseCircleComments($circle->getComments())
        ];
        
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Circle' => $circle,
        ];

        $this->logInfo('Circle new publish notice', $content);

        return $content;
    }
}
