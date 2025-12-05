<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\PullFriendCircleTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 获取指定好友朋友圈任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class PullFriendCircleTaskHandler extends BaseHandler
{

    /**
     * 处理获取指定好友朋友圈
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PullFriendCircleTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PullFriendCircleTaskMessage
     */
    protected function buildRequestContent(array $data): PullFriendCircleTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type string $FriendId
         *           获取好友的朋友圈,传空则返回所有人
         *     @type int|string $StartTime
         *           废弃 //UTC秒，小于该时间，用于向下翻页
         *     @type int $Count
         *           废弃，微信每页获取10条  //最多条数，缺省20条
         *     @type int|string $RefTime
         *           废弃 //UTC秒，大于该时间，用于向上翻页
         *     @type int|string $RefSnsId
         *           首次传0，获取下一页传上一页最后一条的snsid
         */

        $request = new PullFriendCircleTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['StartTime'])) {
            $request->setStartTime($data['StartTime']);
        }

        if (isset($data['Count'])) {
            $request->setCount($data['Count']);
        }

        if (isset($data['RefTime'])) {
            $request->setRefTime($data['RefTime']);
        }

        if (isset($data['RefSnsId'])) {
            $request->setRefSnsId($data['RefSnsId']);
        }

        return $request;
    }
}
