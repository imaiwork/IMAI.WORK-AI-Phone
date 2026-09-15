<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, SphMentionListNoticeMessage};

/**
 * 视频号 推送动态消息列表
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class SphMentionListNoticeHandler extends BaseHandler
{
    /**
     * 处理请求视频号 推送动态消息列表
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new SphMentionListNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::SphMentionListNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param SphMentionListNoticeMessage $request 请求视频号 推送动态消息列表
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(SphMentionListNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type int|string $TaskId
         *     @type string $WeChatId
         *     @type string $SphUserName
         *     @type array<\Jubo\JuLiao\IM\Wx\Proto\MentionMessage>|\Google\Protobuf\Internal\RepeatedField $LikeList
         *          点赞列表
         *     @type array<\Jubo\JuLiao\IM\Wx\Proto\MentionMessage>|\Google\Protobuf\Internal\RepeatedField $CommentList
         *          评论列表
         *     @type array<\Jubo\JuLiao\IM\Wx\Proto\MentionMessage>|\Google\Protobuf\Internal\RepeatedField $FollowList
         *          关注列表
         *     @type bool $IsOriginal
         */
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'SphUserName' => $request->getSphUserName(),
            'LikeList' => $request->getLikeList(),
            'CommentList' => $request->getCommentList(),
            'FollowList' => $request->getFollowList(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Request talk detail task result notice', $content);

        return $content;
    }
}
