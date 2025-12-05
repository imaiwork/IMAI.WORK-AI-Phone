<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    DeleteSNSNewsTaskMessage

};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 3、删除朋友圈任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class DeleteSNSNewsTaskHandler extends BaseHandler
{

    /**
     * 3、删除朋友圈任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::DeleteSNSNewsTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return DeleteSNSNewsTaskMessage
     */
    protected function buildRequestContent(array $data): DeleteSNSNewsTaskMessage
    {
        
        
        $request = new DeleteSNSNewsTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        if (isset($data['CircleId'])) {
            $request->setCircleId((int)$data['CircleId']);
        }
        
        if (isset($data['TaskId'])) {
            $request->setTaskId((int)$data['TaskId']);
        }

        return $request;
    }
}
