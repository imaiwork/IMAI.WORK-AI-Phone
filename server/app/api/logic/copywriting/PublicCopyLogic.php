<?php

namespace app\api\logic\copywriting;

use app\api\logic\ApiLogic;
use app\common\service\copywriting\PublicCopywritingService;

class PublicCopyLogic extends ApiLogic
{
    public static function browse(array $params): bool
    {
        try {
            $libraryType = (int)($params['library_type'] ?? 0);
            $driverType = array_key_exists('driver_type', $params) ? (int)$params['driver_type'] : -1;
            $pageNo = (int)($params['page_no'] ?? 1);
            $pageSize = (int)($params['page_size'] ?? 20);
            self::$returnData = PublicCopywritingService::browse(
                self::$uid,
                (int)$params['persona_id'],
                $libraryType ?: null,
                $driverType >= 0 ? $driverType : null,
                $pageNo,
                $pageSize
            );
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function add(array $params): bool
    {
        try {
            self::$returnData = PublicCopywritingService::addToPersona(
                self::$uid,
                (int)$params['persona_id'],
                $params['ids'] ?? []
            );
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function refresh(array $params): bool
    {
        try {
            $force = !empty($params['force']);
            self::$returnData = PublicCopywritingService::refresh(self::$uid, (int)$params['id'], $force);
            return true;
        } catch (\Throwable $e) {
            if ($e->getMessage() === PublicCopywritingService::NEED_CONFIRM) {
                self::$returnData = ['need_confirm' => 1];
                self::setError('这条文案你手动改过，确认覆盖？');
                return false;
            }
            self::$returnData = [];
            self::setError($e->getMessage());
            return false;
        }
    }
}
