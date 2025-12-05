<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

use Workerman\Connection\TcpConnection;
use Jubo\JuLiao\IM\Wx\Proto\PostDeviceInfoNoticeMessage;
use Jubo\JuLiao\IM\Wx\Proto\PhoneActionTaskMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;
use Jubo\JuLiao\IM\Wx\Proto\EnumPhoneAction;
use app\common\workerman\wechat\constants\ResponseCode;
use GuzzleHttp\Client;
/**
 * 设备服务
 * 
 * 处理设备信息:
 * - 信息更新
 * - 信息查询
 * - 状态维护
 * 
 * @author Qasim
 * @package app\service
 */
class DeviceService extends BaseService
{
    /**
     * 处理设备信息更新通知
     * 
     * @param TcpConnection $conn 连接实例
     * @param string $data 数据
     * @return void
     */
    public function handlePostDeviceInfoNotice(TcpConnection $conn, string $data): void
    {
        try {
            $notice = new PostDeviceInfoNoticeMessage();
            $notice->mergeFromString($data);

            /**
             *     Optional. Data for populating the Message object.
             *
             *     @type string $PhoneBrand
             *           手机品牌
             *     @type string $PhoneModel
             *           手机型号
             *     @type int $OSVerNumber
             *     @type \Jubo\JuLiao\IM\Wx\Proto\PostDeviceInfoNoticeMessage\DeviceAppInfoMessage[]|\Google\Protobuf\Internal\RepeatedField $AppInfos
             *           App信息
             *     @type string $NetType
             *     @type string $WeChatId
             *           微信id
             *     @type string $IMEI
             *     @type string $IMSI1
             *           SIM卡1的IMSI
             *     @type string $IMSI2
             *           SIM卡2的IMSI,
             *     @type string $Number1
             *           SIM卡1的手机号，有可能读不到
             *     @type string $Number2
             *           SIM卡2的手机好，有可能读不到
             *     @type bool $IsHook
             *     @type bool $WxSupport             */

            $deviceId = $conn->deviceId;
            $appInfos = [];
            /**
             *     Optional. Data for populating the Message object.
             *
             *     @type string $PackageName
             *     @type string $AppName
             *     @type int $VerNumber
             *     @type string $Version
             * @var \Jubo\JuLiao\IM\Wx\Proto\PostDeviceInfoNoticeMessage\DeviceAppInfoMessage $appInfo
             */
            foreach ($notice->getAppInfos() as $appInfo) {
                $appInfos[] = [
                    'PackageName' => $appInfo->getPackageName(),
                    'AppName' => $appInfo->getAppName(),
                    'VerNumber' => $appInfo->getVerNumber(),
                    'Version' => $appInfo->getVersion(),
                ];
            }
            $info = [
                // 设备ID
                'DeviceId' => $deviceId,
                // 微信ID
                'WeChatId' => $notice->getWeChatId(),
                // 手机品牌
                'PhoneBrand' => $notice->getPhoneBrand(),
                // 手机型号
                'PhoneModel' => $notice->getPhoneModel(),
                // 操作系统版本
                'OSVerNumber' => $notice->getOSVerNumber(),
                // APP信息
                'AppInfos' => $appInfos,
                // 网络类型
                'NetType' => $notice->getNetType(),
                // imei号
                'IMEI' => $notice->getIMEI(),
                // SIM卡1的IMSI
                'IMSI1' => $notice->getIMSI1(),
                // SIM卡2的IMSI
                'IMSI2' => $notice->getIMSI2(),
                // 手机号
                'Number1' => $notice->getNumber1(),
                // 手机号
                'Number2' => $notice->getNumber2(),
                // 是否Hook
                'IsHook' => $notice->getIsHook(),
                // 是否支持微信
                'WxSupport' => $notice->getWxSupport()
            ];

            // 存储信息
            $key = $this->service->cacheService->getDeviceKey($deviceId, 'info');
            $this->service->cacheService->getRedis()->hMSet($key, $info);

            $this->logInfo('Device info updated', [
                'deviceId' => $deviceId,
                'info' => $info
            ]);
        } catch (\Throwable $e) {
            $this->handleSysError($conn, $e);
        }
    }

