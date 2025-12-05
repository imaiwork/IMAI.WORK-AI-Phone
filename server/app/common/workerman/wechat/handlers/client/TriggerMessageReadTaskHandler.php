<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerMessageReadTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发消息已读任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerMessageReadTaskHandler extends BaseHandler
{

    /**
     * 处理触发消息已读任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerMessageReadTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerMessageReadTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerMessageReadTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $FriendId
         *           好友id
         */

        $request = new TriggerMessageReadTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        return $request;
    }
}
