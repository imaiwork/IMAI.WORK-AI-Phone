<?php

namespace app\common\workerman\rpa\handlers\touch;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevicePreciseClues;
use app\common\model\sv\SvDevicePreciseCluesAccount;
use app\common\model\sv\SvDevicePreciseCluesRecord;
use app\common\model\sv\SvDeviceTask;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use think\facade\Db;
use think\facade\Log;
use Workerman\Connection\TcpConnection;

/**
 * 抖音精准获客触达任务上报处理。
 *
 * 一、服务端首次下发给 RPA 的任务指令格式:
 * [
 *     'type' => DeviceEnum::TASK_PRECISE_CLUES, // 1006，精准获客任务启动
 *     'appType' => 4,                           // platform type: 3=XHS, 4=Douyin
 *     'deviceId' => '设备编号',
 *     'messageId' => 0,
 *     'appVersion' => DeviceEnum::APP_VERSION,
 *     'content' => json_encode([
 *         'taskId' => 1,              // 精准获客主任务ID，对应 sv_device_precise_clues.id
 *         'taskAccountId' => 1,       // 精准获客账号任务ID，对应 sv_device_precise_clues_account.id
 *         'auto_type' => 1,           // 任务类型，1=自动任务
 *         'account' => '抖音账号',     // 当前执行账号
 *         'account_type' => 4,        // 账号平台，4=抖音
 *         'start_time' => 1710000000, // 允许执行开始时间，Unix 时间戳
 *         'end_time' => 1710021600,   // 允许执行结束时间，Unix 时间戳
 *         'round_no' => 1,            // 当前应执行轮次
 *         'mention_limit' => 50,      // 单轮最多艾特人数，服务端默认不超过50
 *         'wait_seconds' => 600,      // 每轮保存后等待秒数，默认10分钟
 *         'clues' => ['uid1'],        // 本轮待艾特抖音用户ID列表，RPA按顺序处理
 *         'all_clues' => ['uid1'],    // 当前任务剩余待触达用户池
 *         'total_count' => 100,       // 用户池总人数
 *         'touched_count' => 0,       // 已成功触达人数量
 *         'remaining_count' => 100,   // 剩余待触达人数量
 *         'msg' => '精准获客任务运行',
 *     ], JSON_UNESCAPED_UNICODE),
 * ]
 *
 * 二、RPA 执行后上报给服务端的指令格式:
 * [
 *     'type' => WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK, // 1006，精准获客触达结果上报
 *     'appType' => 4,                                      // platform type: 3=XHS, 4=Douyin
 *     'deviceId' => '设备编号',
 *     'messageId' => 0,
 *     'appVersion' => WorkerEnum::APP_VERSION,
 *     'content' => [
 *         'taskId' => 1,              // 必填，精准获客主任务ID；兼容 task_id、precise_clues_id
 *         'taskAccountId' => 1,       // 建议必填，账号任务ID；兼容 task_account_id、precise_clues_account_id
 *         'userId' => 1,              // 可选，用户ID
 *         'action' => 'edit',         // 执行动作: edit=本轮艾特保存，delete=删除上一轮艾特，finish=完成，error=异常
 *         'round_no' => 1,            // 当前执行轮次；兼容 roundNo、round、currentRound
 *         'delete_round_no' => 1,     // 删除上一轮艾特时使用；兼容 deleteRoundNo、prevRoundNo
 *         'success' => true,          // 执行是否成功；兼容 isSuccess、is_success、ok、result、status
 *         'users' => ['uid1'],        // 本轮已处理用户；兼容 touchUsers、atUsers、mentionUsers、clues
 *         'video_id' => 'aweme_id',   // 所属视频ID；兼容 videoId、awemeId、itemId
 *         'video_url' => '视频链接',   // 所属视频链接；兼容 videoUrl、shareUrl、url
 *         'video_title' => '标题',     // 所属视频标题；兼容 videoTitle、title
 *         'touch_time' => 1710000000, // 触达时间；兼容 touchTime、execTime、exec_time
 *         'retry_count' => 0,         // RPA本轮重试次数
 *         'total_count' => 100,       // 用户池总人数
 *         'touched_count' => 50,      // RPA侧已触达人数量
 *         'remaining_count' => 50,    // RPA侧剩余待触达人数量
 *         'edit_success_count' => 1,  // RPA侧编辑成功次数
 *         'delete_success_count' => 0,// RPA侧删除成功次数
 *         'fail_reason' => '',        // 失败原因；兼容 errorMsg、errMsg、msg
 *         'day' => '2026-06-08',      // 任务日期，缺省为服务端当前日期
 *     ],
 * ]
 *
 * users 支持字符串 ID，也支持对象:
 * [
 *     'target_user_id' => 'uid1',     // 抖音用户ID；兼容 douyin_user_id、sec_uid、uid、user_id、account、id
 *     'target_nickname' => '昵称',     // 用户昵称；兼容 nickname、nickName、name
 * ]
 *
 * 三、服务端收到 RPA 上报后的响应 reply 格式:
 * [
 *     'ok' => 1,                         // 处理结果，1=服务端已接收并处理
 *     'msg' => '触达信息上报成功',
 *     'isContinue' => 1,                 // 是否还有下一批用户，1=继续，0=结束
 *     'mentionLimit' => 50,              // 下一轮单次艾特上限
 *     'mention_limit' => 50,
 *     'waitSeconds' => 600,              // 下一轮前等待秒数
 *     'wait_seconds' => 600,
 *     'roundNo' => 2,                    // 下一轮轮次
 *     'round_no' => 2,
 *     'changedCount' => 50,              // 本次新增或更新记录数量
 *     'changed_count' => 50,
 *     'skippedCount' => 0,               // 本次因去重或无效用户跳过数量
 *     'skipped_count' => 0,
 *     'totalCount' => 100,               // 服务端统计用户池总人数
 *     'total_count' => 100,
 *     'touchedCount' => 50,              // 服务端统计已触达人数量
 *     'touched_count' => 50,
 *     'remainingCount' => 50,            // 服务端统计剩余人数
 *     'remaining_count' => 50,
 *     'editSuccessCount' => 1,           // 服务端按轮次统计编辑成功次数
 *     'edit_success_count' => 1,
 *     'deleteSuccessCount' => 0,         // 服务端按轮次统计删除成功次数
 *     'delete_success_count' => 0,
 *     'clues' => ['uid51'],              // 下一轮待艾特用户ID列表
 *     'nextUsers' => ['uid51'],
 *     'next_users' => ['uid51'],
 *     'nextUserDetails' => [],           // 下一轮用户明细，包含 target_user_id、target_nickname、raw
 *     'next_user_details' => [],
 * ]
 *
 * 异常响应 content 格式:
 * [
 *     'code' => WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK_FAIL, // 4064
 *     'msg' => '异常信息:失败原因',
 *     'deviceId' => '设备编号',
 * ]
 */
