<?php

namespace app\adminapi\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\model\hotspot\HotspotTask;
use app\common\model\user\User;
use app\common\service\hotspot\HotspotLog;
use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\TaskService;
use app\common\service\hotspot\VideoService;

class TaskLogic extends BaseLogic
{
    public static function detail(int $id): array|false
    {
        $row = HotspotTask::where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }

        $task = TaskService::detail((string)$row->task_no);
        if ($task === null) {
            self::setError('任务不存在');
            return false;
        }
        // 与 C 端详情一致做一次成片补偿，后台查看即可救活漏回写的任务
        $task = VideoService::compensate($task);

        $nickname = '';
        if ((int)$row->user_id > 0) {
            $nickname = (string)User::where('id', (int)$row->user_id)->value('nickname');
        }
        $task['db_id'] = (int)$row->id;
        $task['user'] = $nickname !== '' ? $nickname : '体验用户';
        return $task;
    }

    public static function retry(int $id): array|false
    {
        $row = HotspotTask::where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }
        try {
            $result = TaskService::resetForRetry((string)$row->task_no, (int)$row->user_id);
            $result = VideoService::retryOrEnqueue($result, (int)$row->user_id);
            HotspotLog::write(sprintf(
                '后台重试热点任务：任务号=%s 用户=%d 重试序号=%d',
                (string)$row->task_no,
                (int)$row->user_id,
                (int)($result['options']['retry_seq'] ?? 0)
            ));
            return $result;
        } catch (HotspotUpstreamException $e) {
            self::setError($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            HotspotLog::exception('后台重试热点任务异常', $e);
            self::setError('服务异常，请稍后再试');
            return false;
        }
    }

    public static function delete(mixed $id): bool
    {
        $ids = self::normalizeIds($id);
        if ($ids === []) {
            self::setError('id参数缺失');
            return false;
        }
        $rows = HotspotTask::whereIn('id', $ids)->select();
        if ($rows->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }
        foreach ($rows as $row) {
            if ((string)$row->status === 'running') {
                self::setError('任务 ' . (string)$row->task_no . ' 合成中，请等完成或失败后再删除');
                return false;
            }
        }
        foreach ($rows as $row) {
            $row->delete();
            // 同步软删关联创作记录，避免台账残留「排队合成中」
            TaskService::deleteBoundCreation((string)$row->task_no);
        }
        HotspotLog::write('后台删除热点任务：数量=' . count($rows));
        return true;
    }

    private static function normalizeIds(mixed $id): array
    {
        if (is_array($id)) {
            $raw = $id;
        } elseif ($id === '' || $id === null) {
            $raw = [];
        } else {
            $raw = [$id];
        }
        $out = [];
        foreach ($raw as $item) {
            $item = (int)$item;
            if ($item > 0) {
                $out[] = $item;
            }
        }
        return array_values(array_unique($out));
    }
}
