<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerCircleMsgPushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发朋友圈消息推送任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerCircleMsgPushTaskHandler extends BaseHandler
{

    /**
     * 处理触发朋友圈消息推送任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerCircleMsgPushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerCircleMsgPushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerCircleMsgPushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type bool $OnlyComment
         *           缺省获取全部
         *     @type bool $GetAll
         *           缺省只获取未读消息
         *     @type int|string $TaskId
         */

        $request = new TriggerCircleMsgPushTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['OnlyComment'])) {
            $request->setOnlyComment($data['OnlyComment']);
        }

        if (isset($data['GetAll'])) {
            $request->setGetAll($data['GetAll']);
        }


        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
