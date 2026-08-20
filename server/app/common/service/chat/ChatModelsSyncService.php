<?php

namespace app\common\service\chat;

use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\service\FileService;
use app\common\service\ToolsService;
use Exception;
use think\facade\Db;

class ChatModelsSyncService
{
    public static function sync(): array
    {
        $response = ToolsService::DataCenter()->chatModelsLists();
        $models = $response['data']['models'] ?? [];
        if (!is_array($models)) {
            throw new Exception($response['message'] ?? '中台对话模型列表返回异常');
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'removed' => 0,
            'skipped' => 0,
            'filtered' => 0,
        ];

        Db::startTrans();
        try {
            // speech 音色模型属数字人(type=4)，历史误记为对话(type=1)时在此纠正
            self::fixSpeechModelsCostType();

            $remoteAliases = [];
            foreach ($models as $item) {
                $alias = self::getAlias($item);
                if ($alias === '') {
                    $stats['skipped']++;
                    continue;
                }
                if (!ChatModelSyncFilter::shouldSync($alias)) {
                    $stats['filtered']++;
                    continue;
                }
                $remoteAliases[] = $alias;
                self::syncItem($item, $alias, $stats);
            }

            // 远端已下架：删除本地系统同步模型与计费，保证目录与远端一致
            if ($remoteAliases) {
                $stats['removed'] = self::removeStale($remoteAliases);
            }

            // 业务默认 gpt-4o（id=2）必须保持启用
            self::ensureDefaultGpt4oEnabled();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // GEO 监测计价模型检查:缺计价行监测会免费放行,同步后给出友善提示
        $stats['geo_monitor'] = self::geoMonitorNotice($remoteAliases);

        return $stats;
    }

    /**
     * 检查 GEO 监测必备计价模型(GeoChargeService::MONITOR_PRICE_MODELS):
     * - 本地 models_cost 无启用行 → 监测免费放行,强提示 + 告警日志;
     * - 本地有兜底行但中台未下发 → 弱提示,说明成本价不随同步更新。
     * @return array{missing_remote: string[], missing_local: string[], notice: string}
     */
    private static function geoMonitorNotice(array $remoteAliases): array
    {
        $required = \app\common\service\geo\GeoChargeService::MONITOR_PRICE_MODELS;
        $missingRemote = [];
        foreach ($required as $alias => $name) {
            if (!in_array($alias, $remoteAliases, true)) {
                $missingRemote[$alias] = $name;
            }
        }
        $missingLocal = \app\common\service\geo\GeoChargeService::missingMonitorPriceRows();

        $notice = '';
        if ($missingLocal) {
            $notice = '注意:GEO监测计价模型缺失(' . implode('、', $missingLocal)
                . '),相关监测将免费放行;请在中台添加该模型后重新同步,或在数据库补计价行';
            \think\facade\Log::warning('GEO监测计价模型缺失: ' . implode(',', array_keys($missingLocal)));
        } elseif ($missingRemote) {
            $notice = '提示:GEO监测模型未随中台下发(' . implode('、', $missingRemote)
                . '),当前使用本地兜底计价行,成本价不随同步更新';
        }

        return [
            'missing_remote' => array_keys($missingRemote),
            'missing_local' => array_keys($missingLocal),
            'notice' => $notice,
        ];
    }

    /**
     * 纠正 speech-2.8-hd / speech-2.8-turbo 误记为对话模型(type=1) 的计费行，改为数字人(type=4)。
     */
    private static function fixSpeechModelsCostType(): void
    {
        ModelsCost::whereIn('alias', ['speech-2.8-hd', 'speech-2.8-turbo'])
            ->where('type', ChatEnum::MODEL_TYPE_CHAT)
            ->update(['type' => ChatEnum::MODEL_TYPE_HUMAN]);
    }

    /**
     * 业务默认 gpt-4o（models.id=2 / models_cost.id=2）强制保持启用。
     */
    private static function ensureDefaultGpt4oEnabled(): void
    {
        Models::where('id', 2)->where('is_enable', 0)->update(['is_enable' => 1]);
        ModelsCost::where('id', 2)->where('status', 0)->update(['status' => 1]);
    }

    /**
     * 删除远端已下架的本地系统对话模型（is_system=1，alias 不在远端）
     */
    private static function removeStale(array $remoteAliases): int
    {
        $uniqueAliases = array_unique($remoteAliases);

        $staleRows = ModelsCost::alias('c')
            ->join('models m', 'm.id = c.model_id')
            ->where('c.type', ChatEnum::MODEL_TYPE_CHAT)
            ->where('m.is_system', 1)
            ->whereNotIn('c.alias', $uniqueAliases)
            ->where('c.id', '<>', 2)
            ->where('c.model_id', '<>', 2)
            ->field('c.id, c.model_id')
            ->select()
            ->toArray();

        if (!$staleRows) {
            return 0;
        }

        $staleCostIds = array_column($staleRows, 'id');
        $staleModelIds = array_values(array_unique(array_map('intval', array_column($staleRows, 'model_id'))));

        ModelsCost::whereIn('id', $staleCostIds)->delete();

        $removedModels = 0;
        foreach ($staleModelIds as $modelId) {
            if ($modelId <= 0 || $modelId === 2) {
                continue;
            }
            // 主模型下若还有其它计费行则保留，避免误删
            if (ModelsCost::where('model_id', $modelId)->count() > 0) {
                continue;
            }
            // Models 使用 SoftDelete，此处物理删除以与 syncMedia 一致
            Db::name('models')->where('id', $modelId)->delete();
            $removedModels++;
        }

        return $removedModels;
    }

    private static function syncItem(array $item, string $alias, array &$stats): void
    {
        $inputPrice = self::formatPrice($item['input_cost_price'] ?? $item['cost_price'] ?? 0);
        $outputPrice = self::formatPrice($item['output_cost_price'] ?? 0);
        $quotaType = (int)($item['quota_type'] ?? 0);
        $modelPrice = self::formatPrice($item['model_price'] ?? 0);

        $costModel = ModelsCost::where([
            'type' => ChatEnum::MODEL_TYPE_CHAT,
            'alias' => $alias,
        ])->findOrEmpty();

        if ($costModel->isEmpty()) {
            $display = ChatModelDisplayNameResolver::resolve($alias);

            $mainModel = Models::create([
                'type' => ChatEnum::MODEL_TYPE_CHAT,
                'channel' => $display['channel'],
                'logo' => self::resolveSyncLogo($item, $alias, $display),
                'name' => $display['model_name'],
                'configs' => self::getDefaultConfigs(),
                'is_system' => 1,
                'is_enable' => 1,
            ]);

            ModelsCost::create([
                'model_id' => $mainModel['id'],
                'type' => ChatEnum::MODEL_TYPE_CHAT,
                'channel' => $display['channel'],
                'name' => $display['cost_name'],
                'alias' => $alias,
                'price' => self::normalizeSellPrice($inputPrice),
                'cost_price' => $inputPrice,
                'output_price' => self::normalizeOutputPrice($outputPrice > 0 ? $outputPrice : $inputPrice),
                'quota_type' => $quotaType,
                'model_price' => $modelPrice,
                'status' => 1,
                'sort' => 0,
            ]);
            $stats['created']++;
            return;
        }

        $oldCostPrice = (float)($costModel['cost_price'] ?? 0);
        $oldPrice = (float)($costModel['price'] ?? 0);
        $oldPriceFormatted = self::formatPrice($costModel['price'] ?? 0);
        $oldOutputPrice = (float)($costModel['output_price'] ?? 0);
        $oldOutputPriceFormatted = self::formatPrice($costModel['output_price'] ?? 0);
        $update = [
            'cost_price' => $inputPrice,
            'quota_type' => $quotaType,
            'model_price' => $modelPrice,
            'status' => 1,
        ];

        $nextPrice = $oldPriceFormatted;
        if ($oldPriceFormatted === '50.8687') {
            $nextPrice = '1.0000';
        } elseif ($oldCostPrice <= 0 && $oldPrice <= 0) {
            $nextPrice = self::normalizeSellPrice($inputPrice);
        }
        if ((float)$nextPrice < (float)$inputPrice) {
            $nextPrice = $inputPrice;
        }
        if ($nextPrice !== $oldPriceFormatted) {
            $update['price'] = $nextPrice;
        }

        $nextOutputPrice = $oldOutputPriceFormatted;
        if ($oldOutputPriceFormatted === '50.8687') {
            $nextOutputPrice = '2.0000';
        } elseif ($oldOutputPrice <= 0 && $outputPrice > 0) {
            $nextOutputPrice = self::normalizeOutputPrice($outputPrice);
        } elseif ($oldOutputPrice <= 0 && $inputPrice > 0) {
            $nextOutputPrice = self::normalizeOutputPrice($inputPrice);
        }
        if ((float)$outputPrice > 0 && (float)$nextOutputPrice < (float)$outputPrice) {
            $nextOutputPrice = self::normalizeOutputPrice($outputPrice);
        }
        if ($nextOutputPrice !== $oldOutputPriceFormatted) {
            $update['output_price'] = $nextOutputPrice;
        }

        ModelsCost::update($update, ['id' => $costModel['id']]);
        self::maybeBackfillMainModelLogo((int)$costModel['model_id'], $item, $alias);

        $stats['updated']++;
    }

    /**
     * 中台未返回 logo 时，按 channel/alias 复用内置默认图标。
     *
     * @param array<string, mixed> $item
     * @param array{model_name: string, cost_name: string, channel: string} $display
     */
    private static function resolveSyncLogo(array $item, string $alias, array $display): string
    {
        $logo = trim((string)($item['logo'] ?? ''));
        if ($logo === '') {
            $logo = ChatModelDisplayNameResolver::resolveLogo($display['channel'], $alias);
        }

        return FileService::setFileUrl($logo);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function maybeBackfillMainModelLogo(int $modelId, array $item, string $alias): void
    {
        if ($modelId <= 0) {
            return;
        }

        $mainModel = Models::find($modelId);
        if (!$mainModel || trim((string)($mainModel['logo'] ?? '')) !== '') {
            return;
        }

        $display = ChatModelDisplayNameResolver::resolve($alias);
        $logo = self::resolveSyncLogo($item, $alias, $display);
        if ($logo === '') {
            return;
        }

        Models::update(['logo' => $logo], ['id' => $modelId]);
    }

    private static function getAlias(array $item): string
    {
        return trim((string)($item['alias'] ?? $item['model_name'] ?? ''));
    }

    private static function formatPrice(mixed $price): string
    {
        return number_format(max((float)$price, 0), 4, '.', '');
    }

    /**
     * 同步写入本地售价：无价格默认 1；错误售价 50.8687 纠正为 1。
     */
    private static function normalizeSellPrice(mixed $price): string
    {
        $formatted = self::formatPrice($price);
        if ((float)$formatted <= 0) {
            return '1.0000';
        }
        if ($formatted === '50.8687') {
            return '1.0000';
        }
        return $formatted;
    }

    /**
     * 同步写入本地输出价：错误价格 50.8687 纠正为 2。
     */
    private static function normalizeOutputPrice(mixed $price): string
    {
        $formatted = self::formatPrice($price);
        if ($formatted === '50.8687') {
            return '2.0000';
        }
        return $formatted;
    }

    private static function getDefaultConfigs(): string
    {
        $configs = Models::where([
                'type' => ChatEnum::MODEL_TYPE_CHAT,
            ])
            ->where('configs', '<>', '')
            ->order('id asc')
            ->value('configs');

        return $configs ?: '{}';
    }
}
