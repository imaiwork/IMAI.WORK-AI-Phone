<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, SphGetMentionTaskMessage};

/**
 * 视频号 获取动态消息
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class SphGetMentionTaskHandler extends BaseHandler
{
    /**
     * 处理请求获取动态消息任务
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new SphGetMentionTaskMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::SphGetMentionTask, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param SphGetMentionTaskMessage $request 请求获取动态消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(SphGetMentionTaskMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type int|string $MsgId
         *     @type string $WeChatId
         *     @type string $LastLikeId
         *     @type string $LastCommentId
         *     @type string $LastFollowId
         *     @type string $TaskId
         */
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'LastLikeId' => $request->getLastLikeId(),
            'LastCommentId' => $request->getLastCommentId(),
            'LastFollowId' => $request->getLastFollowId(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Request get mention task result notice', $content);

        return $content;
    }
}
