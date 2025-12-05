<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\WechatSettingTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 微信设置(改昵称，头像)任务
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class WechatSettingTaskHandler extends BaseHandler
{

    /**
     * 微信设置(改昵称，头像)任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::WechatSettingTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return WechatSettingTaskMessage
     */
    protected function buildRequestContent(array $data): WechatSettingTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *           商家个人微信内部全局唯一识别码
         */
        $request = new WechatSettingTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        if (isset($data['Action'])) {
            $request->setAction($data['Action']);
        }
        if (isset($data['Content'])) {
            $request->setContent($data['Content']);
        }
        if (isset($data['IntParam'])) {
            $request->setIntParam($data['IntParam']);
        }
        if (isset($data['TaskId'])) {
            $request->setTaskId($data['TaskId']);
        }
        return $request;
    }
}
