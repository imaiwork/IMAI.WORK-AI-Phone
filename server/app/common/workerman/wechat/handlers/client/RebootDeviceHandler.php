<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\PhoneActionTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;
use Jubo\JuLiao\IM\Wx\Proto\EnumPhoneAction;

/**
 * 重启设备任务处理器
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class RebootDeviceHandler extends BaseHandler
{

    /**
     * 处理重启设备任务
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::PhoneActionTask, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return PhoneActionTaskMessage
     */
    protected function buildRequestContent(array $data): PhoneActionTaskMessage
    {
        /**
         *     Optional. Data for populating the Message object.
         *
         *     @type string $WeChatId
         *     @type string $Imei
         *           备用，用wxid或imei来定位手机
         *     @type int $Action
         *           指令
         *     @type string $StrParam
         *           字符串参数，后续扩展用
         *     @type int $IntParam
         *           整型参数，后续扩展用
         *     @type int|string $TaskId
         */

        $request = new PhoneActionTaskMessage();

        $request->setWeChatId($data['WeChatId']);
        $request->setAction(EnumPhoneAction::Reboot);

        if (isset($data['Imei'])) {
            $request->setImei($data['Imei']);
        }

        if (isset($data['StrParam'])) {
            $request->setStrParam($data['StrParam']);
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
