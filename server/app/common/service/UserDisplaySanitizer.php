<?php

namespace app\common\service;

use app\common\model\chat\Models;
use Throwable;

class UserDisplaySanitizer
{
    private const HUMAN_MODEL_ID_V1 = 7;
    private const HUMAN_MODEL_ID_V2 = 9;
    private const MODEL_ALIAS_CONFIG_KEY = 'display_aliases';
    private const FALLBACK_SOURCE_TYPE_MAP = [
        'shanjian_anchor' => 'provider_anchor',
        'human_anchor' => 'digital_anchor',
        'public_anchor' => 'public_anchor',
    ];
    private const FALLBACK_ANCHOR_ID_KEY_MAP = [
        'chanjing_anchor_id' => 'digital_anchor_id',
        'shanjian_anchor_id' => 'video_anchor_id',
    ];
    private const FALLBACK_EXTRA_INFO_KEY_MAP = [
        'shanjian_voice_id' => 'video_voice_id',
    ];

    private static ?array $humanDisplayModels = null;

    public static function digitalHumanAuthName(?string $name): string
    {
        $name = trim((string)$name);
        if ($name === '') {
            return self::defaultDigitalHumanAuthName();
        }
        return self::replaceProviderNames($name);
    }

    public static function digitalHumanModelList(array $modelList): array
    {
        $channels = $modelList['channel'] ?? [];
        foreach ($channels as $key => $channel) {
            if (!is_array($channel)) {
                continue;
            }
            $channels[$key] = self::replaceProviderNamesInArray($channel);
        }
        $modelList['channel'] = array_values($channels);
        if (isset($modelList['voice']) && is_array($modelList['voice'])) {
            $modelList['voice'] = self::replaceProviderNamesInArray($modelList['voice']);
        }
        return $modelList;
    }

