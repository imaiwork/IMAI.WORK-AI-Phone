<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PullChatRoomQrCodeTaskResultNoticeMessage};

/**
 * 返回：每隔一段时间手机端回传检测清粉好友数
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PullChatRoomQrCodeTaskResultNoticeHandler extends BaseHandler
{
    /**
     * 返回：每隔一段时间手机端回传检测清粉好友数
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new PullChatRoomQrCodeTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::PullChatRoomQrCodeTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param PullChatRoomQrCodeTaskResultNoticeMessage $request 发送朋友圈数据结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PullChatRoomQrCodeTaskResultNoticeMessage $request, string $deviceId): array
    {

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Success' => $request->getSuccess(),
            'ChatRoomId' => $request->getChatRoomId(),
            'QrCodeUrl' => $request->getQrCodeUrl(),
            'TaskId' => $request->getTaskId(),
            'ErrMsg' => $request->getErrMsg()
        ];

        $this->logInfo('Post Friend Detect Count Notice', $content);

        return $content;
    }
}
