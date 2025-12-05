<?php

namespace app\api\logic;

use app\common\model\user\UserTokensLog;

class AccountTokenLogic extends ApiLogic
{


    /**
     * @desc 获取用户token扣费信息
     * @param string $taskId
     * @return array
     * @date 2024/12/24 10:46
     * @author dagouzi
     */
    public static function info(string $taskId)
    {
        $extra = UserTokensLog::where('user_id', self::$uid)->where('task_id', $taskId)->value('extra') ?? '{}';

        $extra = json_decode($extra, true);

        return $extra ?? [];
    }
}
