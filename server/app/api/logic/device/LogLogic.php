<?php

namespace app\api\logic\device;

use app\api\lists\device\LogLists;
use app\api\logic\ApiLogic;
use app\common\model\sv\SvDeviceLog;

/**
 * Device log logic
 */
class LogLogic extends ApiLogic
{
    public static function detail(array $params): bool
    {
        try {
            $log = SvDeviceLog::where('id', (int)$params['id'])
                ->where('user_id', self::$uid)
                ->field('id,user_id,device_code,app_type,content,app_version,day,create_time')
                ->findOrEmpty();

            if ($log->isEmpty()) {
                self::setError('日志不存在');
                return false;
            }

            self::$returnData = LogLists::formatLogItem($log);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params): bool
    {
        try {
            $ids = self::normalizeIds($params['ids']);
            if (empty($ids)) {
                self::setError('请选择日志');
                return false;
            }

            SvDeviceLog::where('user_id', self::$uid)
                ->whereIn('id', $ids)
                ->delete();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function normalizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}
