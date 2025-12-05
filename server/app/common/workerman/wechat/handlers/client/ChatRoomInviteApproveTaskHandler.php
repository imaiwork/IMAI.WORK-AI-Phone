<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\ChatRoomInviteApproveTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 群主通过入群申请任务
 */
class ChatRoomInviteApproveTaskHandler extends BaseHandler
{

    /**
     * 群主通过入群申请任务

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ChatRoomInviteApproveTask, $content);
    }

    protected function buildRequestContent(array $data) : ChatRoomInviteApproveTaskMessage
    {
        $request = new ChatRoomInviteApproveTaskMessage();

        $request->setWeChatId($data['WeChatId']);


        if(isset($data['RoomId'])){
            $request->setRoomId($data['RoomId']);
        }

        if(isset($data['MsgSvrId'])){
            $request->setMsgSvrId($data['MsgSvrId']);
        }

        if(isset($data['MsgContent'])){
            $request->setMsgContent($data['MsgContent']);
        }
        

        if(isset($data['TaskId'])){
            $request->setTaskId($data['TaskId']);
        }


        return $request;
    }
}
