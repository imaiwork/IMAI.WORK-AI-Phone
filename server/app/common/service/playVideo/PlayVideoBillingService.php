<?php

declare(strict_types=1);

namespace app\common\service\playVideo;

use app\common\enum\playVideo\PlayVideoEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\playVideo\PlayVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\api\logic\service\TokenLogService;
use think\facade\Db;
use think\facade\Log;

/**
 * 爆款玩法 C 端算力计费：按计费时长预扣，成功按实际计费时长多退少补，失败全退。
 * 状态机与幂等手法逐条对齐 DrawBillingService（bill_status CAS + 退款流水查重双闸）。
 *
 * 计费基数（2026-09-02 晚定稿，方案 B）：按成片秒数计费，标价 × 成片秒 = 扣费。
 * 数据侧对本站也是「每成片秒综合价」（已含视频编辑输入+输出计量、配音、对口型），
 * 本站秒价 = 数据侧秒价 ÷ 100 即零毛利，后台上调即毛利；结算用数据侧回传的
 * billable_sec（= 成片秒，换脸类不足 3 秒按 3 秒），两端账目按构造一致。
 * 有口播的换脸单，数据侧会先把模板裁到台词长度再换脸，成本随成片走。
 */
class PlayVideoBillingService
{
    /**
     * 玩法秒价（算力/秒，2026-09-02 定：视频生成一律按成片时长计费），
     * 读 la_model_config 场景行，后台可调。
     *
     * @throws \Exception 场景价未配置
     */
    public static function quote(string $play): float
    {
        $scene = PlayVideoEnum::SCENES[$play] ?? '';
        if ($scene === '') {
            throw new \Exception('不支持的玩法');
        }
        $score = (float)TokenLogService::getTypeScore($scene);
        if ($score <= 0) {
            throw new \Exception('该玩法价格未配置，请联系管理员');
        }
        return round($score, 2);
    }

    /**
     * 预扣：余额校验（团队规则经统一入口自动生效）→ 扣算力 → 记账变 → CAS 置 HELD。
     * 只对 bill_status=NONE 的任务行生效，CAS 输了直接返回（并发防双扣）。
     *
     * @throws \Exception 算力不足(4059) / 团队停用等
     */
    public static function hold(PlayVideoTask $task, int $estSeconds = 0): void
    {
        $cost = (float)$task->tokens_cost;
        if ($cost <= 0) {
            return;
        }
        $userId = (int)$task->user_id;
        $billingCode = PlayVideoEnum::billingCode((string)$task->play);
        if ($billingCode <= 0) {
            throw new \Exception('计费码无效');
        }

        $user = User::findOrEmpty($userId);
        if ($user->isEmpty()) {
            throw new \Exception('用户查询失败');
        }
        \app\common\service\TeamMemberService::assertActive($userId);
        if (\app\common\service\TeamBillingService::spendableTokens($userId) < $cost) {
            $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }

        // 条件更新抢占计费权（抄 Draw chargeOnSuccess 的 CAS）
        $claimed = PlayVideoTask::where('id', (int)$task->id)
            ->where('bill_status', PlayVideoEnum::BILL_NONE)
            ->update(['bill_status' => PlayVideoEnum::BILL_HELD]);
        if ($claimed === 0) {
            $task->refresh();
            return;
        }
        $task->bill_status = PlayVideoEnum::BILL_HELD;

        Db::startTrans();
        try {
            User::userTokensChange($userId, $cost);
            AccountLogLogic::add(
                $userId,
                $billingCode,
                AccountLogEnum::DEC,
                $cost,
                1,
                (string)$task->sn,
                AccountLogEnum::getChangeTypeDesc($billingCode) ?: '爆款玩法扣费',
                array_filter([
                    '预扣算力'     => $cost,
                    '算力单价'     => self::rateLabel((string)$task->play),
                    '预估成片时长' => $estSeconds > 0 ? $estSeconds . '秒' : '',
                    '玩法'         => self::playLabel((string)$task->play),
                    '说明'         => '按成片时长预扣,完成后按实际成片时长多退少补,不足3秒按3秒',

                ])
            );
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            // 钱没扣成，释放计费权（下一次重试可再抢）
            PlayVideoTask::where('id', (int)$task->id)
                ->where('bill_status', PlayVideoEnum::BILL_HELD)
                ->update(['bill_status' => PlayVideoEnum::BILL_NONE]);
            $task->bill_status = PlayVideoEnum::BILL_NONE;
            Log::error('play_video hold billing failed: ' . $e->getMessage(), ['sn' => $task->sn]);
            throw $e;
        }
    }

