<?php

declare(strict_types=1);

namespace app\common\workerman\wechat\constants;

/**
 * 客户端请求消息类型常量类
 * 
 * @author Qasim
 * @package app\constant
 */
class ClientRequestMsgType
{
    public const AUTH = 'Auth';
    public const ADD_DEVICE = 'AddDevice';
    public const REMOVE_DEVICE = 'RemoveDevice';
    public const HEARTBEAT = 'Heartbeat';
    public const DEVICE_INFO = 'DeviceInfo';
    public const WX_INFO = 'WxInfo';
    public const TRIGGER_CONVERSATION_PUSH_TASK = 'TriggerConversationPushTask';
    public const TRIGGER_CIRCLE_PUSH_TASK = 'TriggerCirclePushTask';
    public const TRIGGER_BIZ_CONTACT_PUSH_TASK = 'TriggerBizContactPushTask';
    public const TRIGGER_CHAT_ROOM_PUSH_TASK = 'TriggerChatRoomPushTask';
    public const TRIGGER_HISTORY_MSG_PUSH_TASK = 'TriggerHistoryMsgPushTask';
    public const TRIGGER_FRIEND_PUSH_TASK = 'TriggerFriendPushTask';
    public const TRIGGER_MESSAGE_READ_TASK = 'TriggerMessageReadTask';
    public const TALK_TO_FRIEND = 'TalkToFriend';
    public const REBOOT_DEVICE = 'RebootDevice';
    public const UPLOAD_FILE = 'UploadFile';
    public const VOICE_TRANS_TEXT_TASK = 'VoiceTransTextTask';
    public const WECHAT_LOGOUT = 'WechatLogout';
    public const MODIFY_FRIEND_MEMO = 'ModifyFriendMemo';
    public const REQUEST_CONTACTS_INFO = 'RequestContactsInfo';
    public const CHAT_ROOM_ACTION_TASK = 'ChatRoomActionTask';
    public const REQUEST_CHAT_ROOM_INFO_TASK = 'RequestChatRoomInfoTask';
    public const REQUEST_TALK_DETAIL_TASK = 'RequestTalkDetailTask';
    public const PULL_FRIEND_CIRCLE_TASK = 'PullFriendCircleTask';
    public const POST_SNS_NEWS_TASK = 'PostSNSNewsTask';
    public const CIRCLE_COMMENT_REPLY_TASK = 'CircleCommentReplyTask';
    public const PULL_CIRCLE_DETAIL_TASK = 'PullCircleDetailTask';
    public const CIRCLE_LIKE_TASK = 'CircleLikeTask';
    public const CLEAN_CACHE = 'CleanCache';
}
