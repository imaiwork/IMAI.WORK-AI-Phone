<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, ContactLabelInfoNoticeMessage, LabelInfoMessage};

/**
 * 好友标签信息通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class ContactLabelInfoNoticeHandler extends BaseHandler
{

    /**
     * 处理好友标签信息通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new ContactLabelInfoNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::ContactLabelInfoNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param ContactLabelInfoNoticeMessage $request 好友标签信息通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(ContactLabelInfoNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type \Jubo\JuLiao\IM\Wx\Proto\LabelInfoMessage[]|\Google\Protobuf\Internal\RepeatedField $Labels
         *           标签列表
         */

        $labels = [];

        /**
         * 
         *     Optional. Data for populating the Message object.
         *
         *     @type int $LabelId
         *     @type string $LabelName
         *     @type int|string $CreateTime
         * @var LabelInfoMessage $label
         */
        foreach ($request->getLabels() as $label) {
            $labels[] = [
                'LabelId' => $label->getLabelId(),
                'LabelName' => $label->getLabelName(),
                'CreateTime' => $label->getCreateTime(),
            ];
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Labels' => $labels
        ];

        $this->logInfo('Contact label info notice', array_merge($content, [
            'Labels' => count($labels)
        ]));

        return $content;
    }
}