    /**
     * 重启设备 
     * 
     * @param TcpConnection $connection 连接实例
     * @param array $data 数据
     * @return void
     */
    public function handleClientRebootDevice(TcpConnection $connection, array $data): void
    {
        try {
            $deviceId = $connection->deviceId;

            // 构造推送任务请求
            $request = new PhoneActionTaskMessage();

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

            // 可设置的参数组
            $params = ["WeChatId", "Imei", "Action", "StrParam", "IntParam", "TaskId"];

            foreach ($params as $param) {
                if (isset($data[$param])) {
                    $method = 'set' . $param;
                    $request->{$method}($data[$param]);
                }
            }

            // 固定设置Action
            $request->setAction(EnumPhoneAction::Reboot);

            // 构建传输消息
            $message = $this->service->messageService->buildTransportMessage(
                EnumMsgType::PhoneActionTask,
                $request
            );

            // 发送到设备端
            $this->service->connectionService->sendChannelMessage($deviceId, $message);

            $this->logInfo('Reboot device task sent', [
                'deviceId' => $deviceId
            ]);
        } catch (\Throwable $e) {
            $this->logError('Reboot device error', [
                'error' => $e->getMessage(),
                'deviceId' => $connection->deviceId ?? ''
            ]);
            $this->service->responseService->sendError($connection, ResponseCode::SYSTEM_ERROR);
        }
    }

    /**
     * 处理客户端查询设备信息请求
     * 
     * @param TcpConnection $conn 连接实例
     * @return void
     */
    public function handleClientDeviceInfoRequest(TcpConnection $conn): void
    {
        try {
            $deviceId = $conn->deviceId;

            // 获取设备信息
            $deviceInfo = $this->getDeviceInfo($deviceId);

            if (empty($deviceInfo)) {
                $this->service->responseService->sendError($conn, ResponseCode::DEVICE_NOT_FOUND);
                return;
            }

            $deviceInfo['IsOnline'] = $this->service->connectionService->isDeviceOnline($deviceId);
            $this->updateDevices($deviceInfo);

            $response = [
                'MsgType' => 'DeviceInfo',
                'Content' => $deviceInfo
            ];

            $this->service->responseService->sendSuccess($conn, $response);
        } catch (\Throwable $e) {
            $this->handleSysError($conn, $e);
        }
    }

    /**
     * 处理客户端添加设备请求
     * 
     * @param TcpConnection $conn 连接实例
     * @return void
     */
    public function handleClientAddDeviceRequest(TcpConnection $conn): void
    {
        try {
            // 获取设备信息
            $deviceInfo = $this->getDeviceInfo($conn->deviceId);

            // 检查设备是否存在
            if (empty($deviceInfo)) {
                $this->service->responseService->sendError($conn, ResponseCode::DEVICE_NOT_FOUND);
                return;
            }

            // 检查设备是否被使用
            if ($deviceInfo['IsUsed']) {
                $this->service->responseService->sendError($conn, ResponseCode::DEVICE_ALREADY_USED);
                return;
            }

            $deviceInfo['IsUsed'] = true;
            $deviceInfo['IsOnline'] = $this->service->connectionService->isDeviceOnline($conn->deviceId);

            $this->updateDevices($deviceInfo);

            $this->service->responseService->sendSuccess($conn, $deviceInfo);
        } catch (\Throwable $e) {
            $this->handleSysError($conn, $e);
        }
    }


    /**
     * 检查设备
     * 
     * @param string $deviceId 设备ID
     * @return bool
     */
    public function checkDevice(string $deviceId): bool
    {
        try {
            // 获取设备信息    
            $deviceInfo = $this->getDeviceInfo($deviceId);

            return $deviceInfo ? true : false;
        } catch (\Throwable $e) {
            $this->logError('Check device error', [
                'error' => $e->getMessage(),
                'deviceId' => $deviceId
            ]);
            return false;
        }
    }
    
    public function checkSvDevice(string $deviceId): bool
    {
        try {
            // 获取设备信息    
            $deviceInfo = $this->getSvDeviceInfo($deviceId);
            return $deviceInfo ? true : false;
        } catch (\Throwable $e) {
            $this->logError('Check device error', [
                'error' => $e->getMessage(),
                'deviceId' => $deviceId
            ]);
            return false;
        }
    }
    public function checkSvAuthcode(string $deviceId, string $platform): bool
    {
        try {
            // 获取设备信息    
            $deviceInfo = $this->getSvAuthcodeInfo($deviceId, $platform);

            return $deviceInfo ? true : false;
        } catch (\Throwable $e) {
            $this->logError('Check device error', [
                'error' => $e->getMessage(),
                'deviceId' => $deviceId
            ]);
            return false;
        }
    }
    
