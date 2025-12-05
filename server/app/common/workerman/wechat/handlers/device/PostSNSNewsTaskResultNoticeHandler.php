<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, PostSNSNewsTaskResultNoticeMessage, PostSNSNewsTaskResultNoticeMessage\ExtraProperties};

/**
 * 发送发送朋友圈数据结果通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class PostSNSNewsTaskResultNoticeHandler extends BaseHandler
{
    /**
     * 处理发送朋友圈数据结果通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new PostSNSNewsTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::PostSNSNewsTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param PostSNSNewsTaskResultNoticeMessage $request 发送朋友圈数据结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(PostSNSNewsTaskResultNoticeMessage $request, string $deviceId): array
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type bool $Success
         *           是否成功
         *     @type int $Code
         *           错误码 Success = true 忽略
         *     @type string $ErrMsg
         *           错误内容描述 Success = true 忽略
         *     @type int|string $TaskId
         *           业务的id,通用的。   
         *     @type \Jubo\JuLiao\IM\Wx\Proto\PostSNSNewsTaskResultNoticeMessage\ExtraProperties $Extra
         *          扩展信息（手机端不用考虑）
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         */

        if (!$request->getSuccess()) {
            $this->withCode($request->getCode())->withMessage($request->getErrMsg());
        }
        
        $extra = $request->getExtra();

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Success' => $request->getSuccess(),
            'Code' => $request->getCode(),
            'ErrMsg' => $request->getErrMsg(),
            'TaskId' => $request->getTaskId(),
            'CircleId' => $extra->getCircleId()
        ];

        $this->logInfo('Post sns news task result notice', $content);

        return $content;
    }
}
