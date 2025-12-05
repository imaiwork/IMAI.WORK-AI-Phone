<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    AddFriendsTaskMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 4、主动添加好友任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class AddFriendsTaskHandler extends BaseHandler
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

        return $this->buildProtobufResponse(EnumMsgType::AddFriendsTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return AddFriendsTaskMessage
     */
    protected function buildRequestContent(array $data): AddFriendsTaskMessage
    {
        
        
        $request = new AddFriendsTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['Phones'])) {
            $request->setPhones($data['Phones']);
        }

        if (isset($data['Message'])) {
            $request->setMessage($data['Message']);
        }

        if (isset($data['Label'])) {
            $request->setLabel($data['Label']);
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
