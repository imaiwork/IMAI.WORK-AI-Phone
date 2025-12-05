<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    AddFriendNameCardTaskMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 4、主动添加好友任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AddFriendNameCardTaskHandler extends BaseHandler
{

    /**
     * 主动添加好友任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::AddFriendNameCardTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AddFriendNameCardTaskMessage
     */
    protected function buildRequestContent(array $data): AddFriendNameCardTaskMessage
    {
        
        
        $request = new AddFriendNameCardTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['MsgSvrId'])) {
            $request->setMsgSvrId($data['MsgSvrId']);
        }

        if (isset($data['Message'])) {
            $request->setMessage($data['Message']);
        }
        
        if (isset($data['Remark'])) {
            $request->setRemark($data['Remark']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        return $request;
    }
}
