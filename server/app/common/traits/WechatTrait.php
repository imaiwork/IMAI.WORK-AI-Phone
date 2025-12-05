<?php

declare(strict_types=1);

namespace app\common\traits;

use Channel\Client as ChannelClient;
use app\common\workerman\wechat\handlers\client\TalkToFriendTaskHandler;
use app\common\workerman\wechat\handlers\client\AcceptFriendAddRequestTaskHandler;
use app\common\workerman\wechat\handlers\client\PostSNSNewsTaskHandler;
use app\common\workerman\wechat\constants\ResponseCode;
use app\common\workerman\wechat\traits\{DeviceTrait, CacheTrait};
use Jubo\JuLiao\IM\Wx\Proto\TransportMessage;
use Google\Protobuf\Any;
use think\facade\Log;

use app\common\service\FileService;
use Predis\Client as redisClient;
use think\cache\driver\Redis;
use GuzzleHttp\Client as httpClient;

/**
 * 微信操作能力
 * @author Qasim
 * @package app\traits
 */
trait WechatTrait
{
    use DeviceTrait, CacheTrait;
    /**
     * 推送消息
     * @param array $params
     * @return array
     * @throws \Exception
     */
    protected static function wxPush(array $params): array
    {
        try {

            // 1. 参数验证
            if (!isset($params['wechat_id']) || !isset($params['friend_id']) || !isset($params['message']) || !isset($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::INVALID_PARAMS), ResponseCode::INVALID_PARAMS);
            }

            // 2. 验证设备是否存在
            if (!self::checkDevice($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::DEVICE_NOT_FOUND), ResponseCode::DEVICE_NOT_FOUND);
            }

            $deviceId = $params['device_code'];

            $data = [
                'WeChatId' => $params['wechat_id'],
                'FriendId' => $params['friend_id'],
                'Content' => $params['message'],
                'ContentType' => $params['message_type'] ?? 1,
                'Remark' => $params['remark'] ??  '',
                'MsgId' => time(),
                'Immediate' => true,
                'OptType' => $params['opt_type'] ?? 'send'
            ];
            // $key = "push:device:{$deviceId}";
            // self::redis()->lpush($key, json_encode($data, JSON_UNESCAPED_UNICODE));


