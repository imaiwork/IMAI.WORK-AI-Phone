<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\handlers\client;

use Jubo\JuLiao\IM\Wx\Proto\{
    UpgradeDeviceAppNoticeMessage,
    DeviceAppUpgradeMessage
};
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;

/**
 * 3、设备应用升级通知
 * 
 * @method static array handle(array $data) 业务处理
 * @author Qasim
 * @package app\handlers\client
 */
class DeviceAppUpgradeHandler extends BaseHandler
{

    /**
     * 3、设备应用升级通知
     *
     * @param array $data 请求数据
     * @return array
     */
    protected function handle(array $data): array
    {
        // 构造推送任务请求
        $content = $this->buildRequestContent($data);

        return $this->buildProtobufResponse(EnumMsgType::UpgradeDeviceAppNotice, $content);
    }

    /**
     * 构建推送任务请求内容
     *
     * @param array $data 请求数据
     * @return UpgradeDeviceAppNoticeMessage
     */
    protected function buildRequestContent(array $data): UpgradeDeviceAppNoticeMessage
    {


        $request = new UpgradeDeviceAppNoticeMessage();

        $request->setWeChatId($data['WeChatId']);
        if (isset($data['IMEI'])) {
            $request->setIMEI($data['IMEI']);
        }

        if (isset($data['AppInfos'])) {
            $appinfo = new DeviceAppUpgradeMessage();

            if (isset($data['AppInfos']['Version'])) {
                $appinfo->setVersion($data['AppInfos']['Version']);
            }

            if (isset($data['AppInfos']['VerNumber'])) {
                $appinfo->setVerNumber($data['AppInfos']['VerNumber']);
            }

            if (isset($data['AppInfos']['PackageName'])) {
                $appinfo->setPackageName($data['AppInfos']['PackageName']);
            }

            if (isset($data['AppInfos']['PackageUrl'])) {
                $appinfo->setPackageUrl($data['AppInfos']['PackageUrl']);
            }

            $request->setAppInfos([$appinfo]);
        }

        return $request;
    }
}
