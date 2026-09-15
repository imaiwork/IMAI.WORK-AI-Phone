<?php

namespace app\adminapi\logic\coze;

use app\common\logic\BaseLogic;
use app\common\model\coze\CozeLog;

class CozeLogLogic extends BaseLogic
{


    public static function delete($params)
    {
        try {

            $where['conversation_id'] = $params['conversation_id'];
            CozeLog::where($where)->select()->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function reply($params){
        try {

            $where['message_id'] = $params['message_id'];
            $list = CozeLog::where($where)->select()->toArray();
            self::$returnData = $list;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}
