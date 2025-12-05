<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatLog extends BaseModel
{

    // 日志类型
    const TYPE_ACCEPT_FRIEND = 0; // 加好友
    const TYPE_REPLY_CIRCLE = 1; // 评论朋友圈
    const TYPE_LIKE_CIRCLE = 2; // 点赞朋友圈
    const TYPE_SPH_POST = 3; // 视频号发布
    const TYPE_THROUGH_FRIEND = 4; // 通过好友
    const TYPE_MESSAGE_REPLY = 5; // 私域回复
    const TYPE_CIRCLE_POST = 6; // 朋友圈发布
}
