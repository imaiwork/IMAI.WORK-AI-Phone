<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\PullFriendAddReqListTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 获取朋友圈的图片任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class PullFriendAddReqListTaskHandLer extends BaseHandler
{

    /**
     * 处理获取朋友圈的图片任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PullFriendAddReqListTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PullFriendAddReqListTaskMessage
     */
    protected function buildRequestContent(array $data): PullFriendAddReqListTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type int|string $CircleId
         *             
         *     @type bool $GetBigMap
         */

        $request = new PullFriendAddReqListTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['StartTime'])) {
            $request->setStartTime($data['StartTime']);
        }
        
        if (isset($data['OnlyNew'])) {
            $request->setOnlyNew($data['OnlyNew']);
        }

        if (isset($data['GetAll'])) {
            $request->setGetAll($data['GetAll']);
        }

        return $request;
    }
}
