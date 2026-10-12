<?php

namespace app\adminapi\logic\copywriting;

use app\common\logic\BaseLogic;
use app\common\service\copywriting\PublicCopywritingService;

class PublicCopyLogic extends BaseLogic
{
    public static function add(array $params): bool
    {
        try {
            self::$returnData = PublicCopywritingService::create($params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update(array $params): bool
    {
        try {
            self::$returnData = PublicCopywritingService::update((int)$params['id'], $params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            PublicCopywritingService::delete($id);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