    public static function replaceProviderNamesInArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::replaceProviderNamesInArray($value);
                continue;
            }
            if (is_string($value)) {
                $data[$key] = self::replaceProviderNames($value);
            }
        }
        return $data;
    }

    public static function replaceProviderNames(string $value): string
    {
        return strtr($value, self::providerNameReplacements());
    }

    public static function digitalHumanModelNameByVersion(int $modelVersion, bool $refresh = false): string
    {
        foreach (self::humanDisplayModels($refresh) as $model) {
            if ((int)($model['model_version'] ?? 0) === $modelVersion) {
                return trim((string)($model['name'] ?? ''));
            }
        }

        return '';
    }

    public static function digitalHumanAnchorItem(array $item): array
    {
        if (isset($item['source_type'])) {
            $sourceTypeMap = self::sourceTypeMap();
            $item['source_type'] = $sourceTypeMap[$item['source_type']] ?? $item['source_type'];
        }

        if (isset($item['task_ids'])) {
            unset($item['task_ids']);
        }

        if (isset($item['anchor_ids']) && is_array($item['anchor_ids'])) {
            $item['anchor_ids'] = self::neutralAnchorIds($item['anchor_ids']);
        }

        if (isset($item['extra_info']) && is_array($item['extra_info'])) {
            $item['extra_info'] = self::neutralExtraInfo($item['extra_info']);
        }

        $item = self::replaceProviderNamesInArray($item);

        return $item;
    }

    public static function humanImageForUser(array $humanImage): array
    {
        foreach ($humanImage as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $item = self::digitalHumanAnchorItem($item);
            $item = self::neutralFlatAnchorIds($item);
            $item = self::neutralFlatExtraInfo($item);
        }
        unset($item);
        return $humanImage;
    }

    public static function humanImageForStorage(array $humanImage): array
    {
        foreach ($humanImage as &$item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['anchor_ids']) && is_array($item['anchor_ids'])) {
                $item['anchor_ids'] = self::providerAnchorIds($item['anchor_ids']);
                $item = array_merge($item, $item['anchor_ids']);
            }
            if (isset($item['extra_info']) && is_array($item['extra_info'])) {
                $item['extra_info'] = self::providerExtraInfo($item['extra_info']);
                $item = array_merge($item, $item['extra_info']);
            }
            $item = self::providerFlatAnchorIds($item);
            $item = self::providerFlatExtraInfo($item);
        }
        unset($item);
        return $humanImage;
    }

    public static function normalizeHumanImageForStorage(array $humanImage): array
    {
        return self::humanImageForStorage($humanImage);
    }

    public static function normalizeHumanImageForUser(array $humanImage): array
    {
        return self::humanImageForUser($humanImage);
    }

    private static function neutralAnchorIds(array $anchorIds): array
    {
        foreach (self::anchorIdKeyMap() as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $anchorIds)) {
                $anchorIds[$newKey] = $anchorIds[$newKey] ?? $anchorIds[$oldKey];
            }
        }
        return $anchorIds;
    }

    private static function neutralFlatAnchorIds(array $item): array
    {
        return self::neutralAnchorIds($item);
    }

    private static function providerAnchorIds(array $anchorIds): array
    {
        foreach (array_flip(self::anchorIdKeyMap()) as $newKey => $oldKey) {
            if (array_key_exists($newKey, $anchorIds)) {
                $anchorIds[$oldKey] = $anchorIds[$oldKey] ?? $anchorIds[$newKey];
            }
        }
        return $anchorIds;
    }

    private static function providerFlatAnchorIds(array $item): array
    {
        return self::providerAnchorIds($item);
    }

    private static function neutralExtraInfo(array $extraInfo): array
    {
        foreach (self::extraInfoKeyMap() as $oldKey => $newKey) {
            if (array_key_exists($oldKey, $extraInfo)) {
                $extraInfo[$newKey] = $extraInfo[$newKey] ?? $extraInfo[$oldKey];
            }
        }
        return $extraInfo;
    }

    private static function neutralFlatExtraInfo(array $item): array
    {
        return self::neutralExtraInfo($item);
    }

    private static function providerExtraInfo(array $extraInfo): array
    {
        foreach (array_flip(self::extraInfoKeyMap()) as $newKey => $oldKey) {
            if (array_key_exists($newKey, $extraInfo)) {
                $extraInfo[$oldKey] = $extraInfo[$oldKey] ?? $extraInfo[$newKey];
            }
        }
        return $extraInfo;
    }

    private static function providerFlatExtraInfo(array $item): array
    {
        return self::providerExtraInfo($item);
    }

    private static function containsHiddenProvider(string $value): bool
    {
        foreach (array_keys(self::providerNameReplacements()) as $keyword) {
            if (strpos($value, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function providerNameReplacements(): array
    {
        $replacements = [];

        foreach (self::humanDisplayModels() as $model) {
            $displayName = trim((string)($model['name'] ?? ''));
            if ($displayName === '') {
                continue;
            }

            foreach (self::modelDisplayAliases($model) as $alias) {
                if ($alias !== $displayName) {
                    $replacements[$alias] = $displayName;
                }
            }

            $channel = trim((string)($model['channel'] ?? ''));
            if (self::shouldReplaceChannelName($channel, $displayName)) {
                $replacements[$channel] = $displayName;
            }
        }

        uksort($replacements, static fn($a, $b) => strlen($b) <=> strlen($a));

        return $replacements;
    }

    private static function sourceTypeMap(): array
    {
        return self::FALLBACK_SOURCE_TYPE_MAP;
    }

    private static function anchorIdKeyMap(): array
    {
        return self::FALLBACK_ANCHOR_ID_KEY_MAP;
    }

    private static function extraInfoKeyMap(): array
    {
        return self::FALLBACK_EXTRA_INFO_KEY_MAP;
    }

    private static function defaultDigitalHumanAuthName(): string
    {
        return self::humanModelDisplayName(self::HUMAN_MODEL_ID_V2);
    }

    private static function humanModelDisplayName(int $modelId): string
    {
        $model = self::humanDisplayModels()[$modelId] ?? [];
        return trim((string)($model['name'] ?? ''));
    }

    private static function humanDisplayModels(bool $refresh = false): array
    {
        if (!$refresh && self::$humanDisplayModels !== null) {
            return self::$humanDisplayModels;
        }

        try {
            $models = Models::field(['id', 'channel', 'name', 'model_version', 'configs'])
                ->whereIn('id', [self::HUMAN_MODEL_ID_V1, self::HUMAN_MODEL_ID_V2])
                ->select()
                ->toArray();
        } catch (Throwable) {
            return self::$humanDisplayModels = [];
        }

        $indexedModels = [];
        foreach ($models as $model) {
            $indexedModels[(int)$model['id']] = $model;
        }

        return self::$humanDisplayModels = $indexedModels;
    }

    private static function modelDisplayAliases(array $model): array
    {
        $configs = self::decodeModelConfigs((string)($model['configs'] ?? ''));
        $aliases = $configs[self::MODEL_ALIAS_CONFIG_KEY] ?? [];
        if (is_string($aliases)) {
            $aliases = [$aliases];
        }
        if (!is_array($aliases)) {
            return [];
        }

        $aliases = array_map(static fn($alias) => trim((string)$alias), $aliases);
        $aliases = array_filter($aliases, static fn($alias) => $alias !== '');

        return array_values(array_unique($aliases));
    }

    private static function decodeModelConfigs(string $configs): array
    {
        if (trim($configs) === '') {
            return [];
        }

        $decoded = json_decode($configs, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function shouldReplaceChannelName(string $channel, string $displayName): bool
    {
        if ($channel === '' || $channel === $displayName) {
            return false;
        }

        return !preg_match('/^v\d+$/i', $channel);
    }
}
