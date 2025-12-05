<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\device;

use Jubo\JuLiao\IM\Wx\Proto\{EnumMsgType, TalkToFriendTaskResultNoticeMessage};

/**
 * 发送消息结果通知处理器
 * 
 * @method static array handle(string $data, array $context) 业务处理
 * @author Qasim
 * @package app\handlers\device
 */
class TalkToFriendTaskResultNoticeHandler extends BaseHandler
{
    /**
     * 处理发送消息结果通知
     * 
     * @param string $data 二进制数据
     * @param array $context 上下文数据
     * @return array
     */
    protected function handle(string $data, array $context): array
    {
        $request = new TalkToFriendTaskResultNoticeMessage();
        $request->mergeFromString($data);

        $deviceId = $context['DeviceId'];

        // 构建响应内容
        $content = $this->buildResponseContent($request, $deviceId);

        // 返回响应体
        return $this->buildJsonResponse(EnumMsgType::TalkToFriendTaskResultNotice, $content);
    }

    /**
     * 构建响应内容
     * 
     * @param TalkToFriendTaskResultNoticeMessage $request 发送消息结果通知消息
     * @param string $deviceId 设备ID
     * @return array
     */
    private function buildResponseContent(TalkToFriendTaskResultNoticeMessage $request, string $deviceId): array
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
         *     @type string $WeChatId
         *           执行的微信
         *     @type string $FriendId
         *           执行的用户对象
         *     @type int|string $MsgId
         *           聊天Id
         *     @type int|string $MsgSvrId
         *     @type int|string $CreateTime
         *           消息发送时间
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
            'FriendId' => $request->getFriendId(),
            'MsgId' => $request->getMsgId(),
            'MsgSvrId' => (string)$request->getMsgSvrId(),
            'CreateTime' => $request->getCreateTime(),
        ];

        $this->logInfo('Talk to friend task result notice', $content);

        return $content;
    }
}
