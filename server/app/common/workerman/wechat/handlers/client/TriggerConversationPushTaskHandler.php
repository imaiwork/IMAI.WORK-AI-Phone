<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerConversationPushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发会话推送任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerConversationPushTaskHandler extends BaseHandler
{

    /**
     * 处理触发会话推送任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerConversationPushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerConversationPushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerConversationPushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家所属微信号
         *     @type int|string $StartTime
         *           大于StartTime
         *     @type int|string $EndTime
         *           小于EndTime，0为当前时间
         *     @type bool $WithName
         *           返回会话的名称和头像
         *     @type int|string $TaskId
         *     @type int $Limit
         *          返回会话数量 缺省 5000
         *     @type int $Offset
         *          开始位置
         */

        $request = new TriggerConversationPushTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        // if (isset($data['StartTime'])) {
        //     $request->setStartTime($data['StartTime']);
        // }

        if (isset($data['EndTime'])) {
            $request->setEndTime($data['EndTime']);
        }

        if (isset($data['WithName'])) {
            $request->setWithName($data['WithName']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        // if (isset($data['Limit'])) {
        //     $request->setLimit($data['Limit']);
        // }

        if (isset($data['Offset'])) {
            $request->setOffset($data['Offset']);
        }

        return $request;
    }
}
