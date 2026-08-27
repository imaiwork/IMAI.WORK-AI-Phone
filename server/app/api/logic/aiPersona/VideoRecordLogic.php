<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;

/**
 * 人设内容记录 - 视频生成记录
 */
class VideoRecordLogic extends ApiLogic
{
    /**
     * 失败视频重新生成。成功后未到发布时间由调度自动发，已过点不自动发。
     */
    public static function retry(array $params): bool
    {
        // 人设内容视频均为 auto_type=1，本入口豁免手动任务限制
        $result = ShanjianVideoTaskLogic::retryFailedGenerate((int)($params['id'] ?? 0), true);
        if (!$result) {
            self::setError(ShanjianVideoTaskLogic::getError() ?: '重试失败');
            return false;
        }

        self::$returnData = ShanjianVideoTaskLogic::getReturnData();
        return true;
    }
}
