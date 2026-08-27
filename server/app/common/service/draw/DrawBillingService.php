<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\draw\DrawEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\draw\DrawAsset;
use app\common\model\draw\DrawTask;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\service\VideoInfoService;
use think\facade\Db;
use think\facade\Log;

/**
 * draw 扣费：按 la_models_cost 算价 / 预扣 / 视频多退少补 / 幂等退费
 */
class DrawBillingService
{
    /** 视频预扣缺省秒数（请求未传 duration 时） */
    private const DEFAULT_VIDEO_SECONDS = 5.0;

    public const POINTS_REMARK_CONSUME = '消耗';
    public const POINTS_REMARK_REFUND = '失败退还';
    public const POINTS_REMARK_HOLD = '预扣';

    /**
     * @param string $state consume|refund|hold
     */
    public static function consumePointsRemark(string $state, float $amount = 0): string
    {
        return match ($state) {
            'refund' => self::POINTS_REMARK_REFUND,
            'hold' => $amount > 0 ? self::POINTS_REMARK_HOLD : '',
            'consume' => $amount > 0 ? self::POINTS_REMARK_CONSUME : '',
            default => '',
        };
    }

    /**
     * 按模型 alias 从 la_models_cost 取单价，结合数量算价
     *
     * @return array{unit: float, quantity: float, cost: float, code: int, name: string, alias: string, model_sub_id: int}
     * @throws \Exception
     */
    public function quoteByModel(string $model, string $mediaType, float $quantity = 1.0): array
    {
        $costRow = MediaModelsService::findCostByAlias($model);
        $unit = MediaModelsService::resolveUnitPrice($costRow);
        if ($unit <= 0) {
            throw new \Exception('模型售价未配置: ' . ($costRow['alias'] ?? $model));
        }

        $quantity = max($quantity, 0);
        if ($quantity <= 0) {
            $quantity = $mediaType === DrawEnum::MEDIA_VIDEO
                ? self::DEFAULT_VIDEO_SECONDS
                : 1.0;
        }

        $cost = round($unit * $quantity, 2);
        $alias = (string)($costRow['alias'] ?? $model);
        $billingCode = $this->resolveBillingCode($mediaType);

        return [
            'unit'         => $unit,
            'quantity'     => $quantity,
            'cost'         => $cost,
            'code'         => $billingCode,
            'name'         => MediaModelsService::resolveDisplayName($alias, (string)($costRow['name'] ?? $alias)),
            'alias'        => $alias,
            'model_sub_id' => (int)($costRow['id'] ?? 0),
        ];
    }

    public function resolveBillingCode(string $mediaType): int
    {
        return $mediaType === DrawEnum::MEDIA_VIDEO
            ? AccountLogEnum::TOKENS_DEC_DRAW_VIDEO
            : AccountLogEnum::TOKENS_DEC_DRAW_IMAGE;
    }

    /**
     * 从请求参数推断计费数量（张数 / 秒数）
     */
    public function resolveQuantity(array $params, string $mediaType = ''): float
    {
        if (isset($params['quantity']) && is_numeric($params['quantity'])) {
            return max((float)$params['quantity'], 0);
        }

        if ($mediaType === DrawEnum::MEDIA_VIDEO
            || (isset($params['duration']) && is_numeric($params['duration']))
            || (isset($params['seconds']) && is_numeric($params['seconds']))
        ) {
            if (isset($params['seconds']) && is_numeric($params['seconds'])) {
                return max((float)$params['seconds'], 0);
            }
            if (isset($params['duration']) && is_numeric($params['duration'])) {
                return max((float)$params['duration'], 0);
            }
            if ($mediaType === DrawEnum::MEDIA_VIDEO) {
                $conf = config('api_tools.draw') ?: [];
                $def = (float)($conf['video_default_duration'] ?? self::DEFAULT_VIDEO_SECONDS);
                return $def > 0 ? $def : self::DEFAULT_VIDEO_SECONDS;
            }
        }

        if (isset($params['n']) && is_numeric($params['n'])) {
            return max((float)$params['n'], 0);
        }

        return 1.0;
    }

