export enum MsgTypeEnum {
    UnknownMsg = "UnknownMsg", //未知消息
    HeartBeatReq = "HeartBeatReq", //客户端发送的心跳包
    MsgReceivedAck = "MsgReceivedAck", //消息接收确认回复（接收或拒绝接收）
    Error = "Error", //将错误单独提升为一种消息类型
    // 设备客户端授权类消息
    DeviceAuthReq = "DeviceAuthReq", //设备(手机客户端、客服客户端)获取通信token请求
    DeviceAuthRsp = "DeviceAuthRsp", //设备(手机客户端、客服客户端)获取通信token响应
    DeviceExitNotice = "DeviceExitNotice", //设备授权后退出(仅用于服务端内部)
    AccountForceOfflineNotice = "AccountForceOfflineNotice", //账号强制下线通知
    RedirectNotice = "RedirectNotice", //通知变更连服务器的ip端口
    TriggerDeviceInfo = "TriggerDeviceInfo", // 手机客户端上传的通知类消息

    TriggerWechatPushTask = "TriggerWechatPushTask", //触发微信上线通知，用于更新微信账号的信息
    WeChatOnlineNotice = "WeChatOnlineNotice", //手机客户端微信上线通知
    WeChatOfflineNotice = "WeChatOfflineNotice", //手机客户端微信下线通知
    FriendAddNotice = "FriendAddNotice", //微信个人号新增好友通知
    FriendDelNotice = "FriendDelNotice", //微信个人号移除好友通知
    FriendTalkNotice = "FriendTalkNotice", //微信好友发来聊天消息
    TaskResultNotice = "TaskResultNotice", //任务执行结果通知
    WeChatTalkToFriendNotice = "WeChatTalkToFriendNotice", // 手机上回复好友的聊天消息
    FriendAddRequestNotice = "FriendAddReqeustNotice", // 有好友请求添加好友的通知
    TalkToFriendTaskResultNotice = "TalkToFriendTaskResultNotice", // 手机端向服务端通知聊天执行结果
    RequestTalkDetailTaskResultNotice = "RequestTalkDetailTaskResultNotice", //图片或视频消息的详细内容结果
    PullWeChatQrCodeTaskResultNotice = "PullWeChatQrCodeTaskResultNotice", //上传手机客户端上微信的二维码
    CircleNewPublishNotice = "CircleNewPublishNotice", // 手机上发送了朋友圈通知
    CircleDelNotice = "CircleDelNotice", // 手机上删除朋友圈通知
    CircleLikeNotice = "CircleLikeNotice", // 手机检测到有人点赞/取消点赞通知
    CircleCommentNotice = "CircleCommentNotice", // 手机检测到有人评论/删除朋友圈通知
    PostMessageReadNotice = "PostMessageReadNotice", // 消息标记为已读
    ChatRoomAddNotice = "ChatRoomAddNotice", // 群聊新增通知
    ContactLabelAddNotice = "ContactLabelAddNotice", // 联系人标签新增，修改通知
    TakeMoneyTaskResultNotice = "TakeMoneyTaskResultNotice", // 收钱任务执行结果通知
    CircleDetailNotice = "CircleDetailNotice", // 朋友圈图片上传
    ChatRoomDelNotice = "ChatRoomDelNotice", // 群聊删除通知
    ChatRoomChangedNotice = "ChatRoomChangedNotice", // 群聊信息变更通知
    PullChatRoomQrCodeTaskResultNotice = "PullChatRoomQrCodeTaskResultNotice", // 群二维码
    ContactLabelDelNotice = "ContactLabelDelNotice", // 联系人标签删除通知
    ChatMsgFilePushNotice = "ChatMsgFilePushNotice", // 聊天消息的图片，视频，文件推送
    FriendChangeNotice = "FriendChangeNotice", // 好友信息变更通知
    PhoneStateWarningNotice = "PhoneStateWarningNotice", // 手机状态告警通知（存储空间不足，低电量）
    MsgDelNotice = "MsgDelNotice", // 聊天消息删除通知
    ConvDelNotice = "ConvDelNotice", // 聊天会话删除通知
    // 服务端、pc客户端发给设备的指令类消息
    TalkToFriendTask = "TalkToFriendTask", //给好友发消息任务
    PostSNSNewsTask = "PostSNSNewsTask", //发送朋友圈任务
    AddFriendsTask = "AddFriendsTask", //主动添加好友任务
    PostSNSNewsTaskResultNotice = "PostSNSNewsTaskResultNotice", // 发送朋友圈任务后数据回传
    DeleteSNSNewsTask = "DeleteSNSNewsTask", // 删除朋友圈
    AcceptFriendAddRequestTask = "AcceptFriendAddRequestTask", // 客户端或者服务端接受好友请求通知
    WeChatGroupSendTask = "WeChatGroupSendTask", //群发消息任务
    WeChatMaintenanceTask = "WeChatMaintenanceTask", //执行养号动作命令
    RequestTalkDetailTask = "RequestTalkDetailTask", //请求图片或视频消息的详细内容
    PullWeChatQrCodeTask = "PullWeChatQrCodeTask", //服务端主动要求手机上传当前登录的微信二维码
    TriggerFriendPushTask = "TriggerFriendPushTask", // 触发手机推送好友列表任务
    TriggerCirclePushTask = "TriggerCirclePushTask", // 触发手机推送朋友圈列表任务
    CircleCommentDeleteTask = "CircleCommentDeleteTask", // 朋友圈评论删除任务
    CircleCommentDeleteTaskResultNotice = "CircleCommentDeleteTaskResultNotice", // 朋友圈评论删除任务反馈
    CircleCommentReplyTask = "CircleCommentReplyTask", // 朋友圈评论回复任务
    CircleCommentReplyTaskResultNotice = "CircleCommentReplyTaskResultNotice", // 朋友圈评论回复反馈
    TriggerMessageReadTask = "TriggerMessageReadTask", // 通知手机将某个聊天窗口置为已读
    RevokeMessageTask = "RevokeMessageTask", // 消息撤回
    ForwardMessageTask = "ForwardMessageTask", // 转发消息
    TriggerHistoryMsgPushTask = "TriggerHistoryMsgPushTask", // 通知手机推送聊天记录
    PullChatRoomQrCodeTask = "PullChatRoomQrCodeTask", // 获取群聊二维码
    SendMultiPictureTask = "SendMultiPictureTask", // 聊天发送多张图片
    ForwardMultiMessageTask = "ForwardMultiMessageTask", // 转发多条聊天消息（逐条转发）
    // 服务端通知执行的命令
    UpgradeAppNotice = "UpgradeAppNotice", // 服务端通知版本升级
    UpgradeDeviceAppNotice = "UpgradeDeviceAppNotice", // 通知手机客户端软件升级
    PostFriendDetectTask = "PostFriendDetectTask", //清粉任务
    PostStopFriendDetectTask = "PostStopFriendDetectTask", //终止清粉任务
    PostDeleteDeviceNotice = "PostDeleteDeviceNotice", // 删除设备通知
    PostMomentsPraiseTask = "PostMomentsPraiseTask", //朋友圈点赞任务
    PostStopMomentsPraiseTask = "PostStopMomentsPraiseTask", //停止朋友圈点赞任务
    PostStopWeChatMaintenanceTask = "PostStopWeChatMaintenanceTask", //养号任务停止
    ModifyFriendMemoTask = "ModifyFriendMemoTask", //修改备注任务
    AddFriendWithSceneTask = "AddFriendWithSceneTask", //通用加好友任务
    TakeLuckyMoneyTask = "TakeLuckyMoneyTask", // 领取红包或转账
    PullFriendCircleTask = "PullFriendCircleTask", // 获取指定好友朋友圈
    PullCircleDetailTask = "PullCircleDetailTask", // 获取朋友圈图片
    CircleLikeTask = "CircleLikeTask", // 单条朋友圈点赞任务
    TriggerChatroomPushTask = "TriggerChatroomPushTask", //触发手机推送群聊列表
    RequestChatRoomInfoTask = "RequestChatRoomInfoTask", // 请求具体群聊的详细信息
    RequestContactsInfoTask = "RequestContactsInfoTask", // 获取联系人详细信息（不一定是好友，如群聊成员）
    ChatRoomActionTask = "ChatRoomActionTask", // 群聊管理
    AddFriendInChatRoomTask = "AddFriendInChatRoomTask", // 群内加好友
    AddFriendFromPhonebookTask = "AddFriendFromPhonebookTask", // 通讯录加好友
    DeleteFriendTask = "DeleteFriendTask", // 删除好友
    SendLuckyMoneyTask = "SendLuckyMoneyTask", // 发红包
    RequestTalkContentTask = "RequestTalkContentTask", // 获取聊天消息的原始内容（主要是xml内容）
    RequestTalkContentTaskResultNotice = "RequestTalkContentTaskResultNotice", // 返回聊天消息的原始内容
    ForwardMessageByContentTask = "ForwardMessageByContentTask", // 转发消息内容
    ChatRoomInviteApproveTask = "ChatRoomInviteApproveTask", // 群主确认入群申请
    WechatLogoutTask = "WechatLogoutTask", // 微信账号登出
    PhoneActionTask = "PhoneActionTask", // 手机操作指令
    ContactLabelTask = "ContactLabelTask", // 设置联系人标签
    ContactLabelDeleteTask = "ContactLabelDeleteTask", // 删除联系人标签
    VoiceTransTextTask = "VoiceTransTextTask", // 语音消息转文字
    FindContactTask = "FindContactTask", // 查找微信联系人
    FindContactTaskResult = "FindContactTaskResult", // 查找微信联系人结果
    AgreeJoinChatRoomTask = "AgreeJoinChatRoomTask", // 同意加入群聊
    ClearAllChatMsgTask = "ClearAllChatMsgTask", // 清空聊天记录
    SendFriendVerifyTask = "SendFriendVerifyTask", // 聊天界面发送朋友验证
    TriggerConversationPushTask = "TriggerConversationPushTask", // 会话列表推送
    WechatSettingTask = "WechatSettingTask", // 微信设置：改昵称，头像
    PullFriendAddReqListTask = "PullFriendAddReqListTask", //获取加好友请求列表
    TriggerBizContactPushTask = "TriggerBizContactPushTask", // 获取公众号列表
    AddFriendNameCardTask = "AddFriendNameCardTask", // 名片加好友
    TriggerChatMsgIdsPushTask = "TriggerChatMsgIdsPushTask", // 获取时间段内的所有聊天消息msgSvrId
    RequestTalkMsgTask = "RequestTalkMsgTask", // 根据msgSvrId获取聊天消息
    RequestTalkMsgTaskResultNotice = "RequestTalkMsgTaskResultNotice", // 根据msgSvrId获取聊天消息返回结果
    SearchBizContactTask = "SearchBizContactTask", // 关键字搜索公众号或小程序
    SearchBizContactTaskResultNotice = "SearchBizContactTaskResultNotice", // 关键字搜索公众号或小程序返回结果
    PhoneStateTask = "PhoneStateTask", // 查询手机状态 （电量，剩余存储空间等）
    PhoneStateTaskResultNotice = "PhoneStateTaskResultNotice", // 查询手机状态返回结果
    WeChatLocationTask = "WeChatLocationTask", // 通过微信查询手机位置
    WeChatLocationTaskResultNotice = "WeChatLocationTaskResultNotice", // 微信查询手机位置返回结果
    RemittanceTask = "RemittanceTask", // 转账
    WalletBalanceTask = "WalletBalanceTask", // 查钱包余额
    WalletBalanceTaskResultNotice = "WalletBalanceTaskResultNotice", // 查钱包余额
    AddFriendNotice = "AddFriendNotice", // 手机上主动加好友动作通知
    QueryHbDetailTask = "QueryHbDetailTask", // 查询红包详情
    QueryHbDetailTaskResultNotice = "QueryHbDetailTaskResultNotice", // 红包详情返回
    JoinGroupByQrTask = "JoinGroupByQrTask", // 扫二维码进群
    SendJielongTask = "SendJielongTask", // 发接龙消息，任务结果TalkToFriendTaskResultNotice
    ContactSetLabelTask = "ContactSetLabelTask", // 设置用户标签
    CDNDownloadResultNotice = "CDNDownloadResultNotice", // CDN下载文件任务结果返回
    PullEmojiInfoTask = "PullEmojiInfoTask", // 获取动画表情信息详情
    PullEmojiInfoTaskResultNotice = "PullEmojiInfoTaskResultNotice", // 获取动画表情信息详情结果返回
    TriggerCircleMsgPushTask = "TriggerCircleMsgPushTask", // 触发朋友圈消息列表推送
    CircleMsgReadTask = "CircleMsgReadTask", // 朋友圈消息设置为已读
    CircleMsgClearTask = "CircleMsgClearTask", // 清除已读的朋友圈消息
    GetContactInfoTask = "GetContactInfoTask", // 通过指令获取信息，可获取群成员的详细信息，返回ContactInfoNotice
    ContactInfoNotice = "ContactInfoNotice", //
    GetFriendDetectResult = "GetFriendDetectResult", // 获取最后一次清粉的结果
    FriendDetectResultNotice = "FriendDetectResultNotice", // 最后一次清粉的结果
    TriggerUnReadTask = "TriggerUnReadTask", // 设为未读
    ScreenShotTask = "ScreenShotTask", //截屏任务
    ScreenShotTaskResultNotice = "ScreenShotTaskResultNotice", // 截屏返回结果
    GetA8KeyTask = "GetA8KeyTask",
    TriggerQwUserPush = "TriggerQwUserPush", // 触发企微会话推送
    QwUserPUshNotice = "QwUserPUshNotice",
    CallLogPushNotice = "CallLogPushNotice", // 通话记录推送
    SmsPushNotice = "SmsPushNotice", // 短信推送
    SmsReadNotice = "SmsReadNotice", // 短信已读通知
    SmsSentNotice = "SmsSentNotice", // 短信发送通知
    PullSmsTask = "PullSmsTask", // 查询历史短信
    PullSmsTaskResultNotice = "PullSmsTaskResultNotice", // 查询历史短信结果
    PullCallLogTask = "PullCallLogTask", // 查询历史通话记录
    PullCallLogTaskResultNotice = "PullCallLogTaskResultNotice", // 查询历史通话记录结果

