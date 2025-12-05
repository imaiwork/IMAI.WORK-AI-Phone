<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TriggerCirclePushTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 触发朋友圈消息推送任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TriggerCirclePushTaskHandler extends BaseHandler
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

        return $this->buildProtobufResponse(EnumMsgType::TriggerCirclePushTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TriggerCirclePushTaskMessage
     */
    protected function buildRequestContent(array $data): TriggerCirclePushTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type int|string $StartTime
         *           起始时间 选其一 单位 秒
         *     @type int[]|string[]|\Google\Protobuf\Internal\RepeatedField $CircleIds
         *           朋友圈ids 选其一
         */

        $request = new TriggerCirclePushTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['StartTime'])) {
            $request->setStartTime($data['StartTime']);
        }

        if (isset($data['CircleIds'])) {
            $request->setCircleIds($data['CircleIds']);
        }

        return $request;
    }
}
