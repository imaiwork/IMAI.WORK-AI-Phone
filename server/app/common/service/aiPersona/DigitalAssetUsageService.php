<?php

namespace app\common\service\aiPersona;

use app\common\model\shanjian\ShanjianVideoTask;

class DigitalAssetUsageService
{
    public static function getVoiceUseCountMap(array $list): array
    {
        return self::getUseCountMap($list, 'third_voice_id', 'voice_id');
    }

    public static function getAvatarUseCountMap(array $list): array
    {
        return self::getUseCountMap($list, 'third_avatar_id', 'anchor_id');
    }

    public static function getUseCount(array $map, $personaId, $assetId): int
    {
        return (int)($map[self::buildKey((int)$personaId, (string)$assetId)] ?? 0);
    }

    private static function getUseCountMap(array $list, string $listAssetIdField, string $taskAssetIdField): array
    {
        $personaIds = [];
        $assetIds = [];
        $validPairs = [];

        foreach ($list as $item) {
            $personaId = (int)($item['persona_id'] ?? 0);
            $assetId = (string)($item[$listAssetIdField] ?? '');
            if ($personaId <= 0 || $assetId === '') {
                continue;
            }

            $personaIds[$personaId] = $personaId;
            $assetIds[$assetId] = $assetId;
            $validPairs[self::buildKey($personaId, $assetId)] = true;
        }

        if (empty($validPairs)) {
            return [];
        }

        $rows = ShanjianVideoTask::field("persona_id,{$taskAssetIdField} AS asset_id,COUNT(id) AS use_count")
            ->where('persona_id', 'in', array_values($personaIds))
            ->where($taskAssetIdField, 'in', array_values($assetIds))
            ->group("persona_id,{$taskAssetIdField}")
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $key = self::buildKey((int)($row['persona_id'] ?? 0), (string)($row['asset_id'] ?? ''));
            if (isset($validPairs[$key])) {
                $map[$key] = (int)($row['use_count'] ?? 0);
            }
        }

        return $map;
    }

    private static function buildKey(int $personaId, string $assetId): string
    {
        return $personaId . '|' . $assetId;
    }
}