    // 手机端主动发出的交互类消息
    FriendPushNotice = "FriendPushNotice", //手机端推送好友列表
    PostDeviceInfoNotice = "PostDeviceInfoNotice", // 手机端推送当前安装版本
    PostFriendDetectCountNotice = "PostFriendDetectCountNotice", //手机端回传检测清粉好友数
    CirclePushNotice = "CirclePushNotice", // 手机回传朋友圈数据
    PostMomentsPraiseCountNotice = "PostMomentsPraiseCountNotice", // 手机回传朋友圈点赞数量
    ChatroomPushNotice = "ChatroomPushNotice", // 手机端推送群聊列表
    ContactLabelInfoNotice = "ContactLabelInfoNotice", // 手机端推送标签列表
    HistoryMsgPushNotice = "HistoryMsgPushNotice", // 推送历史消息
    ChatRoomMembersNotice = "ChatRoomMembersNotice", // 群成员（陌生人）信息
    ConversationPushNotice = "ConversationPushNotice", // 会话列表
    FriendAddReqListNotice = "FriendAddReqListNotice", // 加好友请求列表
    BizContactPushNotice = "BizContactPushNotice", // 公账号列表推送
    BizContactAddNotice = "BizContactAddNotice", // 新增公众号通知
    CircleMsgPushNotice = "CircleMsgPushNotice", // 朋友圈消息列表推送

