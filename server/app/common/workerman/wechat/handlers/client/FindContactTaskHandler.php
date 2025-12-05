<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\FindContactTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 查找微信联系人任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class FindContactTaskHandler extends BaseHandler
{

    /**
     * 查找微信联系人任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::FindContactTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return FindContactTaskMessage
     */
    protected function buildRequestContent(array $data): FindContactTaskMessage
    {
        

        $request = new FindContactTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        
        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }
        
        

        return $request;
    }
}
