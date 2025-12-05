<?php
declare (strict_types = 1);

namespace app\api\lists\sop;

use app\api\lists\BaseApiDataLists;
use app\common\model\wechat\sop\AiWechatSopPushContent;

/**
 * 推送内容列表
 * Class PushContentLists
 * @package app\api\lists\sop
 */
class PushContentLists extends BaseApiDataLists
{
    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        return AiWechatSopPushContent::where('delete_time', 'null')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        return AiWechatSopPushContent::where('delete_time', 'null')->count();
    }
} 