    // 设备认证
    Auth = "Auth",
    // 添加设备
    AddDevice = "AddDevice",
    // 重启设备
    RebootDevice = "RebootDevice",
    // 设备信息
    DeviceInfo = "DeviceInfo",
    // 清除缓存
    CleanCache = "CleanCache",
    // 移除设备
    RemoveDevice = "RemoveDevice",
    // 上传文件
    UploadFile = "UploadFile",
    // 好友发消息
    TalkToFriend = "TalkToFriendTask",
    // 个人信息
    WxInfo = "WxInfo",
    // 心跳
    Heartbeat = "Heartbeat",
    // 群聊管理
    ChatRoomActionTaskMessage = "ChatRoomActionTaskMessage",
    // 群成员
    RequestChatRoomInfoTaskMessage = "RequestChatRoomInfoTaskMessage",
}

export enum MsgErrorCodeEnum {
    Success = 0, // 成功
    DeviceNotFound = 1013, // 设备未找到
    DeviceOffline = 1014, // 设备离线
    DeviceExist = 1019, // 设备已存在
    DataNotFound = 1020, // 数据未找到
    InternalError = 1003, // 内部错误
    DeviceWechatNotFound = 1021, // 设备微信未找到
    SystemError = 6001, // 系统错误
}

// 聊天内容类型
export enum ContentTypeEnum {
    UnknownContent = 0, // 未知内容
    Text = 1, // 文本内容
    Picture = 2, // 图片消息
    Voice = 3, // 语音消息
    Video = 4, // 视频消息
    System = 5, //系统消息
    Link = 6, // 链接消息
    LinkExt = 7, // 扩展的链接消息（小程序分享等），内容为xml格式，暂未使用
    File = 8, // 文件发送
    NameCard = 9, // 名片
    Location = 10, // 位置信息
    LuckyMoney = 11, // 红包
    MoneyTrans = 12, // 转账
    WeApp = 13, // 小程序
    Emoji = 14, //动画表情消息
    RoomManage = 15, // 群管理消息
    Sys_LuckyMoney = 16, // 领取红包消息
    RoomSystem = 17, // 群聊系统消息
    BizLink = 18, //公众号文章消息
    AudioCall = 19, // 语音通话
    VideoCall = 20, // 视频通话
    NotifyMsg = 21, // 服务通知
    QuoteMsg = 22, // 引用通知
    UnSupport = 99, // 不支持的消息
}

