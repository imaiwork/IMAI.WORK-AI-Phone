<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, CircleCommentDeleteTaskResultNoticeMessage};

/**
 * 朋友圈评论删除任务反馈
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class CircleCommentDeleteTaskResultNoticeHandler extends BaseHandler
{

    /**
     * 处理朋友圈评论回复反馈通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new CircleCommentDeleteTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::CircleCommentDeleteTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param CircleCommentDeleteTaskResultNoticeMessage $request 朋友圈评论回复反馈通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(CircleCommentDeleteTaskResultNoticeMessage $request, string $deviceId): array
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
         *     @type int|string $CommentId
         *           聊天Id
         *     @type int|string $TaskId
         *           请求中传递过去的jkt本地的表主键id
         *     @type int|string $CircleId
         *           朋友圈id
         *     @type string $WeChatId
         *           商家所属微信号
         */

        if (!$request->getSuccess()) {
            $this->withCode($request->getCode())->withMessage($request->getErrMsg());
        }

        $content = [
            'DeviceId' => $deviceId,
            'WeChatId' => $request->getWeChatId(),
            'Success' => $request->getSuccess(),
            'Code' => $request->getCode(),
            'ErrMsg' => $request->getErrMsg(),
            'CircleId' => (string)$request->getCircleId(),
            'CommentId' => $request->getCommentId(),
            'TaskId' => $request->getTaskId(),
        ];

        $this->logInfo('Circle comment reply task result notice', $content);

        return $content;
    }
}
