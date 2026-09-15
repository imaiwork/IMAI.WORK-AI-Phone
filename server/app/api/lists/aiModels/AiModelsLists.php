<?php

namespace app\api\lists\aiModels;

use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\chat\ChatModelsService;
use app\common\service\FileService;

/**
 * AI模型配置 (API端)
 */
class AiModelsLists
{
    /**
     * @notes 模型列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author cwj
     */
    public function lists(): array
    {
        $chatModels = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'model_version'])
            ->where([
                'type'      => ChatEnum::MODEL_TYPE_CHAT,
                'is_enable' => 1,
            ])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $drawModels = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'model_version'])
            ->where([
                'type'      => ChatEnum::MODEL_TYPE_DRAW,
                'is_enable' => 1,
            ])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $humanModels = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'model_version'])
            ->where([
                'type'      => ChatEnum::MODEL_TYPE_HUMAN,
                'is_enable' => 1,
            ])
            ->with(['modelsLists' => function ($query) {
                $query->field(['model_id', 'alias'])->limit(1);
            }])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $priceMap = $this->buildSellPriceMap(array_merge($chatModels, $drawModels, $humanModels));

        foreach ($chatModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
            $this->appendSellPrice($item, $priceMap);
        }

        foreach ($drawModels as &$item) {
            $item['logo'] = FileService::getFileUrl($item['logo']);
            $this->appendSellPrice($item, $priceMap);
        }

        foreach ($humanModels as &$item) {
            $item['logo']  = FileService::getFileUrl($item['logo']);
            $item['alias'] = $item['modelsLists'][0]['alias'] ?? '';
            unset($item['modelsLists']);
            $this->appendSellPrice($item, $priceMap);
        }

        return [
            'chatModels'  => $chatModels,
            'drawModels'  => $drawModels,
            'humanModels' => $humanModels,
        ];
    }

    /**
     * 批量取每个主模型优先子模型的售价（启用优先，再按 sort/id）
     * @param array<int, array<string, mixed>> $models
     * @return array<int, array<string, mixed>>
     */
    private function buildSellPriceMap(array $models): array
    {
        $modelIds = array_values(array_unique(array_filter(array_map(
            static fn(array $item): int => (int)($item['id'] ?? 0),
            $models
        ))));
        if (empty($modelIds)) {
            return [];
        }

        $subModels = (new ModelsCost())
            ->field(['id', 'model_id', 'alias', 'price', 'model_price', 'quota_type', 'status', 'sort'])
            ->whereIn('model_id', $modelIds)
            ->order('status desc, sort asc, id desc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($subModels as $sub) {
            $modelId = (int)($sub['model_id'] ?? 0);
            if ($modelId <= 0 || isset($map[$modelId])) {
                continue;
            }
            $map[$modelId] = $sub;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, array<string, mixed>> $priceMap
     */
    private function appendSellPrice(array &$item, array $priceMap): void
    {
        $sub = $priceMap[(int)($item['id'] ?? 0)] ?? [];
        $quotaType = (int)($sub['quota_type'] ?? ChatModelsService::QUOTA_TYPE_TOKEN);
        $alias = (string)($sub['alias'] ?? '');
        $mediaType = '';

        // 生图/生视频：库内未同步 quota_type 时按媒体类型兜底，与后台 detail 口径一致
        if ((int)($item['type'] ?? 0) === ChatEnum::MODEL_TYPE_DRAW) {
            $mediaType = $this->resolveDrawMediaType($item, $alias);
            if (
                $quotaType === ChatModelsService::QUOTA_TYPE_TOKEN
                && in_array($mediaType, ['image', 'video'], true)
            ) {
                $quotaType = ChatModelsService::QUOTA_TYPE_TIMES;
            }
        }

        $sellPrice = $sub['price'] ?? '0.0000';
        if ($quotaType === ChatModelsService::QUOTA_TYPE_TIMES) {
            $modelPrice = (float)($sub['model_price'] ?? 0);
            if ($modelPrice > 0) {
                $sellPrice = $sub['model_price'];
            }
        }

        $item['sell_price'] = $sellPrice;
        $item['sell_price_desc'] = ChatModelsService::priceDesc($sellPrice, $quotaType, $mediaType, $alias);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveDrawMediaType(array $item, string $alias): string
    {
        $guessFrom = strtolower(trim((string)($item['name'] ?? '') . ' ' . $alias));
        if (
            str_contains($guessFrom, 'seedance')
            || str_contains($guessFrom, 'video')
        ) {
            return 'video';
        }

        return 'image';
    }
}