export const ContentTypeEnumMap = {
    [ContentTypeEnum.UnknownContent]: "未知",
    [ContentTypeEnum.Text]: "文本",
    [ContentTypeEnum.Picture]: "图片",
    [ContentTypeEnum.Video]: "视频",
    [ContentTypeEnum.Voice]: "语音",
    [ContentTypeEnum.File]: "文件",
    [ContentTypeEnum.System]: "系统",
    [ContentTypeEnum.Emoji]: "动画表情",
    [ContentTypeEnum.RoomManage]: "群管理",
    [ContentTypeEnum.Sys_LuckyMoney]: "领取红包",
    [ContentTypeEnum.RoomSystem]: "群聊系统",
    [ContentTypeEnum.BizLink]: "公众号文章",
    [ContentTypeEnum.AudioCall]: "语音通话",
    [ContentTypeEnum.VideoCall]: "视频通话",
    [ContentTypeEnum.NotifyMsg]: "服务通知",
    [ContentTypeEnum.QuoteMsg]: "引用通知",
    [ContentTypeEnum.UnSupport]: "不支持的消息",
    [ContentTypeEnum.LuckyMoney]: "红包",
    [ContentTypeEnum.MoneyTrans]: "转账",
    [ContentTypeEnum.WeApp]: "小程序",
    [ContentTypeEnum.Location]: "位置",
    [ContentTypeEnum.Link]: "链接",
    [ContentTypeEnum.LinkExt]: "扩展链接",
    [ContentTypeEnum.NameCard]: "名片",
};

