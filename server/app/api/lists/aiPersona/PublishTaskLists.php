<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\enum\DeviceEnum;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceTaskLog;
use app\common\service\FileService;
use think\db\Query;

class PublishTaskLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    private ?array $rows = null;
    private ?array $groups = null;
    private ?array $summary = null;
    private ?array $logsByTaskId = null;

    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        return array_slice($this->getGroups(), $this->limitOffset, $this->limitLength);
    }

    public function count(): int
    {
        return count($this->getGroups());
    }

    public function extend(): array
    {
        return $this->getSummary();
    }

    private function baseQuery(): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('sv_publish_setting_account pa', 'pa.id = dt.sub_task_id')
            ->join('sv_publish_setting_detail pd', 'pd.id = dt.sub_data_id')
            ->where('dt.user_id', '=', $this->userId)
            ->whereIn('dt.task_type', $this->publishTaskTypes())
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_PUBLISH)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH)
            ->where('dt.persona_id', '=', (int)$this->params['persona_id'])
            ->where('dt.day', '=', $this->getDate())
            ->whereNull('dt.delete_time')
            ->whereNull('pa.delete_time')
            ->whereNull('pd.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('dt.device_code', '=', trim((string)$this->params['device_code']));
        }

        if ($this->hasStatusFilter()) {
            $status = (int)$this->params['status'];
            if ($status === DeviceEnum::TASK_STATUS_INTERRUPTED) {
                $query->where(function ($query) {
                    $query->where('dt.status', '=', DeviceEnum::TASK_STATUS_INTERRUPTED)
                        ->whereOr('pd.status', '=', DeviceEnum::TASK_STATUS_INTERRUPTED);
                });
            } else {
                $query->where('dt.status', '=', $status)
                    ->where('pd.status', '<>', DeviceEnum::TASK_STATUS_INTERRUPTED);
            }
        } else {
            $query->where('dt.status', '<>', DeviceEnum::TASK_STATUS_INTERRUPTED)
                ->where('pd.status', '<>', DeviceEnum::TASK_STATUS_INTERRUPTED);
        }

        if (isset($this->params['account_type']) && $this->params['account_type'] !== '') {
            $accountType = (int)$this->params['account_type'];
            $query->where(function ($query) use ($accountType) {
                $query->where('dt.account_type', '=', $accountType)
                    ->whereOr('pa.account_type', '=', $accountType)
                    ->whereOr('pd.account_type', '=', $accountType)
                    ->whereOr('pd.platform', '=', $accountType);
            });
        }

        $timeRange = $this->getQueryTimeRange();
        if ($timeRange !== '') {
            $compactTimeRange = str_replace(' ', '', $timeRange);
            $query->where(function ($query) use ($timeRange, $compactTimeRange) {
                $query->where('dt.time_config', 'like', '%' . $compactTimeRange . '%');
                if ($timeRange !== $compactTimeRange) {
                    $query->whereOr('dt.time_config', 'like', '%' . $timeRange . '%');
                }
            });
        }

        if (!empty($this->params['keyword'])) {
            $keyword = '%' . trim((string)$this->params['keyword']) . '%';
            $query->where(function ($query) use ($keyword) {
                $query->where('dt.task_name', 'like', $keyword)
                    ->whereOr('dt.nickname', 'like', $keyword)
                    ->whereOr('pa.name', 'like', $keyword)
                    ->whereOr('pa.nickname', 'like', $keyword)
                    ->whereOr('pd.material_title', 'like', $keyword)
                    ->whereOr('pd.material_subtitle', 'like', $keyword);
            });
        }

        return $query;
    }

    private function wechatCircleQuery(): Query
    {
        $query = SvDeviceTask::alias('dt')
            ->join('ai_wechat_circle_task ct', 'ct.id = dt.sub_data_id')
            ->where('dt.user_id', '=', $this->userId)
            ->where('dt.task_type', '=', DeviceEnum::TASK_TYPE_WECHAT_CIRCLE)
            ->where('dt.source', '=', DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH)
            ->where('dt.task_scene', '=', DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH)
            ->where('dt.persona_id', '=', (int)$this->params['persona_id'])
            ->where('dt.day', '=', $this->getDate())
            ->where('ct.user_id', '=', $this->userId)
            ->whereNull('dt.delete_time');

        if (!empty($this->params['device_code'])) {
            $query->where('dt.device_code', '=', trim((string)$this->params['device_code']));
        }

        $this->applyWechatCircleStatusFilter($query);

        if (isset($this->params['account_type']) && $this->params['account_type'] !== '') {
            $accountType = (int)$this->params['account_type'];
            if ($accountType !== DeviceEnum::PUBLISH_PLATFORM_WX) {
                $query->where('dt.id', '=', 0);
            }
        }

        $timeRange = $this->getQueryTimeRange();
        if ($timeRange !== '') {
            $compactTimeRange = str_replace(' ', '', $timeRange);
            $query->where(function ($query) use ($timeRange, $compactTimeRange) {
                $query->where('dt.time_config', 'like', '%' . $compactTimeRange . '%');
                if ($timeRange !== $compactTimeRange) {
                    $query->whereOr('dt.time_config', 'like', '%' . $timeRange . '%');
                }
            });
        }

        if (!empty($this->params['keyword'])) {
            $keyword = '%' . trim((string)$this->params['keyword']) . '%';
            $query->where(function ($query) use ($keyword) {
                $query->where('dt.task_name', 'like', $keyword)
                    ->whereOr('dt.nickname', 'like', $keyword)
                    ->whereOr('dt.account', 'like', $keyword)
                    ->whereOr('ct.content', 'like', $keyword);
            });
        }

        return $query;
    }

    private function getPublishRows(): array
    {
        return $this->baseQuery()
            ->field([
                'dt.id' => 'task_id',
                'dt.device_code' => 'device_code',
                'dt.account' => 'account',
                'dt.account_type' => 'task_account_type',
                'dt.nickname' => 'task_nickname',
                'dt.avatar' => 'task_avatar',
                'dt.task_name' => 'task_name',
                'dt.status' => 'task_status',
                'dt.remark' => 'task_remark',
                'dt.start_time' => 'start_time',
                'dt.end_time' => 'end_time',
                'dt.day' => 'day',
                'dt.time_config' => 'time_config',
                'pa.id' => 'publish_account_id',
                'pa.name' => 'publish_account_name',
                'pa.account' => 'publish_account',
                'pa.account_type' => 'publish_account_type',
                'pa.nickname' => 'publish_nickname',
                'pa.avatar' => 'publish_avatar',
                'pd.account' => 'detail_account',
                'pd.id' => 'detail_id',
                'pd.platform' => 'platform',
                'pd.material_type' => 'media_type',
                'pd.material_title' => 'material_title',
                'pd.material_subtitle' => 'material_subtitle',
                'pd.material_tag' => 'material_tag',
                'pd.material_url' => 'material_url',
                'pd.pic' => 'pic',
                'pd.publish_time' => 'publish_time',
                'pd.status' => 'detail_status',
                'pd.remark' => 'detail_remark',
            ])
            ->order('dt.start_time', 'asc')
            ->order('dt.id', 'asc')
            ->select()
            ->toArray();
    }

    private function getWechatCircleRows(): array
    {
        $rows = $this->wechatCircleQuery()
            ->field([
                'dt.id' => 'task_id',
                'dt.device_code' => 'device_code',
                'dt.account' => 'account',
                'dt.account_type' => 'task_account_type',
                'dt.nickname' => 'task_nickname',
                'dt.avatar' => 'task_avatar',
                'dt.task_name' => 'task_name',
                'dt.status' => 'task_status',
                'dt.remark' => 'task_remark',
                'dt.start_time' => 'start_time',
                'dt.end_time' => 'end_time',
                'dt.day' => 'day',
                'dt.time_config' => 'time_config',
                'ct.wechat_id' => 'detail_account',
                'ct.id' => 'detail_id',
                'ct.attachment_type' => 'circle_attachment_type',
                'ct.attachment_content' => 'circle_attachment_content',
                'ct.content' => 'circle_content',
                'ct.send_time' => 'circle_send_time',
                'ct.send_status' => 'circle_send_status',
                'ct.shanjian_video_task_id' => 'shanjian_video_task_id',
            ])
            ->order('dt.start_time', 'asc')
            ->order('dt.id', 'asc')
            ->select()
            ->toArray();

        return array_map(fn($row) => $this->formatWechatCircleRow($row), $rows);
    }

    private function applyWechatCircleStatusFilter(Query $query): void
    {
        if ($this->hasStatusFilter()) {
            $status = (int)$this->params['status'];
            $sendStatuses = $this->deviceStatusToWechatCircleSendStatuses($status);
            if (empty($sendStatuses)) {
                $query->where('dt.id', '=', 0);
                return;
            }

            $query->where('ct.send_status', 'in', $sendStatuses);
            return;
        }

        $query->where('dt.status', '<>', DeviceEnum::TASK_STATUS_INTERRUPTED)
            ->where('ct.send_status', '<>', 4);
    }

    private function formatWechatCircleRow(array $row): array
    {
        $attachmentType = (int)($row['circle_attachment_type'] ?? 0);
        $attachmentUrl = $this->firstAttachmentUrl($row['circle_attachment_content'] ?? []);
        $mediaType = $this->wechatCircleMediaType($attachmentType);
        $videoPic = \app\common\model\shanjian\ShanjianVideoTask::where('id', $row['shanjian_video_task_id'])->value('pic');
        return [
            'task_id' => (int)($row['task_id'] ?? 0),
            'device_code' => (string)($row['device_code'] ?? ''),
            'account' => (string)($row['account'] ?? ''),
            'task_account_type' => DeviceEnum::PUBLISH_PLATFORM_WX,
            'task_nickname' => (string)($row['task_nickname'] ?? ''),
            'task_avatar' => (string)($row['task_avatar'] ?? ''),
            'task_name' => (string)($row['task_name'] ?? ''),
            'task_status' => (int)($row['task_status'] ?? 0),
            'task_remark' => (string)($row['task_remark'] ?? ''),
            'start_time' => $row['start_time'] ?? 0,
            'end_time' => $row['end_time'] ?? 0,
            'day' => $row['day'] ?? '',
            'time_config' => $row['time_config'] ?? '',
            'publish_account_id' => 0,
            'publish_account_name' => '',
            'publish_account' => '',
            'publish_account_type' => DeviceEnum::PUBLISH_PLATFORM_WX,
            'publish_nickname' => '',
            'publish_avatar' => '',
            'detail_account' => (string)($row['detail_account'] ?? ''),
            'detail_id' => (int)($row['detail_id'] ?? 0),
            'platform' => DeviceEnum::PUBLISH_PLATFORM_WX,
            'media_type' => $mediaType,
            'material_title' => '',
            'material_subtitle' => (string)($row['circle_content'] ?? ''),
            'material_tag' => '',
            'material_url' => $attachmentUrl,
            'pic' => $mediaType === 2 ? $attachmentUrl : $videoPic,
            'publish_time' => (string)($row['circle_send_time'] ?? ''),
            'detail_status' => $this->wechatCircleDetailStatus((int)($row['circle_send_status'] ?? 0)),
            'detail_remark' => '',
        ];
    }

    private function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $rows = array_merge($this->getPublishRows(), $this->getWechatCircleRows());
        usort($rows, static function ($left, $right) {
            $startCompare = ((int)($left['start_time'] ?? 0)) <=> ((int)($right['start_time'] ?? 0));
            if ($startCompare !== 0) {
                return $startCompare;
            }
            return ((int)($left['task_id'] ?? 0)) <=> ((int)($right['task_id'] ?? 0));
        });

        $this->rows = $rows;

        return $this->rows;
    }

    private function getGroups(): array
    {
        if ($this->groups !== null) {
            return $this->groups;
        }

        $groups = [];
        foreach ($this->getRows() as $row) {
            $slot = $this->timeSlot($row);
            $slotKey = $slot['slot_key'];
            $statusKey = $this->resolveStatusKey($row);
            $deviceKey = (string)($row['device_code'] ?? '');

            if (!isset($groups[$slotKey])) {
                $groups[$slotKey] = [
                    'slot_key' => $slotKey,
                    'time_config' => $slot['time_config'],
                    'task_id' => (int)$row['task_id'],
                    'task_ids' => [],
                    'time_range' => $slot['time_range'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'status' => DeviceEnum::TASK_STATUS_WAIT,
                    'status_text' => $this->groupStatusText(DeviceEnum::TASK_STATUS_WAIT),
                    'total_count' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'running_count' => 0,
                    'waiting_count' => 0,
                    'is_all_done' => false,
                    'devices' => [],
                ];
            }

            $groups[$slotKey]['task_ids'][] = (int)$row['task_id'];
            $groups[$slotKey]['total_count']++;
            $this->incrementStatusCount($groups[$slotKey], $statusKey);

            if (!isset($groups[$slotKey]['devices'][$deviceKey])) {
                $groups[$slotKey]['devices'][$deviceKey] = [
                    'device_code' => (string)($row['device_code'] ?? ''),
                    'account' => '',
                    'nickname' => '',
                    'avatar' => '',
                    'accounts' => [],
                    '_account_keys' => [],
                    'items' => [],
                ];
            }

            $this->appendDeviceAccount($groups[$slotKey]['devices'][$deviceKey], $row);
            $groups[$slotKey]['devices'][$deviceKey]['items'][] = $this->formatItem($row);
        }

        foreach ($groups as &$group) {
            $group['task_ids'] = array_values(array_unique($group['task_ids']));
            $group['is_all_done'] = $group['total_count'] > 0 && $group['success_count'] === $group['total_count'];
            $group['status'] = $this->resolveGroupTimeStatus($group);
            $group['status_text'] = $this->groupStatusText($group['status']);
            foreach ($group['devices'] as &$device) {
                unset($device['_account_keys']);
            }
            unset($device);
            $group['devices'] = array_values($group['devices']);
        }
        unset($group);

        $this->groups = array_values($groups);
        return $this->groups;
    }

    private function getSummary(): array
    {
        if ($this->summary !== null) {
            return $this->summary;
        }

        $summary = [
            'total_count' => 0,
            'device_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'running_count' => 0,
            'waiting_count' => 0,
            'is_all_done' => false,
            'tabs' => [],
            'status_tabs' => [],
        ];
        $devices = [];

        foreach ($this->getRows() as $row) {
            $summary['total_count']++;
            $devices[(string)($row['device_code'] ?? '')] = true;
            $this->incrementStatusCount($summary, $this->resolveStatusKey($row));
        }

        $summary['device_count'] = count(array_filter(array_keys($devices), static fn($item) => $item !== ''));
        $summary['is_all_done'] = $summary['total_count'] > 0 && $summary['success_count'] === $summary['total_count'];
        $summary['tabs'] = $this->buildTimeTabs();
        $summary['status_tabs'] = [
            ['status' => 'all', 'name' => '全部', 'count' => $summary['total_count']],
            ['status' => DeviceEnum::TASK_STATUS_FINISHED, 'name' => '已完成', 'count' => $summary['success_count']],
            ['status' => DeviceEnum::TASK_STATUS_RUNNING, 'name' => '执行中', 'count' => $summary['running_count']],
            ['status' => DeviceEnum::TASK_STATUS_WAIT, 'name' => '待执行', 'count' => $summary['waiting_count']],
            ['status' => DeviceEnum::TASK_STATUS_FAILED, 'name' => '发布失败', 'count' => $summary['failed_count']],
        ];

        $this->summary = $summary;
        return $this->summary;
    }

    private function formatItem(array $row): array
    {
        $platform = (int)($row['platform'] ?: ($row['publish_account_type'] ?: $row['task_account_type']));
        $mediaType = (int)($row['media_type'] ?? 0);
        $logs = $this->getTaskLogs((int)$row['task_id']);
        $account = $this->rowAccount($row);
        $nickname = $this->rowNickname($row);
        $avatar = $this->rowAvatar($row);
        $taskStatus = $this->resolveItemTaskStatus($row);

        return [
            'task_id' => (int)$row['task_id'],
            'detail_id' => (int)$row['detail_id'],
            'device_code' => (string)($row['device_code'] ?? ''),
            'account' => $account,
            'nickname' => $nickname,
            'avatar' => $this->formatFileUrl($avatar),
            'platform' => $platform,
            'platform_name' => $this->platformName($platform),
            'media_type' => $mediaType,
            'media_type_text' => $this->mediaTypeText($mediaType),
            'material_title' => (string)($row['material_title'] ?? ''),
            'material_subtitle' => (string)($row['material_subtitle'] ?? ''),
            'material_tag' => (string)($row['material_tag'] ?? ''),
            'material_url' => (string)($row['material_url'] ?? ''),
            'pic' => $this->formatFileUrl((string)($row['pic'] ?? '')),
            'publish_time' => (string)($row['publish_time'] ?? ''),
            'task_status' => $taskStatus,
            'task_status_text' => $this->taskStatusText($taskStatus),
            'remark' => $this->resolvePublishRemark($row),
            'latest_log' => $logs ? end($logs) : [],
            'logs' => $logs,
        ];
    }

    /** 明细失败(status=2)时对外统一展示为执行失败 */
    private function resolveItemTaskStatus(array $row): int
    {
        $detailStatus = (int)($row['detail_status'] ?? 0);
        $taskStatus = (int)($row['task_status'] ?? 0);
        if ($detailStatus === 2 || $taskStatus === DeviceEnum::TASK_STATUS_FAILED) {
            return DeviceEnum::TASK_STATUS_FAILED;
        }
        if ($detailStatus === 1 || $taskStatus === DeviceEnum::TASK_STATUS_FINISHED) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }
        if ($detailStatus === 3 || $taskStatus === DeviceEnum::TASK_STATUS_RUNNING) {
            return DeviceEnum::TASK_STATUS_RUNNING;
        }
        return $taskStatus;
    }

    /**
     * 失败原因优先取服务端系统错误(算力不足等), 避免设备进度文案/英文占位覆盖
     */
    private function resolvePublishRemark(array $row): string
    {
        $detailRemark = trim((string)($row['detail_remark'] ?? ''));
        $taskRemark = trim((string)($row['task_remark'] ?? ''));

        foreach ([$taskRemark, $detailRemark] as $remark) {
            if ($remark !== '' && (str_contains($remark, '算力不足') || str_starts_with($remark, '任务执行失败'))) {
                return $remark;
            }
        }

        $noise = [
            'publish dispatch failed',
            '发布下发失败',
            '收到发布任务',
            '任务执行中',
            'RPA执行：收到发布任务',
            'RPA执行：',
        ];
        if ($detailRemark !== '' && !in_array($detailRemark, $noise, true)) {
            return $detailRemark;
        }
        if ($taskRemark !== '' && !in_array($taskRemark, $noise, true)) {
            return $taskRemark;
        }

        return $detailRemark !== '' ? $detailRemark : $taskRemark;
    }

    private function appendDeviceAccount(array &$device, array $row): void
    {
        $account = $this->rowAccount($row);
        $nickname = $this->rowNickname($row);
        $avatar = $this->rowAvatar($row);
        $platform = (int)($row['platform'] ?: ($row['publish_account_type'] ?: $row['task_account_type']));
        $accountKey = $account !== '' ? $account . '|' . $platform : (string)($row['publish_account_id'] ?? $row['detail_id'] ?? '');

        if ($device['account'] === '' && $account !== '') {
            $device['account'] = $account;
        }
        if ($device['nickname'] === '' && $nickname !== '') {
            $device['nickname'] = $nickname;
        }
        if ($device['avatar'] === '' && $avatar !== '') {
            $device['avatar'] = $this->formatFileUrl($avatar);
        }

        if (!isset($device['_account_keys'][$accountKey])) {
            $device['_account_keys'][$accountKey] = true;
            $device['accounts'][] = [
                'account' => $account,
                'nickname' => $nickname,
                'avatar' => $this->formatFileUrl($avatar),
                'platform' => $platform,
                'platform_name' => $this->platformName($platform),
            ];
        }
    }

    private function buildTimeTabs(): array
    {
        $tabs = [];
        foreach ($this->getGroups() as $group) {
            $tabs[] = [
                'slot_key' => $group['slot_key'],
                'time_config' => $group['time_config'],
                'time_range' => $group['time_range'],
                'start_time' => $group['start_time'],
                'end_time' => $group['end_time'],
                'status' => $group['status'],
                'status_text' => $group['status_text'],
                'total_count' => $group['total_count'],
                'success_count' => $group['success_count'],
                'failed_count' => $group['failed_count'],
                'running_count' => $group['running_count'],
                'waiting_count' => $group['waiting_count'],
                'device_count' => count($group['devices']),
                'is_all_done' => $group['is_all_done'],
            ];
        }
        return $tabs;
    }

    private function getLogsByTaskId(): array
    {
        if ($this->logsByTaskId !== null) {
            return $this->logsByTaskId;
        }

        $taskIds = array_values(array_unique(array_map(static fn($row) => (int)$row['task_id'], $this->getRows())));
        if (empty($taskIds)) {
            $this->logsByTaskId = [];
            return $this->logsByTaskId;
        }

        $logs = SvDeviceTaskLog::where('user_id', '=', $this->userId)
            ->where('task_id', 'in', $taskIds)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();

        $grouped = [];
        foreach ($logs as $log) {
            $taskId = (int)($log['task_id'] ?? 0);
            $grouped[$taskId][] = $log;
        }

        $result = [];
        foreach ($grouped as $taskId => $taskLogs) {
            $result[$taskId] = array_map(function (array $log) use ($taskId) {
                return [
                    'id' => (int)($log['id'] ?? 0),
                    'task_id' => $taskId,
                    'message' => (string)($log['message'] ?? ''),
                    'image' => $this->formatFileUrl((string)($log['image'] ?? '')),
                    'create_time' => $log['create_time'] ?? '',
                ];
            }, $this->deduplicateTaskLogs($taskLogs));
        }

        $this->logsByTaskId = $result;
        return $this->logsByTaskId;
    }

    private function deduplicateTaskLogs(array $logs): array
    {
        if (empty($logs)) {
            return [];
        }

        $latestByKey = [];
        foreach ($logs as $log) {
            $key = implode('|', [
                (string)($log['task_source'] ?? ''),
                (string)($log['device_code'] ?? ''),
                (string)($log['message'] ?? ''),
            ]);
            if (!isset($latestByKey[$key]) || (int)($log['id'] ?? 0) > (int)($latestByKey[$key]['id'] ?? 0)) {
                $latestByKey[$key] = $log;
                continue;
            }
            if ((int)($log['id'] ?? 0) === (int)($latestByKey[$key]['id'] ?? 0)
                && (int)($log['create_time'] ?? 0) > (int)($latestByKey[$key]['create_time'] ?? 0)) {
                $latestByKey[$key] = $log;
            }
        }

        $deduped = array_values($latestByKey);
        usort($deduped, static function (array $left, array $right) {
            $timeCompare = ((int)($left['create_time'] ?? 0)) <=> ((int)($right['create_time'] ?? 0));
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            return ((int)($left['id'] ?? 0)) <=> ((int)($right['id'] ?? 0));
        });

        return $deduped;
    }

    private function getTaskLogs(int $taskId): array
    {
        $logs = $this->getLogsByTaskId();
        return $logs[$taskId] ?? [];
    }

    private function incrementStatusCount(array &$target, string $statusKey): void
    {
        if ($statusKey === 'success') {
            $target['success_count']++;
            return;
        }
        if ($statusKey === 'failed') {
            $target['failed_count']++;
            return;
        }
        if ($statusKey === 'running') {
            $target['running_count']++;
            return;
        }
        if ($statusKey === 'waiting') {
            $target['waiting_count']++;
        }
    }

    private function resolveStatusKey(array $row): string
    {
        $detailStatus = (int)($row['detail_status'] ?? 0);
        $taskStatus = (int)($row['task_status'] ?? 0);

        if ($detailStatus === DeviceEnum::TASK_STATUS_INTERRUPTED || $taskStatus === DeviceEnum::TASK_STATUS_INTERRUPTED) {
            return 'interrupted';
        }
        if ($detailStatus === 1 || $taskStatus === DeviceEnum::TASK_STATUS_FINISHED) {
            return 'success';
        }
        if ($detailStatus === 2 || $taskStatus === DeviceEnum::TASK_STATUS_FAILED) {
            return 'failed';
        }
        if ($detailStatus === 3 || $taskStatus === DeviceEnum::TASK_STATUS_RUNNING) {
            return 'running';
        }
        return 'waiting';
    }

    private function resolveGroupStatus(array $group): int
    {
        if ($group['is_all_done']) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }
        if ($group['failed_count'] > 0) {
            return DeviceEnum::TASK_STATUS_FAILED;
        }
        if ($group['running_count'] > 0) {
            return DeviceEnum::TASK_STATUS_RUNNING;
        }
        if ($group['waiting_count'] > 0) {
            return DeviceEnum::TASK_STATUS_WAIT;
        }
        return DeviceEnum::TASK_STATUS_INTERRUPTED;
    }

    private function resolveGroupTimeStatus(array $group): int
    {
        $startTime = trim((string)($group['start_time'] ?? ''));
        $endTime = trim((string)($group['end_time'] ?? ''));
        if (!$this->isClockTime($startTime) || !$this->isClockTime($endTime)) {
            return $this->resolveGroupStatus($group);
        }

        $dateTimestamp = strtotime($this->getDate() . ' 00:00:00');
        if ($dateTimestamp === false) {
            return $this->resolveGroupStatus($group);
        }

        $date = date('Y-m-d', $dateTimestamp);
        $today = date('Y-m-d');
        if ($date < $today) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }
        if ($date > $today) {
            return DeviceEnum::TASK_STATUS_WAIT;
        }

        $startAt = strtotime($date . ' ' . $startTime . ':00');
        $endAt = strtotime($date . ' ' . $endTime . ':00');
        if ($startAt === false || $endAt === false) {
            return $this->resolveGroupStatus($group);
        }
        if ($endAt < $startAt) {
            $endAt += 86400;
        }

        $now = time();
        if ($endAt < $now) {
            return DeviceEnum::TASK_STATUS_FINISHED;
        }
        if ($startAt > $now) {
            return DeviceEnum::TASK_STATUS_WAIT;
        }
        return DeviceEnum::TASK_STATUS_RUNNING;
    }

    private function timeSlot(array $row): array
    {
        $timeConfig = $this->normalizeTimeConfig($row['time_config'] ?? '');
        $range = $this->parseTimeRange($timeConfig);

        if (!empty($range)) {
            return [
                'slot_key' => 'time_config:' . $range['time_range'],
                'time_config' => $timeConfig,
                'time_range' => $range['time_range'],
                'start_time' => $range['start_time'],
                'end_time' => $range['end_time'],
            ];
        }

        $startTime = $this->formatClock($row['start_time'] ?? 0);
        $endTime = $this->formatClock($row['end_time'] ?? 0);
        $timeRange = trim($startTime . ' - ' . $endTime);

        return [
            'slot_key' => 'time_config:' . ($timeConfig !== '' ? $timeConfig : $timeRange),
            'time_config' => $timeConfig,
            'time_range' => $timeConfig !== '' ? $timeConfig : $timeRange,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    private function normalizeTimeConfig($timeConfig): string
    {
        if (is_array($timeConfig)) {
            $value = $this->firstTimeConfigValue($timeConfig);
            return $this->normalizeTimeRangeText($value);
        }

        $timeConfig = trim((string)$timeConfig);
        if ($timeConfig === '') {
            return '';
        }

        $decoded = json_decode($timeConfig, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return $this->normalizeTimeRangeText($this->firstTimeConfigValue($decoded));
            }
            if (is_scalar($decoded)) {
                return $this->normalizeTimeRangeText((string)$decoded);
            }
        }

        return $this->normalizeTimeRangeText($timeConfig);
    }

    private function firstTimeConfigValue(array $timeConfig): string
    {
        if (isset($timeConfig['times']) && is_array($timeConfig['times'])) {
            return $this->firstTimeConfigValue($timeConfig['times']);
        }
        if (isset($timeConfig['start_time'], $timeConfig['end_time'])) {
            return $this->formatClock($timeConfig['start_time']) . '-' . $this->formatClock($timeConfig['end_time']);
        }

        foreach ($timeConfig as $value) {
            if (is_array($value)) {
                $nested = $this->firstTimeConfigValue($value);
                if ($nested !== '') {
                    return $nested;
                }
                continue;
            }
            if (is_scalar($value) && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        return '';
    }

    private function normalizeTimeRangeText(string $timeRange): string
    {
        $timeRange = trim($timeRange);
        if ($timeRange === '') {
            return '';
        }

        $timeRange = str_replace(['～', '—', '–', '~', '至'], '-', $timeRange);
        $timeRange = preg_replace('/\s+/', '', $timeRange);
        $range = $this->parseTimeRange($timeRange);

        return !empty($range) ? $range['time_range'] : $timeRange;
    }

    private function parseTimeRange(string $timeRange): array
    {
        $timeRange = str_replace(['～', '—', '–', '~', '至'], '-', trim($timeRange));
        $timeRange = preg_replace('/\s+/', '', $timeRange);

        if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $timeRange, $matches)) {
            return [];
        }

        $startHour = min(23, max(0, (int)$matches[1]));
        $startMinute = min(59, max(0, (int)$matches[2]));
        $endHour = min(23, max(0, (int)$matches[3]));
        $endMinute = min(59, max(0, (int)$matches[4]));
        $startTime = sprintf('%02d:%02d', $startHour, $startMinute);
        $endTime = sprintf('%02d:%02d', $endHour, $endMinute);

        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_range' => $startTime . ' - ' . $endTime,
        ];
    }

    private function deviceStatusToWechatCircleSendStatuses(int $status): array
    {
        $map = [
            DeviceEnum::TASK_STATUS_WAIT => [0],
            DeviceEnum::TASK_STATUS_RUNNING => [1],
            DeviceEnum::TASK_STATUS_FINISHED => [2],
            DeviceEnum::TASK_STATUS_FAILED => [3],
            DeviceEnum::TASK_STATUS_INTERRUPTED => [4],
        ];
        return $map[$status] ?? [];
    }

    private function wechatCircleDetailStatus(int $sendStatus): int
    {
        $map = [
            0 => 0,
            1 => 3,
            2 => 1,
            3 => 2,
            4 => DeviceEnum::TASK_STATUS_INTERRUPTED,
        ];
        return $map[$sendStatus] ?? 0;
    }

    private function wechatCircleMediaType(int $attachmentType): int
    {
        if (in_array($attachmentType, [2, 3], true)) {
            return 1;
        }
        if ($attachmentType === 1) {
            return 2;
        }
        return 3;
    }

    private function firstAttachmentUrl($attachmentContent): string
    {
        $attachments = $this->normalizeAttachmentContent($attachmentContent);
        foreach ($attachments as $attachment) {
            if (is_string($attachment) || is_numeric($attachment)) {
                $url = trim((string)$attachment);
                if ($url !== '') {
                    return $url;
                }
                continue;
            }

            if (!is_array($attachment)) {
                continue;
            }

            foreach (['url', 'file_url', 'uri', 'path'] as $field) {
                if (!empty($attachment[$field])) {
                    return trim((string)$attachment[$field]);
                }
            }
        }

        return '';
    }

    private function normalizeAttachmentContent($attachmentContent): array
    {
        if (is_array($attachmentContent)) {
            return array_values($attachmentContent);
        }

        if (!is_string($attachmentContent)) {
            return [];
        }

        $attachmentContent = trim($attachmentContent);
        if ($attachmentContent === '') {
            return [];
        }

        $decoded = json_decode($attachmentContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return array_values($decoded);
            }
            if (is_scalar($decoded) && trim((string)$decoded) !== '') {
                return [(string)$decoded];
            }
        }

        return [$attachmentContent];
    }

    private function rowAccount(array $row): string
    {
        return (string)(($row['account'] ?? '') ?: (($row['publish_account'] ?? '') ?: ($row['detail_account'] ?? '')));
    }

    private function rowNickname(array $row): string
    {
        return (string)(($row['publish_nickname'] ?? '') ?: ($row['task_nickname'] ?? ''));
    }

    private function rowAvatar(array $row): string
    {
        return (string)(($row['publish_avatar'] ?? '') ?: ($row['task_avatar'] ?? ''));
    }

    private function publishTaskTypes(): array
    {
        return [DeviceEnum::TASK_TYPE_PUBLISH, DeviceEnum::AUTO_TYPE_PUBLISH];
    }

    private function getDate(): string
    {
        return !empty($this->params['date']) ? (string)$this->params['date'] : date('Y-m-d');
    }

    private function getQueryTimeRange(): string
    {
        foreach (['time_config', 'time_range', 'slot_key'] as $field) {
            if (empty($this->params[$field])) {
                continue;
            }

            $value = trim((string)$this->params[$field]);
            if ($field === 'slot_key' && str_starts_with($value, 'time_config:')) {
                $value = substr($value, strlen('time_config:'));
            }

            $timeRange = $this->normalizeTimeRangeText($value);
            if ($timeRange !== '') {
                return $timeRange;
            }
        }

        $startTime = trim((string)(($this->params['time_start'] ?? '') ?: ($this->params['start_time'] ?? '')));
        $endTime = trim((string)(($this->params['time_end'] ?? '') ?: ($this->params['end_time'] ?? '')));
        if ($this->isClockTime($startTime) && $this->isClockTime($endTime)) {
            return $this->normalizeTimeRangeText($startTime . '-' . $endTime);
        }

        return '';
    }

    private function isClockTime(string $time): bool
    {
        return (bool)preg_match('/^\d{1,2}:\d{2}$/', $time);
    }

    private function hasStatusFilter(): bool
    {
        return isset($this->params['status']) && $this->params['status'] !== '';
    }

    private function formatClock($time): string
    {
        if (empty($time)) {
            return '';
        }
        if (is_numeric($time)) {
            return date('H:i', (int)$time);
        }
        $timestamp = strtotime((string)$time);
        return $timestamp ? date('H:i', $timestamp) : (string)$time;
    }

    private function formatFileUrl(string $url): string
    {
        return $url === '' ? '' : FileService::getFileUrl($url);
    }

    private function platformName(int $platform): string
    {
        if ($platform === DeviceEnum::PUBLISH_PLATFORM_WX) {
            return '朋友圈';
        }

        $map = [
            DeviceEnum::ACCOUNT_TYPE_SPH => '视频号',
            DeviceEnum::ACCOUNT_TYPE_XHS => '小红书',
            DeviceEnum::ACCOUNT_TYPE_DY => '抖音',
            DeviceEnum::ACCOUNT_TYPE_KS => '快手',
        ];
        return $map[$platform] ?? '未知';
    }

    private function mediaTypeText(int $mediaType): string
    {
        $map = [
            1 => '短视频',
            2 => '图文',
            3 => '文案',
        ];
        return $map[$mediaType] ?? '未知';
    }

    private function detailStatusText(int $status): string
    {
        $map = [
            0 => '待发布',
            1 => '发布成功',
            2 => '发布失败',
            3 => '发布中',
            4 => '已删除',
        ];
        return $map[$status] ?? '未知';
    }

    private function taskStatusText(int $status): string
    {
        $map = [
            DeviceEnum::TASK_STATUS_WAIT => '待执行',
            DeviceEnum::TASK_STATUS_RUNNING => '执行中',
            DeviceEnum::TASK_STATUS_FINISHED => '已完成',
            DeviceEnum::TASK_STATUS_FAILED => '执行失败',
            DeviceEnum::TASK_STATUS_INTERRUPTED => '已中断',
        ];
        return $map[$status] ?? '未知';
    }

    private function groupStatusText(int $status, bool $isAllDone = false): string
    {
        if ($isAllDone) {
            return '全部完成';
        }

        $map = [
            DeviceEnum::TASK_STATUS_WAIT => '待执行',
            DeviceEnum::TASK_STATUS_RUNNING => '执行中',
            DeviceEnum::TASK_STATUS_FINISHED => '已完成',
            DeviceEnum::TASK_STATUS_FAILED => '有失败',
            DeviceEnum::TASK_STATUS_INTERRUPTED => '已中断',
        ];
        return $map[$status] ?? '未知';
    }
}
