<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\ContactSetLabelTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 设置标签到好友任务
 */
class ContactSetLabelTaskHandler extends BaseHandler
{

    /**
     * 处理设置标签到好友任务请求

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ContactSetLabelTask, $content);
    }

    protected function buildRequestContent(array $data) : ContactSetLabelTaskMessage
    {
        $request = new ContactSetLabelTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if(isset($data['FriendId'])){
            $request->setFriendId($data['FriendId']);
        }

        if(isset($data['LabelIds'])){
            $request->setLabelIds($data['LabelIds']);
        }
        
        if(isset($data['TaskId'])){
            $request->setTaskId($data['TaskId']);
        }


        return $request;
    }
}
