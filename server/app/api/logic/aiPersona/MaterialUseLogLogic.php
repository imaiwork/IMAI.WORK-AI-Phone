<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\MaterialUseLog;

class MaterialUseLogLogic extends ApiLogic
{
    public static function update(array $params): bool
    {
        try {
            $log = MaterialUseLog::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($log->isEmpty()) {
                self::setError('使用记录不存在');
                return false;
            }

            unset($params['id']);
            $params['update_time'] = time();
            $log->save($params);
            self::$returnData = $log->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $log = MaterialUseLog::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($log->isEmpty()) {
                self::setError('使用记录不存在');
                return false;
            }

            self::$returnData = $log->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
