<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\chat\ChatModelsService;
use app\common\service\FileService;

/**
 * 生图/生视频模型（中台同步落库后的读取）
 */
class MediaModelsService
{
    /**
     * 技术 alias => 前端展示别名（name）
     * alias 仍用于调中台，不可改写。
     */
    private const DISPLAY_ALIASES = [
        'doubao-seedream-4-0-250828'     => 'seedream4.0',
        'gpt-image-2'                    => 'image-2',
        'doubao-seedance-1-0-pro-250528' => 'seedance1.0-pro',
    ];

    /**
     * @notes 按技术 alias 取展示名，未配置则回退原名
     */
    public static function resolveDisplayName(string $alias, string $fallback = ''): string
    {
        $alias = trim($alias);
        if ($alias === '') {
            return $fallback;
        }
        $map = [];
        foreach (self::DISPLAY_ALIASES as $key => $name) {
            $map[strtolower($key)] = $name;
        }
        return $map[strtolower($alias)] ?? ($fallback !== '' ? $fallback : $alias);
    }

    /**
     * 展示名 → 技术 alias（反向）
     */
    public static function resolveTechnicalAlias(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }
        $lower = strtolower($key);
        foreach (self::DISPLAY_ALIASES as $alias => $display) {
            if (strtolower($alias) === $lower || strtolower($display) === $lower) {
                return $alias;
            }
        }
        return $key;
    }

    /**
     * 按 alias / 展示名查找 draw 计费行
     *
     * @return array<string, mixed>
     * @throws \Exception
     */
    public static function findCostByAlias(string $modelKey, bool $requireEnabled = true): array
    {
        $key = trim($modelKey);
        if ($key === '') {
            throw new \Exception('模型不能为空');
        }

        $candidates = array_values(array_unique(array_filter([
            $key,
            self::resolveTechnicalAlias($key),
        ])));

        $cost = ModelsCost::where('type', ChatEnum::MODEL_TYPE_DRAW)
            ->whereIn('alias', $candidates)
            ->order('status desc, id desc')
            ->findOrEmpty()
            ->toArray();

        if (!$cost) {
            $cost = ModelsCost::where('type', ChatEnum::MODEL_TYPE_DRAW)
                ->whereIn('name', $candidates)
                ->order('status desc, id desc')
                ->findOrEmpty()
                ->toArray();
        }

        if (!$cost) {
            throw new \Exception('计费模型未配置: ' . $key);
        }

        if ($requireEnabled) {
            if ((int)($cost['status'] ?? 0) !== 1) {
                throw new \Exception('模型未启用: ' . $key);
            }
            $main = Models::where('id', (int)$cost['model_id'])->findOrEmpty()->toArray();
            if (!$main || (int)($main['is_enable'] ?? 0) !== 1) {
                throw new \Exception('模型未启用: ' . $key);
            }
        }

        return $cost;
    }

    /**
     * 按量单价：优先 model_price，否则 price
     */
    public static function resolveUnitPrice(array $cost): float
    {
        $modelPrice = (float)($cost['model_price'] ?? 0);
        if ($modelPrice > 0) {
            return $modelPrice;
        }
        return (float)($cost['price'] ?? 0);
    }

    /**
     * @notes 模型通道列表（对齐 ChatModelsService::getChannelList）
     * @param bool $filterDisabled 是否过滤未启用
     * @return array{channel: array}
     */
    public static function getChannelList(bool $filterDisabled = true): array
    {
        $lists = (new Models())
            ->alias('m')
            ->join('models_cost c', 'c.model_id = m.id')
            ->field([
                'm.id'      => 'model_id',
                'm.name',
                'm.logo',
                'm.channel',
                'm.is_enable',
                'm.configs',
                'c.id'      => 'model_sub_id',
                'c.alias',
                'c.status',
                'c.price',
                'c.model_price',
                'c.quota_type',
            ])
            ->where('m.type', ChatEnum::MODEL_TYPE_DRAW)
            ->whereNull('m.delete_time')
            ->order('m.sort asc, c.sort asc, c.id asc')
            ->select()
            ->toArray();

        $channel = [];
        foreach ($lists as $item) {
            $isEnabled = (int)$item['is_enable'] === 1 && (int)$item['status'] === 1;
            if ($filterDisabled && !$isEnabled) {
                continue;
            }

            $configs = [];
            if (!empty($item['configs'])) {
                $decoded = is_array($item['configs'])
                    ? $item['configs']
                    : json_decode((string)$item['configs'], true);
                $configs = is_array($decoded) ? $decoded : [];
            }

            $alias = (string)($item['alias'] ?? '');
            $mediaType = (string)($configs['media_type'] ?? '');
            $quotaType = (int)($item['quota_type'] ?? ChatModelsService::QUOTA_TYPE_TIMES);
            // 生图/生视频库内未同步 quota_type 时按媒体类型兜底为按量
            if (
                $quotaType === ChatModelsService::QUOTA_TYPE_TOKEN
                && in_array($mediaType, ['image', 'video'], true)
            ) {
                $quotaType = ChatModelsService::QUOTA_TYPE_TIMES;
            }
            $unitPrice = self::resolveUnitPrice($item);
            $billUnit = ChatModelsService::resolveBillUnit($quotaType, $mediaType, $alias);
            $channel[] = [
                'id'                => (string)$item['model_sub_id'],
                'name'              => self::resolveDisplayName($alias, (string)($item['name'] ?? '')),
                'model_id'          => (int)$item['model_id'],
                'model_sub_id'      => (int)$item['model_sub_id'],
                'channel'           => (string)($item['channel'] ?? ''),
                'alias'             => $alias,
                'media_type'        => $mediaType,
                'status'            => (string)(int)$isEnabled,
                'logo'              => FileService::getFileUrl($item['logo'] ?? ''),
                'unit_price'        => $unitPrice,
                'quota_type'        => $quotaType,
                'bill_unit'         => $billUnit,
                // 展示单位：算力/张 | 算力/秒 | 算力/次 | 算力
                'price_unit_label'  => ChatModelsService::priceUnitLabel($quotaType, $mediaType, $alias),
                'unit'              => ChatModelsService::priceUnitLabel($quotaType, $mediaType, $alias),
            ];
        }

        return ['channel' => $channel];
    }
}
