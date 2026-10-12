<?php

namespace app\api\logic\square;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\service\square\SquareAssetService;

class SquareLogic extends ApiLogic
{
    public static function browse(array $params): array
    {
        $groupId = (int)($params['group_id'] ?? 0);
        $kind = (int)($params['kind'] ?? 0);
        $pageNo = (int)($params['page_no'] ?? 1);
        $pageSize = (int)($params['page_size'] ?? 20);
        return SquareAssetService::browse($groupId ?: null, $kind ?: null, $pageNo, $pageSize);
    }

    public static function refs(array $params): bool
    {
        try {
            self::assertPersona((int)$params['persona_id']);
            self::$returnData = SquareAssetService::listRefs(self::$uid, (int)$params['persona_id']);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function addRefs(array $params): bool
    {
        try {
            self::assertPersona((int)$params['persona_id']);
            self::$returnData = SquareAssetService::addRefs(
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

    public static function removeRefs(array $params): bool
    {
        try {
            self::assertPersona((int)$params['persona_id']);
            $ids = array_map([SquareAssetService::class, 'parseAssetId'], $params['ids'] ?? []);
            $removed = SquareAssetService::removeRefs(self::$uid, (int)$params['persona_id'], $ids);
            self::$returnData = ['removed' => $removed];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function assertPersona(int $personaId): void
    {
        $persona = AiPersona::where('id', $personaId)->where('user_id', self::$uid)->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \RuntimeException('人设不存在');
        }
    }
}