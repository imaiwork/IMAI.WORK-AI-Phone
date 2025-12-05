<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\services;

use app\common\workerman\wechat\traits\{LoggerTrait, ResponseTrait, CacheTrait, AichatTrait, OperationTrait, TaskNoticeTrait, AiCircleTrait};
use app\common\workerman\wechat\validators\MessageValidator;
use app\common\workerman\wechat\exceptions\ResponseException;
use Workerman\Connection\TcpConnection;
use Jubo\JuLiao\IM\Wx\Proto\TransportMessage;
use Jubo\JuLiao\IM\Wx\Proto\EnumMsgType;
use app\common\workerman\wechat\constants\ResponseCode;
use app\common\workerman\wechat\constants\ClientRequestMsgType;
use Channel\Client;
use Google\Protobuf\Any;
use Google\Protobuf\Internal\Message;
use app\common\workerman\wechat\constants\SocketType;
use app\common\workerman\wechat\services\Service;

/**
 * 消息核心服务
 * 
 * 消息处理:
 * - 进程间消息互通
 * - 构建protobuf消息
 * - 参数验证
 * - 自动分发业务
 * 
 * @author Qasim
 * @package app\service
 */
class MessageService
{
    use LoggerTrait, ResponseTrait, CacheTrait, AichatTrait, OperationTrait, TaskNoticeTrait, AiCircleTrait;

