<?php

namespace app\adminapi\logic\square;

use app\common\logic\BaseLogic;
use app\common\service\square\SquareAssetService;

class SquareLogic extends BaseLogic
{
    public static function groupLists(): array
    {
        return SquareAssetService::listGroups(false);
    }

    public static function addGroup(array $params): bool
    {
        try {
            self::$returnData = SquareAssetService::createGroup($params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateGroup(array $params): bool
    {
        try {
            self::$returnData = SquareAssetService::updateGroup((int)$params['id'], $params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteGroup(int $id): bool
    {
        try {
            SquareAssetService::deleteGroup($id);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function addAsset(array $params): bool
    {
        try {
            $items = $params['items'] ?? [];
            if (is_array($items) && $items) {
                $groupId = (int)($params['group_id'] ?? 0);
                $created = SquareAssetService::createAssets($groupId, $items);
                self::$returnData = ['created' => count($created), 'assets' => $created];
                return true;
            }
            self::$returnData = SquareAssetService::createAsset($params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateAsset(array $params): bool
    {
        try {
            self::$returnData = SquareAssetService::updateAsset((int)$params['id'], $params);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteAsset(int $id): bool
    {
        try {
            SquareAssetService::deleteAsset($id);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}