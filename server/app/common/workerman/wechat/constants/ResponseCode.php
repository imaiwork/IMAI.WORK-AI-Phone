<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\constants;

/**
 * 响应状态码常量类
 * 
 * 基于微信客服通信接口文档定义响应状态码
 * 
 * @author Qasim
 * @package app\constant
 */
class ResponseCode
{
    /**
     * 成功
     */
    public const SUCCESS = 0;

    /**
     * 基础消息类错误 (1000-1999)
     */
    public const HEARTBEAT_ERROR = 1001;           // 心跳包错误
    public const MSG_RECEIVED_ERROR = 1002;        // 消息接收确认错误
    public const GENERAL_ERROR = 1003;             // 通用错误
    public const TASK_RESULT_ERROR = 1004;         // 任务执行结果错误
    public const INVALID_MESSAGE_FORMAT = 1005;   // 消息格式错误
    public const INVALID_PARAMS = 1006;             // 参数错误
    public const INVALID_OPERATION = 1007;        // 操作错误
    public const RESOURCE_ACCESS_DENIED = 1008;   // 资源访问被拒绝
    public const QUOTA_EXCEEDED = 1009;            // 操作配额已超限
    public const TOO_MANY_REQUESTS = 1010;         // 请求次数过多
    public const SERVICE_UNAVAILABLE = 1011;      // 服务不可用
    public const UNAUTHORIZED = 1012;             // 未授权
    public const DEVICE_NOT_FOUND = 1013;         // 设备未找到
    public const DEVICE_OFFLINE = 1014;          // 设备离线
    public const SEND_FAILED = 1015;             // 发送失败
    public const DATA_NOT_FOUND = 1016;          // 数据未找到
    public const TOKEN_EXPIRED = 1017;            // Token已过期
    public const TOKEN_INVALID = 1018;            // Token无效
    public const DEVICE_ALREADY_USED = 1019;    // 设备已使用
    public const DEVICE_WECHAT_NOT_FOUND = 1020; // 设备微信未找到
    public const HANDLER_NOT_FOUND = 1021;      // 处理器未找到
    public const HANDLER_METHOD_NOT_FOUND = 1022; // 处理器方法未找到
    public const DEVICE_UNAUTHORIZED = 1023; //设备未授权
    public const DEVICE_NOT_AUTHORIZED = 1024; //无效授权码
    public const DEVICE_ALREADY_AUTHORIZED = 1025; //设备已授权
    public const CODE_ALREADY_USED = 1026; //授权码已被使用
    public const CODE_ALREADY_AUTHORIZED_BY_DEVICE = 1027; //授权已被设备授权
    public const INVALID_PLATFORM = 1028; //平台参数无效
    public const DEVICE_CODE_ERROR = 1029; //设备授权码错误
    public const DOMAINE_NOT_FOUND = 1030;//未找到授权域名
    /**
     * 设备授权类错误 (2000-2999)
     */
    public const DEVICE_AUTH_ERROR = 2001;         // 设备授权错误
    public const DEVICE_TOKEN_INVALID = 2002;      // 设备token无效
    public const DEVICE_EXIT_ERROR = 2003;         // 设备退出错误
    public const ACCOUNT_FORCE_OFFLINE = 2004;     // 账号强制下线
    public const SERVER_REDIRECT_ERROR = 2005;     // 服务器重定向错误

    /**
     * 微信通知类错误 (3000-3999)
     */
    public const WECHAT_ONLINE_ERROR = 3001;       // 微信上线通知错误
    public const WECHAT_OFFLINE_ERROR = 3002;      // 微信下线通知错误
    public const FRIEND_ADD_ERROR = 3003;          // 好友添加通知错误
    public const FRIEND_DEL_ERROR = 3004;          // 好友删除通知错误
    public const CHAT_MSG_ERROR = 3005;            // 聊天消息通知错误
    public const FRIEND_REQUEST_ERROR = 3006;      // 好友请求通知错误
    public const MOMENTS_ERROR = 3007;             // 朋友圈相关通知错误
    public const GROUP_CHAT_ERROR = 3008;          // 群聊相关通知错误
    public const CONTACT_LABEL_ERROR = 3009;       // 联系人标签通知错误

    /**
     * 任务执行类错误 (4000-4999)
     */
    public const SEND_MSG_ERROR = 4001;            // 发送消息任务错误
    public const POST_MOMENTS_ERROR = 4002;        // 发朋友圈任务错误
    public const ADD_FRIEND_ERROR = 4003;          // 添加好友任务错误
    public const GROUP_SEND_ERROR = 4004;          // 群发消息任务错误
    public const CLEAN_FRIEND_ERROR = 4005;        // 清粉任务错误
    public const GET_QRCODE_ERROR = 4006;          // 获取二维码任务错误
    public const FRIEND_LIST_ERROR = 4007;         // 获取好友列表任务错误
    public const CHAT_HISTORY_ERROR = 4008;        // 获取聊天记录任务错误
    public const GROUP_MANAGE_ERROR = 4009;        // 群管理任务错误
    public const REDPACKET_ERROR = 4010;           // 红包相关任务错误
    public const TRANSFER_ERROR = 4011;            // 转账相关任务错误

    /**
     * 系统操作类错误 (5000-5999)
     */
    public const WECHAT_LOGOUT_ERROR = 5001;       // 微信登出错误
    public const PHONE_OPERATION_ERROR = 5002;     // 手机操作指令错误
    public const APP_UPGRADE_ERROR = 5003;         // 软件升级通知错误
    public const DEVICE_DELETE_ERROR = 5004;       // 设备删除通知错误


