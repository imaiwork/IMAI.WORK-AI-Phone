<?php

namespace app\common\service\phoneAgent;

use app\common\enum\ChatEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\phoneAgent\PhoneAgentTask;
use app\common\model\phoneAgent\PhoneAgentTurn;
use app\common\model\user\User;
use app\common\service\chat\ChatBillingService;
use think\facade\Db;

class PhoneAgentBillingService
{
    private const DEFAULT_MODEL = 'autoglm-phone';

    public static function modelKey(PhoneAgentTask $task): string
    {
        return self::modelKeyFromString((string)$task->model);
    }

    public static function modelKeyFromString(string $model): string
    {
        $model = trim($model);
        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    public static function checkBalanceByModel(int $userId, string $model): void
    {
        $modelKey = self::modelKeyFromString($model);
        self::assertModelReadyForBilling($modelKey);
        ChatBillingService::checkBalance($userId, $modelKey);
    }

    public static function checkBalance(PhoneAgentTask $task): void
    {
        self::checkBalanceByModel((int)$task->user_id, self::modelKey($task));
    }

    public static function billableUsage(array $usage): array
    {
        $promptTokens = max(0, (int)($usage['prompt_tokens'] ?? 0));
        $completionTokens = max(0, (int)($usage['completion_tokens'] ?? 0));
        $totalTokens = max(0, (int)($usage['total_tokens'] ?? 0));

        if ($totalTokens <= 0 && ($promptTokens > 0 || $completionTokens > 0)) {
            $totalTokens = $promptTokens + $completionTokens;
        }

        if ($promptTokens <= 0 && $completionTokens <= 0 && $totalTokens <= 0) {
            throw new \InvalidArgumentException('模型用量为空，无法扣费');
        }

        $usage['prompt_tokens'] = $promptTokens;
        $usage['completion_tokens'] = $completionTokens;
        $usage['total_tokens'] = $totalTokens;
        return $usage;
    }

    /**
     * 按模型售价扣费（la_models_cost）。
     */
    public static function chargeByModel(
        int $userId,
        string $modelKey,
        array $usage,
        string $taskRef,
        array $extra = []
    ): float {
        $usage = self::billableUsage($usage);
        $modelKey = self::modelKeyFromString($modelKey);
        $modelCost = self::assertModelReadyForBilling($modelKey);
        $modelAlias = ChatBillingService::resolveModelAlias($modelKey, $modelCost);

        $points = ChatBillingService::calculatePoints($modelCost, $usage);
        if ($points <= 0) {
            throw new \RuntimeException('模型「' . $modelAlias . '」用量过低或售价无效，无法扣费');
        }

        User::userTokensChange($userId, $points);

        $priceExtra = self::buildPriceExtra($modelCost, $usage, $points, $modelAlias);
        AccountLogLogic::recordUserTokensLog(
            true,
            $userId,
            AccountLogEnum::TOKENS_DEC_COMMON_CHAT,
            $points,
            $taskRef,
            array_merge($priceExtra, $extra)
        );

        return $points;
    }

    /**
     * @deprecated 请使用 chargeByModel，保留兼容旧调用
     */
    public static function chargeByCommonChat(
        int $userId,
        array $usage,
        string $taskRef,
        array $extra = []
    ): float {
        $modelKey = (string)($extra['模型'] ?? self::DEFAULT_MODEL);
        return self::chargeByModel($userId, $modelKey, $usage, $taskRef, $extra);
    }

    public static function chargeTurn(PhoneAgentTask $task, PhoneAgentTurn $turn, array $usage): float
    {
        if ((int)($turn->charged_time ?? 0) > 0) {
            return (float)($turn->charged_amount ?? 0);
        }

        $usage = self::billableUsage($usage);
        $modelKey = self::modelKey($task);

        Db::startTrans();
        try {
            $lockedTurn = PhoneAgentTurn::where('id', (int)$turn->id)->lock(true)->findOrEmpty();
            if ($lockedTurn->isEmpty()) {
                throw new \Exception('模型轮次不存在，无法扣费');
            }
            if ((int)($lockedTurn->charged_time ?? 0) > 0) {
                Db::commit();
                $turn->charged_amount = $lockedTurn->charged_amount;
                $turn->charged_time = $lockedTurn->charged_time;
                return (float)($lockedTurn->charged_amount ?? 0);
            }

            $points = self::chargeByModel(
                (int)$task->user_id,
                $modelKey,
                $usage,
                (string)$task->task_id,
                [
                    '来源' => 'AI手机操控',
                    '模型' => $modelKey,
                    '轮次' => (int)$turn->turn_no,
                    '设备号' => (string)$task->device_code,
                ]
            );

            $lockedTurn->charged_amount = $points;
            $lockedTurn->charged_time = time();
            $lockedTurn->charge_error = '';
            $lockedTurn->save();

            Db::commit();

            $turn->charged_amount = $lockedTurn->charged_amount;
            $turn->charged_time = $lockedTurn->charged_time;
            $turn->charge_error = '';
            return $points;
        } catch (\Throwable $e) {
            Db::rollback();
            PhoneAgentTurn::where('id', (int)$turn->id)->update(['charge_error' => $e->getMessage()]);
            $turn->charge_error = $e->getMessage();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $modelCost
     * @param array<string, mixed> $usage
     * @return array<string, mixed>
     */
    private static function buildPriceExtra(array $modelCost, array $usage, float $points, string $modelAlias): array
    {
        $extra = [
            '模型' => $modelAlias,
            '输入tokens' => (int)($usage['prompt_tokens'] ?? 0),
            '输出tokens' => (int)($usage['completion_tokens'] ?? 0),
            '总tokens' => (int)($usage['total_tokens'] ?? 0),
            '实际消耗算力' => $points,
        ];

        if ((int)($modelCost['quota_type'] ?? 0) === 1) {
            $extra['计费方式'] = '按次';
            $extra['按次售价'] = (float)($modelCost['model_price'] ?? 0);
        } else {
            $inputPrice = (float)($modelCost['price'] ?? 0);
            $outputPrice = (float)($modelCost['output_price'] ?? 0);
            if ($outputPrice <= 0) {
                $outputPrice = $inputPrice;
            }
            $extra['计费方式'] = '按token';
            $extra['输入单价'] = $inputPrice . '/1k tokens';
            $extra['输出单价'] = $outputPrice . '/1k tokens';
        }

        return $extra;
    }

    /**
     * 扣费前校验模型状态：计费行存在、启用，且父模型启用。
     *
     * @return array<string, mixed>
     */
    private static function assertModelReadyForBilling(string $modelKey): array
    {
        $modelKey = self::modelKeyFromString($modelKey);
        $modelCost = self::findModelCostAny($modelKey);
        $modelAlias = ChatBillingService::resolveModelAlias($modelKey, $modelCost);

        if ($modelCost === null) {
            throw new \RuntimeException('模型「' . $modelAlias . '」售价未配置，无法扣费');
        }

        // if ((int)($modelCost['status'] ?? 0) !== 1) {
        //     throw new \RuntimeException('模型「' . $modelAlias . '」未启用，无法扣费');
        // }

        $modelId = (int)($modelCost['model_id'] ?? 0);
        if ($modelId > 0) {
            $mainModel = Models::where(['id' => $modelId, 'type' => ChatEnum::MODEL_TYPE_CHAT])->findOrEmpty();
            if ($mainModel->isEmpty()) {
                throw new \RuntimeException('模型「' . $modelAlias . '」主模型不存在，无法扣费');
            }
            if ((int)($mainModel['is_enable'] ?? 0) !== 1) {
                throw new \RuntimeException('模型「' . $modelAlias . '」未启用，无法扣费');
            }
        }

        return $modelCost;
    }

    /**
     * 按 alias/name/id 查找计费模型（不限制 status）。
     *
     * @return array<string, mixed>|null
     */
    private static function findModelCostAny(string $modelKey): ?array
    {
        if ($modelKey === '') {
            return null;
        }

        $query = ModelsCost::where(['type' => ChatEnum::MODEL_TYPE_CHAT]);

        if (is_numeric($modelKey)) {
            $id = (int)$modelKey;
            $row = (clone $query)->where(['id' => $id])->findOrEmpty();
            if (!$row->isEmpty()) {
                return $row->toArray();
            }
            $row = (clone $query)->where(['model_id' => $id])->order('sort asc, id desc')->findOrEmpty();
            return $row->isEmpty() ? null : $row->toArray();
        }

        $alias = trim($modelKey);
        $row = (clone $query)->where(['alias' => $alias])->findOrEmpty();
        if (!$row->isEmpty()) {
            return $row->toArray();
        }

        $row = (clone $query)->where(['name' => $alias])->order('id desc')->findOrEmpty();
        return $row->isEmpty() ? null : $row->toArray();
    }
}
