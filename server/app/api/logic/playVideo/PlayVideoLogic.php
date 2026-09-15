<?php

declare(strict_types=1);

namespace app\api\logic\playVideo;

use app\common\enum\playVideo\PlayVideoEnum;
use app\common\logic\BaseLogic;
use app\common\model\playVideo\PlayVideoTask;
use app\common\service\playVideo\PlayVideoBillingService;
use app\common\service\playVideo\PlayVideoCoverService;
use app\common\service\playVideo\PlayVideoFaceService;
use app\common\service\playVideo\PlayVideoMidService;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\facade\Cache;
use think\facade\Log;

/**
 * 爆款玩法（P0：影视换脸改词 face / 跳舞换脸 dance）。
 *
 * 提交链：校验 → 建单(uk_sn 幂等) → 预扣(一口价) → 调中台(模板字段直传+人脸签名URL)。
 * 状态同步三路收敛到 syncOne：中台回调(主) / cron 兜底(分钟级) / detail 惰性触发，
 * 终态落库用 status CAS，计费动作再过 bill_status 状态机第二道闸——重放安全。
 */
class PlayVideoLogic extends BaseLogic
{
    private const BG_PRESETS = ['store', 'office', 'street', 'studio'];

    // ─────────────────────────── 提交 ───────────────────────────

