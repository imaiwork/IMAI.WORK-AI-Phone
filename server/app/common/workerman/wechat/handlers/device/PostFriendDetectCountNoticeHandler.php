<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PostFriendDetectCountNoticeMessage};

/**
 * 返回：每隔一段时间手机端回传检测清粉好友数
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PostFriendDetectCountNoticeHandler extends BaseHandler
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
        $request = new PostFriendDetectCountNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::PostFriendDetectCountNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param PostFriendDetectCountNoticeMessage $request 发送朋友圈数据结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PostFriendDetectCountNoticeMessage $request, string $deviceId): array
    {
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Count' => $request->getCount(),
            'DelCount' => $request->getDelCount(),
            'IsFinished' => $request->getIsFinished(),
            'TaskId' => $request->getTaskId(),
            'SkipCount' => $request->getSkipCount(),
            'Zombies' => $request->getZombies()
        ];

        $this->logInfo('Post Friend Detect Count Notice', $content);

        return $content;
    }
}