// 账号类型
export enum AccountTypeEnum {
    UnknownAccountType = 0, // 未知
    Main = 1, // 主账号
    SubUser = 2, // 子账号
}

// 账号来源
export const AccountSource = {
    [1000001]: "对方通过搜索QQ号添加",
    [1000003]: "通过搜索微信号添加",
    [1000004]: "来自QQ好友",
    [1000012]: "来自QQ好友",
    [1000008]: "通过群聊",
    [1000014]: "通过群聊",
    [1000015]: "通过搜索手机号添加",
    [1000017]: "通过名片分享添加",
    [1000018]: "通过附近的人添加",
    [1000022]: "通过摇一摇添加",
    [1000025]: "通过漂流瓶添加",
    [1000030]: "通过扫一扫添加",
    [1000034]: "通过公众号添加",
    [1000048]: "通过雷达添加",
};

export enum FriendPanelEnum {
    Dialogue = "dialogue",
    Friend = "friend",
    Group = "group",
}

export interface TriggerTaskParams {
    deviceId?: string;
    accessToken?: string;
    wechatId?: string;
    TaskId?: number;
}

// 账号类型
export enum AccountTypeEnum {
    // 个人
    Personal = 1,
    // 企业
    Enterprise = 2,
}

// 账号类型Map
export const AccountTypeMap = {
    [AccountTypeEnum.Personal]: "个微",
    [AccountTypeEnum.Enterprise]: "企微",
};

export enum TalkActionTypeEnum {
    PostHistory = "PostHistory",
    GetHistory = "GetHistory",
    VoiceToText = "VoiceToText",
    VoiceToTextResult = "VoiceToTextResult",
    PostPicture = "PostPicture",
}

export enum HandleEventEnum {
    UpdateUserInfo = "UpdateUserInfo",
    DownloadFile = "DownloadFile",
    PreviewVideo = "PreviewVideo",
    ChooseEmoji = "ChooseEmoji",
    VoiceToText = "VoiceToText",
    PreviewImage = "PreviewImage",
    SendComment = "SendComment",
    Like = "Like",
    DeleteCircle = "DeleteCircle",
    DeleteComment = "DeleteComment",
    PullCircleDetail = "PullCircleDetail",
}

export enum EnumTalkType {
    Single = 0,
    Merge = 1,
    Number = 2,
}

export enum MaterialTypeEnum {
    TEXT = 0,
    IMAGE = 1,
    VIDEO = 2,
    LINK = 3,
    MINI_PROGRAM = 4,
    FILE = 5,
}
