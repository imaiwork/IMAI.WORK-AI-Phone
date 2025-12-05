<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\RequestContactsInfoTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 请求联系人信息任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RequestContactsInfoTaskHandler extends BaseHandler
{

    /**
     * 处理请求联系人信息任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::RequestContactsInfoTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return RequestContactsInfoTaskMessage
     */
    protected function buildRequestContent(array $data): RequestContactsInfoTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *          微信唯一Id
         *     @type string $Contact
         *           联系人username
         *     @type bool $Local
         *           只返回本地信息，不打开详情页
         */

        $request = new RequestContactsInfoTaskMessage();

        $request->setWeChatId($data['WeChatId']);

        if (isset($data['Contact'])) {
            $request->setContact($data['Contact']);
        }

        if (isset($data['Local'])) {
            $request->setLocal($data['Local']);
        }

        return $request;
    }
}