class PreciseCluesTaskHandler extends BaseMessageHandler
{
    protected $appType = 0;

    private const DEFAULT_MENTION_LIMIT = 50;
    private const DEFAULT_WAIT_SECONDS = 600;

    private const STATUS_PENDING = 0;
    private const STATUS_SUCCESS = 1;
    private const STATUS_FAILED = 2;
    private const STATUS_SKIPPED = 3;

    private const EDIT_STATUS_SUCCESS = 1;
    private const EDIT_STATUS_FAILED = 2;
    private const DELETE_STATUS_SUCCESS = 1;
    private const DELETE_STATUS_FAILED = 2;

    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = $payload['content'] ?? [];
        $content = !is_array($content) ? json_decode($content, true) : $content;
        $this->uid = $uid;
        $this->payload = $payload;
        $this->connection = $connection;

        try {
            if (!is_array($content)) {
                throw new \InvalidArgumentException('精准获客上报内容格式错误');
            }

            $this->msgType = (int)(WorkerEnum::DESC[$payload['type'] ?? ''] ?? ($payload['type'] ?? 0));
            $this->appType = isset($payload['appType']) ? (int)$payload['appType'] : 0;
            $this->userId = (int)($content['userId'] ?? 0);
            $this->setLog([
                'stage' => 'precise_clues_handle_start',
                'uid' => $uid,
                'deviceId' => $payload['deviceId'] ?? '',
                'type' => $payload['type'] ?? '',
                'appType' => $this->appType,
                'userId' => $this->userId,
                'content_keys' => array_keys($content),
            ], 'precise_clues');

            $this->payload['reply'] = $this->saveLog($content);
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK;
            $this->setLog([
                'stage' => 'precise_clues_handle_response',
                'deviceId' => $payload['deviceId'] ?? '',
                'taskId' => $content['taskId'] ?? $content['task_id'] ?? $content['precise_clues_id'] ?? 0,
                'reply' => $this->payload['reply'],
            ], 'precise_clues');
            //$this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Throwable $e) {
            $this->setLog('精准获客触达上报异常:' . $e, 'precise_clues');
            $this->setLog([
                'stage' => 'precise_clues_handle_exception',
                'deviceId' => $this->payload['deviceId'] ?? '',
                'message' => $e->getMessage(),
                'content' => is_array($content) ? $this->shrinkPayload($content) : $content,
            ], 'precise_clues');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] = WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK;
            $this->payload['content'] = [
                'code' => WorkerEnum::RPA_DEVICE_PRECISE_CLUES_TASK_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId'] ?? '',
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    private function saveLog(array $content): array
    {
        Log::channel('socket')->write('精准获客触达上报:' . json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'precise_clues');
        $this->setLog([
            'stage' => 'precise_clues_report_received',
            'deviceId' => $this->payload['deviceId'] ?? '',
            'payload' => $this->shrinkPayload($content),
        ], 'precise_clues');

        $taskId = $this->getIntValue($content, ['taskId', 'task_id', 'precise_clues_id']);
        if ($taskId <= 0) {
            throw new \InvalidArgumentException('精准获客任务ID不能为空');
        }

        $taskAccountId = $this->getIntValue($content, ['taskAccountId', 'task_account_id', 'precise_clues_account_id']);
        $this->setLog([
            'stage' => 'precise_clues_load_task_start',
            'taskId' => $taskId,
            'taskAccountId' => $taskAccountId,
            'deviceId' => $this->payload['deviceId'] ?? '',
            'appType' => $this->appType,
        ], 'precise_clues');

        $task = $this->getTaskConfig($taskId, $taskAccountId);
        if ($task->isEmpty()) {
            throw new \RuntimeException('精准获客任务不存在:' . Db::getLastSql());
        }
        $this->appType = $this->resolvePlatform($task);
        $this->setLog([
            'stage' => 'precise_clues_load_task_success',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'account' => $task->account,
            'platform' => $this->appType,
            'deviceId' => $task->device_code,
        ], 'precise_clues');

        $video = $this->normalizeVideo($content);
        $roundNo = $this->normalizeRoundNo($content, $task, $video['video_key']);
        $action = $this->normalizeAction($content);
        $success = $this->normalizeSuccess($content);
        $errorMsg = $this->normalizeError($content);
        $reportedUsers = $this->normalizeReportedUsers($content);
        $this->setLog([
            'stage' => 'precise_clues_report_normalized',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'video' => $video,
            'roundNo' => $roundNo,
            'action' => $action,
            'success' => $success ? 1 : 0,
            'reportedUserCount' => count($reportedUsers),
            'errorMsg' => $errorMsg,
        ], 'precise_clues');

        Db::startTrans();
        try {
            $changedCount = 0;
            $skippedCount = 0;
            $this->setLog([
                'stage' => 'precise_clues_transaction_start',
                'taskId' => (int)$task->id,
                'roundNo' => $roundNo,
                'action' => $action,
            ], 'precise_clues');

            if ($this->isDeleteAction($action)) {
                $changedCount = $this->markDeleteResult($task, $video, $roundNo, $success, $errorMsg, $content);
            } elseif (!empty($reportedUsers)) {
                foreach ($reportedUsers as $user) {
                    $result = $this->saveTouchRecord($task, $video, $roundNo, $action, $success, $errorMsg, $user, $content);
                    if ($result === 'skipped') {
                        $skippedCount++;
                    } else {
                        $changedCount++;
                    }
                }
            } else {
                $this->setLog([
                    'stage' => 'precise_clues_no_reported_users',
                    'taskId' => (int)$task->id,
                    'roundNo' => $roundNo,
                    'action' => $action,
                ], 'precise_clues');
            }

            $stats = $this->buildStats($task, $video['video_key']);
            $this->syncTaskStatus($task, $stats, $success, $errorMsg);
            $this->setLog([
                'stage' => 'precise_clues_transaction_ready_commit',
                'taskId' => (int)$task->id,
                'roundNo' => $roundNo,
                'action' => $action,
                'changedCount' => $changedCount,
                'skippedCount' => $skippedCount,
                'totalCount' => $stats['total_count'],
                'touchedCount' => $stats['touched_count'],
                'remainingCount' => $stats['remaining_count'],
                'editSuccessCount' => $stats['edit_success_count'],
                'deleteSuccessCount' => $stats['delete_success_count'],
            ], 'precise_clues');

            Db::commit();
            $this->setLog([
                'stage' => 'precise_clues_transaction_committed',
                'taskId' => (int)$task->id,
                'roundNo' => $roundNo,
                'action' => $action,
            ], 'precise_clues');
        } catch (\Throwable $th) {
            Db::rollback();
            $this->setLog([
                'stage' => 'precise_clues_transaction_rollback',
                'taskId' => (int)$task->id,
                'roundNo' => $roundNo,
                'action' => $action,
                'message' => $th->getMessage(),
            ], 'precise_clues');
            throw $th;
        }

        $limit = $this->normalizeMentionLimit($content);
        $nextUsers = array_slice($stats['remaining_users'], 0, $limit);
        $nextUserDetails = array_slice($stats['remaining_user_details'], 0, $limit);
        $nextRoundNo = empty($reportedUsers) && !$this->isDeleteAction($action)
            ? $roundNo
            : $this->getNextRoundNo($task, $video['video_key']);

        $reply = [
            'ok' => 1,
            'msg' => $success ? '触达信息上报成功' : '触达异常已记录',
            'isContinue' => empty($nextUsers) ? 0 : 1,
            'mentionLimit' => $limit,
            'mention_limit' => $limit,
            'waitSeconds' => self::DEFAULT_WAIT_SECONDS,
            'wait_seconds' => self::DEFAULT_WAIT_SECONDS,
            'roundNo' => $nextRoundNo,
            'round_no' => $nextRoundNo,
            'changedCount' => $changedCount,
            'changed_count' => $changedCount,
            'skippedCount' => $skippedCount,
            'skipped_count' => $skippedCount,
            'totalCount' => $stats['total_count'],
            'total_count' => $stats['total_count'],
            'touchedCount' => $stats['touched_count'],
            'touched_count' => $stats['touched_count'],
            'remainingCount' => $stats['remaining_count'],
            'remaining_count' => $stats['remaining_count'],
            'editSuccessCount' => $stats['edit_success_count'],
            'edit_success_count' => $stats['edit_success_count'],
            'deleteSuccessCount' => $stats['delete_success_count'],
            'delete_success_count' => $stats['delete_success_count'],
            'clues' => $nextUsers,
            'nextUsers' => $nextUsers,
            'next_users' => $nextUsers,
            'nextUserDetails' => $nextUserDetails,
            'next_user_details' => $nextUserDetails,
        ];
        $this->setLog([
            'stage' => 'precise_clues_reply_built',
            'taskId' => (int)$task->id,
            'roundNo' => $roundNo,
            'nextRoundNo' => $nextRoundNo,
            'isContinue' => $reply['isContinue'],
            'nextUserCount' => count($nextUsers),
            'changedCount' => $changedCount,
            'skippedCount' => $skippedCount,
        ], 'precise_clues');

        return $reply;
    }

    private function getTaskConfig(int $taskId, int $taskAccountId = 0)
    {
        $queryAppType = $this->appType > 0
            ? $this->appType
            : ($taskAccountId > 0 ? 0 : DeviceEnum::ACCOUNT_TYPE_DY);

        return SvDevicePreciseClues::alias('ps')
            ->field('ps.*,s.id as precise_clues_account_id,s.clues,s.account,s.nickname,s.avatar,s.account_type,s.device_code')
            ->join('sv_device_precise_clues_account s', 's.precise_clues_id = ps.id')
            ->where('ps.id', $taskId)
            ->where('s.device_code', '=', $this->payload['deviceId'] ?? '')
            ->when($queryAppType > 0, function ($query) use ($queryAppType) {
                $query->where('s.account_type', $queryAppType);
            })
            ->when($taskAccountId > 0, function ($query) use ($taskAccountId) {
                $query->where('s.id', $taskAccountId);
            })
            ->limit(1)
            ->findOrEmpty();
    }

    private function resolvePlatform(?SvDevicePreciseClues $task = null): int
    {
        if ($this->appType > 0) {
            return (int)$this->appType;
        }

        $accountType = (int)($task?->account_type ?? 0);
        return $accountType > 0 ? $accountType : DeviceEnum::ACCOUNT_TYPE_DY;
    }

    private function saveTouchRecord(SvDevicePreciseClues $task, array $video, int $roundNo, string $action, bool $success, string $errorMsg, array $user, array $content): string
    {
        $targetUserId = $user['target_user_id'] ?? '';
        if ($targetUserId === '') {
            $this->setLog([
                'stage' => 'precise_clues_touch_record_skip_empty_user',
                'taskId' => (int)$task->id,
                'taskAccountId' => (int)$task->precise_clues_account_id,
                'roundNo' => $roundNo,
                'videoKey' => $video['video_key'],
            ], 'precise_clues');
            return 'skipped';
        }

        $this->setLog([
            'stage' => 'precise_clues_touch_record_save_start',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'roundNo' => $roundNo,
            'targetUserId' => $targetUserId,
            'videoKey' => $video['video_key'],
            'action' => $action,
            'success' => $success ? 1 : 0,
        ], 'precise_clues');

        $record = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $video['video_key'])
            ->where('target_user_id', $targetUserId)
            ->whereNull('delete_time')
            ->findOrEmpty();

        if (!$record->isEmpty() && (int)$record->status === self::STATUS_SUCCESS) {
            $this->setLog([
                'stage' => 'precise_clues_touch_record_skipped_duplicate',
                'taskId' => (int)$task->id,
                'taskAccountId' => (int)$task->precise_clues_account_id,
                'recordId' => (int)$record->id,
                'roundNo' => $roundNo,
                'targetUserId' => $targetUserId,
                'videoKey' => $video['video_key'],
            ], 'precise_clues');
            return 'skipped';
        }

        $now = time();
        $data = [
            'user_id' => (int)$task->user_id,
            'precise_clues_id' => (int)$task->id,
            'precise_clues_account_id' => (int)$task->precise_clues_account_id,
            'auto_type' => (int)$task->auto_type,
            'device_code' => $this->payload['deviceId'] ?? $task->device_code,
            'account' => $task->account,
            'platform' => $this->resolvePlatform($task),
            'nickname' => $task->nickname,
            'target_user_id' => $targetUserId,
            'target_nickname' => $user['target_nickname'] ?? '',
            'video_id' => $video['video_id'],
            'video_key' => $video['video_key'],
            'video_title' => $video['video_title'],
            'video_url' => $video['video_url'],
            'round_no' => $roundNo,
            'status' => $success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
            'edit_status' => $success ? self::EDIT_STATUS_SUCCESS : self::EDIT_STATUS_FAILED,
            'touch_time' => $this->normalizeTimestamp($content['touchTime'] ?? $content['touch_time'] ?? $content['execTime'] ?? $content['exec_time'] ?? $now),
            'retry_count' => $this->getIntValue($content, ['retryCount', 'retry_count']),
            'total_count' => $this->getIntValue($content, ['totalCount', 'total_count']),
            'touched_count' => $this->getIntValue($content, ['touchedCount', 'touched_count', 'finishedCount', 'finished_count']),
            'remaining_count' => $this->getIntValue($content, ['remainingCount', 'remaining_count']),
            'edit_success_count' => $this->getIntValue($content, ['editSuccessCount', 'edit_success_count']),
            'delete_success_count' => $this->getIntValue($content, ['deleteSuccessCount', 'delete_success_count']),
            'fail_reason' => $success ? '' : $errorMsg,
            'exception_log' => $success ? [] : [[
                'time' => date('Y-m-d H:i:s', $now),
                'round_no' => $roundNo,
                'action' => $action,
                'message' => $errorMsg,
            ]],
            'remark' => $success ? '触达成功' : $errorMsg,
            'extra' => $user['raw'] ?? [],
            'raw_payload' => $this->shrinkPayload($content),
            'day' => $content['day'] ?? date('Y-m-d'),
            'update_time' => $now,
        ];

        if ($record->isEmpty()) {
            $data['create_time'] = $now;
            $created = SvDevicePreciseCluesRecord::create($data);
            $this->setLog([
                'stage' => 'precise_clues_touch_record_created',
                'taskId' => (int)$task->id,
                'taskAccountId' => (int)$task->precise_clues_account_id,
                'recordId' => (int)$created->id,
                'roundNo' => $roundNo,
                'targetUserId' => $targetUserId,
                'status' => $data['status'],
                'editStatus' => $data['edit_status'],
            ], 'precise_clues');
            return 'created';
        }

        $record->save($data);
        $this->setLog([
            'stage' => 'precise_clues_touch_record_updated',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'recordId' => (int)$record->id,
            'roundNo' => $roundNo,
            'targetUserId' => $targetUserId,
            'status' => $data['status'],
            'editStatus' => $data['edit_status'],
        ], 'precise_clues');
        return 'updated';
    }

