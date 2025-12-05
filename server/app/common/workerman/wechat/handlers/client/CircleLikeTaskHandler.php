<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\CircleLikeTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 单条朋友圈点赞任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class CircleLikeTaskHandler extends BaseHandler
{

    /**
     * 处理单条朋友圈点赞任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::CircleLikeTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return CircleLikeTaskMessage
     */
    protected function buildRequestContent(array $data): CircleLikeTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type int|string $CircleId
         *           所属朋友圈
         *     @type bool $IsCancel
         *           是否取消点赞
         *     @type int|string $TaskId
         */

        $request = new CircleLikeTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['CircleId'])) {
            $request->setCircleId($data['CircleId']);
        }

        if (isset($data['IsCancel'])) {
            $request->setIsCancel($data['IsCancel']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        
        return $request;
    }
}
