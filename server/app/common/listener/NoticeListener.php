<?php

namespace app\common\listener;

use app\common\logic\NoticeLogic;
use think\facade\Log;

/**
 * 通知事件监听
 * Class NoticeListener
 * @package app\listener
 */
class NoticeListener
{
    public function handle($params)
    {
        try {
            if (empty($params['scene_id'])) {
                throw new \Exception('场景ID不能为空');
            }
            // 根据不同的场景发送通知
            $result = NoticeLogic::noticeByScene($params);
            if (false === $result) {
                throw new \Exception(NoticeLogic::getError());
            }
            return true;
        } catch (\Exception $e) {
            Log::write('通知发送失败:' . $e->getMessage());
            return $e->getMessage();
        }
    }
}
