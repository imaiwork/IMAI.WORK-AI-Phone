<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

/**
 * AI销售加微执行记录表
 */
class AiWechatAddRecord extends BaseModel
{
    // 执行状态
    const STATUS_SUCCESS = 1;   // 成功
    const STATUS_RUNNING = 2;   // 执行中
    const STATUS_COOLING = 3;   // 账号安全冷却（暂停添加）
    const STATUS_FREQUENT = 4;  // 频繁执行
    const STATUS_FAIL = 0;      // 失败
}