    public function checkSvDeviceAuth(array $device, string $platform): bool
    {
        try {
            // 获取设备信息    
            $codeInfo  = $this->getSvDeviceAuthInfo($device, $platform);

            return $codeInfo ? true : false;
        } catch (\Throwable $e) {
            $this->logError('Check device error', [
                'error' => $e->getMessage(),
                'deviceId' => $device['DeviceId']
            ]);
            return false;
        }
    }
    
    public function getDeviceDetail(string $deviceId): array
    {
        try {
            // 获取设备信息
            $deviceInfo = $this->getDeviceInfo($deviceId);
            return $deviceInfo? $deviceInfo : []; // 返回设备信息或空数组，如果设备不存在则返回空数组
        }catch (\Throwable $e) {
            $this->logError('Get device detail error', [
                'error' => $e->getMessage(),
                'deviceId' => $deviceId
            ]);
            return [];
        }
    }
    
    public function getSvDeviceDetail(string $deviceId): array
    {
        try {
            // 获取设备信息
            $deviceInfo = $this->getSvDeviceInfo($deviceId);
            return $deviceInfo? $deviceInfo : []; // 返回设备信息或空数组，如果设备不存在则返回空数组
        }catch (\Throwable $e) {
            $this->logError('Get device detail error', [
                'error' => $e->getMessage(),
                'deviceId' => $deviceId
            ]);
            return [];
        }
    }
    
    private function getSvDeviceAuthInfo(array $device, string $platform): array
    {
        $codes = json_decode(file_get_contents(public_path($platform . '_code.json')), true);
        foreach ($codes as $item) {
            if ($item['Code'] === $device['Code'] && $item['DeviceId'] == $device['DeviceId']) {
                return $item;
            }
        }

        return [];
    }
    
    public function getSvAuthcodeInfo(string $code, string $platform): array
    {
        $codes = json_decode(file_get_contents(public_path($platform . '_code.json')), true);
        foreach ($codes as $item) {
            if ($item['Code'] === $code) {
                return $item;
            }
        }

        return [];
    }
    /**
     * 获取设备信息
     * 
     * @param string $deviceId 设备ID
     * @return array
     */
    private function getDeviceInfo(string $deviceId): array
    {
        $devices = json_decode(file_get_contents(public_path('device.json')), true);

        foreach ($devices as $item) {
            if ($item['DeviceId'] === $deviceId) {
                return $item;
            }
        }

        return [];
    }
    
    private function getSvDeviceInfo(string $deviceId): array
    {
        $devices = json_decode(file_get_contents(public_path('svdevice.json')), true);
        
        foreach ($devices as $item) {
            if (strtolower(trim($item['DeviceId'])) === strtolower(trim($deviceId))) {
                
                return $item;
            }
        }

        return [];
    }
    
    public function domainCheck(string $url):bool
    {
        $result = \app\common\service\ToolsService::Auth()->checkDomain($url);
        if((int)$result['code'] === 200){
            return $result['data']['check'] == true ? true : false;
        }else{
            return false;
        }
    }
    
    public function checkDeviceRpaVersion(string $versionCode):array
    {
        $result = \app\common\service\ToolsService::Auth()->checkRpaVersion($versionCode);
        return $result;
        
    }

    /**
     * 更新设备池
     * 
     * @param array $device 设备池
     * @return void
     */
    private function updateDevices(array $device): void
    {
        $devices = json_decode(file_get_contents(public_path('device.json')), true);

        foreach ($devices as $key => $item) {
            if ($item['DeviceId'] === $device['DeviceId']) {
                $devices[$key] = $device;
            }
        }

        file_put_contents(public_path('device.json'), json_encode($devices));
    }
    
    
    /**
     * 更新设备池
     * 
     * @param array $device 设备池
     * @return void
     */
    public function updateDevicesAuthCode(array $device, array $code, string $platform): bool
    {
        
        $devices = json_decode(file_get_contents(public_path('svdevice.json')), true);
        foreach ($devices as $key => $item) {
            if ($item['DeviceId'] === $device['DeviceId']) {
                $devices[$key] = $device;
            }
        }
        $devres = file_put_contents(public_path('svdevice.json'), json_encode($devices));
        
        $codes = json_decode(file_get_contents(public_path($platform . '_code.json')), true);
        foreach ($codes as $key => $item) {
            if ($item['Code'] === $code['Code']) {
                $codes[$key] = $code;
            }
        }
        $coderes = file_put_contents(public_path($platform . '_code.json'), json_encode($codes, JSON_UNESCAPED_UNICODE));
        
        return $devres && $coderes;
    }
}
