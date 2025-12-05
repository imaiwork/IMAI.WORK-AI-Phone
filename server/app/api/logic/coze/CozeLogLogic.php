<?php

namespace app\api\logic\coze;

use app\api\logic\ApiLogic;
use app\common\model\coze\CozeLog;

class CozeLogLogic extends ApiLogic
{


    public static function delete($params)
    {
        try {
            $conversation_id = $params['conversation_id'] ?? 0;

            $where['user_id'] = self::$uid;
            $where['bot_id'] = $params['bot_id'];

            if ($conversation_id != 0) {
                $where['conversation_id'] = $conversation_id;
            }
            CozeLog::where($where)->select()->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}