    private function markDeleteResult(SvDevicePreciseClues $task, array $video, int $roundNo, bool $success, string $errorMsg, array $content): int
    {
        $deleteRoundNo = $this->getIntValue($content, ['deleteRoundNo', 'delete_round_no', 'prevRoundNo', 'prev_round_no']);
        if ($deleteRoundNo <= 0) {
            $deleteRoundNo = max(1, $roundNo - 1);
        }

        $query = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $video['video_key'])
            ->where('round_no', $deleteRoundNo)
            ->whereNull('delete_time');

        $users = $this->normalizeReportedUsers($content);
        if (!empty($users)) {
            $targetUserIds = array_column($users, 'target_user_id');
            $query->where('target_user_id', 'in', $targetUserIds);
        }
        $this->setLog([
            'stage' => 'precise_clues_delete_result_start',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'roundNo' => $roundNo,
            'deleteRoundNo' => $deleteRoundNo,
            'videoKey' => $video['video_key'],
            'reportedUserCount' => count($users),
            'success' => $success ? 1 : 0,
            'errorMsg' => $errorMsg,
        ], 'precise_clues');

        $exceptionLog = $success ? null : json_encode([[
            'time' => date('Y-m-d H:i:s'),
            'round_no' => $deleteRoundNo,
            'action' => 'delete',
            'message' => $errorMsg,
        ]], JSON_UNESCAPED_UNICODE);

