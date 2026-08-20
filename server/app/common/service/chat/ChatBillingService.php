<?php

namespace app\common\service\chat;

use app\common\enum\ChatEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\chat\ModelsCost;
use app\common\model\user\User;
use think\facade\Log;

class ChatBillingService
{
    /**
     * @param bool $skipTeamCheck 分享智能体等公开场景跳过团队到期/停用校验
     */
    public static function checkBalance(int $userId, mixed $modelKey, bool $skipTeamCheck = false): void
    {
        // 团队被停用 / 成员到期 → 拦截(消费前校验);公开分享对话不校验团队
        if (!$skipTeamCheck) {
            \app\common\service\TeamMemberService::assertActive($userId);
        }
        $modelCost = self::findModelCost($modelKey);
        if ($modelCost === null) {
            throw new \Exception('对话模型已被下架');
        }
        $need = self::estimateMinimumPoints($modelCost);
        $userInfo = User::findOrEmpty($userId)->toArray();
        if (empty($userInfo)) {
            throw new \Exception('用户查询失败');
        }
        // 企业空间内成员可用算力=企业钱包+团队长个人算力
        if (\app\common\service\TeamBillingService::spendableTokens($userId) < $need) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }
    }

    public static function charge(
        int $userId,
        mixed $modelKey,
        array $usage,
        int $logType,
        string $taskId,
        array $extra = []
    ): float {
        $modelCost = self::findModelCost($modelKey);
        $modelAlias = self::resolveModelAlias($modelKey, $modelCost);
        $points = self::calculatePoints($modelCost, $usage);

        if ($points <= 0 && ($modelCost === null || (int)($modelCost['quota_type'] ?? 0) === 0)) {
            Log::warning('对话模型售价未配置，跳过扣费: ' . $modelAlias);
        }

        if ($points > 0) {
            User::userTokensChange($userId, $points);
            $logExtra = array_merge([
                '模型'         => $modelAlias,
                '输入tokens'   => (int)($usage['prompt_tokens'] ?? 0),
                '输出tokens'   => (int)($usage['completion_tokens'] ?? 0),
                '总tokens'     => (int)($usage['total_tokens'] ?? 0),
                '实际消耗算力' => $points,
            ], $extra);
            AccountLogLogic::recordUserTokensLog(true, $userId, $logType, $points, $taskId, $logExtra);
        }

        return $points;
    }

    /**
     * @param array<string, mixed>|null $modelCost
     */
    public static function calculatePoints(?array $modelCost, array $usage): float
    {
        if ($modelCost === null) {
            return 0;
        }

        if ((int)($modelCost['quota_type'] ?? 0) === 1) {
            return round((float)($modelCost['model_price'] ?? 0), 2);
        }

        $inputPrice = (float)($modelCost['price'] ?? 0);
        $outputPrice = (float)($modelCost['output_price'] ?? 0);
        if ($outputPrice <= 0) {
            $outputPrice = $inputPrice;
        }

        $promptTokens = (int)($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int)($usage['completion_tokens'] ?? 0);

        if ($promptTokens === 0 && $completionTokens === 0) {
            $totalTokens = (int)($usage['total_tokens'] ?? 0);
            if ($totalTokens <= 0 || $inputPrice <= 0) {
                return 0;
            }
            return round($totalTokens * $inputPrice / 1000, 2);
        }

        return round(
            ($promptTokens * $inputPrice + $completionTokens * $outputPrice) / 1000,
            2
        );
    }

    public static function estimateMinimumPoints(?array $modelCost): float
    {
        if ($modelCost === null) {
            return 1;
        }
        if ((int)($modelCost['quota_type'] ?? 0) === 1) {
            $price = (float)($modelCost['model_price'] ?? 0);
            return $price > 0 ? $price : 1;
        }
        return 1;
    }

    public static function resolveModelAlias(mixed $modelKey, ?array $modelCost = null): string
    {
        if (is_string($modelKey) && $modelKey !== '') {
            return $modelKey;
        }
        return (string)($modelCost['alias'] ?? 'unknown');
    }

    public static function findModelCost(mixed $modelKey): ?array
    {
        if ($modelKey === null || $modelKey === '') {
            return null;
        }

        $query = ModelsCost::where(['type' => ChatEnum::MODEL_TYPE_CHAT, 'status' => 1]);

        if (is_numeric($modelKey)) {
            $modelKey = (int)$modelKey;
            $row = (clone $query)->where(['id' => $modelKey])->findOrEmpty();
            if (!$row->isEmpty()) {
                return $row->toArray();
            }
            $row = (clone $query)->where(['model_id' => $modelKey])->order('sort asc, id desc')->findOrEmpty();
            return $row->isEmpty() ? null : $row->toArray();
        }

        $alias = trim((string)$modelKey);
        $row = (clone $query)->where(['alias' => $alias])->findOrEmpty();
        if (!$row->isEmpty()) {
            return $row->toArray();
        }

        $row = (clone $query)->where(['name' => $alias])->order('id desc')->findOrEmpty();
        return $row->isEmpty() ? null : $row->toArray();
    }

    public static function normalizeUsage(array|int|float $usage): array
    {
        if (is_array($usage)) {
            return $usage;
        }
        $total = (int)$usage;
        return [
            'total_tokens'      => $total,
            'prompt_tokens'     => 0,
            'completion_tokens' => $total,
        ];
    }
}
