<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerHistoryMsgPushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发历史消息推送任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerHistoryMsgPushTaskHandler extends BaseHandler
{

    /**
     * 处理触发历史消息推送任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TriggerHistoryMsgPushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerHistoryMsgPushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerHistoryMsgPushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $FriendId
         *           好友id, 置空表示全部好友
         *     @type int|string $StartTime
         *           开始同步的消息时间，0表示全部,UTC毫秒
         *     @type int|string $EndTime
         *           结束同步的消息时间，0表示到当前时间为止
         *     @type int $Flag
         *           获取全部时，0：只有好友， 1：只有群聊， 2：所有（好友和群聊）
         *     @type int $Count
         *           单个会话获取的最多消息数
         *     @type int|string $TaskId
         */

        $request = new TriggerHistoryMsgPushTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['StartTime'])) {
            $request->setStartTime($data['StartTime']);
        }

        if (isset($data['EndTime'])) {
            $request->setEndTime($data['EndTime']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        if (isset($data['Flag'])) {
            $request->setFlag($data['Flag']);
        }

        if (isset($data['Count'])) {
            $request->setCount($data['Count']);
        }

        return $request;
    }
}