    /**
     * 预扣算力，返回 tokens_log_id
     *
     * @throws \Exception
     */
    public function hold(int $userId, int $billingCode, float $cost, string $taskNo, array $snapshot = []): int
    {
        if ($cost <= 0) {
            return 0;
        }

        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            throw new \Exception('用户查询失败');
        }
        // 团队被停用 / 成员到期 → 拦截(消费前校验)
        \app\common\service\TeamMemberService::assertActive($userId);
        // 企业空间内成员可用算力=企业钱包+团队长个人算力
        if (\app\common\service\TeamBillingService::spendableTokens($userId) < $cost) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }

        Db::startTrans();
        try {
            User::userTokensChange($userId, $cost);
            $extra = self::sanitizeTokensLogExtra(array_merge([
                '实际消耗算力' => $cost,
                '扣费项目'     => AccountLogEnum::getChangeTypeDesc($billingCode) ?: 'draw生成扣费',
            ], $this->snapshotToLogFields($billingCode, $snapshot)));
            $remark = AccountLogEnum::getChangeTypeDesc($billingCode);
            if (!is_string($remark) || $remark === '') {
                $remark = 'draw生成扣费';
            }
            $log = AccountLogLogic::add(
                $userId,
                $billingCode,
                AccountLogEnum::DEC,
                $cost,
                1,
                $taskNo,
                $remark,
                $extra
            );
            Db::commit();

            return (int)($log->id ?? 0);
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('draw hold billing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 任务快照 → 流水可读字段（中文键）
     *
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function snapshotToLogFields(int $billingCode, array $snapshot): array
    {
        $fields = [];
        if (!empty($snapshot['model']) || !empty($snapshot['alias'])) {
            $fields['模型'] = (string)($snapshot['model'] ?? $snapshot['alias']);
        }
        if (isset($snapshot['unit']) && $snapshot['unit'] !== '' && $snapshot['unit'] !== null) {
            $fields['算力单价'] = $snapshot['unit'];
        }
        if (!empty($snapshot['name'])) {
            $fields['模型名称'] = (string)$snapshot['name'];
        }
        if (isset($snapshot['image_count']) || isset($snapshot['生成图片数'])) {
            $fields['生成图片数'] = (int)($snapshot['生成图片数'] ?? $snapshot['image_count']);
        }
        if (
            $billingCode === AccountLogEnum::TOKENS_DEC_DRAW_VIDEO
            && isset($snapshot['quantity'])
            && is_numeric($snapshot['quantity'])
        ) {
            $fields['预估秒数'] = (float)$snapshot['quantity'];
        }

        return $fields;
    }

    /**
     * 流水 extra 展示清洗：英文字段转中文，隐藏内部键
     *
     * @param array<string, mixed>|null $extra
     * @return array<string, mixed>
     */
    public static function sanitizeTokensLogExtra(?array $extra): array
    {
        if (!is_array($extra) || $extra === []) {
            return [];
        }

        $rename = [
            'model'           => '模型',
            'alias'           => '模型',
            'unit'            => '算力单价',
            '单价'             => '算力单价',
            'name'            => '模型名称',
            'image_count'     => '生成图片数',
            // 团队划拨/回收流水(account_log 变动详情展示中文键)
            'operator_id'     => '操作人ID',
            'operator_role'   => '操作人角色',
            'target_user_id'  => '目标用户ID',
        ];
        // quantity / code / model_sub_id / cost 不展示（cost 与「实际消耗算力」重复）
        // conversation_id / message_id / scene 为内部关联，不展示
        $hide = [
            'model_sub_id',
            'quantity',
            'code',
            'cost',
            'conversation_id',
            'message_id',
            'scene',
            '计费场景',
        ];
        $roleLabel = [
            1 => '成员',
            2 => '创始人',
            3 => '管理员',
        ];

        $out = [];
        foreach ($extra as $key => $value) {
            $key = (string)$key;
            if (in_array($key, $hide, true)) {
                continue;
            }
            if ($key === 'operator_role' || $key === '操作人角色') {
                $role = (int)$value;
                $value = $roleLabel[$role] ?? $value;
            }
            if (isset($rename[$key])) {
                $cn = $rename[$key];
                if (!array_key_exists($cn, $extra) && !array_key_exists($cn, $out)) {
                    $out[$cn] = $value;
                }
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * 成功确认消耗（图片等无需按量结算时直接确认）
     */
    public function consume(DrawTask $task): void
    {
        if ((int)$task->bill_status !== DrawEnum::BILL_HELD) {
            return;
        }
        $task->bill_status = DrawEnum::BILL_CONSUMED;
        $task->save();
    }

    /**
     * PPT 等「有结果才扣费」：成功落盘后调用。
     * 幂等：已 CONSUMED/REFUNDED 则直接返回；若已是 HELD 则转 consume。
     * 余额不足抛异常（由调用方把任务标失败）。
     *
     * @throws \Exception
     */
    public function chargeOnSuccess(DrawTask $task): void
    {
        $status = (int)$task->bill_status;
        if (in_array($status, [DrawEnum::BILL_CONSUMED, DrawEnum::BILL_REFUNDED], true)) {
            return;
        }
        if ($status === DrawEnum::BILL_HELD) {
            $this->consume($task);
            return;
        }

        $snapshot = is_array($task->bill_snapshot) ? $task->bill_snapshot : [];
        $unit = (float)($snapshot['unit'] ?? 0);
        $quantity = (float)($snapshot['quantity'] ?? 1);
        if ($quantity <= 0) {
            $quantity = 1.0;
        }
        $cost = isset($snapshot['cost']) && is_numeric($snapshot['cost'])
            ? (float)$snapshot['cost']
            : round($unit * $quantity, 2);
        if ($cost < 0) {
            $cost = 0.0;
        }

        $billingCode = (int)$task->billing_code ?: (int)($snapshot['code'] ?? 0);
        if ($billingCode <= 0) {
            $billingCode = $this->resolveBillingCode((string)$task->media_type);
        }
        if ($billingCode <= 0) {
            throw new \Exception('计费码无效');
        }

        // 条件更新抢占计费权，避免并发回调重复扣费
        $claimed = DrawTask::where('id', (int)$task->id)
            ->where('bill_status', DrawEnum::BILL_NONE)
            ->update(['bill_status' => DrawEnum::BILL_HELD]);
        if ($claimed === 0) {
            $fresh = DrawTask::findOrEmpty((int)$task->id);
            if (!$fresh->isEmpty() && (int)$fresh->bill_status === DrawEnum::BILL_HELD) {
                $this->consume($fresh);
            }
            $task->refresh();
            return;
        }
        $task->bill_status = DrawEnum::BILL_HELD;

        $mergedSnapshot = array_merge($snapshot, [
            'unit'        => $unit,
            'quantity'    => $quantity,
            'cost'        => $cost,
            'bill_timing' => 'on_success',
            '生成图片数'   => (int)$quantity,
        ]);

        try {
            $logId = $this->hold(
                (int)$task->user_id,
                $billingCode,
                $cost,
                (string)$task->task_no,
                $mergedSnapshot
            );
        } catch (\Throwable $e) {
            DrawTask::where('id', (int)$task->id)
                ->where('bill_status', DrawEnum::BILL_HELD)
                ->update(['bill_status' => DrawEnum::BILL_NONE]);
            $task->bill_status = DrawEnum::BILL_NONE;
            throw $e;
        }

        $task->billing_code = $billingCode;
        $task->tokens_cost = $cost;
        $task->tokens_log_id = $logId;
        $task->bill_snapshot = $mergedSnapshot;
        $task->save();
        $this->consume($task);
    }

    /**
     * 视频成功后按实际秒数结算：多扣退回，少扣追加，再确认消耗
     */
    public function settleVideo(DrawTask $task, float $actualSeconds): void
    {
        if ((int)$task->bill_status !== DrawEnum::BILL_HELD) {
            return;
        }

        $snapshot = is_array($task->bill_snapshot) ? $task->bill_snapshot : [];
        if (!empty($snapshot['settled'])) {
            $task->bill_status = DrawEnum::BILL_CONSUMED;
            $task->save();
            return;
        }

        $unit = (float)($snapshot['unit'] ?? 0);
        $heldQty = (float)($snapshot['quantity'] ?? 0);
        $heldCost = (float)$task->tokens_cost;

        if ($unit <= 0 && $heldQty > 0 && $heldCost > 0) {
            $unit = round($heldCost / $heldQty, 4);
        }

        $actualSeconds = max($actualSeconds, 0);
        if ($actualSeconds <= 0 || $unit <= 0) {
            // 拿不到实际时长：按预扣确认
            $snapshot['settled'] = 1;
            $snapshot['settle_skip'] = 'no_actual_duration';
            $task->bill_snapshot = $snapshot;
            $task->bill_status = DrawEnum::BILL_CONSUMED;
            $task->save();
            return;
        }

        $actualCost = round($unit * $actualSeconds, 2);
        $diff = round($actualCost - $heldCost, 2);
        $billingCode = (int)$task->billing_code ?: AccountLogEnum::TOKENS_DEC_DRAW_VIDEO;

        Db::startTrans();
        try {
            if ($diff > 0) {
                // 少扣 → 追加(补扣主体按预扣那一次的空间,用户切换空间后仍算原空间)
                $settleTeamId = \app\common\service\TeamBillingService::deductByOriginalLog(
                    (int)$task->user_id,
                    $diff,
                    $billingCode,
                    (string)$task->task_no
                );
                AccountLogLogic::recordUserTokensLog(
                    true,
                    (int)$task->user_id,
                    $billingCode,
                    $diff,
                    $task->task_no,
                    [
                        '扣费项目'     => 'AI生视频-超出预估补扣',
                        '算力单价'     => $unit,
                        '预估秒数'     => $heldQty,
                        '实际秒数'     => $actualSeconds,
                        '补扣秒数'     => round($actualSeconds - $heldQty, 2),
                        '实际消耗算力' => $diff,
                    ],
                    $settleTeamId
                );
            } elseif ($diff < 0) {
                // 多扣 → 退回
                $refund = abs($diff);
                AccountLogLogic::recordUserTokensLog(
                    false,
                    (int)$task->user_id,
                    $billingCode,
                    $refund,
                    $task->task_no,
                    [
                        '扣费项目'     => 'AI生视频-结余预估退费',
                        '算力单价'     => $unit,
                        '预估秒数'     => $heldQty,
                        '实际秒数'     => $actualSeconds,
                        '退费秒数'     => round($heldQty - $actualSeconds, 2),
                        '实际退费算力' => $refund,
                    ]
                );
            }

            $snapshot['quantity'] = $actualSeconds;
            $snapshot['cost'] = $actualCost;
            $snapshot['held_quantity'] = $heldQty;
            $snapshot['held_cost'] = $heldCost;
            $snapshot['actual_seconds'] = $actualSeconds;
            $snapshot['settle_diff'] = $diff;
            $snapshot['settled'] = 1;

            $task->tokens_cost = $actualCost;
            $task->bill_snapshot = $snapshot;
            $task->bill_status = DrawEnum::BILL_CONSUMED;
            $task->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('draw video settle failed: ' . $e->getMessage(), ['task_no' => $task->task_no]);
            throw $e;
        }
    }

    /**
     * 从回调 payload / 本地视频文件解析实际时长（秒）
     */
    public function resolveActualVideoSeconds(DrawTask $task, array $payload = []): float
    {
        $fromPayload = $this->extractDurationFromPayload($payload);
        if ($fromPayload > 0) {
            return $fromPayload;
        }

        $video = DrawAsset::where('task_id', $task->id)
            ->where('asset_type', DrawEnum::ASSET_VIDEO)
            ->where('file_url', '<>', '')
            ->order('sort', 'asc')
            ->find();
        if (!$video) {
            return 0;
        }

        try {
            $url = FileService::getFileUrl((string)$video->file_url);
            $info = (new VideoInfoService())->getInfo($url);
            $duration = (float)($info['duration'] ?? 0);
            return $duration > 0 ? $duration : 0;
        } catch (\Throwable $e) {
            Log::warning('draw resolve video duration failed: ' . $e->getMessage(), [
                'task_no' => $task->task_no,
            ]);
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractDurationFromPayload(array $payload): float
    {
        $buckets = [$payload];
        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            $buckets[] = $data;
            if (isset($data['result']) && is_array($data['result'])) {
                $buckets[] = $data['result'];
            }
            if (isset($data['task']) && is_array($data['task'])) {
                $buckets[] = $data['task'];
            }
        }

        foreach ($buckets as $bucket) {
            foreach (['duration', 'video_duration', 'seconds', 'length'] as $key) {
                if (isset($bucket[$key]) && is_numeric($bucket[$key])) {
                    $v = (float)$bucket[$key];
                    if ($v > 0) {
                        return $v;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * 失败幂等退费
     */
    public function refundIfHeld(DrawTask $task): void
    {
        if ((int)$task->bill_status !== DrawEnum::BILL_HELD) {
            return;
        }
        if ((float)$task->tokens_cost <= 0) {
            $task->bill_status = DrawEnum::BILL_REFUNDED;
            $task->save();
            return;
        }

        $billingCode = (int)$task->billing_code;
        if ($billingCode <= 0) {
            Log::error('draw refund missing billing_code', ['task_no' => $task->task_no]);
            return;
        }

        $refunded = UserTokensLog::where('user_id', $task->user_id)
            ->where('task_id', $task->task_no)
            ->where('change_type', $billingCode)
            ->where('action', AccountLogEnum::INC)
            ->count();
        if ($refunded > 0) {
            $task->bill_status = DrawEnum::BILL_REFUNDED;
            $task->save();
            return;
        }

        Db::startTrans();
        try {
            AccountLogLogic::recordUserTokensLog(
                false,
                (int)$task->user_id,
                $billingCode,
                (float)$task->tokens_cost,
                $task->task_no,
                ['退费原因' => $task->error_msg ?: '生成失败退费']
            );
            $task->bill_status = DrawEnum::BILL_REFUNDED;
            $task->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('draw refund failed: ' . $e->getMessage(), ['task_no' => $task->task_no]);
            throw $e;
        }
    }

    /**
     * 创作记录展示消耗：优先 draw_task.tokens_cost，否则按流水汇总
     * （兼容 checkCode 未命中导致 task_id 为空、只能对上 source_sn 的旧流水）
     *
     * @param int[] $changeTypes
     */
    public static function resolveRecordPoints(int $userId, string $taskNo, int $drawTaskId = 0, array $changeTypes = []): float
    {
        if ($drawTaskId > 0) {
            $cost = DrawTask::where('id', $drawTaskId)->value('tokens_cost');
            if ($cost !== null && (float)$cost > 0) {
                return round((float)$cost, 2);
            }
        }

        if ($taskNo === '') {
            return 0.0;
        }

        $query = UserTokensLog::where('user_id', $userId)
            ->where(function ($q) use ($taskNo) {
                $q->where('task_id', $taskNo)->whereOr('source_sn', $taskNo);
            });
        if ($changeTypes !== []) {
            $query->whereIn('change_type', $changeTypes);
        }

        return self::sumRecordPoints($query->field('action,change_amount,extra')->select()->toArray());
    }

    /**
     * 同 resolveRecordPoints，但吃调用方预取好的流水
     * 列表页批量展示时用：一次把整页的流水查回来，避免每行各查一次
     *
     * @param array      $logs         该记录对应的 user_tokens_log 行（需含 action/change_amount/extra）
     * @param float|null $drawTaskCost draw_task.tokens_cost，大于 0 时优先采用
     */
    public static function resolveRecordPointsFromLogs(array $logs, ?float $drawTaskCost = null): float
    {
        if ($drawTaskCost !== null && $drawTaskCost > 0) {
            return round($drawTaskCost, 2);
        }

        return self::sumRecordPoints($logs);
    }

    /**
     * 流水汇总口径：扣减累加、退还相抵，优先取 extra.实际消耗算力
     */
    protected static function sumRecordPoints(iterable $logs): float
    {
        $points = 0.0;
        foreach ($logs as $log) {
            $extra = $log['extra'] ?? '';
            if (is_string($extra) && $extra !== '') {
                $extra = json_decode($extra, true) ?: [];
            }
            if (!is_array($extra)) {
                $extra = [];
            }
            $amt = isset($extra['实际消耗算力']) && $extra['实际消耗算力'] !== ''
                ? (float)$extra['实际消耗算力']
                : (float)($log['change_amount'] ?? 0);
            if ((int)$log['action'] === AccountLogEnum::DEC) {
                $points += $amt;
            } else {
                $points -= $amt;
            }
        }

        return max(round($points, 2), 0);
    }

    /**
     * @return int[]
     */
    public static function videoRecordChangeTypes(): array
    {
        return [
            AccountLogEnum::TOKENS_DEC_DRAW_VIDEO,
            AccountLogEnum::TOKENS_DEC_VOLC_TEXT_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_VOLC_IMAGE_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_DOUBAO_TEXT_TO_VIDEO,
            AccountLogEnum::TOKENS_DEC_DOUBAO_IMAGE_TO_VIDEO,
        ];
    }

    /**
     * draw_video.task_status：-1失败 0等待 1成功 2处理中
     *
     * @return array{points: string, points_remark: string}
     */
    public static function describeVideoRecordPoints(float $amount, int $taskStatus): array
    {
        $amount = max(round($amount, 2), 0);
        if ($amount <= 0) {
            return [
                'points'        => '0',
                'points_remark' => '',
            ];
        }
        if (in_array($taskStatus, [1, 2], true)) {
            return [
                'points'        => '-' . $amount,
                'points_remark' => self::POINTS_REMARK_CONSUME,
            ];
        }
        if ($taskStatus === -1) {
            return [
                'points'        => '+' . $amount,
                'points_remark' => self::POINTS_REMARK_REFUND,
            ];
        }
        return [
            'points'        => (string)$amount,
            'points_remark' => self::POINTS_REMARK_HOLD,
        ];
    }

    public static function formatVideoRecordPoints(float $amount, int $taskStatus): string
    {
        return self::describeVideoRecordPoints($amount, $taskStatus)['points'];
    }
}