    /**
     * 系统错误 (6000-6999)
     */
    public const SYSTEM_ERROR = 6001;             // 系统错误
    public const NETWORK_ERROR = 6002;           // 网络错误
    public const AUTH_ERROR = 6003;               // 认证错误
    public const INVALID_REQUEST = 6004;         // 无效请求
    public const INVALID_RESPONSE = 6005;        // 无效响应
    public const INVALID_STATE = 6006;           // 无效状态

    /**
     * 文件上传类错误 (7000-7999)
     */
    public const FILE_UPLOAD_ERROR = 7001;       // 文件上传错误
    public const FILE_DOWNLOAD_ERROR = 7002;      // 文件下载错误
    public const FILE_DELETE_ERROR = 7003;       // 文件删除错误


    /**
     * 错误消息映射
     */
    private static array $messages = [
        self::SUCCESS => '操作成功',
        self::HEARTBEAT_ERROR => '心跳包错误',
        self::INVALID_MESSAGE_FORMAT => '消息格式错误',
        self::MSG_RECEIVED_ERROR => '消息接收确认错误',
        self::GENERAL_ERROR => '通用错误',
        self::TASK_RESULT_ERROR => '任务执行结果错误',
        self::INVALID_PARAMS => '参数错误',
        self::INVALID_OPERATION => '操作错误',
        self::RESOURCE_ACCESS_DENIED => '资源访问被拒绝',
        self::QUOTA_EXCEEDED => '操作配额已超限',
        self::TOO_MANY_REQUESTS => '请求次数过多',
        self::SERVICE_UNAVAILABLE => '服务不可用',
        self::UNAUTHORIZED => '未授权',
        self::DEVICE_NOT_FOUND => '设备未找到',
        self::DEVICE_OFFLINE => '设备离线',
        self::SEND_FAILED => '发送失败',
        self::DATA_NOT_FOUND => '数据未找到',
        self::TOKEN_EXPIRED => 'Token已过期',
        self::TOKEN_INVALID => 'Token无效',
        self::DEVICE_ALREADY_USED => '设备已使用',
        self::DEVICE_WECHAT_NOT_FOUND => '设备微信未找到',
        self::HANDLER_NOT_FOUND => '处理器未找到',
        self::HANDLER_METHOD_NOT_FOUND => '处理器方法未找到',
        self::DEVICE_AUTH_ERROR => '设备授权错误',
        self::DEVICE_TOKEN_INVALID => '设备token无效',
        self::DEVICE_EXIT_ERROR => '设备退出错误',
        self::ACCOUNT_FORCE_OFFLINE => '账号强制下线',
        self::SERVER_REDIRECT_ERROR => '服务器重定向错误',
        self::WECHAT_ONLINE_ERROR => '微信上线通知错误',
        self::WECHAT_OFFLINE_ERROR => '微信下线通知错误',
        self::FRIEND_ADD_ERROR => '好友添加通知错误',
        self::FRIEND_DEL_ERROR => '好友删除通知错误',
        self::CHAT_MSG_ERROR => '聊天消息通知错误',
        self::FRIEND_REQUEST_ERROR => '好友请求通知错误',
        self::MOMENTS_ERROR => '朋友圈相关通知错误',
        self::GROUP_CHAT_ERROR => '群聊相关通知错误',
        self::CONTACT_LABEL_ERROR => '联系人标签通知错误',
        self::SEND_MSG_ERROR => '发送消息任务错误',
        self::POST_MOMENTS_ERROR => '发朋友圈任务错误',
        self::ADD_FRIEND_ERROR => '添加好友任务错误',
        self::GROUP_SEND_ERROR => '群发消息任务错误',
        self::CLEAN_FRIEND_ERROR => '清粉任务错误',
        self::GET_QRCODE_ERROR => '获取二维码任务错误',
        self::FRIEND_LIST_ERROR => '获取好友列表任务错误',
        self::CHAT_HISTORY_ERROR => '获取聊天记录任务错误',
        self::GROUP_MANAGE_ERROR => '群管理任务错误',
        self::REDPACKET_ERROR => '红包相关任务错误',
        self::TRANSFER_ERROR => '转账相关任务错误',
        self::WECHAT_LOGOUT_ERROR => '微信登出错误',
        self::PHONE_OPERATION_ERROR => '手机操作指令错误',
        self::APP_UPGRADE_ERROR => '软件升级通知错误',
        self::DEVICE_DELETE_ERROR => '设备删除通知错误',
        self::SYSTEM_ERROR => '系统错误',
        self::NETWORK_ERROR => '网络错误',
        self::AUTH_ERROR => '认证错误',
        self::INVALID_REQUEST => '无效请求',
        self::INVALID_RESPONSE => '无效响应',
        self::INVALID_STATE => '无效状态',
        self::FILE_UPLOAD_ERROR => '文件上传错误',
        self::FILE_DOWNLOAD_ERROR => '文件下载错误',
        self::FILE_DELETE_ERROR => '文件删除错误',
        self::DEVICE_UNAUTHORIZED => '设备未授权',
        self::DEVICE_NOT_AUTHORIZED => '无效授权码',
        self::DEVICE_ALREADY_AUTHORIZED => '设备已授权',
        self::CODE_ALREADY_USED => '授权码已被使用',
        self::CODE_ALREADY_AUTHORIZED_BY_DEVICE => '授权码已被设备授权',
        self::INVALID_PLATFORM => '平台参数无效',
        self::DEVICE_CODE_ERROR => '设备授权码错误',
        self::DOMAINE_NOT_FOUND => '未找到授权域名'
    ];

    /**
     * 获取错误消息
     */
    public static function getMessage(int $code): string
    {
        return self::$messages[$code] ?? '未知错误';
    }
}
