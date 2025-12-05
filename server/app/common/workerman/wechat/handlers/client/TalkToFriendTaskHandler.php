<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\TalkToFriendTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 给好友发送消息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class TalkToFriendTaskHandler extends BaseHandler
{

    /**
     * 处理给好友发送消息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::TalkToFriendTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return TalkToFriendTaskMessage
     */
    protected function buildRequestContent(array $data): TalkToFriendTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           所属微信号
         *     @type string $FriendId
         *           发送给那个好友
         *     @type int $ContentType
         *           发送消息类型 Text Picture Voice Video Link File NameCard WeApp Quote Emoji ShiPinHao
         *     @type string $Content
         *           发送消息内容 文本; 图片，视频，语音，文件url; 链接json; 名片wxid; Emoji的md5或Emoji的详细json
         *     @type string $Remark
         *           其他备注信息，群聊&#64;别人；Quote（引用消息）：引用消息的msgSvrId字符串
         *     @type int|string $MsgId
         *           发送给手机端的时候需要赋值，用于TalkToFriendTaskResultNotice中
         *     @type bool $Immediate
         *          立即发送（用于群发消息时优先发送聊天消息）
         */

        $request = new TalkToFriendTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['FriendId'])) {
            $request->setFriendId($data['FriendId']);
        }

        if (isset($data['ContentType'])) {
            $request->setContentType($data['ContentType']);
        }

        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }

        if (isset($data['Remark'])) {
            $request->setRemark($data['Remark']);
        }

        if (isset($data['MsgId'])) {
            $request->setMsgId($data['MsgId']);
        }

        if (isset($data['Immediate'])) {
            $request->setImmediate($data['Immediate']);
        }

        return $request;
    }
}