    private MessageValidator $validator;
    protected Service $service;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->validator = new MessageValidator();
        $this->service = Service::getInstance();
    }

    /**
     * 设备消息处理器
     * 
     * @param TcpConnection $connection 连接实例
     * @param string $data 消息数据
     * @return void
     */
    public function handleDeviceMessage(TcpConnection $connection, string $data): void
    {

        $message = new TransportMessage();
        $message->mergeFromString($data);

        $msgType = $message->getMsgType();
        $content = $message->getContent();

        try {

            // 特殊消息类型处理
            $content = match ($msgType) {
                EnumMsgType::HeartBeatReq => '',
                default => $content->getValue()
            };


            // 构建业务数据上下文
            $context = ['Connection' => $connection];

            // 获取设备、验证设备
            if ($msgType !== EnumMsgType::DeviceAuthReq) {

                // 验证消息
                $deviceId = $this->validator->validateDeviceMessage($connection);

                $context['DeviceId'] = $deviceId;
            }

            // 获取处理器类
            $handlerClass = $this->getHandlerClass($msgType);

            // 调用业务处理，获取响应内容
            $response = $handlerClass::handle($content, $context);
            if ($msgType !== EnumMsgType::HeartBeatReq) {

                // 记录下日志
                // $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Device message received')->withContext([
                //     'msgType' => EnumMsgType::name($msgType),
                //     'content' => $content,
                // ])->log();
            }
            // 获取设备ID
            if ($msgType === EnumMsgType::DeviceAuthReq) {
                $deviceId = $connection->deviceId;

                unset($connection->deviceId);

                // 绑定设备连接
                $this->service->connectionService->bindConnection($connection, $deviceId, SocketType::SOCKET);
            }

            if ($msgType == 1025) {

                $this->voiceToTextOpt($deviceId, $response);
                $this->AddFriendsTaskOpt($deviceId, $response);
                $this->SphPostTaskOpt($deviceId, $response);
            }
            if ($msgType == 1027) {
                $this->AcceptFriendAddRequestTaskOpt($deviceId, $response);
            }

            if ($msgType == 2029) {
                $this->circleReplyLikeTask($deviceId, $response);
            }
            if ($msgType == 1073) {
                $this->circlePostTaskOpt($deviceId, $response);
            }

            // 根据消息类型处理响应
            match ($msgType) {
                // Socket设备消息响应
                EnumMsgType::HeartBeatReq => $this->sendChannelMessage(SocketType::SOCKET, $deviceId, $response),
                EnumMsgType::DeviceAuthReq => $this->sendChannelMessage(SocketType::SOCKET, $deviceId, $response),
                EnumMsgType::FriendTalkNotice => $this->sendFriendTalkNoticeMessage(SocketType::SOCKET, $deviceId, $response, $connection),
                EnumMsgType::FriendAddNotice => $this->sendFriendAddNotice(SocketType::SOCKET, $deviceId, $response, $connection),
                // 其他业务消息通过Channel广播
                default => $this->sendChannelMessage(SocketType::WEBSOCKET, $deviceId, $response)
            };
        } catch (ResponseException $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Device message handle failed')->withContext([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'msgType' => $msgType,
                'trace' => $e->getTraceAsString()
            ])->log();

            // 发送错误消息到Socket进程
            $response = \app\common\workerman\wechat\handlers\device\ErrorHandler::handle($e->getCode(), $e->getMessage());

            $meesage = $this->buildMessage($response);

            // 发送消息到Socket进程 
            $connection->send(pack('N', strlen($meesage)) . $meesage);
        }
    }

    /**
     * 客户端消息处理器
     * 
     * @param TcpConnection $connection 连接实例
     * @param array $message 消息数据
     * @return void
     */
    public function handleClientMessage(TcpConnection $connection, array $message): void
    {
        // 验证消息
        [$msgType, $content] = $this->validator->validateClientMessage($connection, $message);

        try {
            if ($msgType !== ClientRequestMsgType::HEARTBEAT) {
                $this->withChannel('wechat_socket')->withLevel('info')->withTitle('Client message received')->withContext([
                    'msgType' => $msgType,
                    'content' => $content,
                    //'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                ])->log();
            }

            // 追加连接实例
            $content['Connection'] = $connection;

            // 获取对应的业务处理器
            $handlerClass = $this->getHandlerClass($msgType);
            $response = $handlerClass::handle($content);
            //$response = json_encode($response, JSON_UNESCAPED_UNICODE);
            // 绑定设备连接
            if ($msgType === ClientRequestMsgType::AUTH) {
                $this->service->connectionService->bindConnection($connection, $content['DeviceId'], SocketType::WEBSOCKET);
            }

            // 发送响应消息
            match ($msgType) {
                // Socket设备消息响应
                ClientRequestMsgType::AUTH => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::HEARTBEAT => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::ADD_DEVICE => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::REMOVE_DEVICE => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::WX_INFO => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::DEVICE_INFO => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                ClientRequestMsgType::CLEAN_CACHE => $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE)),
                // 其他业务消息通过Channel广播
                default => $this->sendChannelMessage(SocketType::SOCKET, $content['DeviceId'], $response)
            };
        } catch (ResponseException $e) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Client message handle failed')->withContext([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'msgType' => $msgType,
                'trace' => $e->getTraceAsString()
            ])->log();
            unset($content['Connection']);
            $response = \app\common\workerman\wechat\handlers\client\ErrorHandler::handle($e->getCode(), $e->getMessage(), $msgType, $content);
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Client message handle response failed')->withContext([
                'response' => $response
            ])->log();
            $connection->send(json_encode($response, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 发送消息到指定进程
     * 
     * @param string $targetProcess 目标进程
     * @param string $deviceId 设备ID
     * @param array $data 消息内容
     * @return void
     */
    public function sendChannelMessage(string $targetProcess, string $deviceId, array $data): void
    {

        if ($targetProcess === SocketType::SOCKET) {

            // 构建protobuf消息
            $data = $this->buildMessage($data);
        }
        $channel = "{$targetProcess}.{$deviceId}.message";
        Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        Client::publish($channel, [
            'data' => is_array($data) ? json_encode($data) : $data
        ]);
    }


    /**
     * 构建protobuf消息
     * 
     * @param array $data 消息内容
     * @return string 消息内容
     */
    public function buildMessage(array $data): string
    {

        $message = new TransportMessage();
        $message->setMsgType($data['MsgType']);

        $any = new Any();
        $any->pack($data['Content']);
        $message->setContent($any);
        return $message->serializeToString();
    }

    /**
     * 构建传输消息
     * 
     * @param int $msgType 消息类型
     * @param Message $content 消息内容
     * @return string
     */
    public function buildTransportMessage(int $msgType, Message $content): string
    {
        $message = new TransportMessage();
        $message->setMsgType($msgType);

        $any = new Any();
        $any->pack($content);
        $message->setContent($any);

        return $message->serializeToString();
    }


    /**
     * 获取消息处理器
     * 
     * @param string|int $type 消息类型
     * @return string 处理器类名
     */
    private function getHandlerClass(string|int $type): string
    {
        // 处理设备消息类型
        if (is_numeric($type)) {
            $handlerClass = 'app\\common\\workerman\\wechat\\handlers\\device\\' . EnumMsgType::name($type) . 'Handler';
        } else {
            // 处理客户端消息类型
            $handlerClass = 'app\\common\\workerman\\wechat\\handlers\\client\\' . ucfirst($type) . 'Handler';
        }

        if (!class_exists($handlerClass)) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Handler not found')->withContext([
                'type' => $type,
                'class' => $handlerClass
            ])->log();
            throw new ResponseException(ResponseCode::HANDLER_NOT_FOUND);
        }

        // 验证是否实现了静态handle方法
        if (!method_exists($handlerClass, 'handle')) {
            $this->withChannel('wechat_socket')->withLevel('error')->withTitle('Handler method not found')->withContext([
                'type' => $type,
                'class' => $handlerClass
            ])->log();
            throw new ResponseException(ResponseCode::HANDLER_METHOD_NOT_FOUND);
        }

        return $handlerClass;
    }
}
