<?php

namespace app\common\service\ffmpeg;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\service\storage\Driver as StorageDriver;
use app\common\service\transcoding\OssMediaProcessService;
use app\common\service\VideoInfoService;
use app\model\VideoSlice;
use app\model\VideoSliceItem;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class MaterialSliceBatchService
{
    /** 基准片长（秒） */
    public const SLICE_SECONDS = 5.0;

    /** (5s, 10s) 区间：尾数严格大于该值才切成两段 */
    public const SHORT_TAIL_MIN = 2.0;

    /** ≥10s：末段小于该值则并入上一段 */
    public const LONG_TAIL_MERGE_LT = 3.0;

    /**
     * 计算切割计划（各段时长，秒）。空数组 = 不切割（原片入库、不扣费）。
     *
     * 规则：
     * - ≤5s：不切
     * - 5s < t < 10s：尾数 >2s 才切成 5 + rem（如 7.2→5+2.2；6/7 不切）
     * - ≥10s：按 5s 切；末段 <3s 并入上一段（如 11→5+6；22→5×3+7；24→5×4+4）
     *
     * @return float[]
     */
    public static function buildSlicePlan(float $duration): array
    {
        $duration = round(max(0, $duration), 2);
        if ($duration <= self::SLICE_SECONDS) {
            return [];
        }

        $unit = self::SLICE_SECONDS;
        $n = (int)floor($duration / $unit);
        $rem = round($duration - $n * $unit, 2);

        // 5 < t < 10：仅当尾数 > 2 才切成两段
        if ($n === 1) {
            return $rem > self::SHORT_TAIL_MIN ? [$unit, $rem] : [];
        }

        // ≥ 10s
        if ($rem <= 0) {
            return array_fill(0, $n, $unit);
        }
        if ($rem < self::LONG_TAIL_MERGE_LT) {
            $plan = $n > 1 ? array_fill(0, $n - 1, $unit) : [];
            $plan[] = round($unit + $rem, 2);
            return $plan;
        }

        $plan = array_fill(0, $n, $unit);
        $plan[] = $rem;
        return $plan;
    }

    /**
     * 切割段数；0 = 不切割。
     */
    public static function calcSliceCount(float $duration): int
    {
        return count(self::buildSlicePlan($duration));
    }

    public static function skipSliceReason(float $duration): string
    {
        $duration = round($duration, 2);
        if ($duration <= self::SLICE_SECONDS) {
            return '视频时长不足5秒，将直接入库不切割、不扣费';
        }

        return '视频无需切割（如6~7秒尾段过短），将直接入库不扣费';
    }

    public function options(): array
    {
        $mode = $this->currentMode();
        $unitPrice = $this->unitPrice($mode);

        return [
            // 固定 5 秒片段规则；一个原视频=一个切割任务
            'slice_seconds' => self::SLICE_SECONDS,
            'short_tail_min' => self::SHORT_TAIL_MIN,
            'long_tail_merge_lt' => self::LONG_TAIL_MERGE_LT,
            'videos_per_task' => 1,
            // 后台当前通道（转码+切割共用，前端不可更改）
            'process_mode' => $mode,
            'channel_text' => $mode === VideoSlice::MODE_OSS ? '服务器切割(阿里云OSS/MPS)' : '本地切割(FFmpeg)',
            'unit_price' => $unitPrice,
            'unit' => '算力/秒',
            'slice_rule' => '一个原视频一个切割任务：≤5秒不切；5~10秒仅尾数>2秒才切成5+尾（如7.2→5+2.2，6/7不切）；≥10秒按5秒切，末段<3秒并入上一段（如11→5+6，22→5×3+7，24→5×4+4）。不切割不扣费；任一段失败则该任务全额退款',
            // ffmpeg=0/1/2 三种上传处理模式说明
            'ffmpeg_modes' => [
                [
                    'value' => 0,
                    'label' => '原样入库',
                    'description' => '不转码、不切割，原始完整视频直接进入素材库，不扣费',
                    'transcode' => false,
                    'slice' => false,
                    'billing' => false,
                ],
                [
                    'value' => 1,
                    'label' => '仅转码',
                    'description' => '按后台通道免费转码；传了 persona_id 则转码后入库，未传则只转码，由前端调素材新增；不切割、不扣费',
                    'transcode' => true,
                    'slice' => false,
                    'billing' => false,
                ],
                [
                    'value' => 2,
                    'label' => '转码并切割',
                    'description' => '上传后自动免费转码；满足切割规则则按秒计费切割；不满足（如≤5秒、6~7秒）则原片入库且不扣切割费',
                    'transcode' => true,
                    'slice' => true,
                    'billing' => true,
                ],
            ],
        ];
    }

    /**
     * 当前后台媒体处理通道（转码+切割共用同一单选值）。
     */
    private function currentMode(): string
    {
        return OssMediaProcessService::mediaProcessMode() === OssMediaProcessService::MEDIA_PROCESS_OSS
            ? VideoSlice::MODE_OSS
            : VideoSlice::MODE_LOCAL;
    }

    public function quote(int $userId, array $params): array
    {
        $this->assertPersona($userId, (int)$params['persona_id']);
        // 通道由后台单选决定，前端不可指定；一个原视频=一个切割任务
        $mode = $this->currentMode();
        $file = $this->loadFile($userId, (int)$params['file_id']);
        $info = $this->probeFile($file);
        $duration = round((float)$info['duration'], 2);
        if ($duration <= 0) {
            throw new \RuntimeException('视频时长无效：' . (string)$file->name);
        }
        // 回填上传未写入的时长，供转码中 statistics 预估子素材数
        if ((float)($file->duration ?? 0) <= 0 || abs((float)$file->duration - $duration) > 0.05) {
            File::where('id', (int)$file->id)->update([
                'duration' => $duration,
                'update_time' => time(),
            ]);
        }

        $slicePlan = self::buildSlicePlan($duration);
        $sliceCount = count($slicePlan);
        $canSlice = $sliceCount > 0;
        // 不切割：报价为 0，不扣费；由 startFromFile 直接原片入库
        $billingSeconds = $canSlice ? (int)ceil($duration) : 0;
        $video = [
            'file_id' => (int)$file->id,
            'original_name' => (string)$file->name,
            'file_path' => (string)$file->uri,
            'duration' => $duration,
            'width' => (int)($info['video']['width'] ?? 0),
            'height' => (int)($info['video']['height'] ?? 0),
            'slice_count' => $sliceCount,
            'slice_plan' => $slicePlan,
        ];

        $unitPrice = $this->unitPrice($mode);
        $tokensCost = round($billingSeconds * $unitPrice, 2);
        // 企业空间成员看企业钱包，勿用个人 tokens
        $balance = \app\common\service\TeamBillingService::spendableTokens($userId);

        return [
            'process_mode' => $mode,
            'channel_text' => $mode === VideoSlice::MODE_OSS ? '服务器切割(阿里云OSS/MPS)' : '本地切割(FFmpeg)',
            'file_id' => (int)$file->id,
            'video_count' => 1,
            'total_duration' => $duration,
            'billing_seconds' => $billingSeconds,
            'total_slice_count' => $sliceCount,
            'slice_plan' => $slicePlan,
            'can_slice' => $canSlice,
            'skip_slice' => !$canSlice,
            'skip_reason' => $canSlice ? '' : self::skipSliceReason($duration),
            'unit_price' => $unitPrice,
            'tokens_cost' => $tokensCost,
            'tokens_balance' => $balance,
            'enough_tokens' => $balance >= $tokensCost,
            'video' => $video,
        ];
    }

    public function confirm(int $userId, array $params): array
    {
        return $this->startFromFile(
            $userId,
            (int)$params['file_id'],
            (int)$params['persona_id'],
            (string)$params['scene']
        );
    }

    /**
     * 按单个已转码文件发起切割：预扣 → 建 VideoSlice 任务 → 投递 video_slice 队列。
     * 不满足切割规则（≤5秒、6~7秒等）：直接原片入库，不建任务、不入切割队列、不扣费。
     * 主流程由上传 ffmpeg=2 + 转码成功自动触发；confirm 仅作手动重试入口。
     *
     * @param array{thumbnail_url?:string,width?:int,height?:int} $extra
     */
    public function startFromFile(int $userId, int $fileId, int $personaId, string $scene, array $extra = []): array
    {
        $quote = $this->quote($userId, [
            'persona_id' => $personaId,
            'scene' => $scene,
            'file_id' => $fileId,
        ]);

        // 无需切割：不进切割队列、不扣费，直接入库原视频
        if (empty($quote['can_slice'])) {
            return $this->publishShortOriginalAndSkipQueue(
                $userId,
                $fileId,
                $personaId,
                $scene,
                $quote,
                $extra
            );
        }

        $mode = (string)$quote['process_mode'];
        $video = (array)$quote['video'];

        $batchNo = $this->makeBatchNo();
        $slice = null;
        Db::startTrans();
        try {
            $user = User::where('id', $userId)->lock(true)->findOrEmpty();
            if ($user->isEmpty()) {
                throw new \RuntimeException('用户不存在');
            }
            // 企业空间成员看企业钱包；实际扣费走 userTokensChange → TeamBillingService
            $spendable = \app\common\service\TeamBillingService::spendableTokens($userId);
            if ($spendable < (float)$quote['tokens_cost']) {
                $msg = \app\common\service\TeamBillingService::resolveSpender($userId) !== null
                    ? '当前团队算力不足，请联系团队主' : '算力不足，请充值后重试';
                throw new \RuntimeException($msg, 4059);
            }

            // 同一原视频不允许重复投递进行中的切割任务；不同视频可并发
            $existing = VideoSlice::where('user_id', $userId)
                ->where('original_video_id', $fileId)
                ->whereIn('status', [VideoSlice::STATUS_PENDING, VideoSlice::STATUS_PROCESSING])
                ->whereNotNull('batch_no')
                ->where('batch_no', '<>', '')
                ->lock(true)
                ->findOrEmpty();
            if (!$existing->isEmpty()) {
                // 已在切割中：直接返回现有任务，保证上传自动触发幂等
                Db::rollback();
                return $this->formatBatch($existing);
            }

            $now = time();
            $slice = VideoSlice::create([
                'batch_no' => $batchNo,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'scene' => $scene,
                'process_mode' => $mode,
                'status' => VideoSlice::STATUS_PENDING,
                'billing_status' => VideoSlice::BILL_HELD,
                'original_video_id' => $fileId,
                'original_name' => (string)$video['original_name'],
                'original_duration' => (float)$video['duration'],
                'original_path' => (string)$video['file_path'],
                'slice_count' => (int)$video['slice_count'],
                'success_slice_count' => 0,
                'unit_price' => (float)$quote['unit_price'],
                'cost_unit' => 0,
                'tokens_cost' => (float)$quote['tokens_cost'],
                'tokens_log_id' => 0,
                'error_message' => '',
                'thumbnail_url' => '',
                'width' => (int)$video['width'],
                'height' => (int)$video['height'],
                'batch_id' => 0,
                'update_time' => $now,
                'finish_time' => 0,
            ]);

            User::userTokensChange($userId, (float)$quote['tokens_cost']);
            $log = AccountLogLogic::add(
                $userId,
                AccountLogEnum::TOKENS_DEC_MATERIAL_SLICE,
                AccountLogEnum::DEC,
                (float)$quote['tokens_cost'],
                1,
                $batchNo,
                '素材分割预扣算力',
                [
                    '扣费项目' => '素材分割预扣算力',
                    '切割通道' => $mode,
                    '原视频ID' => $fileId,
                    '原视频秒数' => (float)$quote['total_duration'],
                    '计费秒数' => (float)$quote['billing_seconds'],
                    '算力单价' => (float)$quote['unit_price'],
                ]
            );
            if (!$log) {
                throw new \RuntimeException('算力流水记录失败');
            }
            $slice->save(['tokens_log_id' => (int)$log->id]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        try {
            $jobId = Queue::push(
                \app\common\Jobs\MaterialSliceBatchJob::class,
                ['batch_no' => $batchNo],
                (string)config('video_slice.queue_name', 'video_slice')
            );
            if ($jobId === false) {
                throw new \RuntimeException('素材分割任务投递失败');
            }
        } catch (\Throwable $e) {
            $this->failBatch($batchNo, '任务投递失败：' . $e->getMessage());
            throw $e;
        }

        return $this->detail($userId, $batchNo);
    }

    /**
     * 无需切割：原片直接入库为可用素材，不建切割任务、不投递队列、不扣费。
     *
     * @param array{thumbnail_url?:string,width?:int,height?:int} $extra
     */
    private function publishShortOriginalAndSkipQueue(
        int $userId,
        int $fileId,
        int $personaId,
        string $scene,
        array $quote,
        array $extra = []
    ): array {
        $video = (array)($quote['video'] ?? []);
        $duration = (float)($video['duration'] ?? $quote['total_duration'] ?? 0);
        $filePath = (string)($video['file_path'] ?? '');
        $name = pathinfo((string)($video['original_name'] ?? ''), PATHINFO_FILENAME);
        $thumbnailUrl = trim((string)($extra['thumbnail_url'] ?? ''));
        $width = (int)($extra['width'] ?? $video['width'] ?? 0);
        $height = (int)($extra['height'] ?? $video['height'] ?? 0);
        $skipReason = (string)($quote['skip_reason'] ?? self::skipSliceReason($duration));

        $materialId = MaterialService::publishUploadedVideo(
            $fileId,
            $personaId,
            $userId,
            [
                'file_url' => $filePath,
                'name' => $name !== '' ? $name : '视频',
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'thumbnail_url' => $thumbnailUrl,
                // 转码阶段已截到封面则不再强制重截；否则兜底再截
                'ensure_thumbnail' => $thumbnailUrl === '',
            ]
        );

        $hasThumb = $thumbnailUrl !== '';
        if (!$hasThumb && $materialId > 0) {
            $raw = (string)(Material::where('id', $materialId)->value('thumbnail_url') ?? '');
            $hasThumb = trim($raw) !== '';
        }

        Log::channel('video_slice')->info(
            "[素材批量分割] 无需切割，跳过切割队列直接入库（不扣费） file_id={$fileId}"
            . " material_id={$materialId} duration={$duration} persona_id={$personaId}"
            . " reason={$skipReason}"
            . " thumb=" . ($hasThumb ? 'yes' : 'no')
        );

        return [
            'batch_no' => '',
            'scene' => $scene,
            'persona_id' => $personaId,
            'file_id' => $fileId,
            'original_video_id' => $fileId,
            'skipped_slice' => true,
            'skip_reason' => $skipReason,
            'status' => 'success',
            'status_text' => '已直接入库',
            'phase' => 'skipped_slice',
            'pending_slice' => false,
            'billing_status' => 'none',
            'process_mode' => (string)($quote['process_mode'] ?? ''),
            'channel_text' => (string)($quote['channel_text'] ?? ''),
            'total_duration' => $duration,
            'success_slice_count' => 0,
            'total_slice_count' => 0,
            'estimated_slice_count' => 0,
            'progress_text' => '已入库',
            'progress_percent' => 100.0,
            'unit_price' => (float)($quote['unit_price'] ?? 0),
            'tokens_cost' => 0.0,
            'error_message' => '',
            'show_progress' => false,
            'material_id' => $materialId,
            'has_thumbnail' => $hasThumb,
            'file_name' => (string)($video['original_name'] ?? ''),
        ];
    }

    /**
     * 素材库进度：返回该人设+场景下全部进行中的单视频切割任务。
     * 新前端请优先用 statistics()；本方法保留兼容。
     */
    public function activeBatch(int $userId, int $personaId, string $scene): array
    {
        return $this->statistics($userId, $personaId, $scene, []);
    }

    /**
     * 切割状态与进度统计（主入口，供 /videoSlice/statistics 使用）。
     *
     * - 不传 file_ids：返回进行中任务（转码中待切割 + 切割 pending/processing），含 progress_text / progress_percent
     * - 传 file_ids / file_id：按文件返回各自最新进度（含转码中预估片数、已完成/失败批次）
     */
    public function statistics(int $userId, int $personaId, string $scene, array $fileIds = []): array
    {
        if ($personaId <= 0) {
            throw new \RuntimeException('参数错误');
        }
        if (!in_array($scene, ['ai_creation', 'persona'], true)) {
            $scene = 'persona';
        }

        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if (empty($fileIds)) {
            $slices = VideoSlice::where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('scene', $scene)
                ->whereIn('status', [VideoSlice::STATUS_PENDING, VideoSlice::STATUS_PROCESSING])
                ->whereNotNull('batch_no')
                ->where('batch_no', '<>', '')
                ->order(['id' => 'desc'])
                ->select();

            $lists = [];
            $batchFileIds = [];
            foreach ($slices as $slice) {
                $item = $this->formatBatch($slice);
                $lists[] = $item;
                if ((int)($item['file_id'] ?? 0) > 0) {
                    $batchFileIds[] = (int)$item['file_id'];
                }
            }

            // 转码中/转码完成尚未建批次：先展示待切割与预估子素材数（仅读库 duration，不探测）
            foreach ($this->listAwaitingSliceFiles($userId, $personaId, $scene, $batchFileIds) as $awaiting) {
                array_unshift($lists, $awaiting);
            }

            return $this->wrapStatisticsLists($lists);
        }

        $lists = [];
        $mode = $this->currentMode();
        $unitPrice = $this->unitPrice($mode);
        foreach ($fileIds as $fileId) {
            $slice = VideoSlice::where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('scene', $scene)
                ->where('original_video_id', $fileId)
                ->whereNotNull('batch_no')
                ->where('batch_no', '<>', '')
                ->order(['id' => 'desc'])
                ->findOrEmpty();
            if (!$slice->isEmpty()) {
                $item = $this->formatBatch($slice);
                $item['original_video_id'] = $fileId;
                $lists[] = $item;
                continue;
            }

            $awaiting = $this->formatAwaitingSliceFileById($userId, $personaId, $scene, $fileId, $mode, $unitPrice);
            $lists[] = $awaiting ?? $this->emptyFileProgress($fileId, $personaId, $scene);
        }

        return $this->wrapStatisticsLists($lists);
    }

    private function wrapStatisticsLists(array $lists): array
    {
        $summary = $this->summarizeProgressLists($lists);
        $latest = $lists[0] ?? [];

        return [
            'count' => count($lists),
            'total' => count($lists),
            'lists' => $lists,
            // 兼容旧前端：取最新一条；无任务时为空对象
            'latest' => $latest,
            'summary' => $summary,
            // 顶部进度条常用：直接透出最新任务进度字段
            'batch_no' => (string)($latest['batch_no'] ?? ''),
            'file_id' => (int)($latest['file_id'] ?? 0),
            'status' => (string)($latest['status'] ?? 'none'),
            'status_text' => (string)($latest['status_text'] ?? '无任务'),
            'phase' => (string)($latest['phase'] ?? 'none'),
            'pending_slice' => (bool)($latest['pending_slice'] ?? false),
            'progress_text' => (string)($latest['progress_text'] ?? '0/0'),
            'progress_percent' => (float)($latest['progress_percent'] ?? 0),
            'success_slice_count' => (int)($latest['success_slice_count'] ?? 0),
            'total_slice_count' => (int)($latest['total_slice_count'] ?? 0),
            'estimated_slice_count' => (int)($latest['estimated_slice_count'] ?? ($latest['total_slice_count'] ?? 0)),
            'show_progress' => (bool)($latest['show_progress'] ?? false),
        ];
    }

    private function emptyFileProgress(int $fileId, int $personaId, string $scene): array
    {
        return [
            'batch_no' => '',
            'scene' => $scene,
            'persona_id' => $personaId,
            'file_id' => $fileId,
            'original_video_id' => $fileId,
            'process_mode' => '',
            'channel_text' => '',
            'status' => 'none',
            'status_text' => '无任务',
            'phase' => 'none',
            'pending_slice' => false,
            'billing_status' => 'none',
            'video_count' => 0,
            'total_duration' => 0.0,
            'success_slice_count' => 0,
            'total_slice_count' => 0,
            'estimated_slice_count' => 0,
            'progress_text' => '0/0',
            'progress_percent' => 0.0,
            'unit_price' => 0.0,
            'tokens_cost' => 0.0,
            'error_message' => '',
            'show_progress' => false,
        ];
    }

    private function summarizeProgressLists(array $lists): array
    {
        $transcoding = 0;
        $pending = 0;
        $processing = 0;
        $success = 0;
        $failed = 0;
        $totalSlice = 0;
        $successSlice = 0;
        $pendingSliceFiles = 0;

        foreach ($lists as $item) {
            $status = (string)($item['status'] ?? 'none');
            match ($status) {
                'transcoding' => $transcoding++,
                'pending' => $pending++,
                'processing' => $processing++,
                'success' => $success++,
                'failed' => $failed++,
                default => null,
            };
            if (!empty($item['pending_slice'])) {
                $pendingSliceFiles++;
            }
            $totalSlice += (int)($item['total_slice_count'] ?? 0);
            $successSlice += (int)($item['success_slice_count'] ?? 0);
        }

        $total = $transcoding + $pending + $processing + $success + $failed;

        return [
            'total_count' => $total,
            'transcoding_count' => $transcoding,
            'pending_count' => $pending,
            'pending_slice_count' => $pendingSliceFiles,
            'slicing_count' => $processing,
            'finished_count' => $success + $failed,
            'success_count' => $success,
            'failed_count' => $failed,
            'total_slice_count' => $totalSlice,
            'success_slice_count' => $successSlice,
            'item_count' => $total,
            'sliced_count' => $success,
        ];
    }

    /**
     * ffmpeg=2 且尚未建切割批次：转码中 / 转码完成待入队。
     *
     * @param int[] $excludeFileIds
     * @return array<int, array>
     */
    private function listAwaitingSliceFiles(int $userId, int $personaId, string $scene, array $excludeFileIds = []): array
    {
        $query = File::where('source_id', $userId)
            ->where('persona_id', $personaId)
            ->where('scene', $scene)
            ->where('slice_mode', 2)
            ->whereIn('transcode_status', [1, 2, 3])
            ->order(['id' => 'desc']);
        if (!empty($excludeFileIds)) {
            $query->whereNotIn('id', $excludeFileIds);
        }

        $files = $query->select();
        if ($files->isEmpty()) {
            return [];
        }

        $candidateIds = [];
        foreach ($files as $file) {
            $candidateIds[] = (int)$file->id;
        }
        // 已有任意切割任务（含失败）则交由任务进度展示，避免重复与按文件 count N+1
        $batchedFileIds = VideoSlice::whereIn('original_video_id', $candidateIds)
            ->whereNotNull('batch_no')
            ->where('batch_no', '<>', '')
            ->column('original_video_id');
        $batchedSet = array_fill_keys(array_map('intval', $batchedFileIds), true);

        $mode = $this->currentMode();
        $unitPrice = $this->unitPrice($mode);
        $lists = [];
        foreach ($files as $file) {
            $fileId = (int)$file->id;
            if (isset($batchedSet[$fileId])) {
                continue;
            }
            // 无需切割（≤5秒、6~7秒等）：不进入切割进度（转码后按原片入库）
            $knownDuration = round((float)($file->duration ?? 0), 2);
            if ($knownDuration > 0 && self::calcSliceCount($knownDuration) <= 0) {
                continue;
            }
            $lists[] = $this->formatAwaitingSliceFile($file, $personaId, $scene, $mode, $unitPrice);
        }
        return $lists;
    }

    private function formatAwaitingSliceFileById(
        int $userId,
        int $personaId,
        string $scene,
        int $fileId,
        ?string $mode = null,
        ?float $unitPrice = null
    ): ?array {
        if ($fileId <= 0) {
            return null;
        }
        $file = File::where('id', $fileId)
            ->where('source_id', $userId)
            ->where('persona_id', $personaId)
            ->where('scene', $scene)
            ->where('slice_mode', 2)
            ->whereIn('transcode_status', [1, 2, 3])
            ->findOrEmpty();
        if ($file->isEmpty()) {
            return null;
        }
        if (VideoSlice::where('original_video_id', $fileId)
            ->whereNotNull('batch_no')
            ->where('batch_no', '<>', '')
            ->count() > 0) {
            return null;
        }
        $knownDuration = round((float)($file->duration ?? 0), 2);
        if ($knownDuration > 0 && self::calcSliceCount($knownDuration) <= 0) {
            return null;
        }
        return $this->formatAwaitingSliceFile($file, $personaId, $scene, $mode, $unitPrice);
    }

    private function formatAwaitingSliceFile(
        File $file,
        int $personaId,
        string $scene,
        ?string $mode = null,
        ?float $unitPrice = null
    ): array {
        $fileId = (int)$file->id;
        $transcodeStatus = (int)($file->transcode_status ?? 0);
        // 进度轮询只读库字段，禁止同步 ffprobe（否则 OSS 探测可达数十秒）
        $meta = $this->estimateSliceMetaFromDb($file);
        $total = (int)$meta['slice_count'];
        $duration = (float)$meta['duration'];
        $mode = $mode ?? $this->currentMode();
        $unitPrice = $unitPrice ?? $this->unitPrice($mode);
        $isTranscoding = in_array($transcodeStatus, [1, 2], true);
        $status = $isTranscoding ? 'transcoding' : 'pending';
        $phase = $isTranscoding ? 'transcoding' : 'awaiting_slice';

        return [
            'batch_no' => '',
            'scene' => $scene,
            'persona_id' => $personaId,
            'file_id' => $fileId,
            'original_video_id' => $fileId,
            'process_mode' => $mode,
            'channel_text' => $mode === VideoSlice::MODE_OSS ? '服务器切割' : '本地切割',
            'status' => $status,
            'status_text' => $this->progressStatusText($status),
            'phase' => $phase,
            'pending_slice' => true,
            'billing_status' => 'none',
            'video_count' => 1,
            'total_duration' => $duration,
            'success_slice_count' => 0,
            'total_slice_count' => $total,
            'estimated_slice_count' => $total,
            'progress_text' => '0/' . $total,
            'progress_percent' => 0.0,
            'unit_price' => $unitPrice,
            'tokens_cost' => 0.0,
            'error_message' => '',
            'show_progress' => true,
            'transcode_status' => $transcodeStatus,
            'file_name' => (string)($file->name ?? ''),
        ];
    }

    /**
     * 进度统计用：仅用上传/回填写入的 duration 预估片数，不做源文件探测。
     *
     * @return array{duration: float, slice_count: int}
     */
    private function estimateSliceMetaFromDb(File $file): array
    {
        $duration = round((float)($file->duration ?? 0), 2);

        return [
            'duration' => max(0, $duration),
            // 不满足切割规则时预估片数为 0（不切割、不扣费）
            'slice_count' => self::calcSliceCount($duration),
        ];
    }

    private function progressStatusText(string $status): string
    {
        return match ($status) {
            'transcoding' => '转码中(待切割)',
            'pending' => '待切割',
            'processing' => '切割中',
            'success' => '已完成',
            'failed' => '失败',
            default => '无任务',
        };
    }

    public function detail(int $userId, string $batchNo): array
    {
        $slice = VideoSlice::where('batch_no', $batchNo)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($slice->isEmpty()) {
            throw new \RuntimeException('素材分割批次不存在');
        }
        return $this->formatBatch($slice);
    }

    public function process(string $batchNo): void
    {
        $slice = VideoSlice::where('batch_no', $batchNo)->findOrEmpty();
        if ($slice->isEmpty()) {
            return;
        }
        $started = VideoSlice::where('id', (int)$slice->id)
            ->where('status', VideoSlice::STATUS_PENDING)
            ->update(['status' => VideoSlice::STATUS_PROCESSING, 'update_time' => time()]);
        if ($started !== 1) {
            return;
        }
        $slice = $slice->refresh();
        try {
            // 兜底：队列中任务若不满足切割规则，不切割，退款并直接原片入库
            if (self::calcSliceCount((float)$slice->original_duration) <= 0) {
                $this->completeShortQueuedBatch($slice);
                return;
            }
            $this->processVideo($slice);
            $this->publishBatch($slice->refresh());
        } catch (\Throwable $e) {
            Log::channel('video_slice')->error("[素材批量分割] 失败 batch_no={$batchNo} error=" . $e->getMessage());
            $this->failBatch($batchNo, $e->getMessage());
            throw $e;
        }
    }

    /**
     * 已入队但不满足切割规则：退还预扣，原片入库为可用素材，任务标记成功（未切割）。
     */
    private function completeShortQueuedBatch(VideoSlice $slice): void
    {
        $now = time();
        $duration = (float)$slice->original_duration;
        $skipReason = self::skipSliceReason($duration);
        MaterialService::publishUploadedVideo(
            (int)$slice->original_video_id,
            (int)$slice->persona_id,
            (int)$slice->user_id,
            [
                'file_url' => (string)$slice->original_path,
                'name' => pathinfo((string)$slice->original_name, PATHINFO_FILENAME),
                'duration' => $duration,
                'width' => (int)$slice->width,
                'height' => (int)$slice->height,
                'ensure_thumbnail' => true,
            ]
        );
        $this->refund($slice);
        $slice->save([
            'status' => VideoSlice::STATUS_SUCCESS,
            'billing_status' => VideoSlice::BILL_REFUNDED,
            'slice_count' => 0,
            'success_slice_count' => 0,
            'error_message' => mb_substr($skipReason . '（已跳过切割并直接入库）', 0, 500),
            'finish_time' => $now,
            'update_time' => $now,
        ]);
        Log::channel('video_slice')->info(
            '[素材批量分割] 队列任务无需切割，已退款并直接入库 batch_no=' . (string)$slice->batch_no
            . ' duration=' . $duration
            . ' reason=' . $skipReason
        );
    }

    public function failBatch(string $batchNo, string $reason): void
    {
        $slice = VideoSlice::where('batch_no', $batchNo)->findOrEmpty();
        if ($slice->isEmpty()) {
            return;
        }
        $outputPaths = [];
        $claimed = Db::transaction(function () use ($slice, $reason, &$outputPaths) {
            $locked = VideoSlice::where('id', (int)$slice->id)->lock(true)->findOrEmpty();
            if ($locked->isEmpty() || (int)$locked->status === VideoSlice::STATUS_SUCCESS) {
                return false;
            }
            $now = time();
            $batchId = (int)$locked->id;

            // 已写出的子素材：软删并清理切片文件；失败只保留「原视频」一条记录
            $outputPaths = VideoSliceItem::where('slice_id', $batchId)->column('file_path');
            Material::where('slice_batch_id', $batchId)
                ->where('source_type', 'slice')
                ->update([
                    'use_status' => Material::USE_STATUS_DELETED,
                    'slice_status' => Material::SLICE_STATUS_FAILED,
                    'delete_time' => $now,
                    'update_time' => $now,
                ]);

            $this->upsertFailedOriginalMaterial($locked, $reason, $now);

            $locked->save([
                'status' => VideoSlice::STATUS_FAILED,
                'error_message' => mb_substr($reason, 0, 500),
                'finish_time' => $now,
                'update_time' => $now,
            ]);
            return true;
        });
        if (!$claimed) {
            return;
        }

        foreach ($outputPaths as $path) {
            $this->deleteOutput((string)$path);
        }
        $this->refund($slice->refresh());
    }

    /**
     * 切割失败：入库/更新为「原视频」素材（非子片段），use_status=2、slice_status=4。
     */
    private function upsertFailedOriginalMaterial(VideoSlice $slice, string $reason, int $now): void
    {
        $batchId = (int)$slice->id;
        $name = pathinfo((string)$slice->original_name, PATHINFO_FILENAME);
        if ($name === '') {
            $name = '视频';
        }

        $thumb = trim((string)($slice->thumbnail_url ?? ''));
        if ($thumb === '') {
            $thumb = MaterialService::makeVideoThumbnail((string)$slice->original_path, 0.5);
            if ($thumb !== '') {
                $slice->save(['thumbnail_url' => $thumb, 'update_time' => $now]);
            }
        }

        $payload = [
            'persona_id' => (int)$slice->persona_id,
            'user_id' => (int)$slice->user_id,
            'material_name' => $name,
            'material_type' => Material::MATERIAL_TYPE_VIDEO,
            'file_url' => (string)$slice->original_path,
            'thumbnail_url' => $thumb,
            'duration' => (int)round((float)$slice->original_duration),
            'width' => (string)$slice->width,
            'height' => (string)$slice->height,
            'use_status' => Material::USE_STATUS_DISABLED,
            'publish_mode' => 1,
            'source_type' => 'original',
            'source_video_id' => (int)$slice->original_video_id,
            'slice_status' => Material::SLICE_STATUS_FAILED,
            'slice_batch_id' => $batchId,
            'update_time' => $now,
        ];

        $existing = Material::where('slice_batch_id', $batchId)
            ->where('source_type', 'original')
            ->where('slice_status', Material::SLICE_STATUS_FAILED)
            ->findOrEmpty();
        if (!$existing->isEmpty()) {
            $existing->save($payload);
        } else {
            $payload['create_time'] = $now;
            Material::create($payload);
        }

        Log::channel('video_slice')->warning(
            '[素材批量分割] 切割失败已入库原视频 material batch_no=' . (string)$slice->batch_no
            . ' file_id=' . (int)$slice->original_video_id
            . ' reason=' . mb_substr($reason, 0, 200)
        );
    }

    private function processVideo(VideoSlice $slice): void
    {
        // 先截一张原视频封面作兜底，再对各片段单独截帧
        if ((string)$slice->thumbnail_url === '') {
            $fallbackThumb = $this->makeThumbnail((string)$slice->original_path, 0.5);
            if ($fallbackThumb !== '') {
                $slice->save(['thumbnail_url' => $fallbackThumb, 'update_time' => time()]);
            }
        }

        $plan = self::buildSlicePlan((float)$slice->original_duration);
        if ($plan === []) {
            throw new \RuntimeException('切割计划为空，无法切割');
        }
        if (count($plan) !== (int)$slice->slice_count) {
            $slice->save(['slice_count' => count($plan), 'update_time' => time()]);
        }

        $isLocal = (string)$slice->process_mode !== VideoSlice::MODE_OSS;
        try {
            $start = 0.0;
            foreach ($plan as $index => $segDuration) {
                $sequence = $index + 1;
                $duration = max(0.01, round((float)$segDuration, 2));
                $output = $isLocal
                    ? $this->cutLocal($slice, $sequence, $start, $duration)
                    : $this->cutOss($slice, $sequence, $start, $duration);
                $name = $this->sliceName((string)$slice->original_name, $sequence);
                // 优先用当前片段视频截帧；失败时回退原视频封面，避免列表无封面
                $thumbnailUrl = $this->makeThumbnail($output, 0.5);
                if ($thumbnailUrl === '') {
                    $thumbnailUrl = (string)$slice->thumbnail_url;
                }
                try {
                    $material = Material::create([
                        'persona_id' => (int)$slice->persona_id,
                        'user_id' => (int)$slice->user_id,
                        'material_name' => $name,
                        'material_type' => Material::MATERIAL_TYPE_VIDEO,
                        'file_url' => $output,
                        'thumbnail_url' => $thumbnailUrl,
                        'duration' => (int)round($duration),
                        'width' => (string)$slice->width,
                        'height' => (string)$slice->height,
                        'use_status' => Material::USE_STATUS_DISABLED,
                        'publish_mode' => 1,
                        'source_type' => 'slice',
                        'source_video_id' => (int)$slice->original_video_id,
                        // 单片切完即记成功，便于统计 success_slice_count 逐步增长；
                        // 仍保持 use_status=禁用，整批 publishBatch 后再启用
                        'slice_status' => Material::SLICE_STATUS_SUCCESS,
                        'slice_batch_id' => (int)$slice->id,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                    VideoSliceItem::create([
                        'slice_id' => (int)$slice->id,
                        'user_id' => (int)$slice->user_id,
                        'sequence' => $sequence,
                        'name' => $name,
                        'time_start' => round($start, 2),
                        'time_end' => round($start + $duration, 2),
                        'duration' => round($duration, 2),
                        'file_path' => $output,
                        'file_size' => $this->outputSize($output),
                        'material_id' => (int)$material->id,
                        'width' => (string)$slice->width,
                        'height' => (string)$slice->height,
                    ]);
                } catch (\Throwable $e) {
                    $this->deleteOutput($output);
                    throw $e;
                }
                VideoSlice::where('id', (int)$slice->id)
                    ->inc('success_slice_count')
                    ->update(['update_time' => time()]);
                $start = round($start + $duration, 2);
            }
        } finally {
            if ($isLocal) {
                $this->cleanupBatchTempDir((int)$slice->id);
            }
        }
    }

    private function publishBatch(VideoSlice $slice): void
    {
        Db::transaction(function () use ($slice) {
            $locked = VideoSlice::where('id', (int)$slice->id)->lock(true)->findOrEmpty();
            if ($locked->isEmpty() || (int)$locked->status !== VideoSlice::STATUS_PROCESSING) {
                throw new \RuntimeException('素材分割批次状态已变化');
            }
            if ((int)$locked->success_slice_count !== (int)$locked->slice_count) {
                throw new \RuntimeException('批次片段数量不完整');
            }
            $now = time();
            Material::where('slice_batch_id', (int)$locked->id)->update([
                'use_status' => Material::USE_STATUS_ENABLED,
                'slice_status' => Material::SLICE_STATUS_SUCCESS,
                'update_time' => $now,
            ]);
            $locked->save([
                'status' => VideoSlice::STATUS_SUCCESS,
                'billing_status' => VideoSlice::BILL_CONFIRMED,
                'finish_time' => $now,
                'update_time' => $now,
            ]);
        });
    }

    private function refund(VideoSlice $slice): void
    {
        Db::transaction(function () use ($slice) {
            $locked = VideoSlice::where('id', (int)$slice->id)->lock(true)->findOrEmpty();
            if ($locked->isEmpty() || (int)$locked->billing_status !== VideoSlice::BILL_HELD) {
                return;
            }
            $already = UserTokensLog::where('user_id', (int)$locked->user_id)
                ->where('task_id', (string)$locked->batch_no)
                ->where('change_type', AccountLogEnum::TOKENS_DEC_MATERIAL_SLICE)
                ->where('action', AccountLogEnum::INC)
                ->count();
            if ($already > 0) {
                $locked->save(['billing_status' => VideoSlice::BILL_REFUNDED, 'update_time' => time()]);
                return;
            }
            AccountLogLogic::recordUserTokensLog(
                false,
                (int)$locked->user_id,
                AccountLogEnum::TOKENS_DEC_MATERIAL_SLICE,
                (float)$locked->tokens_cost,
                (string)$locked->batch_no,
                ['退费原因' => (string)$locked->error_message, '切割通道' => (string)$locked->process_mode]
            );
            $locked->save(['billing_status' => VideoSlice::BILL_REFUNDED, 'update_time' => time()]);
        });
    }

    private function cutLocal(VideoSlice $slice, int $sequence, float $start, float $duration): string
    {
        $tempDir = $this->batchTempDir((int)$slice->id, true);
        $source = $this->ensureLocalCutSource((string)$slice->original_path, $tempDir);
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . (int)$slice->original_video_id . '_' . $sequence . '.mp4';
        $ffmpeg = $this->findBinary(['ffmpeg6', 'ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'], 'ffmpeg');
        $startText = number_format($start, 3, '.', '');
        $durationText = number_format($duration, 3, '.', '');
        $attempts = [
            // 对齐旧 VideoSliceService：本地输入 + 无 -map
            'reencode' => sprintf(
                '%s -hide_banner -loglevel error -y -i %s -ss %s -t %s -c:v libx264 -preset veryfast -crf 28 -c:a aac -b:a 128k -pix_fmt yuv420p -movflags +faststart -avoid_negative_ts make_zero %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($source),
                escapeshellarg($startText),
                escapeshellarg($durationText),
                escapeshellarg($tempPath)
            ),
            // 去掉音频，规避坏音轨/音频编码器崩溃
            'video_only' => sprintf(
                '%s -hide_banner -loglevel error -y -i %s -ss %s -t %s -an -c:v libx264 -preset veryfast -crf 28 -pix_fmt yuv420p -movflags +faststart -avoid_negative_ts make_zero %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($source),
                escapeshellarg($startText),
                escapeshellarg($durationText),
                escapeshellarg($tempPath)
            ),
            // 前置 seek，降低部分 ffmpeg 构建在精确定位重编码时的崩溃概率
            'seek_before' => sprintf(
                '%s -hide_banner -loglevel error -y -ss %s -i %s -t %s -an -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -movflags +faststart -avoid_negative_ts make_zero %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($startText),
                escapeshellarg($source),
                escapeshellarg($durationText),
                escapeshellarg($tempPath)
            ),
        ];

        $errors = [];
        $cutOk = false;
        foreach ($attempts as $name => $command) {
            @unlink($tempPath);
            $result = $this->runFfmpegCutCommand($command, $tempPath);
            if ($result['ok']) {
                $cutOk = true;
                if ($name !== 'reencode') {
                    Log::channel('video_slice')->warning(
                        "[素材批量分割] 本地切割降级成功 strategy={$name} batch_id={$slice->id} sequence={$sequence}"
                    );
                }
                break;
            }
            $errors[] = $name . ':' . $result['reason'];
            Log::channel('video_slice')->warning(
                "[素材批量分割] 本地切割尝试失败 strategy={$name} batch_id={$slice->id} sequence={$sequence} reason="
                . $result['reason']
            );
        }
        if (!$cutOk) {
            throw new \RuntimeException(
                '本地 FFmpeg 切割失败：start=' . $startText
                . ' duration=' . $durationText
                . ' source_local=1'
                . ' attempts=' . implode(' | ', $errors)
            );
        }

        try {
            $outputInfo = (new VideoInfoService())->extractVideoInfo($tempPath, 30);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            throw new \RuntimeException('本地切片校验失败：' . $e->getMessage());
        }
        if (!$outputInfo || empty($outputInfo['video']) || (float)($outputInfo['duration'] ?? 0) <= 0) {
            @unlink($tempPath);
            throw new \RuntimeException('本地 FFmpeg 切片不可播放');
        }

        $relativeDir = trim((string)config('video_slice.storage_path', 'uploads/slices/video'), '/')
            . '/' . date('Ymd') . '/batch_' . (int)$slice->id;
        $fileName = (int)$slice->original_video_id . '_' . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT) . '.mp4';
        $relativePath = $relativeDir . '/' . $fileName;
        $default = (string)ConfigService::get('storage', 'default', 'local');
        if ($default === 'local') {
            $dir = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                @unlink($tempPath);
                throw new \RuntimeException('无法创建切片存储目录');
            }
            if (!@rename($tempPath, $dir . DIRECTORY_SEPARATOR . $fileName)) {
                @unlink($tempPath);
                throw new \RuntimeException('切片文件入库失败');
            }
        } else {
            $storage = $this->storageDriver();
            $storage->setUploadFileByFileName($tempPath, $fileName);
            if (!$storage->upload($relativeDir)) {
                @unlink($tempPath);
                throw new \RuntimeException('切片文件上传失败：' . $storage->getError());
            }
            @unlink($tempPath);
        }
        return $relativePath;
    }

    /**
     * 确保切割输入是本地文件：远程 URL 先下载到批次临时目录并复用。
     */
    private function ensureLocalCutSource(string $uri, string $tempDir): string
    {
        $source = $this->sourceForFile($uri);
        if (!$this->isRemotePath($source)) {
            if (!is_file($source)) {
                throw new \RuntimeException('本地切割源文件不存在：' . $source);
            }
            return $source;
        }

        $extension = strtolower((string)pathinfo((string)parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($extension === '' || strlen($extension) > 8) {
            $extension = 'mp4';
        }
        $localPath = $tempDir . DIRECTORY_SEPARATOR . 'source_input.' . $extension;
        if (is_file($localPath) && filesize($localPath) > 100) {
            return $localPath;
        }

        Log::channel('video_slice')->info(
            '[素材批量分割] 切割前下载远端源文件 source=' . $source . ' local=' . $localPath
        );
        return $this->downloadRemoteCutSource($source, $localPath);
    }

    private function downloadRemoteCutSource(string $url, string $localPath): string
    {
        @unlink($localPath);
        $fp = fopen($localPath, 'w+');
        if (!$fp) {
            throw new \RuntimeException('无法创建远端源临时文件：' . $localPath);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'MaterialSlice/1.0',
        ]);
        $success = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = (string)curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode < 200 || $httpCode >= 300) {
            @unlink($localPath);
            throw new \RuntimeException("远端源文件下载失败：HTTP {$httpCode} {$error}");
        }
        if (!is_file($localPath) || filesize($localPath) < 100) {
            @unlink($localPath);
            throw new \RuntimeException('远端源文件下载后无效或过小');
        }
        return $localPath;
    }

    /**
     * @return array{ok:bool,reason:string}
     */
    private function runFfmpegCutCommand(string $command, string $tempPath): array
    {
        $output = [];
        $code = 0;
        exec($command, $output, $code);
        $exists = is_file($tempPath);
        $size = $exists ? (int)filesize($tempPath) : 0;
        if ($code === 0 && $exists && $size >= 100) {
            return ['ok' => true, 'reason' => ''];
        }

        $stderr = trim(implode("\n", array_slice($output, -8)));
        if ($code !== 0) {
            $reason = "exit={$code}";
        } elseif (!$exists) {
            $reason = '输出文件不存在';
        } else {
            $reason = "输出文件过小 size={$size}";
        }
        if ($stderr !== '') {
            $reason .= ' stderr=' . $stderr;
        }
        @unlink($tempPath);
        return ['ok' => false, 'reason' => $reason];
    }

    private function batchTempDir(int $sliceId, bool $create = false): string
    {
        $tempDir = rtrim((string)config('video_slice.temp_path'), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'batch_' . $sliceId;
        if ($create && !is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            throw new \RuntimeException('无法创建素材分割临时目录');
        }
        return $tempDir;
    }

    private function cleanupBatchTempDir(int $sliceId): void
    {
        $tempDir = $this->batchTempDir($sliceId);
        if ($sliceId <= 0 || !is_dir($tempDir)) {
            return;
        }
        try {
            $files = scandir($tempDir);
            if (!is_array($files)) {
                return;
            }
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $tempDir . DIRECTORY_SEPARATOR . $file;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            @rmdir($tempDir);
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning(
                '[素材批量分割] 清理临时目录失败 dir=' . $tempDir . ' error=' . $e->getMessage()
            );
        }
    }

    private function isRemotePath(string $path): bool
    {
        return preg_match('/^https?:\/\//i', $path) === 1;
    }

    private function cutOss(VideoSlice $slice, int $sequence, float $start, float $duration): string
    {
        $service = OssMediaProcessService::makeForSlice();
        $input = OssMediaProcessService::toObjectKey((string)$slice->original_path);
        $output = trim((string)config('video_slice.storage_path', 'uploads/slices/video'), '/')
            . '/' . date('Ymd') . '/batch_' . (int)$slice->id . '/' . (int)$slice->original_video_id
            . '_' . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT) . '.mp4';
        $jobId = $service->submit($input, $output, [
            'Seek' => OssMediaProcessService::formatSeek($start),
            'Duration' => OssMediaProcessService::formatDuration($duration),
        ]);
        $deadline = time() + 600;
        while (time() < $deadline) {
            $result = $service->query($jobId);
            if ($result['success']) {
                return (string)($result['output_object'] ?: $output);
            }
            if (in_array($result['state'], ['TranscodeFail', 'TranscodeCancelled'], true)) {
                throw new \RuntimeException('OSS 切割失败：' . ($result['message'] ?: $result['state']));
            }
            sleep(5);
        }
        throw new \RuntimeException("OSS 切割等待超时 job_id={$jobId}");
    }

    private function loadFile(int $userId, int $fileId): File
    {
        if ($fileId <= 0) {
            throw new \RuntimeException('请选择已上传的视频');
        }
        $file = File::where('id', $fileId)->where('source_id', $userId)->findOrEmpty();
        if ($file->isEmpty()) {
            throw new \RuntimeException('视频不存在或不属于当前用户');
        }
        if ((int)$file->slice_mode !== 2) {
            throw new \RuntimeException('该视频未按“转码并切割”上传：' . (string)$file->name);
        }
        $transcodeStatus = (int)($file->transcode_status ?? 0);
        if ($transcodeStatus === 4) {
            throw new \RuntimeException('视频转码失败，不能进入切割：' . (string)$file->name);
        }
        if ($transcodeStatus !== 3) {
            throw new \RuntimeException('视频转码尚未完成，请稍后再试：' . (string)$file->name);
        }
        return $file;
    }

    private function probeFile(File $file): array
    {
        $source = $this->sourceForFile((string)$file->uri);
        $info = (new VideoInfoService())->extractVideoInfo($source, 60);
        if (!$info || empty($info['video'])) {
            throw new \RuntimeException('文件不是有效视频：' . (string)$file->name);
        }
        return $info;
    }

    private function sourceForFile(string $uri): string
    {
        $local = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($uri, '/');
        if (is_file($local)) {
            return $local;
        }
        return FileService::getFileUrl($uri, '', (string)ConfigService::get('storage', 'default', 'local') !== 'local');
    }

    private function assertPersona(int $userId, int $personaId): void
    {
        if (!AiPersona::where('id', $personaId)->where('user_id', $userId)->where('delete_time', null)->count()) {
            throw new \RuntimeException('IP人设不存在');
        }
    }

    private function unitPrice(string $mode): float
    {
        $scene = $mode === VideoSlice::MODE_OSS ? 'material_slice_oss' : 'material_slice_local';
        $fallback = $mode === VideoSlice::MODE_OSS ? 3.0 : 1.0;
        $row = ModelConfig::where('scene', $scene)->where('status', 1)->findOrEmpty();
        return $row->isEmpty() ? $fallback : max(0, (float)$row->score);
    }

    private function formatBatch(VideoSlice $slice): array
    {
        $statusMap = [0 => 'pending', 1 => 'processing', 2 => 'success', 3 => 'failed'];
        $billMap = [0 => 'none', 1 => 'held', 2 => 'confirmed', 3 => 'refunded'];
        $total = (int)$slice->slice_count;
        $success = (int)$slice->success_slice_count;
        $fileId = (int)$slice->original_video_id;
        $status = $statusMap[(int)$slice->status] ?? 'pending';
        return [
            'batch_no' => (string)$slice->batch_no,
            'scene' => (string)$slice->scene,
            'persona_id' => (int)$slice->persona_id,
            'file_id' => $fileId,
            'original_video_id' => $fileId,
            'process_mode' => (string)$slice->process_mode,
            'channel_text' => (string)$slice->process_mode === VideoSlice::MODE_OSS ? '服务器切割' : '本地切割',
            'status' => $status,
            'status_text' => $this->progressStatusText($status),
            'phase' => 'slicing',
            'pending_slice' => false,
            'billing_status' => $billMap[(int)$slice->billing_status] ?? 'none',
            'video_count' => 1,
            'total_duration' => (float)$slice->original_duration,
            'success_slice_count' => $success,
            'total_slice_count' => $total,
            'estimated_slice_count' => $total,
            'progress_text' => $success . '/' . $total,
            'progress_percent' => $total > 0 ? round($success * 100 / $total, 2) : 0,
            'unit_price' => (float)$slice->unit_price,
            'tokens_cost' => (float)$slice->tokens_cost,
            'error_message' => (string)$slice->error_message,
            'show_progress' => in_array((int)$slice->status, [VideoSlice::STATUS_PENDING, VideoSlice::STATUS_PROCESSING], true),
        ];
    }

    private function storageDriver(): StorageDriver
    {
        return new StorageDriver([
            'default' => ConfigService::get('storage', 'default', 'local'),
            'engine' => ConfigService::get('storage') ?: ['local' => []],
        ]);
    }

    private function deleteOutput(string $path): void
    {
        if ($path === '') {
            return;
        }
        $local = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, '/');
        if (is_file($local)) {
            @unlink($local);
            return;
        }
        try {
            $this->storageDriver()->delete(ltrim($path, '/'));
        } catch (\Throwable $e) {
            Log::channel('video_slice')->warning('[素材批量分割] 清理切片失败 path=' . $path . ' error=' . $e->getMessage());
        }
    }

    private function outputSize(string $path): int
    {
        $local = rtrim(public_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, '/');
        return is_file($local) ? (int)filesize($local) : 0;
    }

    private function sliceName(string $originalName, int $sequence): string
    {
        return pathinfo($originalName, PATHINFO_FILENAME) . '片段' . $sequence;
    }

    /**
     * 补全缺少封面的素材（切割子素材 + 原片直接入库），运维/修复用。
     *
     * @return array{total:int,success:int,failed:int}
     */
    public function backfillMissingThumbnails(int $personaId = 0, int $limit = 100): array
    {
        $query = Material::where(function ($q) {
                $q->whereNull('thumbnail_url')->whereOr('thumbnail_url', '');
            })
            ->where(function ($q) {
                $q->where(function ($ok) {
                    $ok->where('source_type', 'slice')
                        ->where('slice_status', Material::SLICE_STATUS_SUCCESS);
                })->whereOr(function ($orig) {
                    // 短视频直接入库的原片、或切割失败入库的原片
                    $orig->where('source_type', 'original')
                        ->whereIn('slice_status', [
                            Material::SLICE_STATUS_NONE,
                            Material::SLICE_STATUS_FAILED,
                        ]);
                });
            })
            ->whereNull('delete_time')
            ->order(['id' => 'desc'])
            ->limit(max(1, $limit));
        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }

        $success = 0;
        $failed = 0;
        $rows = $query->select();
        foreach ($rows as $row) {
            $fileUri = (string)($row->getData('file_url') ?? '');
            $duration = (float)($row->duration ?? 0);
            $seek = $duration > 0 ? min(0.3, max(0.01, $duration * 0.15)) : 0.1;
            $thumb = $this->makeThumbnail($fileUri, $seek);
            if ($thumb === '') {
                $failed++;
                continue;
            }
            $row->save([
                'thumbnail_url' => $thumb,
                'update_time' => time(),
            ]);
            $success++;
        }

        return [
            'total' => count($rows),
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * 从视频截取封面，返回相对路径；失败返回空字符串（不中断切割主流程）。
     */
    private function makeThumbnail(string $videoUri, float $time = 0.5): string
    {
        return MaterialService::makeVideoThumbnail($videoUri, $time);
    }

    private function makeBatchNo(): string
    {
        return 'MS' . date('YmdHis') . strtoupper(bin2hex(random_bytes(5)));
    }

    private function findBinary(array $candidates, string $label): string
    {
        foreach ($candidates as $binary) {
            $output = shell_exec(escapeshellcmd($binary) . ' -version 2>/dev/null');
            if (is_string($output) && stripos($output, $label . ' version') !== false) {
                return $binary;
            }
        }
        throw new \RuntimeException($label . ' 未安装或不在 PATH 中');
    }
}
