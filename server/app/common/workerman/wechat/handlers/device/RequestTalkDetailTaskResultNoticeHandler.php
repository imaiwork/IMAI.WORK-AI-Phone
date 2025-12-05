<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, RequestTalkDetailTaskResultNoticeMessage};

/**
 * 请求聊天图片或视频消息的详细内容任务处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class RequestTalkDetailTaskResultNoticeHandler extends BaseHandler
{
    /**
     * 处理请求聊天图片或视频消息的详细内容任务
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new RequestTalkDetailTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::RequestTalkDetailTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param RequestTalkDetailTaskResultNoticeMessage $request 请求聊天图片或视频消息的详细内容
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(RequestTalkDetailTaskResultNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type int|string $MsgId
         *     @type string $WeChatId
         *     @type string $FriendId
         *     @type string $Content
         *          大图url
         *     @type bool $IsOriginal
         */
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'FriendId' => $request->getFriendId(),
            'MsgId' => $request->getMsgId(),
            'Content' => base64_decode(base64_encode($request->getContent())),
            'IsOriginal' => $request->getIsOriginal(),
        ];

        $this->logInfo('Request talk detail task result notice', $content);

        return $content;
    }
}
