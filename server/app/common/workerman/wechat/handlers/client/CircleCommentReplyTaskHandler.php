<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\CircleCommentReplyTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 朋友圈评论回复任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class CircleCommentReplyTaskHandler extends BaseHandler
{

    /**
     * 处理朋友圈评论回复任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::CircleCommentReplyTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return CircleCommentReplyTaskMessage
     */
    protected function buildRequestContent(array $data): CircleCommentReplyTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信
         *     @type int|string $CircleId
         *           所属朋友圈
         *     @type string $ToWeChatId
         *           回复的好友
         *     @type string $Content
         *           回复的内容
         *     @type int|string $ReplyCommentId
         *           回复的评论id
         *     @type int|string $TaskId
         *           本地的评论表数据id （重发需要传递）
         *     @type bool $IsResend
         *           是否是重发 （手机端忽略）
         */

        $request = new CircleCommentReplyTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['CircleId'])) {
            $request->setCircleId($data['CircleId']);
        }

        if (isset($data['ToWeChatId'])) {
            $request->setToWeChatId($data['ToWeChatId']);
        }
        
        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }
        
        if (isset($data['ReplyCommentId'])) {
            $request->setReplyCommentId($data['ReplyCommentId']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        
        if (isset($data['IsResend'])) {
            $request->setIsResend($data['IsResend']);
        }

        return $request;
    }
}
