<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\GetContactInfoTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 获取群成员详细信息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class GetContactInfoTaskHandler extends BaseHandler
{

    /**
     * 处理群成员详细信息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::GetContactInfoTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return GetContactInfoTaskMessage
     */
    protected function buildRequestContent(array $data): GetContactInfoTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          
         *     @type string $Contact
         *           联系人username
         *     @type string $Chatroom
         *           共同的群聊
         *     @type string $Ticket
         *           备用
         *     @type int|string $TaskId
         */

        $request = new GetContactInfoTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['Chatroom'])) {
            $request->setChatroom($data['Chatroom']);
        }

        if (isset($data['Contact'])) {
            $request->setContact($data['Contact']);
        }
        
        if (isset($data['Ticket'])) {
            $request->setContact($data['Ticket']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setContact($data['TaskId']);
        }

        return $request;
    }
}
