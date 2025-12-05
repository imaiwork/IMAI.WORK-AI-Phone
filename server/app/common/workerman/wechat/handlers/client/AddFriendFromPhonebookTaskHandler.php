<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\AddFriendFromPhonebookTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 群内加好友任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AddFriendFromPhonebookTaskHandler extends BaseHandler
{

    /**
     * 群内加好友任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::AddFriendFromPhonebookTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AddFriendFromPhonebookTaskMessage
     */
    protected function buildRequestContent(array $data): AddFriendFromPhonebookTaskMessage
    {
        
        
        $request = new AddFriendFromPhonebookTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['Count'])) {
            $request->setCount($data['Count']);
        }

        if (isset($data['Message'])) {
            $request->setMessage($data['Message']);
        }

        if (isset($data['Reset'])) {
            $request->setReset($data['Reset']);
        }

        if (isset($data['Index'])) {
            $request->setIndex($data['Index']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
