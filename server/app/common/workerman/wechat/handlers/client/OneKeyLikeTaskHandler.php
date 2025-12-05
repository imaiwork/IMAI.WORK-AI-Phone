<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\OneKeyLikeTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 9、清粉任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class OneKeyLikeTaskHandler extends BaseHandler
{

    /**
     * 9、清粉任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::OneKeyLikeTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return OneKeyLikeTaskMessage
     */
    protected function buildRequestContent(array $data): OneKeyLikeTaskMessage
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

        $request = new OneKeyLikeTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        if (isset($data['Rate'])) {
            $request->setRate($data['Rate']);
        }
        
        if (isset($data['EndTime'])) {
            $request->setEndTime($data['EndTime']);
        }

        if (isset($data['Num'])) {
            $request->setNum($data['Num']);
        }

        if (isset($data['TimeOut'])) {
            $request->setTimeOut($data['TimeOut']);
        }


        return $request;
    }
}
