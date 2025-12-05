<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CirclePushNoticeMessage, CircleInformationMessage};

/**
 * 一键点赞任务结果通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class OneKeyLikeTaskResultNoticeHandler extends BaseHandler
{

    /**
     * 处理历史消息推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CirclePushNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::OneKeyLikeTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CirclePushNoticeMessage $request 一键点赞任务结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CirclePushNoticeMessage $request, string $deviceId): array
    {
        /**
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type \Jubo\JuLiao\IM\Wx\Proto\CircleInformationMessage[]|\Google\Protobuf\Internal\RepeatedField $Circles
         *           好友信息模型 多个
         *     @type int $Size
         *     @type int $Count
         *     @type int $Page
         *     @type int $RetCode
         *           获取朋友圈返回结果代码（0 还有更多，203 ? 207 已是最底 2001 ? 2003 拉黑？ 2004 展示三天 2005 展示一个月 ）
         *     @type string $RetTips
         *           朋友圈底线提示：非对方的朋友只显示最近十条朋友圈，朋友仅展示最近三天的朋友圈，。。。
         */

        // 获取朋友圈消息
        $circles = [];
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
        foreach ($request->getCircles() as $circle) {

            // 解析朋友圈内容
            $content = $this->parseCircleContent($circle->getContent());

            // 解析点赞好友
            $likes = $this->parseCircleLikes($circle->getLikes());

            // 解析评论好友
            $comments = $this->parseCircleComments($circle->getComments());

            $circles[] = [
                'WeChatId' => $circle->getWeChatId(),
                'CircleId' => $circle->getCircleId(),
                'Content' => $content,
                'Likes' => $likes,
                'Comments' => $comments,
                'PublishTime' => $circle->getPublishTime(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Circles' => $circles,
        ];

        $this->logInfo('One key like task result notice', array_merge($content, [
            'Circles' => count($circles),
        ]));

        return $content;
    }
}
