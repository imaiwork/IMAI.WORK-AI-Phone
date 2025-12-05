<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\ClearAllChatMsgTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 清空聊天记录任务
 */
class ClearAllChatMsgTaskHandler extends BaseHandler
{

    /**
     * 清空聊天记录任务

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ClearAllChatMsgTask, $content);
    }

    protected function buildRequestContent(array $data) : ClearAllChatMsgTaskMessage
    {
        $request = new ClearAllChatMsgTaskMessage();

        $request->setWeChatId($data['WeChatId']);


        if(isset($data['Flag'])){
            $request->setFlag($data['Flag']);
        }
        

        if(isset($data['TaskId'])){
            $request->setTaskId($data['TaskId']);
        }


        return $request;
    }
}
