<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ContactLabelDelNoticeMessage};

/**
 * 标签删除处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ContactLabelDelNoticeHandler extends BaseHandler
{

    /**
     * 标签删除处理器
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ContactLabelDelNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ContactLabelDelNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ContactLabelDelNoticeMessage $request 标签删除处理器
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ContactLabelDelNoticeMessage $request, string $deviceId): array
    {
        

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'LabelId' => $request->getLabelId()
        ];

        $this->logInfo('Contact label info notice', $content);

        return $content;
    }
}
