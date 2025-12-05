<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\ContactLabelDeleteTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 标签删除任务
 */
class ContactLabelDeleteTaskHandler extends BaseHandler
{

    /**
     * 处理标签删除请求

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ContactLabelDeleteTask, $content);
    }

    protected function buildRequestContent(array $data) : ContactLabelDeleteTaskMessage
    {
        $request = new ContactLabelDeleteTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }

        if(isset($data['LabelId'])){
            $request->setLabelId($data['LabelId']);
        }
        

        return $request;
    }
}