    /** 单价文案（消耗记录展示用）；价未配置返回空串 */
    private static function rateLabel(string $play): string
    {
        try {
            return self::quote($play) . ' 算力/秒';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** 玩法中文名（消耗记录展示用，不给用户看内部英文标识） */
    private static function playLabel(string $play): string
    {
        return match ($play) {
            PlayVideoEnum::PLAY_FACE => '影视换脸改词',
            PlayVideoEnum::PLAY_DANCE => '跳舞换脸',
            PlayVideoEnum::PLAY_CLIP => '影视引流拼接',
            default => $play,
        };
    }

    /** 换脸类最低计费秒数：视频编辑上游输入不能短于 3 秒，不足 3 秒按 3 秒计 */
    public const MIN_BILL_SECONDS = 3;
    /** 视频编辑上游输出上限（秒）：不开口播的跳舞成片=模板时长，但最多 15 秒 */
    public const EDIT_MAX_OUTPUT_SEC = 15;

    /**
     * 预估成片秒数（预扣 = 秒价 × 它；结算按数据侧回传的实际计费秒数多退少补）：
     *   face         = 台词口播时长（成片=音频时长，模板会先裁到台词长度）；
     *   dance 开口播 = 口播时长；不开口播 = 模板时长（未知按 15s 上限估）；
     *   换脸类不足 3 秒按 3 秒；clip = keep + 用户视频时长（素材库回填），未知才按 60s。
     */
    public static function estimateSeconds(string $play, array $input, array $template): int
    {
        if ($play === PlayVideoEnum::PLAY_CLIP) {
            // 成片=切片 keep+用户视频整段;素材库查到了时长就按实际估,未知才退回 60s
            $productSec = (float)($input['product_video_sec'] ?? 0);
            if ($productSec > 0) {
                return (int)ceil((float)($input['clip_keep_sec'] ?? 15) + $productSec);
            }
            return 60;
        }
        $speech = '';
        if ($play === PlayVideoEnum::PLAY_FACE) {
            $speech = implode('', array_map(fn ($l) => (string)($l['text'] ?? ''), (array)($input['lines'] ?? [])));
        } elseif (($input['speech_text'] ?? '') !== '') {
            $speech = (string)$input['speech_text'];
        }
        if ($speech !== '') {
            $sec = (int)ceil(mb_strlen($speech) / PlayVideoEnum::SPEECH_RUNES_PER_SECOND);
        } else {
            $tplSec = (int)($template['duration_sec'] ?? 0);
            $sec = $tplSec > 0 ? min($tplSec, self::EDIT_MAX_OUTPUT_SEC) : self::EDIT_MAX_OUTPUT_SEC;
        }
        return max(self::MIN_BILL_SECONDS, $sec);
    }

    /**
     * 成功结算（2026-09-02 按时长计费）：HELD → CONSUMED（CAS 赢家动钱），
     * 实扣 = 秒价 × 计费秒数（$billableSeconds = 成片秒，换脸类不足 3 秒按 3 秒），
     * 与预扣差额多退少补；$outputSeconds 为成片秒数，进消耗记录展示：
     *   多收了 → 结余退费（recordUserTokensLog 按原扣费流水的团队归属退回，
     *            备注自动为「超额恢复算力」）；
     *   少收了 → 差额补扣（量级小，允许扣至负由后续消费抵平）。
     * 结算后任务 tokens_cost 更新为实扣值。幂等：CAS 输了直接返回。
     */
    public static function consume(PlayVideoTask $task, float $billableSeconds = 0, float $outputSeconds = 0): void
    {
        $won = PlayVideoTask::where('id', (int)$task->id)
            ->where('bill_status', PlayVideoEnum::BILL_HELD)
            ->update(['bill_status' => PlayVideoEnum::BILL_CONSUMED]);
        $task->refresh();
        if ($won === 0 || $billableSeconds <= 0) {
            return;
        }

        try {
            $rate = self::quote((string)$task->play);
        } catch (\Throwable $e) {
            return; // 价被下架等异常：保持预扣即实扣
        }
        $held = (float)$task->tokens_cost;
        $actual = round($rate * $billableSeconds, 2);
        $delta = round($actual - $held, 2);
        $userId = (int)$task->user_id;
        $billingCode = PlayVideoEnum::billingCode((string)$task->play);
        $roundedUp = $outputSeconds > 0 && $billableSeconds > $outputSeconds + 0.05;
        $detail = array_filter([
            '算力单价' => $rate . ' 算力/秒',
            '成片时长' => $outputSeconds > 0 ? round($outputSeconds, 2) . '秒' : '',
            '计费时长' => $roundedUp ? $billableSeconds . '秒(不足' . self::MIN_BILL_SECONDS . '秒按' . self::MIN_BILL_SECONDS . '秒计)' : '',
            '预扣算力' => $held,
            '实扣算力' => $actual,
        ]);

        try {
            if ($delta < 0) {
                AccountLogLogic::recordUserTokensLog(false, $userId, $billingCode, -$delta, (string)$task->sn,
                    ['扣费项目' => '结余预估退费'] + $detail);
            } elseif ($delta > 0) {
                Db::startTrans();
                try {
                    User::userTokensChange($userId, $delta);
                    AccountLogLogic::add(
                        $userId,
                        $billingCode,
                        AccountLogEnum::DEC,
                        $delta,
                        1,
                        (string)$task->sn,
                        AccountLogEnum::getChangeTypeDesc($billingCode) ?: '爆款玩法扣费',
                        ['扣费项目' => '实际计费差额补扣'] + $detail
                    );
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    throw $e;
                }
            }
            if ($delta != 0.0) {
                PlayVideoTask::where('id', (int)$task->id)->update(['tokens_cost' => $actual]);
                $task->refresh();
            }
        } catch (\Throwable $e) {
            Log::channel('playvideo')->error('play_video 时长结算失败(预扣已保留) sn=' . $task->sn . ': ' . $e->getMessage());
        }
    }

    /**
     * 失败/超时退款：双闸幂等——bill_status CAS 为第一道，退款流水查重为第二道
     * （逐条对齐 DrawBillingService::refundIfHeld；退款经 recordUserTokensLog(false,...)
     * 自动按原扣费流水的团队归属退回正确主体）。
     */
    public static function refundIfHeld(PlayVideoTask $task, string $reason = ''): void
    {
        if ((int)$task->bill_status !== PlayVideoEnum::BILL_HELD) {
            return;
        }
        $cost = (float)$task->tokens_cost;
        $billingCode = PlayVideoEnum::billingCode((string)$task->play);
        if ($cost <= 0 || $billingCode <= 0) {
            PlayVideoTask::where('id', (int)$task->id)
                ->where('bill_status', PlayVideoEnum::BILL_HELD)
                ->update(['bill_status' => PlayVideoEnum::BILL_REFUNDED]);
            $task->refresh();
            return;
        }

        // 第二道闸：已有同任务退款流水（历史退款/并发路已处理）→ 只补状态
        $refunded = UserTokensLog::where('user_id', (int)$task->user_id)
            ->where('task_id', (string)$task->sn)
            ->where('change_type', $billingCode)
            ->where('action', AccountLogEnum::INC)
            ->count();
        if ($refunded > 0) {
            PlayVideoTask::where('id', (int)$task->id)
                ->where('bill_status', PlayVideoEnum::BILL_HELD)
                ->update(['bill_status' => PlayVideoEnum::BILL_REFUNDED]);
            $task->refresh();
            return;
        }

        // 第一道闸：CAS 抢退款权，赢家才动钱
        $claimed = PlayVideoTask::where('id', (int)$task->id)
            ->where('bill_status', PlayVideoEnum::BILL_HELD)
            ->update(['bill_status' => PlayVideoEnum::BILL_REFUNDED]);
        if ($claimed === 0) {
            $task->refresh();
            return;
        }

        try {
            AccountLogLogic::recordUserTokensLog(
                false,
                (int)$task->user_id,
                $billingCode,
                $cost,
                (string)$task->sn,
                ['退费原因' => $reason !== '' ? $reason : ((string)$task->fail_reason ?: '生成失败退费')]
            );
            $task->refresh();
        } catch (\Throwable $e) {
            // 钱没退成，回滚闸门允许 cron/人工重试
            PlayVideoTask::where('id', (int)$task->id)
                ->where('bill_status', PlayVideoEnum::BILL_REFUNDED)
                ->update(['bill_status' => PlayVideoEnum::BILL_HELD]);
            $task->bill_status = PlayVideoEnum::BILL_HELD;
            Log::error('play_video refund failed: ' . $e->getMessage(), ['sn' => $task->sn]);
            throw $e;
        }
    }
}
