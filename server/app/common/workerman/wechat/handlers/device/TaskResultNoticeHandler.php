<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{TaskResultNoticeMessage};

/**
 * 历史消息推送通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class TaskResultNoticeHandler extends BaseHandler
{
    /**
     * 处理历史消息推送通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new TaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse($content['TaskType'], $content);
    }

    /**
     * 构建响应内容
     * 
     * @param TaskResultNoticeMessage $request 历史消息推送通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(TaskResultNoticeMessage $request, string $deviceId): array
    {

        /**
         *     @type bool $Success
         *           是否成功
         *     @type int $Code
         *           错误码 Success = true 忽略
         *     @type string $ErrMsg
         *           错误内容描述 获取 成功时附带的结果内容
         *     @type int|string $TaskId
         *           业务的id,通用的。
         *     @type int $TaskType
         *          原来执行的任务的类型
         *     @type string $WeChatId
         *           执行的微信
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
            'TaskId' => (string)$request->getTaskId(),
            'TaskType' => $request->getTaskType(),
        ];

        $this->logInfo('Task result notice', $content);

        return $content;
    }
}
