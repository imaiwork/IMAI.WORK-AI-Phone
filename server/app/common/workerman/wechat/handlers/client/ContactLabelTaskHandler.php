<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;
use Jubo\JuLiao\IM\Wx\Proto\ContactLabelTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 标签添加任务
 */
class ContactLabelTaskHandler extends BaseHandler
{

    /**
     * 处理标签添加请求

     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {

        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::ContactLabelTask, $content);
    }

    protected function buildRequestContent(array $data) : ContactLabelTaskMessage
    {
        $request = new ContactLabelTaskMessage();

        $request->setWeChatId($data['WeChatId']);


        if(isset($data['LabelName'])){
            $request->setLabelName($data['LabelName']);
        }

        if(isset($data['LabelId'])){
            $request->setLabelId($data['LabelId']);
        }

        if(isset($data['AddList'])){
            $request->setAddList($data['AddList']);
        }

        if(isset($data['DelList'])){
            $request->setDelList($data['DelList']);
        }

        if(isset($data['TaskId'])){
            $request->setTaskId($data['TaskId']);
        }


        return $request;
    }
}
