<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\AcceptFriendAddRequestTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 接受好友请求处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AcceptFriendAddRequestTaskHandler extends BaseHandler
{

    /**
     * 处理接受好友请求
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::AcceptFriendAddRequestTask, $content);
    }
    
    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AcceptFriendAddRequestTaskMessage
     */
    protected function buildRequestContent(array $data): AcceptFriendAddRequestTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         *     @type string $FriendId
         *           请求的好友微信内部全局唯一识别码
         *     @type bool $AddWithWW
         *           去企业微信添加
         *     @type int $Operation
         *           处理结果
         *     @type string $Remark
         *           备注信息
         *     @type string $FriendNick
         *           好友的微信昵称 没用
         *     @type string $ReplyMsg
         *           拒绝时的回复消息
         *     @type int|string $TaskId
         *          任务Id
         *     @type bool $OnlyWW
         *           只在企微添加
         */

        $request = new AcceptFriendAddRequestTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['Operation'])) {
            $request->setOperation($data['Operation']);
        }

        if (isset($data['Remark'])) {
            $request->setRemark($data['Remark']);
        }

        if (isset($data['FriendNick'])) {
            $request->setFriendNick($data['FriendNick']);
        }
        
        if (isset($data['AddWithWW'])) {
            $request->setAddWithWW($data['AddWithWW']);
        }
        
        if (isset($data['OnlyWW'])) {
            $request->setOnlyWW($data['OnlyWW']);
        }
        
        if (isset($data['ReplyMsg'])) {
            $request->setReplyMsg($data['ReplyMsg']);
        }

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