        $affectedRows = $query->update([
            'delete_status' => $success ? self::DELETE_STATUS_SUCCESS : self::DELETE_STATUS_FAILED,
            'remove_time' => time(),
            'delete_success_count' => $this->getIntValue($content, ['deleteSuccessCount', 'delete_success_count']),
            'fail_reason' => $success ? '' : $errorMsg,
            'exception_log' => $exceptionLog,
            'remark' => $success ? '上一轮艾特删除成功' : $errorMsg,
            'raw_payload' => json_encode($this->shrinkPayload($content), JSON_UNESCAPED_UNICODE),
            'update_time' => time(),
        ]);
        $this->setLog([
            'stage' => 'precise_clues_delete_result_updated',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'deleteRoundNo' => $deleteRoundNo,
            'affectedRows' => $affectedRows,
            'deleteStatus' => $success ? self::DELETE_STATUS_SUCCESS : self::DELETE_STATUS_FAILED,
        ], 'precise_clues');

        return $affectedRows;
    }

    private function buildStats(SvDevicePreciseClues $task, string $videoKey): array
    {
        $pool = $this->normalizeCluePool($task->clues);
        $poolIds = array_column($pool, 'target_user_id');

        $touchedIds = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $videoKey)
            ->where('status', self::STATUS_SUCCESS)
            ->whereNull('delete_time')
            ->group('target_user_id')
            ->column('target_user_id');

        $touchedIds = array_values(array_unique(array_filter($touchedIds)));
        $remainingUsers = [];
        $remainingUserDetails = [];
        foreach ($pool as $user) {
            if (!in_array($user['target_user_id'], $touchedIds, true)) {
                $remainingUsers[] = $user['target_user_id'];
                $remainingUserDetails[] = $user;
            }
        }

        $editSuccessRounds = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $videoKey)
            ->where('edit_status', self::EDIT_STATUS_SUCCESS)
            ->whereNull('delete_time')
            ->group('round_no')
            ->column('round_no');
        $deleteSuccessRounds = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $videoKey)
            ->where('delete_status', self::DELETE_STATUS_SUCCESS)
            ->whereNull('delete_time')
            ->group('round_no')
            ->column('round_no');

        $stats = [
            'total_count' => count($poolIds),
            'touched_count' => count(array_intersect($poolIds, $touchedIds)),
            'remaining_count' => count($remainingUsers),
            'remaining_users' => $remainingUsers,
            'remaining_user_details' => $remainingUserDetails,
            'edit_success_count' => count(array_unique(array_filter($editSuccessRounds))),
            'delete_success_count' => count(array_unique(array_filter($deleteSuccessRounds))),
        ];
        $this->setLog([
            'stage' => 'precise_clues_stats_built',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'videoKey' => $videoKey,
            'totalCount' => $stats['total_count'],
            'touchedCount' => $stats['touched_count'],
            'remainingCount' => $stats['remaining_count'],
            'editSuccessCount' => $stats['edit_success_count'],
            'deleteSuccessCount' => $stats['delete_success_count'],
        ], 'precise_clues');

        return $stats;
    }

    private function syncTaskStatus(SvDevicePreciseClues $task, array $stats, bool $success, string $errorMsg): void
    {
        $status = $stats['remaining_count'] === 0 ? DeviceEnum::TASK_STATUS_FINISHED : DeviceEnum::TASK_STATUS_RUNNING;
        if (!$success && $stats['touched_count'] === 0) {
            $status = DeviceEnum::TASK_STATUS_RUNNING;
        }

        SvDevicePreciseCluesAccount::where('id', $task->precise_clues_account_id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);

        SvDevicePreciseClues::where('id', $task->id)->update([
            'status' => $status,
            'update_time' => time(),
        ]);

        $deviceTaskStatus = $status === DeviceEnum::TASK_STATUS_FINISHED
            ? DeviceEnum::TASK_STATUS_RUNNING
            : $status;

        SvDeviceTask::where('sub_task_id', $task->id)
            ->where('sub_data_id', $task->precise_clues_account_id)
            ->where('source', DeviceEnum::TASK_SOURCE_PRECISE_CLUES)
            ->update([
                'status' => $deviceTaskStatus,
                'remark' => $status === DeviceEnum::TASK_STATUS_FINISHED
                    ? '精准获客任务触达完成，等待调度结算'
                    : ($success ? '任务执行中' : ('触达异常:' . $errorMsg)),
                'update_time' => time(),
            ]);
        $this->setLog([
            'stage' => 'precise_clues_task_status_synced',
            'taskId' => (int)$task->id,
            'taskAccountId' => (int)$task->precise_clues_account_id,
            'taskStatus' => $status,
            'deviceTaskStatus' => $deviceTaskStatus,
            'success' => $success ? 1 : 0,
            'touchedCount' => $stats['touched_count'],
            'remainingCount' => $stats['remaining_count'],
            'errorMsg' => $errorMsg,
        ], 'precise_clues');
    }

    private function getNextRoundNo(SvDevicePreciseClues $task, string $videoKey): int
    {
        $maxRoundNo = SvDevicePreciseCluesRecord::where('precise_clues_account_id', $task->precise_clues_account_id)
            ->where('video_key', $videoKey)
            ->whereNull('delete_time')
            ->max('round_no');

        return max(1, (int)$maxRoundNo + 1);
    }

    private function normalizeCluePool(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $users = [];
        $exists = [];
        foreach ($value as $item) {
            $user = $this->normalizeUserItem($item);
            if ($user['target_user_id'] === '' || isset($exists[$user['target_user_id']])) {
                continue;
            }
            $exists[$user['target_user_id']] = true;
            $users[] = $user;
        }

        return $users;
    }

    private function normalizeReportedUsers(array $content): array
    {
        $items = $content['users']
            ?? $content['touchUsers']
            ?? $content['touch_users']
            ?? $content['atUsers']
            ?? $content['at_users']
            ?? $content['mentionUsers']
            ?? $content['mention_users']
            ?? $content['clues']
            ?? [];

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $items)));
        }

        if (!is_array($items)) {
            return [];
        }

        $users = [];
        foreach ($items as $item) {
            $user = $this->normalizeUserItem($item);
            if ($user['target_user_id'] !== '') {
                $users[] = $user;
            }
        }

        return $users;
    }

    private function normalizeUserItem(mixed $item): array
    {
        if (is_scalar($item)) {
            $userId = trim((string)$item);
            return [
                'target_user_id' => $userId,
                'target_nickname' => '',
                'raw' => ['user_id' => $userId],
            ];
        }

        if (!is_array($item)) {
            return [
                'target_user_id' => '',
                'target_nickname' => '',
                'raw' => [],
            ];
        }

        $userId = $item['target_user_id']
            ?? $item['targetUserId']
            ?? $item['douyin_user_id']
            ?? $item['douyinUserId']
            ?? $item['douyin_id']
            ?? $item['douyinId']
            ?? $item['sec_uid']
            ?? $item['secUid']
            ?? $item['uid']
            ?? $item['user_id']
            ?? $item['userId']
            ?? $item['account']
            ?? $item['id']
            ?? '';

        return [
            'target_user_id' => trim((string)$userId),
            'target_nickname' => (string)($item['nickname'] ?? $item['nickName'] ?? $item['name'] ?? ''),
            'raw' => $item,
        ];
    }

    private function normalizeVideo(array $content): array
    {
        $videoId = trim((string)($content['videoId'] ?? $content['video_id'] ?? $content['awemeId'] ?? $content['aweme_id'] ?? $content['itemId'] ?? $content['item_id'] ?? ''));
        $videoUrl = trim((string)($content['videoUrl'] ?? $content['video_url'] ?? $content['shareUrl'] ?? $content['share_url'] ?? $content['url'] ?? ''));
        $videoTitle = (string)($content['videoTitle'] ?? $content['video_title'] ?? $content['title'] ?? '');
        $source = $videoId !== '' ? $videoId : ($videoUrl !== '' ? $videoUrl : 'task:' . ($content['taskId'] ?? $content['task_id'] ?? '0'));

        return [
            'video_id' => $videoId,
            'video_url' => $videoUrl,
            'video_title' => $videoTitle,
            'video_key' => hash('sha256', $source),
        ];
    }

    private function normalizeAction(array $content): string
    {
        $action = strtolower((string)($content['action'] ?? $content['event'] ?? $content['stage'] ?? $content['type'] ?? 'edit'));
        return match ($action) {
            'remove', 'delete', 'clear', 'clean', 'delete_mentions', 'deletemention', 'delete_at' => 'delete',
            'finish', 'finished', 'complete', 'completed' => 'finish',
            'error', 'failed', 'fail' => 'error',
            default => 'edit',
        };
    }

    private function normalizeRoundNo(array $content, SvDevicePreciseClues $task, string $videoKey): int
    {
        $roundNo = $this->getIntValue($content, ['roundNo', 'round_no', 'round', 'currentRound', 'current_round']);
        if ($roundNo > 0) {
            return $roundNo;
        }

        return $this->getNextRoundNo($task, $videoKey);
    }

    private function normalizeSuccess(array $content): bool
    {
        foreach (['success', 'isSuccess', 'is_success', 'ok', 'result'] as $key) {
            if (array_key_exists($key, $content)) {
                return in_array($content[$key], [1, '1', true, 'true', 'success', 'ok'], true);
            }
        }

        $status = strtolower((string)($content['status'] ?? $content['execStatus'] ?? $content['exec_status'] ?? 'success'));
        return !in_array($status, ['0', 'fail', 'failed', 'error', 'exception'], true);
    }

    private function normalizeError(array $content): string
    {
        $message = $content['failReason']
            ?? $content['fail_reason']
            ?? $content['errorMsg']
            ?? $content['error_msg']
            ?? $content['errMsg']
            ?? $content['err_msg']
            ?? $content['msg']
            ?? '';

        return mb_substr((string)$message, 0, 1000);
    }

    private function normalizeMentionLimit(array $content): int
    {
        $limit = $this->getIntValue($content, ['mentionLimit', 'mention_limit', 'atLimit', 'at_limit', 'limit']);
        if ($limit <= 0) {
            return self::DEFAULT_MENTION_LIMIT;
        }

        return min($limit, self::DEFAULT_MENTION_LIMIT);
    }

    private function isDeleteAction(string $action): bool
    {
        return $action === 'delete';
    }

    private function getIntValue(array $content, array $keys): int
    {
        foreach ($keys as $key) {
            if (isset($content[$key]) && is_numeric($content[$key])) {
                return (int)$content[$key];
            }
        }

        return 0;
    }

    private function normalizeTimestamp(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        $time = strtotime((string)$value);
        return $time ?: time();
    }

    private function shrinkPayload(array $content): array
    {
        unset($content['image'], $content['avatar'], $content['users'], $content['touchUsers'], $content['touch_users'], $content['atUsers'], $content['at_users']);
        return $content;
    }
}
