<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use app\common\service\ffmpeg\MaterialSliceBatchService;
use app\model\VideoSlice;
use think\facade\Db;

class VideoSliceLogic extends ApiLogic
{
    public static function sliceOptions(): array
    {
        return (new MaterialSliceBatchService())->options();
    }

    public static function sliceQuote(int $userId, array $params): bool
    {
        return self::runSliceAction(fn() => (new MaterialSliceBatchService())->quote($userId, $params));
    }

    public static function sliceConfirm(int $userId, array $params): bool
    {
        return self::runSliceAction(fn() => (new MaterialSliceBatchService())->confirm($userId, $params));
    }

    public static function sliceBatchDetail(int $userId, string $batchNo): bool
    {
        return self::runSliceAction(fn() => (new MaterialSliceBatchService())->detail($userId, $batchNo));
    }

    public static function activeSliceBatch(int $userId, int $personaId, string $scene): bool
    {
        return self::runSliceAction(fn() => (new MaterialSliceBatchService())->activeBatch($userId, $personaId, $scene));
    }

    /**
     * 切割统计（兼容旧前端）。
     * @param int $fromTime 时间下限 unix；默认今天 00:00:00，只统计该时间及之后，规避历史旧数据
     */
    public static function statistics(
        int $userId,
        int $personaId,
        string $scene = 'persona',
        array $fileIds = [],
        int $fromTime = 0
    ): bool {
        try {
            $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
            if ($fromTime <= 0) {
                $fromTime = (int)strtotime('today');
            }
            if (empty($fileIds)) {
                $fileIds = self::discoverStatisticsFileIds($userId, $personaId, $scene, $fromTime);
            }

            self::$returnData = self::buildBatchStatistics($userId, $personaId, $scene, $fileIds, $fromTime);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * file_ids 为空时：只发现「进行中」且 fromTime 之后的切割相关原视频。
     *
     * @return int[]
     */
    private static function discoverStatisticsFileIds(
        int $userId,
        int $personaId,
        string $scene,
        int $fromTime
    ): array {
        // 仅未完成的切片素材（不含 SUCCESS/FAILED 历史）
        $fromMaterials = self::baseStatisticsQuery($userId, $personaId)
            ->where('slice_status', 'in', [
                Material::SLICE_STATUS_PENDING,
                Material::SLICE_STATUS_PROCESSING,
            ])
            ->where('create_time', '>=', $fromTime)
            ->group('source_video_id')
            ->column('source_video_id');

        $fromDateTime = date('Y-m-d H:i:s', $fromTime);
        $fromTasks = VideoSlice::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('scene', $scene)
            ->whereIn('status', [VideoSlice::STATUS_PENDING, VideoSlice::STATUS_PROCESSING])
            ->whereNotNull('batch_no')
            ->where('batch_no', '<>', '')
            ->where(function ($query) use ($fromTime, $fromDateTime) {
                $query->where('created_at', '>=', $fromDateTime)
                    ->whereOr('update_time', '>=', $fromTime);
            })
            ->column('original_video_id');

        $awaitingFiles = File::where('source_id', $userId)
            ->where('persona_id', $personaId)
            ->where('scene', $scene)
            ->where('slice_mode', 2)
            ->whereIn('transcode_status', [1, 2, 3])
            ->where('create_time', '>=', $fromTime)
            ->field('id,duration,transcode_status')
            ->select();

        $candidateIds = [];
        foreach ($awaitingFiles as $file) {
            $duration = round((float)($file->duration ?? 0), 2);
            // 已知时长且按规则不切割：不进入预切割统计
            if ($duration > 0 && MaterialSliceBatchService::calcSliceCount($duration) <= 0) {
                continue;
            }
            $candidateIds[] = (int)$file->id;
        }

        // 已有切割任务（含成功/失败）的文件不再当「待切割」
        $batchedSet = [];
        if ($candidateIds !== []) {
            $batchedIds = VideoSlice::whereIn('original_video_id', $candidateIds)
                ->whereNotNull('batch_no')
                ->where('batch_no', '<>', '')
                ->column('original_video_id');
            $batchedSet = array_fill_keys(array_map('intval', $batchedIds ?: []), true);
        }

        $fromAwaiting = [];
        foreach ($candidateIds as $fileId) {
            if (isset($batchedSet[$fileId])) {
                continue;
            }
            $fromAwaiting[] = $fileId;
        }

        return array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($fromMaterials ?: [], $fromTasks ?: [], $fromAwaiting)
        ))));
    }

    private static function buildBatchStatistics(
        int $userId,
        int $personaId,
        string $scene,
        array $fileIds,
        int $fromTime = 0
    ): array {
        if (empty($fileIds)) {
            return [
                'total' => 0,
                'lists' => [],
                'summary' => self::buildStatisticsData(self::emptyStatusData()),
                'from_time' => $fromTime,
            ];
        }

        $materialByVideoId = [];
        foreach ($fileIds as $videoId) {
            $materialByVideoId[$videoId] = self::emptyStatusData();
        }

        $materialQuery = self::baseStatisticsQuery($userId, $personaId)
            ->whereIn('source_video_id', $fileIds)
            ->where('slice_status', 'in', [
                Material::SLICE_STATUS_PENDING,
                Material::SLICE_STATUS_PROCESSING,
                Material::SLICE_STATUS_SUCCESS,
                Material::SLICE_STATUS_FAILED,
            ]);
        if ($fromTime > 0) {
            $materialQuery->where('create_time', '>=', $fromTime);
        }

        $rows = $materialQuery
            ->field('source_video_id, slice_status, COUNT(id) AS total_count')
            ->group('source_video_id, slice_status')
            ->select()
            ->toArray();

        foreach ($rows as $row) {
            $videoId = (int)$row['source_video_id'];
            $status = self::materialStatusToVideoStatus((int)$row['slice_status']);
            if (!isset($materialByVideoId[$videoId][$status])) {
                continue;
            }
            $materialByVideoId[$videoId][$status]['slice_count'] = (int)$row['total_count'];
        }

        $summaryStatusData = self::emptyStatusData();
        $list = [];
        foreach ($fileIds as $videoId) {
            $statusData = self::buildFileStatistics(
                $userId,
                $personaId,
                $scene,
                (int)$videoId,
                $materialByVideoId[$videoId] ?? self::emptyStatusData(),
                $fromTime
            );
            if ($statusData === null) {
                // 显式传了 file_ids 时仍返回空壳，便于前端对齐
                $statusData = self::emptyStatusData();
            }

            foreach ($statusData as $status => $row) {
                $summaryStatusData[$status]['count'] += (int)$row['count'];
                $summaryStatusData[$status]['slice_count'] += (int)$row['slice_count'];
            }

            $built = self::buildStatisticsData($statusData);
            $built['original_video_id'] = (int)$videoId;
            $built['file_id'] = (int)$videoId;
            $list[] = $built;
        }

        return [
            'total' => count($list),
            'lists' => $list,
            'summary' => self::buildStatisticsData($summaryStatusData),
            'from_time' => $fromTime,
        ];
    }

    /**
     * 单原视频统计：
     * - 已完成：子数量用已落库素材
     * - 预切割/切割中：子数量用计划片数（时长按切割规则重算），避免只统计已写出的素材导致偏小
     */
    private static function buildFileStatistics(
        int $userId,
        int $personaId,
        string $scene,
        int $fileId,
        array $materialData,
        int $fromTime = 0
    ): ?array {
        $taskQuery = VideoSlice::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('scene', $scene)
            ->where('original_video_id', $fileId)
            ->whereNotNull('batch_no')
            ->where('batch_no', '<>', '');
        if ($fromTime > 0) {
            $fromDateTime = date('Y-m-d H:i:s', $fromTime);
            $taskQuery->where(function ($query) use ($fromTime, $fromDateTime) {
                $query->where('created_at', '>=', $fromDateTime)
                    ->whereOr('update_time', '>=', $fromTime);
            });
        }
        $task = $taskQuery->order(['id' => 'desc'])->findOrEmpty();

        $fileQuery = File::where('id', $fileId)->where('source_id', $userId);
        if ($fromTime > 0) {
            $fileQuery->where('create_time', '>=', $fromTime);
        }
        $file = $fileQuery->findOrEmpty();

        $planned = self::resolvePlannedSliceCount($task, $file);
        $materialTotal = (int)array_sum(array_column($materialData, 'slice_count'));
        $successSlices = (int)$materialData[VideoSlice::STATUS_SUCCESS]['slice_count'];
        $failedSlices = (int)$materialData[VideoSlice::STATUS_FAILED]['slice_count'];
        $pendingSlices = (int)$materialData[VideoSlice::STATUS_PENDING]['slice_count'];
        $processingSlices = (int)$materialData[VideoSlice::STATUS_PROCESSING]['slice_count'];

        $taskStatus = $task->isEmpty() ? null : (int)$task->status;
        $inProgress = in_array($taskStatus, [VideoSlice::STATUS_PENDING, VideoSlice::STATUS_PROCESSING], true);
        $awaiting = $task->isEmpty()
            && !$file->isEmpty()
            && (int)($file->slice_mode ?? 0) === 2
            && in_array((int)($file->transcode_status ?? 0), [1, 2, 3], true)
            && (int)($file->persona_id ?? 0) === $personaId
            && (string)($file->scene ?? '') === $scene;

        // 无任务、无素材、也不在待切割：跳过
        if ($materialTotal <= 0 && !$inProgress && !$awaiting) {
            return null;
        }

        // 待切割但不满足切割规则、且时长已确定：跳过
        if ($awaiting && !$inProgress && $materialTotal <= 0 && $planned <= 0) {
            $duration = round((float)($file->duration ?? 0), 2);
            if ($duration > 0) {
                return null;
            }
        }

        $data = self::emptyStatusData();

        // 已完成/失败终态：以落库素材为准
        if ($taskStatus === VideoSlice::STATUS_SUCCESS || $taskStatus === VideoSlice::STATUS_FAILED) {
            foreach ($materialData as $status => $row) {
                $data[$status]['slice_count'] = (int)$row['slice_count'];
            }
            if ($materialTotal <= 0 && $planned > 0) {
                // 素材被清但仍有任务记录时，用计划片数兜底
                $status = $taskStatus === VideoSlice::STATUS_FAILED
                    ? VideoSlice::STATUS_FAILED
                    : VideoSlice::STATUS_SUCCESS;
                $data[$status]['slice_count'] = $planned;
            }
            $videoStatus = self::deriveVideoStatus($data);
            if ($videoStatus !== null) {
                $data[$videoStatus]['count'] = 1;
            }
            return $data;
        }

        // 无进行中任务，但素材已全部成功/失败：按终态统计，避免误报「切割中」
        if (!$inProgress && !$awaiting && $materialTotal > 0 && ($pendingSlices + $processingSlices) <= 0) {
            return self::finalizeMaterialOnly($materialData);
        }

        // 预切割 / 切割中：子数量按计划片数补齐；已完成片数逐步累加（勿等整批结束才从 0 跳到 total）
        $targetSlices = $planned > 0 ? $planned : max($materialTotal, (int)($task->slice_count ?? 0));
        if ($targetSlices <= 0 && $awaiting) {
            // 转码中时长未知：先不虚构子数量，原视频记待处理
            $data[VideoSlice::STATUS_PENDING]['count'] = 1;
            $data[VideoSlice::STATUS_PENDING]['slice_count'] = 0;
            return $data;
        }
        if ($targetSlices <= 0) {
            return $materialTotal > 0 ? self::finalizeMaterialOnly($materialData) : null;
        }

        // 已切出片数：优先任务计数，其次已落库素材（含处理中旧数据）
        $taskDone = !$task->isEmpty() ? max(0, (int)$task->success_slice_count) : 0;
        $createdSlices = $successSlices + $processingSlices + $pendingSlices;
        $doneSlices = min($targetSlices, max($taskDone, $createdSlices, $successSlices));

        $data[VideoSlice::STATUS_SUCCESS]['slice_count'] = max(0, $doneSlices - $failedSlices);
        $data[VideoSlice::STATUS_FAILED]['slice_count'] = $failedSlices;
        $accounted = (int)$data[VideoSlice::STATUS_SUCCESS]['slice_count'] + $failedSlices;
        $remain = max(0, $targetSlices - $accounted);

        // 计划片数已全部切完：视为成功，不再挂「切割中」
        if ($remain <= 0 && (int)$data[VideoSlice::STATUS_SUCCESS]['slice_count'] > 0) {
            $data[VideoSlice::STATUS_PROCESSING]['slice_count'] = 0;
            $data[VideoSlice::STATUS_PENDING]['slice_count'] = 0;
            $data[VideoSlice::STATUS_SUCCESS]['count'] = 1;
            return $data;
        }

        if ($taskStatus === VideoSlice::STATUS_PROCESSING || $processingSlices > 0 || ($inProgress && $doneSlices > 0)) {
            $data[VideoSlice::STATUS_PROCESSING]['slice_count'] = $remain;
            $data[VideoSlice::STATUS_PENDING]['slice_count'] = 0;
            $data[VideoSlice::STATUS_PROCESSING]['count'] = 1;
        } elseif ($inProgress || $awaiting || $remain > 0) {
            // 待入队 / 待切割 / 任务 pending
            $data[VideoSlice::STATUS_PENDING]['slice_count'] = max($remain, $pendingSlices);
            $data[VideoSlice::STATUS_PROCESSING]['slice_count'] = 0;
            $data[VideoSlice::STATUS_PENDING]['count'] = 1;
        } else {
            return self::finalizeMaterialOnly($materialData);
        }

        return $data;
    }

    private static function finalizeMaterialOnly(array $materialData): array
    {
        $data = self::emptyStatusData();
        foreach ($materialData as $status => $row) {
            $data[$status]['slice_count'] = (int)$row['slice_count'];
        }
        $videoStatus = self::deriveVideoStatus($data);
        if ($videoStatus !== null) {
            $data[$videoStatus]['count'] = 1;
        }
        return $data;
    }

    private static function deriveVideoStatus(array $data): ?int
    {
        return match (true) {
            (int)$data[VideoSlice::STATUS_PROCESSING]['slice_count'] > 0 => VideoSlice::STATUS_PROCESSING,
            (int)$data[VideoSlice::STATUS_PENDING]['slice_count'] > 0 => VideoSlice::STATUS_PENDING,
            (int)$data[VideoSlice::STATUS_FAILED]['slice_count'] > 0 => VideoSlice::STATUS_FAILED,
            (int)$data[VideoSlice::STATUS_SUCCESS]['slice_count'] > 0 => VideoSlice::STATUS_SUCCESS,
            (int)$data[VideoSlice::STATUS_PENDING]['count'] > 0 => VideoSlice::STATUS_PENDING,
            (int)$data[VideoSlice::STATUS_PROCESSING]['count'] > 0 => VideoSlice::STATUS_PROCESSING,
            default => null,
        };
    }

    /**
     * 按新切割规则从时长计算计划子片数；时长无效时回退任务上的 slice_count。
     */
    private static function resolvePlannedSliceCount($task, $file): int
    {
        $duration = 0.0;
        if ($task && !$task->isEmpty()) {
            $duration = round((float)($task->original_duration ?? 0), 2);
        }
        if ($duration <= 0 && $file && !$file->isEmpty()) {
            $duration = round((float)($file->duration ?? 0), 2);
        }

        $planned = MaterialSliceBatchService::calcSliceCount($duration);
        if ($planned > 0) {
            return $planned;
        }

        if ($task && !$task->isEmpty()) {
            return max(0, (int)$task->slice_count);
        }

        return 0;
    }

    private static function baseStatisticsQuery(int $userId, int $personaId = 0)
    {
        return Db::name('ai_persona_material')
            ->where('user_id', $userId)
            ->where('material_type', Material::MATERIAL_TYPE_VIDEO)
            ->where('use_status', '<>', Material::USE_STATUS_DELETED)
            ->where('delete_time', null)
            ->where('source_type', 'slice')
            ->where('source_video_id', '>', 0)
            ->whereRaw("NOT (file_url = '' AND duration <= 0)")
            ->when($personaId > 0, function ($query) use ($personaId) {
                $query->where('persona_id', $personaId);
            });
    }

    private static function emptyStatusData(): array
    {
        $statusData = [];
        foreach (self::statusMap() as $status => $text) {
            $statusData[$status] = [
                'status' => $status,
                'status_text' => $text,
                'count' => 0,
                'slice_count' => 0,
            ];
        }

        return $statusData;
    }

    /**
     * 切割统计字段语义（按产品约定）：
     * - slicing_count：要切割的【原视频】数（排队 + 切割中）
     * - pending_count：切割中的【原视频】数
     * - item_count / total_slice_count：预生成【子片段】总数
     * - success_slice_count：已切完的【子片段】数
     * - total_count：原视频合计
     */
    private static function buildStatisticsData(array $statusData): array
    {
        // count = 原视频数；slice_count = 子片段数
        $queueCount = (int)$statusData[VideoSlice::STATUS_PENDING]['count'];      // 排队
        $cuttingCount = (int)$statusData[VideoSlice::STATUS_PROCESSING]['count']; // 切割中
        $successCount = (int)$statusData[VideoSlice::STATUS_SUCCESS]['count'];
        $failedCount = (int)$statusData[VideoSlice::STATUS_FAILED]['count'];

        $totalCount = $queueCount + $cuttingCount + $successCount + $failedCount;
        $finishedCount = $successCount + $failedCount;
        $totalSliceCount = (int)array_sum(array_column($statusData, 'slice_count'));
        $successSliceCount = (int)$statusData[VideoSlice::STATUS_SUCCESS]['slice_count'];

        return [
            'total_count' => $totalCount,
            // 切割中的原视频数
            'pending_count' => $cuttingCount,
            // 要切割的原视频数（排队 + 切割中）
            'slicing_count' => $queueCount + $cuttingCount,
            // 兼容：单独透出排队原视频数
            'queue_count' => $queueCount,
            'finished_count' => $finishedCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total_slice_count' => $totalSliceCount,
            'success_slice_count' => $successSliceCount,
            // 预生成子片段总数
            'item_count' => $totalSliceCount,
            'sliced_count' => $successCount,
            'status' => array_values($statusData),
        ];
    }

    private static function materialStatusToVideoStatus(int $sliceStatus): int
    {
        return match ($sliceStatus) {
            Material::SLICE_STATUS_PROCESSING => VideoSlice::STATUS_PROCESSING,
            Material::SLICE_STATUS_SUCCESS => VideoSlice::STATUS_SUCCESS,
            Material::SLICE_STATUS_FAILED => VideoSlice::STATUS_FAILED,
            default => VideoSlice::STATUS_PENDING,
        };
    }

    private static function runSliceAction(callable $callback): bool
    {
        try {
            self::$returnData = $callback();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            self::$returnData = $e->getCode() === 4059
                ? ['recharge_required' => true, 'recharge_path' => '/user/recharge']
                : [];
            return false;
        }
    }

    public static function statusMap(): array
    {
        return [
            VideoSlice::STATUS_PENDING => '待处理',
            VideoSlice::STATUS_PROCESSING => '处理中',
            VideoSlice::STATUS_SUCCESS => '成功',
            VideoSlice::STATUS_FAILED => '失败',
        ];
    }

    public static function statusText(int $status): string
    {
        return self::statusMap()[$status] ?? '待处理';
    }
}
