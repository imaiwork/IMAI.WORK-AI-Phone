<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ContactLabelAddNoticeMessage};

/**
 * 标签添加通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ContactLabelAddNoticeHandler extends BaseHandler
{

    /**
     * 标签添加通知处理器
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ContactLabelAddNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ContactLabelAddNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ContactLabelAddNoticeMessage $request 标签添加通知处理器
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ContactLabelAddNoticeMessage $request, string $deviceId): array
    {
       
        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Label' => $request->getLabel()
        ];

        $this->logInfo('Contact label add notice', $content);

        return $content;
    }
}