            // 3. 构建消息发送请求
            $content = \app\common\workerman\wechat\handlers\client\TalkToFriendTaskHandler::handle($data);
            // 4. 构建protobuf消息
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $data = $message->serializeToString();
            // 5. 发送到设备端
            $channel = "socket.{$deviceId}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => $data
            ]);

            return [
                'code' => 10000,
                'message' => 'success'
            ];
        } catch (\Throwable $th) {
            self::setLog([
                'title' => 'Send message Device push',
                'params' => $params,
                'error' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString()
            ]);
            //throw new \Exception($th->getMessage(), $th->getCode());
            return [
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ];
        }
    }

    /**
     * 微信设备在线状态
     * @param array $params
     * @return array
     */
    public static function wxOnline(array $params): array
    {
        try {

            // 1. 参数验证
            if (!isset($params['wechat_id']) || !isset($params['device_code']) || !isset($params['type'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::INVALID_PARAMS), ResponseCode::INVALID_PARAMS);
            }

            $wechatId = $params['wechat_id'];
            $deviceId = $params['device_code'];
            $type     = $params['type'];

            if ($type == 1) {

                $online = self::isDeviceOnline($deviceId);
                $content = [
                    'online_status' => $online ? 1 : 0
                ];
            } else {
                $online = self::isDeviceOnline($deviceId);
                if ($online) {
                    // 获取微信信息
                    $key = self::getWechatKey($deviceId, $wechatId);
                    $content = self::redis()->hGetAll($key) ?: [];
                    $content['online_status'] = (isset($content['Status']) && $content['Status'] == 'online') ? 1 : 0;
                } else {
                    $content['online_status'] = 0;
                }
            }

            return [
                'code' => 10000,
                'message' => 'success',
                'data' => $content
            ];
        } catch (\Throwable $th) {
            self::setLog([
                'title' => 'Send message Device online',
                'params' => $params,
                'error' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString()
            ]);

            return [
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ];
        }
    }


    /**
     * 接受好友请求
     * @param array $params
     * @return array
     */
    protected static function wxAccept(array $params): array
    {
        try {
            self::setLog([
                'title' => 'Send message Device accept',
                'params' => $params,
            ]);
            // 1. 参数验证
            if (!isset($params['wechat_id']) || !isset($params['friend_id']) || !isset($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::INVALID_PARAMS), ResponseCode::INVALID_PARAMS);
            }

            // 2. 验证设备是否存在
            if (!self::checkDevice($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::DEVICE_NOT_FOUND), ResponseCode::DEVICE_NOT_FOUND);
            }

            $deviceId = $params['device_code'];

            // 3. 构建消息发送请求
            $content = AcceptFriendAddRequestTaskHandler::handle([
                'WeChatId' => $params['wechat_id'],
                'FriendId' => $params['friend_id'],
                'Operation' => 1,
                'TaskId' => time(),
            ]);

            // 4. 构建protobuf消息
            $message = new TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $data = $message->serializeToString();

            // 5. 发送到设备端
            $channel = "socket.{$deviceId}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => $data
            ]);

            return [
                'code' => 10000,
                'message' => 'success',
            ];
        } catch (\Throwable $th) {
            self::setLog([
                'title' => 'Send message Device accept',
                'params' => $params,
                'error' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString()
            ]);
            return [
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ];
        }
    }

    protected static function wxCircle(array $params): array
    {
        try {
            self::setLog([
                'title' => 'Send message Device pcircleush',
                'params' => $params,
            ]);
            // 1. 参数验证
            if (!isset($params['wechat_id']) || !isset($params['content']) || !isset($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::INVALID_PARAMS), ResponseCode::INVALID_PARAMS);
            }
            // 2. 验证设备是否存在
            if (!self::checkDevice($params['device_code'])) {
                throw new \Exception(ResponseCode::getMessage(ResponseCode::DEVICE_NOT_FOUND), ResponseCode::DEVICE_NOT_FOUND);
            }

            $deviceId = $params['device_code'];
            $attachmentType = $params['attachment_type'] ?? 0;
            $attachment = [];

            //0: 纯文本 1：图片 2：短视频 3：长视频 4：链接 5：小程序
            // 附件类型 0：链接 2： 图片 3：短视频 4：长视频
            switch ($attachmentType) {
                case 1:
                    $attachment = [
                        'Type' => 2,
                        'Content' => $params['attachment_content'] ?? []
                    ];
                    break;
                case 2:
                    $attachment = [
                        'Type' => 3,
                        'Content' => explode(',', $params['attachment_content']) ?? []
                    ];
                    break;
                case 3:
                    $attachment = [
                        'Type' => 0,
                        'Content' => [
                            'title' => $params['attachment_content']['link'] ?? '',
                            'desc' => $params['attachment_content']['desc'] ?? '',
                            'url' => $params['attachment_content']['link'] ?? '',
                            'thumb' => FileService::getFileUrl($params['attachment_content']['pic'] ?? ''),
                        ]
                    ];
                    break;
                case 4:
                    $attachment = [
                        'Type' => 6,
                        'Content' => [json_encode([
                            "Title" => $params['attachment_content']['title'] ?? '',
                            "Url" => "https://mp.weixin.qq.com/mp/waerrpage?appid={$params['attachment_content']['appid']}&type=upgrade&upgradetype=3#wechat_redirect",
                            "PagePath" => $params['attachment_content']['link'] ?? 'pages/index/index.html',
                            "Source" => $params['attachment_content']['source'] ?? '',
                            "SourceName" => $params['attachment_content']['SourceName'] ?? '',
                            "Thumb" => FileService::getFileUrl($params['attachment_content']['pic'] ?? ''),
                            "AppId" => $params['attachment_content']['appid'] ?? '',
                            "Icon" => FileService::getFileUrl($params['attachment_content']['pic'] ?? ''),
                            'version' => 48,
                        ], JSON_UNESCAPED_UNICODE)]
                    ];
                    break;

                default:
                    break;
            }

            $data = [
                'WeChatId' => $params['wechat_id'],
                'Content' => $params['content'],
                'Attachment' => $attachment,
                'ExtComment' => $params['comment'] ?? [],
                'TaskId' => $params['task_id'] ?? time(),
            ];
            self::setLog($data);
            // 3. 构建消息发送请求
            $content = PostSNSNewsTaskHandler::handle($data);

            // 4. 构建protobuf消息
            $message = new TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $data = $message->serializeToString();

            // 5. 发送到设备端
            $channel = "socket.{$deviceId}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => $data
            ]);

            return [
                'code' => 10000,
                'message' => 'success',
            ];
        } catch (\Throwable $th) {
            self::setLog([
                'title' => 'Send message Device circle',
                'params' => $params,
                'error' => $th->getMessage(),
                'code' => $th->getCode(),
                'trace' => $th->getTraceAsString()
            ]);
            return [
                'code' => $th->getCode(),
                'message' => $th->getMessage()
            ];
        }
    }

    public static function getWxDeviceInfo(string $deviceId): array
    {
        return self::getDeviceInfo($deviceId);
    }

    public static function isWxDeviceOnline(string $deviceId): bool
    {
        return self::isDeviceOnline($deviceId);
    }

    public static function updateWxDevices(array $deviceInfo): void
    {
        try {

            $body = \app\common\service\ToolsService::Auth()->deviceUpdate($deviceInfo);
        } catch (\Throwable $e) {
            //throw $th;
            self::setLog([
                'title' => 'updateWxDevices',
                'params' => $deviceInfo,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private static function checkDevice(string $deviceId): bool
    {
        // 获取设备信息    
        $deviceInfo = self::getDeviceInfo($deviceId);
        return $deviceInfo ? true : false;
    }

    /**
     * 获取设备信息
     * 
     * @param string $deviceId 设备ID
     * @return array
     */
    private static function getDeviceInfo(string $deviceId): array
    {
        try {

            $result = \app\common\service\ToolsService::Auth()->checkDevice($deviceId);
            if ((int)$result['code'] === 10000) {
                return $result['data'];
            } else {
                return [];
            }
        } catch (\Throwable $e) {

            self::setLog([
                'title' => 'getDeviceInfo',
                'deviceId' => $deviceId,
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * 检查设备是否在线
     * 
     * @param string $deviceId 设备ID
     * @return bool
     */
    private static function isDeviceOnline(string $deviceId): bool
    {
        $statusKey = self::getDeviceKey($deviceId, 'status');
        $status = self::redis()->get($statusKey);

        return $status === 'online';
    }

    private static function getDeviceKey(string $deviceId, string $type): string
    {
        return sprintf('%s:%s:%s', 'device', $deviceId, $type);
    }

    private static function getWechatKey(string $deviceId, string $type): string
    {
        return sprintf('%s:%s:%s', 'wechat', $deviceId, $type);
    }

    private static function redis(): Redis
    {
        return new Redis([
            'host'        => env('redis.HOST', '127.0.0.1'),
            'port'        => env('redis.PORT', 6379),
            'password'    => env('redis.PASSWORD', '123456'),
            'select'      => env('redis.WX_SELECT', 9),
            'timeout'     => 0,
            'persistent'  => true,
        ]);
    }


    private static function setLog($content)
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        //Log::write($content, 'wxchat');
    }
}
