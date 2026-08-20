<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\ChatEnum;
use app\common\enum\draw\DrawEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\FileService;
use app\common\service\ToolsService;
use Exception;
use think\facade\Db;

/**
 * 生图/生视频模型同步（对齐 ChatModelsSyncService）
 *
 * 中台接口：POST /api/data/media/models/lists
 * 落库：la_models(type=MODEL_TYPE_DRAW) + la_models_cost(type=MODEL_TYPE_DRAW)
 *
 * 策略：按 alias upsert，成本价随远端更新；运营手动改的售价(price)保留；
 *      远端已下架的本地删除，使目录与远端一致。
 */
class MediaModelsSyncService
{
    private const TYPE = ChatEnum::MODEL_TYPE_DRAW;

    public static function sync(): array
    {
        $response = ToolsService::DataCenter()->mediaModelsLists();
        $models = $response['data']['models'] ?? [];
        if (!is_array($models)) {
            throw new Exception($response['message'] ?? '中台生图/生视频模型列表返回异常');
        }

        $stats = [
            'total'   => count($models),
            'created' => 0,
            'updated' => 0,
            'removed' => 0,
            'skipped' => 0,
        ];

        Db::startTrans();
        try {
            $remoteAliases = [];
            foreach ($models as $item) {
                $alias = self::getAlias($item);
                if ($alias === '') {
                    $stats['skipped']++;
                    continue;
                }
                $remoteAliases[] = $alias;
                self::syncItem($item, $alias, $stats);
            }

            // 远端已下架：删除本地对应模型与计费，保证目录与远端一致
            $stats['removed'] = self::removeStale($remoteAliases);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $stats;
    }

    private static function syncItem(array $item, string $alias, array &$stats): void
    {
        $price = self::formatPrice($item['cost_price'] ?? 0);
        $outputPrice = self::formatPrice($item['output_cost_price'] ?? 0);
        $quotaType = (int)($item['quota_type'] ?? 1);
        $modelPrice = self::formatPrice($item['model_price'] ?? 0);
        $channel = trim((string)($item['channel'] ?? ''));
        $remoteName = trim((string)($item['name'] ?? $item['model_name'] ?? $alias));
        $displayName = MediaModelsService::resolveDisplayName($alias, $remoteName);
        $logo = FileService::setFileUrl(trim((string)($item['logo'] ?? '')));

        $costModel = ModelsCost::where([
            'type'  => self::TYPE,
            'alias' => $alias,
        ])->findOrEmpty();

        if ($costModel->isEmpty()) {
            $mainModel = Models::create([
                'type'      => self::TYPE,
                'channel'   => $channel,
                'logo'      => $logo,
                'name'      => $displayName,
                'configs'   => self::buildConfigs($alias),
                'is_system' => 1,
                'is_enable' => 1,
            ]);

            ModelsCost::create([
                'model_id'     => $mainModel['id'],
                'type'         => self::TYPE,
                'channel'      => $channel,
                'name'         => $displayName,
                'alias'        => $alias,
                'price'        => $price,
                'cost_price'   => $price,
                'output_price' => $outputPrice > 0 ? $outputPrice : $price,
                'quota_type'   => $quotaType,
                'model_price'  => $modelPrice,
                'status'       => 1,
                'sort'         => 0,
            ]);
            $stats['created']++;
            return;
        }

        // 更新：成本价随远端；售价(price)保留运营设置，仅未设置过时用成本价兜底
        $oldCostPrice = (float)($costModel['cost_price'] ?? 0);
        $oldPrice = (float)($costModel['price'] ?? 0);
        $oldOutputPrice = (float)($costModel['output_price'] ?? 0);
        $update = [
            'name'        => $displayName,
            'cost_price'  => $price,
            'quota_type'  => $quotaType,
            'model_price' => $modelPrice,
            'status'      => 1,
        ];
        if ($oldCostPrice <= 0 && $oldPrice <= 0) {
            $update['price'] = $price;
        }
        if ($oldOutputPrice <= 0 && $outputPrice > 0) {
            $update['output_price'] = $outputPrice;
        } elseif ($oldOutputPrice <= 0 && $price > 0) {
            $update['output_price'] = $price;
        }

        ModelsCost::update($update, ['id' => $costModel['id']]);
        Models::update(['name' => $displayName], ['id' => (int)$costModel['model_id']]);
        self::maybeBackfillMainModel((int)$costModel['model_id'], $logo);

        $stats['updated']++;
    }

    /**
     * 删除远端已下架的本地模型（type=DRAW，alias 不在远端）
     */
    private static function removeStale(array $remoteAliases): int
    {
        $query = ModelsCost::where('type', self::TYPE);
        if ($remoteAliases) {
            $query->whereNotIn('alias', array_unique($remoteAliases));
        }
        $staleModelIds = $query->column('model_id');

        // 计费行始终清掉不在远端的
        $costQuery = ModelsCost::where('type', self::TYPE);
        if ($remoteAliases) {
            $costQuery->whereNotIn('alias', array_unique($remoteAliases));
        }
        $costQuery->delete();

        if ($staleModelIds) {
            Db::name('models')->whereIn('id', $staleModelIds)->delete();
        }

        return count($staleModelIds);
    }

    /**
     * 主模型 logo 为空时回填
     */
    private static function maybeBackfillMainModel(int $modelId, string $logo): void
    {
        if ($modelId <= 0 || $logo === '') {
            return;
        }
        $mainModel = Models::find($modelId);
        if (!$mainModel || trim((string)($mainModel['logo'] ?? '')) !== '') {
            return;
        }
        Models::update(['logo' => $logo], ['id' => $modelId]);
    }

    /**
     * configs 记录 media_type 提示，供前端按图/视频分组（不新增表字段）
     */
    private static function buildConfigs(string $alias): string
    {
        return json_encode([
            'media_type' => self::guessMediaType($alias),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 按模型名猜测媒体类型：seedance 系为视频，其余按图片
     */
    private static function guessMediaType(string $alias): string
    {
        $lower = strtolower($alias);
        if (str_contains($lower, 'seedance') || str_contains($lower, 'video')) {
            return DrawEnum::MEDIA_VIDEO;
        }
        return DrawEnum::MEDIA_IMAGE;
    }

    private static function getAlias(array $item): string
    {
        return trim((string)($item['alias'] ?? $item['model_name'] ?? ''));
    }

    private static function formatPrice(mixed $price): string
    {
        return number_format(max((float)$price, 0), 4, '.', '');
    }
}
