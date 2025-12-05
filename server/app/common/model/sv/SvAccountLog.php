<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvAccountLog extends BaseModel
{

    // 平台类型
    const PLATFORM_XHS = 3; // 小红书
    const PLATFORM_DY = 4; // 抖音
    const PLATFORM_KS = 5; // 快手

    // 日志类型
    const TYPE_MESSAGE_REPLY = 5; // 社媒回复
}
