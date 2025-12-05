<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerWechatPushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 7、触发微信上线通知
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerWechatPushTaskHandler extends BaseHandler
{

    /**
     * 处理语音转文字任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerWechatPushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerWechatPushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerWechatPushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $FriendId
         *           好友id
         *     @type int|string $MsgSvrId
         *           消息唯一id
         *     @type int|string $TaskId
         */

        $request = new TriggerWechatPushTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        

        return $request;
    }
}