    /**
     * @return array|false ['sn','tokens_cost','status']
     */
    public static function submit(int $userId, array $params)
    {
        try {
            $play = (string)($params['play'] ?? '');
            if (!in_array($play, PlayVideoEnum::PLAYS, true)) {
                return self::failWith('不支持的玩法');
            }
            if ((int)($params['consent'] ?? 0) !== 1) {
                return self::failWith('请先勾选「本人授权」');
            }
            $template = self::normalizeTemplate($params['template'] ?? null);
            if ($template === null) {
                return self::failWith('模板信息缺失（template.video_url 必填）');
            }
            // 模板以本地表为准：客户端传的只是它打开页面时的快照，后台随时可能把模板下架
            // （前台显示开关=status），停在创作页的用户照样能点合成；顺带杜绝客户端自己拼一个
            // video_url 当模板提交。查不到/已下架一律拒，素材字段全部用库里的覆盖。
            $template = self::resolveTemplate($play, $template);
            if ($template === null) {
                return self::failWith('该模板已下架，请返回重新选择');
            }
            $faceFileId = (int)($params['face_file_id'] ?? 0);
            if ($play !== PlayVideoEnum::PLAY_CLIP && $faceFileId <= 0) {
                return self::failWith('请先上传正脸照');
            }
            $input = self::normalizeInput($play, $template, $params);
            if ($input === null) {
                return false; // failWith 已设置错误
            }

            // 在途上限：同一用户进行中任务数（含待提交）
            $running = PlayVideoTask::where('user_id', $userId)
                ->whereIn('status', [PlayVideoEnum::STATUS_PENDING, PlayVideoEnum::STATUS_GENERATING])
                ->count();
            if ($running >= PlayVideoEnum::MAX_USER_RUNNING) {
                return self::failWith('进行中的任务已达上限（' . PlayVideoEnum::MAX_USER_RUNNING . '个），请等完成后再来');
            }

            $sn = self::normalizeSn((string)($params['sn'] ?? ''));
            // 按时长计费（2026-09-02）：预扣 = 秒价 × 预估成片时长，成功按实际时长多退少补
            $estSeconds = PlayVideoBillingService::estimateSeconds($play, $input, $template);
            $cost = round(PlayVideoBillingService::quote($play) * $estSeconds, 2);

            // 建单（uk_sn 抢占，幂等锚点）
            $task = new PlayVideoTask();
            try {
                $playName = match ($play) {
                    PlayVideoEnum::PLAY_FACE => '影视换脸改词',
                    PlayVideoEnum::PLAY_DANCE => '跳舞换脸',
                    default => '影视引流拼接',
                };
                $task->save([
                    'sn'                => $sn,
                    'user_id'           => $userId,
                    'play'              => $play,
                    'name'              => $playName . ($template['name'] !== '' ? '·' . $template['name'] : ''),
                    'pic'               => (string)($template['cover_url'] ?? ''),
                    'template_ref_id'   => (string)($template['ref_id'] ?? ''),
                    'template_snapshot' => json_encode($template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'input'             => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status'            => PlayVideoEnum::STATUS_PENDING,
                    'bill_status'       => PlayVideoEnum::BILL_NONE,
                    'tokens_cost'       => $cost,
                    'create_time'       => time(),
                    'update_time'       => time(),
                ]);
            } catch (\Throwable $e) {
                // uk_sn 冲突 → 幂等返回原任务（重试同 sn 不重复扣费）
                $existing = PlayVideoTask::where('sn', $sn)->findOrEmpty();
                if (!$existing->isEmpty() && (int)$existing->user_id === $userId) {
                    return self::shapeSubmitResult($existing, true);
                }
                throw $e;
            }

            // 预扣（一口价，失败全退模式的起点）
            try {
                PlayVideoBillingService::hold($task, $estSeconds);
            } catch (\Throwable $e) {
                $task->save([
                    'status'      => PlayVideoEnum::STATUS_FAILED,
                    'fail_reason' => mb_substr($e->getMessage(), 0, 250),
                    'finish_time' => time(),
                    'update_time' => time(),
                ]);
                return self::failWith($e->getMessage());
            }

            // 调中台（模板字段直传；人脸换 48h 签名 URL）
            try {
                $midParams = self::buildMidParams($userId, $play, $sn, $template, $input);
                $data = PlayVideoMidService::submit($play, $midParams);
            } catch (\Throwable $e) {
                PlayVideoBillingService::refundIfHeld($task, '提交生成失败退费');
                $task->save([
                    'status'      => PlayVideoEnum::STATUS_FAILED,
                    'fail_reason' => mb_substr($e->getMessage(), 0, 250),
                    'finish_time' => time(),
                    'update_time' => time(),
                ]);
                return self::failWith($e->getMessage());
            }

            $task->save([
                'mid_task_id' => (string)($data['task_id'] ?? ''),
                'status'      => PlayVideoEnum::STATUS_GENERATING,
                'step_label'  => '排队等待生成',
                'update_time' => time(),
            ]);
            return self::shapeSubmitResult($task, false);
        } catch (\Throwable $e) {
            Log::channel('playvideo')->error('play_video submit error: ' . $e->getMessage());
            return self::failWith('提交失败，请稍后重试');
        }
    }

    // ─────────────────────────── 查询 ───────────────────────────

    /**
     * @return array|false
     */
    public static function detail(int $userId, string $sn)
    {
        $task = PlayVideoTask::where('sn', $sn)->where('user_id', $userId)->findOrEmpty();
        if ($task->isEmpty()) {
            return self::failWith('任务不存在');
        }
        // 惰性触发：生成中且 10s 内没同步过 → 顺手查一次中台（防抖锁防打爆）
        if ((int)$task->status === PlayVideoEnum::STATUS_GENERATING
            && time() - (int)$task->update_time > 10
            && self::acquireLock('play_video_lazy_' . $task->sn, 10)
        ) {
            try {
                self::syncOne($task, null);
            } catch (\Throwable $e) {
                Log::channel('playvideo')->warning('play_video lazy sync failed: ' . $e->getMessage());
            }
        }
        return self::shapeDetail($task);
    }

    public static function lists(int $userId, array $params): array
    {
        $pageNo = max(1, (int)($params['page_no'] ?? 1));
        $pageSize = min(50, max(1, (int)($params['page_size'] ?? 15)));
        $query = PlayVideoTask::where('user_id', $userId);
        if (!empty($params['play']) && in_array($params['play'], PlayVideoEnum::PLAYS, true)) {
            $query->where('play', $params['play']);
        }
        $count = (clone $query)->count();
        $rows = $query->order('id', 'desc')->page($pageNo, $pageSize)->select();
        $list = [];
        foreach ($rows as $row) {
            $list[] = self::shapeDetail($row);
        }
        return ['count' => $count, 'page_no' => $pageNo, 'page_size' => $pageSize, 'lists' => $list];
    }

    /**
     * 模板墙数据。⚠️ 模板归外部系统（决策 9），对接资料未到位前返回空列表占位；
     * 对接后此处改为代理外部系统 + 60s 缓存（02 文档 §5.5）。
     * 顺带下发各玩法一口价与用户可用算力（前端底部价签用，口径与 hold 预检一致）。
     */
    public static function templates(int $userId, string $play): array
    {
        $prices = [];
        foreach (PlayVideoEnum::PLAYS as $p) {
            try {
                $prices[$p] = PlayVideoBillingService::quote($p);
            } catch (\Throwable $e) {
                // 场景价未配置则该玩法不下发价签，前端隐藏
            }
        }
        $balance = 0;
        try {
            $balance = (float)\app\common\service\TeamBillingService::spendableTokens($userId);
        } catch (\Throwable $e) {
        }
        // 模板读本地表（定时任务从模板中心同步而来），零外部请求
        $query = \think\facade\Db::name('play_video_template')->where('status', 1);
        if (in_array($play, PlayVideoEnum::PLAYS, true)) {
            $query->where('play', $play);
        }
        $rows = $query->order('sort', 'asc')->order('create_time', 'desc')->select()->toArray();
        $templates = [];
        foreach ($rows as $row) {
            $script = json_decode((string)($row['script'] ?? '[]'), true);
            $templates[] = [
                'ref_id'           => (string)$row['id'],
                'play'             => (string)$row['play'],
                'name'             => (string)$row['name'],
                'category'         => (string)$row['category'],
                'heat'             => (string)$row['heat'],
                'duration_sec'     => (int)$row['duration_sec'],
                'video_url'        => \app\common\service\FileService::getFileUrl((string)$row['video_url']),
                'cover_url'        => $row['cover_url'] !== '' ? \app\common\service\FileService::getFileUrl((string)$row['cover_url']) : '',
                'face_count'       => (int)$row['face_count'],
                'allow_bg_replace' => (bool)$row['allow_bg_replace'],
                'script'           => is_array($script) ? $script : [],
            ];
        }

        return [
            'templates' => $templates,
            'notice'    => $templates === [] ? '模板上架中，敬请期待' : '',
            'prices'    => $prices,
            'balance'   => round($balance, 2),
        ];
    }

    // ─────────────────────────── 状态同步（三路收敛） ───────────────────────────

    /**
     * 中台回调入口（token 校验在控制器）。返回 false 时控制器回 fail 让中台重试。
     */
    public static function notify(array $payload): bool
    {
        $sn = trim((string)($payload['station_task_id'] ?? $payload['task_id'] ?? ''));
        if ($sn === '') {
            Log::channel('playvideo')->warning('play_video notify 缺少任务号');
            return false;
        }
        $task = PlayVideoTask::where('sn', $sn)->findOrEmpty();
        if ($task->isEmpty()) {
            Log::channel('playvideo')->warning('play_video notify 未知任务: ' . $sn);
            return true; // 未知任务 ack 掉，避免中台无限重试
        }
        if (in_array((int)$task->status, PlayVideoEnum::TERMINAL_STATUSES, true)) {
            return true; // 已终态幂等 ack（消化中台重放）
        }
        if (!self::acquireLock('play_video_notify_' . $sn, 180)) {
            return true; // 处理中先 ack，靠 cron 收敛
        }
        try {
            self::syncOne($task, $payload);
            return true;
        } catch (\Throwable $e) {
            Log::channel('playvideo')->error('play_video notify sync failed: ' . $e->getMessage());
            return false;
        } finally {
            self::releaseLock('play_video_notify_' . $sn);
        }
    }

    /**
     * 单任务状态收敛。$payload 为空时主动查中台。
     * 终态 CAS（status ∈ 未终态 → 终态）保证三路并发只生效一次；
     * 计费动作在 CAS 赢家侧执行，且 bill_status 状态机自身还有一道闸。
     */
    public static function syncOne(PlayVideoTask $task, ?array $payload): void
    {
        if (in_array((int)$task->status, PlayVideoEnum::TERMINAL_STATUSES, true)) {
            return;
        }
        if ($payload === null) {
            if ((string)$task->mid_task_id === '') {
                return; // 无中台任务号（提交中断件），由 sweepStuck 处理
            }
            $payload = PlayVideoMidService::queryTask((string)$task->mid_task_id);
        }

        $midStatus = strtoupper(trim((string)($payload['status'] ?? '')));
        switch ($midStatus) {
            case 'SUCCESS':
                $videoUrl = self::persistFinalVideo((string)$task->play, (string)($payload['video_url'] ?? ''));
                // 成片秒数（展示与结算基数）；billable_sec = 数据侧计费秒数（=成片秒，换脸类
                // 不足 3 秒按 3 秒），回调没带该字段时退回成片秒数
                $outputSec = round((float)($payload['duration_sec'] ?? 0), 2);
                $duration = (int)round($outputSec);
                $billableSec = round((float)($payload['billable_sec'] ?? 0), 2);
                if ($billableSec <= 0) {
                    $billableSec = $outputSec;
                }
                $update = [
                    'status'      => PlayVideoEnum::STATUS_SUCCESS,
                    'video_url'   => $videoUrl,
                    'duration'    => $duration,
                    'step_label'  => '',
                    'fail_reason' => '',
                    'finish_time' => time(),
                    'update_time' => time(),
                ];
                // 换脸类成片的脸已经换成用户的，封面必须取成片截帧，不能留模板原脸
                // （创作库卡片与预览浮层都读 pic）；拼接类沿用模板封面，只在模板没配封面时截帧兜底。
                // 站点非 OSS 存储时 snapshotUri 为空串：换脸类保持模板封面不留白，兜底场景照旧写空。
                $faceSwapped = in_array((string)$task->play, [PlayVideoEnum::PLAY_FACE, PlayVideoEnum::PLAY_DANCE], true);
                if ($faceSwapped || (string)$task->pic === '') {
                    $snapshot = PlayVideoCoverService::snapshotUri($videoUrl);
                    if ($snapshot !== '' || (string)$task->pic === '') {
                        $update['pic'] = $snapshot;
                    }
                }
                $won = PlayVideoTask::where('id', (int)$task->id)
                    ->whereIn('status', [PlayVideoEnum::STATUS_PENDING, PlayVideoEnum::STATUS_GENERATING])
                    ->update($update);
                $task->refresh();
                if ($won > 0) {
                    // 按计费秒数结算（多退少补）；计费秒数为 0 时保持预扣即实扣
                    PlayVideoBillingService::consume($task, $billableSec, $outputSec);
                    // 创作记录：任务表本身即 type=11 数据源(VideoLogic UNION)，无需另落库
                }
                return;
            case 'FAILURE':
                $reason = mb_substr((string)($payload['fail_reason'] ?? '生成失败'), 0, 250);
                $won = PlayVideoTask::where('id', (int)$task->id)
                    ->whereIn('status', [PlayVideoEnum::STATUS_PENDING, PlayVideoEnum::STATUS_GENERATING])
                    ->update([
                        'status'      => PlayVideoEnum::STATUS_FAILED,
                        'fail_reason' => $reason,
                        'step_label'  => '',
                        'finish_time' => time(),
                        'update_time' => time(),
                    ]);
                $task->refresh();
                if ($won > 0) {
                    PlayVideoBillingService::refundIfHeld($task, $reason . '（自动退费）');
                }
                return;
            default:
                // 非终态：刷新步骤文案与心跳（中台带 step_no/step_total 时拼「第X/Y步」前缀）
                $stepLabel = mb_substr((string)($payload['step_label'] ?? ''), 0, 24);
                $stepNo = (int)($payload['step_no'] ?? 0);
                $stepTotal = (int)($payload['step_total'] ?? 0);
                if ($stepLabel !== '' && $stepNo > 0 && $stepTotal > 1) {
                    $stepLabel = "第{$stepNo}/{$stepTotal}步·" . $stepLabel;
                }
                PlayVideoTask::where('id', (int)$task->id)
                    ->whereIn('status', [PlayVideoEnum::STATUS_PENDING, PlayVideoEnum::STATUS_GENERATING])
                    ->update([
                        'status'      => PlayVideoEnum::STATUS_GENERATING,
                        'step_label'  => $stepLabel,
                        'update_time' => time(),
                    ]);
                $task->refresh();
        }
    }

    /**
     * cron 兜底①：批量同步生成中的任务（回调丢失/迟到的收敛路径）。
     */
    public static function syncBatch(int $limit = 50): void
    {
        $rows = PlayVideoTask::where('status', PlayVideoEnum::STATUS_GENERATING)
            ->where('update_time', '<', time() - 60)
            ->order('update_time', 'asc')
            ->limit($limit)
            ->select();
        foreach ($rows as $task) {
            try {
                self::syncOne($task, null);
            } catch (\Throwable $e) {
                Log::channel('playvideo')->warning('play_video cron sync failed sn=' . $task->sn . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * cron 兜底②：超时判死（35min，比中台 30min 晚——正常情况中台先判死先退，
     * 这里只兜「中台失联」；判死前再查一次，以中台终态为准）+ 提交中断件清理。
     */
    public static function sweepTimeout(): void
    {
        // 超时件
        $rows = PlayVideoTask::where('status', PlayVideoEnum::STATUS_GENERATING)
            ->where('create_time', '<', time() - PlayVideoEnum::TASK_TIMEOUT_SECONDS)
            ->limit(20)
            ->select();
        foreach ($rows as $task) {
            try {
                self::syncOne($task, null); // 最后再给中台一次机会
            } catch (\Throwable $e) {
                // 查询失败照样进入判死
            }
            $task->refresh();
            if (in_array((int)$task->status, PlayVideoEnum::TERMINAL_STATUSES, true)) {
                continue;
            }
            $won = PlayVideoTask::where('id', (int)$task->id)
                ->where('status', PlayVideoEnum::STATUS_GENERATING)
                ->update([
                    'status'      => PlayVideoEnum::STATUS_TIMEOUT,
                    'fail_reason' => '生成超时，算力已自动退还',
                    'step_label'  => '',
                    'finish_time' => time(),
                    'update_time' => time(),
                ]);
            $task->refresh();
            if ($won > 0) {
                PlayVideoBillingService::refundIfHeld($task, '生成超时退费');
                Log::channel('playvideo')->warning('play_video 超时判死 sn=' . $task->sn);
            }
        }

        // 提交中断件：PENDING 超 10 分钟（hold 后进程中断，没到中台）→ 退款收尾
        $stuck = PlayVideoTask::where('status', PlayVideoEnum::STATUS_PENDING)
            ->where('create_time', '<', time() - 600)
            ->limit(20)
            ->select();
        foreach ($stuck as $task) {
            $won = PlayVideoTask::where('id', (int)$task->id)
                ->where('status', PlayVideoEnum::STATUS_PENDING)
                ->update([
                    'status'      => PlayVideoEnum::STATUS_FAILED,
                    'fail_reason' => '提交中断，算力已自动退还',
                    'finish_time' => time(),
                    'update_time' => time(),
                ]);
            $task->refresh();
            if ($won > 0) {
                PlayVideoBillingService::refundIfHeld($task, '提交中断退费');
                Log::channel('playvideo')->warning('play_video 提交中断收尾 sn=' . $task->sn);
            }
        }
    }

    // ─────────────────────────── 内部 ───────────────────────────

    /**
     * 模板直传对象（外部模板系统未接入前由前端透传，对接后改为 template_id 换取）。
     */
    /**
     * 按 ref_id（本地 play_video_template.id）回查模板，要求同玩法且 status=1（前台显示开）；
     * 返回以库为准的模板快照，客户端传的素材/名称/人脸数一概不信。
     */
    private static function resolveTemplate(string $play, array $client): ?array
    {
        // ⚠️ id 是模板中心的 ref_id（varchar(64) 十六进制串，如 89eb50b79526），不是自增数字：
        // 按 (int) 转会截成前导数字（"89eb…"→89）查空，所有模板都会被判「已下架」（2026-09-03 踩过）
        $id = mb_substr(trim((string)($client['ref_id'] ?? '')), 0, 64);
        if ($id === '') {
            return null;
        }
        $row = \think\facade\Db::name('play_video_template')
            ->where('id', $id)
            ->where('play', $play)
            ->find();
        if (!$row || (int)$row['status'] !== 1 || trim((string)$row['video_url']) === '') {
            return null;
        }
        return [
            'ref_id'           => (string)$row['id'],
            'video_url'        => (string)FileService::getFileUrl((string)$row['video_url']),
            'name'             => mb_substr((string)$row['name'], 0, 24),
            'cover_url'        => $row['cover_url'] !== '' ? (string)FileService::getFileUrl((string)$row['cover_url']) : '',
            'face_count'       => max(1, min(2, (int)$row['face_count'])),
            'allow_bg_replace' => (bool)$row['allow_bg_replace'],
            'duration_sec'     => max(0, min(3600, (int)$row['duration_sec'])),
        ];
    }

    private static function normalizeTemplate($template): ?array
    {
        if (!is_array($template)) {
            return null;
        }
        $videoUrl = trim((string)($template['video_url'] ?? ''));
        if ($videoUrl === '' || !preg_match('#^https?://#i', $videoUrl)) {
            return null;
        }
        return [
            'ref_id'           => mb_substr(trim((string)($template['ref_id'] ?? '')), 0, 64),
            'video_url'        => $videoUrl,
            'name'             => mb_substr(trim((string)($template['name'] ?? '')), 0, 24),
            'cover_url'        => mb_substr(trim((string)($template['cover_url'] ?? '')), 0, 512),
            'face_count'       => max(1, min(2, (int)($template['face_count'] ?? 1))),
            'allow_bg_replace' => (bool)($template['allow_bg_replace'] ?? false),
            'duration_sec'     => max(0, min(3600, (int)($template['duration_sec'] ?? 0))), // 预扣估算用
        ];
    }

    /**
     * 玩法差异参数校验与归一（中台会再校验一遍，这里为了给出友好错误）。
     */
    private static function normalizeInput(string $play, array $template, array $params): ?array
    {
        $input = [
            'face_file_id' => (int)($params['face_file_id'] ?? 0),
            'consent_at'   => time(),
            'consent_ip'   => (string)request()->ip(),
        ];
        if ($play === PlayVideoEnum::PLAY_FACE) {
            // 双人模板（face_count=2）要两张正脸：人物A 主角脸、人物B 配角脸（2026-09-03 用户定）
            if ((int)($template['face_count'] ?? 1) >= 2) {
                $input['face_file_id2'] = (int)($params['face_file_id2'] ?? 0);
                if ($input['face_file_id2'] <= 0) {
                    self::failWith('该模板有两位人物，请上传两张正脸照');
                    return null;
                }
            }
            $lines = $params['lines'] ?? [];
            if (!is_array($lines) || $lines === [] || count($lines) > 12) {
                self::failWith('台词需 1~12 条');
                return null;
            }
            $total = 0;
            $clean = [];
            foreach ($lines as $i => $line) {
                $text = trim((string)($line['text'] ?? ''));
                if ($text === '') {
                    self::failWith('第 ' . ($i + 1) . ' 条台词为空');
                    return null;
                }
                $total += mb_strlen($text);
                $clean[] = ['role' => mb_substr(trim((string)($line['role'] ?? '')), 0, 12), 'text' => mb_substr($text, 0, 200)];
            }
            $estSec = (int)ceil($total / PlayVideoEnum::SPEECH_RUNES_PER_SECOND);
            if ($estSec > PlayVideoEnum::MAX_SPEECH_SECONDS) {
                $maxRunes = (int)(PlayVideoEnum::MAX_SPEECH_SECONDS * PlayVideoEnum::SPEECH_RUNES_PER_SECOND);
                self::failWith("台词共 {$total} 字，预估口播约 {$estSec} 秒，超过 " . PlayVideoEnum::MAX_SPEECH_SECONDS . " 秒上限（约 {$maxRunes} 字），请精简");
                return null;
            }
            $input['lines'] = $clean;
            $input['voice'] = mb_substr(trim((string)($params['voice'] ?? '')), 0, 12);
            return $input;
        }

        if ($play === PlayVideoEnum::PLAY_CLIP) {
            // 影视引流拼接：用户视频(公开素材通道 URL) + 保留时长/转场/音效/钩子
            $productUrl = trim((string)($params['product_video_url'] ?? ''));
            if ($productUrl !== '' && !preg_match('#^https?://#i', $productUrl)) {
                // 素材通道返回相对 uri,转完整公网地址
                $productUrl = (string)\app\common\service\FileService::getFileUrl($productUrl);
            }
            if ($productUrl === '' || !preg_match('#^https?://#i', $productUrl)) {
                self::failWith('请先上传你的视频');
                return null;
            }
            $keep = (float)($params['clip_keep_sec'] ?? 15);
            if ($keep < 5 || $keep > 30) {
                self::failWith('切片保留时长需在 5~30 秒');
                return null;
            }
            // 语义值,不是 IMS 的 SubType——具体映射到哪个转场由中台决定
            // (旧前端传的 fadecolor 继续收,归一成 flash)
            $transition = strtolower(trim((string)($params['transition'] ?? 'none')));
            if ($transition === 'fadecolor') {
                $transition = 'flash';
            }
            if (!in_array($transition, ['none', 'fade', 'flash'], true)) {
                self::failWith('转场参数不正确');
                return null;
            }
            $hook = trim((string)($params['hook_text'] ?? ''));
            if (mb_strlen($hook) > 20) {
                self::failWith('钩子文案不能超过 20 字');
                return null;
            }
            // 用户视频时长:以本站素材库落库值为准(上传时 ffprobe 写的),查不到才信客户端传的;
            // 成片=切片+整段用户视频,不封顶预扣 60s 实扣能到 600s+,差额直接补扣到负
            $productSec = self::resolveProductVideoSec($productUrl, (float)($params['product_video_sec'] ?? 0));
            if ($productSec > PlayVideoEnum::CLIP_PRODUCT_MAX_SEC) {
                self::failWith('你的视频不能超过 ' . PlayVideoEnum::CLIP_PRODUCT_MAX_SEC . ' 秒，请先裁剪');
                return null;
            }
            $input['product_video_url'] = $productUrl;
            $input['product_video_sec'] = $productSec;
            $input['clip_keep_sec'] = $keep;
            $input['transition'] = $transition;
            $input['splice_sfx'] = (int)($params['splice_sfx'] ?? 1) === 1;
            $input['hook_text'] = $hook;
            unset($input['face_file_id']);
            return $input;
        }

        // dance
        $bgMode = (string)($params['bg_mode'] ?? 'keep');
        $input['bg_mode'] = $bgMode;
        if ($bgMode === 'preset') {
            $preset = (string)($params['bg_preset'] ?? '');
            if (!in_array($preset, self::BG_PRESETS, true)) {
                self::failWith('请选择平台背景场景');
                return null;
            }
            $input['bg_preset'] = $preset;
        } elseif ($bgMode === 'upload') {
            $bgFileId = (int)($params['bg_file_id'] ?? 0);
            if ($bgFileId <= 0) {
                self::failWith('请上传背景图');
                return null;
            }
            $input['bg_file_id'] = $bgFileId;
        } elseif ($bgMode !== 'keep') {
            self::failWith('背景模式不正确');
            return null;
        }
        if ($bgMode !== 'keep' && !$template['allow_bg_replace']) {
            self::failWith('该模板不支持换背景');
            return null;
        }
        $speech = trim((string)($params['speech_text'] ?? ''));
        if (mb_strlen($speech) > 30) {
            self::failWith('口播文案不能超过 30 字');
            return null;
        }
        if ($speech !== '' && mb_strlen($speech) < PlayVideoEnum::MIN_SPEECH_RUNES) {
            self::failWith('口播文案至少 ' . PlayVideoEnum::MIN_SPEECH_RUNES . ' 个字，太短无法对口型');
            return null;
        }
        $input['speech_text'] = $speech;
        $input['voice'] = mb_substr(trim((string)($params['voice'] ?? '')), 0, 12);
        return $input;
    }

    /**
     * 本站阿里云 OSS 直写目标（对齐 storyboard：成片与工作文件都落本站桶）。
     * 返回 null 表示未配阿里云 OSS 或地域不在数据侧云剪辑覆盖范围。
     *
     * @return array{bucket:string,location:string,region:string}|null
     */
    private static function ossDirectWriteTarget(): ?array
    {
        $ossConfig = (array)ConfigService::get('storage', 'aliyun');
        $bucket    = (string)($ossConfig['bucket'] ?? '');
        $location  = (string)($ossConfig['Location'] ?? '');
        foreach (['cn-beijing', 'cn-hangzhou', 'cn-shanghai', 'cn-shenzhen'] as $r) {
            if ($bucket !== '' && str_contains($location, $r)) {
                return ['bucket' => $bucket, 'location' => $location, 'region' => $r];
            }
        }
        return null;
    }

    /**
     * 组中台契约参数（01 文档 §2.1）。人脸/背景图此刻换 48h 签名 URL。
     *
     * @throws \Exception
     */
    private static function buildMidParams(int $userId, string $play, string $sn, array $template, array $input): array
    {
        $params = [
            'station_task_id' => $sn,
            'template'        => [
                'ref_id'           => $template['ref_id'],
                'video_url'        => $template['video_url'],
                'name'             => $template['name'],
                'face_count'       => $template['face_count'],
                'allow_bg_replace' => $template['allow_bg_replace'],
                // 中台预扣估算（dance 无口播按模板时长）与拼接校验都要用，之前漏传一直是 0
                'duration_sec'     => (int)($template['duration_sec'] ?? 0),
            ],
        ];
        if ($play !== PlayVideoEnum::PLAY_CLIP) {
            $params['face_url'] = PlayVideoFaceService::signedUrl($userId, (int)$input['face_file_id']);
            if (!empty($input['face_file_id2'])) {
                $params['face_url2'] = PlayVideoFaceService::signedUrl($userId, (int)$input['face_file_id2']);
            }
        }
        if ($play === PlayVideoEnum::PLAY_CLIP) {
            $params['product_video_url'] = (string)$input['product_video_url'];
            $params['product_video_sec'] = (float)($input['product_video_sec'] ?? 0);
            $params['clip_keep_sec'] = (float)$input['clip_keep_sec'];
            // 成片直写本站存储桶（对齐 storyboard 模式）：需阿里云 OSS 且地域在云剪辑覆盖范围
            $target = self::ossDirectWriteTarget();
            if ($target === null) {
                throw new \Exception('影视引流拼接需要配置阿里云OSS存储（地域限北京/杭州/上海/深圳）');
            }
            $params['region'] = $target['region'];
            $params['output_media_url'] = 'https://' . $target['bucket'] . '.' . $target['location'] . '.aliyuncs.com/uploads/playvideo/' . date('Ymd') . '/' . $sn . '.mp4';
            $params['transition'] = (string)$input['transition'];
            $params['splice_sfx'] = (bool)$input['splice_sfx'];
            if (($input['hook_text'] ?? '') !== '') {
                $params['hook_text'] = (string)$input['hook_text'];
            }
            unset($params['face_url']);
            return $params;
        }
        // 换脸类有口播时，数据侧先把模板裁到台词长度再换脸（成本随成片走，2026-09-02 定），
        // 裁剪产物落本站桶的工作目录；未配阿里云 OSS 则不裁剪、整段换脸（功能不受影响，只是贵）
        $needsSpeech = $play === PlayVideoEnum::PLAY_FACE || (($input['speech_text'] ?? '') !== '');
        if ($needsSpeech && ($target = self::ossDirectWriteTarget()) !== null) {
            $params['region'] = $target['region'];
            $params['work_media_url'] = 'https://' . $target['bucket'] . '.' . $target['location'] . '.aliyuncs.com/uploads/playvideo/tmp/' . date('Ymd') . '/' . $sn . '_trim.mp4';
        }
        if ($play === PlayVideoEnum::PLAY_FACE) {
            $params['lines'] = $input['lines'];
            $params['voice'] = (string)($input['voice'] ?? '');
        } else {
            if (($input['bg_mode'] ?? 'keep') === 'preset') {
                $params['bg_preset'] = (string)$input['bg_preset'];
            } elseif (($input['bg_mode'] ?? 'keep') === 'upload') {
                $params['bg_url'] = PlayVideoFaceService::signedUrl($userId, (int)$input['bg_file_id']);
            }
            if (($input['speech_text'] ?? '') !== '') {
                $params['speech_text'] = (string)$input['speech_text'];
                $params['voice'] = (string)($input['voice'] ?? '');
            }
        }
        return $params;
    }

    private static function shapeSubmitResult(PlayVideoTask $task, bool $duplicate): array
    {
        return [
            'sn'          => (string)$task->sn,
            'status'      => (int)$task->status,
            'tokens_cost' => (float)$task->tokens_cost,
            'duplicate'   => $duplicate,
        ];
    }

    /**
     * 成片落地本站存储（对齐 storyboard 模式，上游不落存储）：
     * clip 成片已由上游直写本站桶 → 裁成相对路径存库（域名迁移友好，展示走 getFileUrl）；
     * face/dance 为上游 24h 临时链接 → 立即下载转存本站存储；
     * 下载失败保留原链接（24h 内仍可看）并记告警，人工可重推。
     */
    private static function persistFinalVideo(string $play, string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if ($play === PlayVideoEnum::PLAY_CLIP) {
            $pos = strpos($url, 'uploads/');
            return $pos === false ? $url : substr($url, $pos);
        }
        $saved = FileService::downloadFileBySource($url, 'video');
        if ($saved === $url) {
            Log::channel('playvideo')->warning('play_video 成片转存失败,暂存上游临时链接(24h内有效): ' . $url);
        }
        return $saved;
    }

    /**
     * 用户视频时长(秒):先按相对 uri 查本站素材库(上传链路 ffprobe 落的 duration),
     * 查不到(外链/老素材)才用客户端上报值兜底;都没有返回 0 表示未知。
     */
    private static function resolveProductVideoSec(string $productUrl, float $clientSec): float
    {
        $pos = strpos($productUrl, 'uploads/');
        if ($pos !== false) {
            $uri = substr($productUrl, $pos);
            $sec = (float)(\app\common\model\file\File::where('uri', $uri)->value('duration') ?? 0);
            if ($sec > 0) {
                return round($sec, 2);
            }
        }
        return max(0, round($clientSec, 2));
    }

    private static function shapeDetail(PlayVideoTask $task): array
    {
        $snapshot = json_decode((string)$task->template_snapshot, true) ?: [];
        $input = json_decode((string)$task->input, true) ?: [];
        // 拼接的可选项回显:用户核对「我当时选了什么」,排查也不用翻库
        $settings = (string)$task->play === PlayVideoEnum::PLAY_CLIP ? [
            'clip_keep_sec' => (float)($input['clip_keep_sec'] ?? 0),
            'transition'    => (string)($input['transition'] ?? 'none'),
            'splice_sfx'    => !empty($input['splice_sfx']),
            'hook_text'     => (string)($input['hook_text'] ?? ''),
        ] : [];
        return [
            'settings'      => $settings,
            'sn'            => (string)$task->sn,
            'play'          => (string)$task->play,
            'status'        => (int)$task->status,
            'status_desc'   => PlayVideoEnum::statusDesc((int)$task->status),
            'step_label'    => (string)$task->step_label,
            'video_url'     => FileService::getFileUrl((string)$task->video_url),
            'fail_reason'   => (string)$task->fail_reason,
            'refunded'      => (int)$task->bill_status === PlayVideoEnum::BILL_REFUNDED,
            'tokens_cost'   => (float)$task->tokens_cost,
            'duration'      => (int)($task->duration ?? 0),
            'template_name' => (string)($snapshot['name'] ?? ''),
            'cover_url'     => (string)($snapshot['cover_url'] ?? ''),
            'create_time'   => (int)$task->create_time,
            'finish_time'   => (int)($task->finish_time ?? 0),
        ];
    }

    private static function normalizeSn(string $sn): string
    {
        $sn = trim($sn);
        if ($sn !== '' && preg_match('/^[A-Za-z0-9_\-]{6,64}$/', $sn)) {
            return $sn;
        }
        return 'pv' . date('ymdHis') . substr(md5(uniqid('', true)), 0, 6);
    }

    /** @return false 恒 false，顺手 setError（likeadmin 惯例） */
    private static function failWith(string $message): bool
    {
        self::setError($message);
        return false;
    }

    private static function acquireLock(string $key, int $ttl): bool
    {
        try {
            $redis = Cache::store('concurrent_redis')->handler();
            $fullKey = (string)config('cache.stores.concurrent_redis.prefix', '') . $key;
            if (!$redis->setnx($fullKey, 1)) {
                return false;
            }
            $redis->expire($fullKey, $ttl);
            return true;
        } catch (\Throwable $e) {
            Log::channel('playvideo')->warning('play_video 获取锁失败: ' . $e->getMessage());
            return false;
        }
    }

    private static function releaseLock(string $key): void
    {
        try {
            Cache::store('concurrent_redis')->delete($key);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
