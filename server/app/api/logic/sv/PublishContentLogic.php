<?php

namespace app\api\logic\sv;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

/**
 * 今日待发布内容
 */
class PublishContentLogic extends SvBaseLogic
{
    private const SOURCE_SV = 'sv';
    private const SOURCE_CIRCLE = 'circle';

    private const PLATFORM_CIRCLE = 'circle';

    private const PLATFORMS = [
        ['platform' => 4, 'platform_name' => '抖音', 'source' => self::SOURCE_SV],
        ['platform' => 3, 'platform_name' => '小红书', 'source' => self::SOURCE_SV],
        ['platform' => 5, 'platform_name' => '快手', 'source' => self::SOURCE_SV],
        ['platform' => 1, 'platform_name' => '视频号', 'source' => self::SOURCE_SV],
        ['platform' => self::PLATFORM_CIRCLE, 'platform_name' => '朋友圈', 'source' => self::SOURCE_CIRCLE],
    ];

    private const MEDIA_LABELS = [
        1 => '视频',
        2 => '图片',
    ];

    /**
     * @desc 获取当天待发布内容
     */
    public static function lists(array $params): bool
    {
        try {
            [$start, $end, $date] = self::getDateRange($params['date'] ?? '');
            $platform = self::normalizePlatform($params['platform'] ?? self::PLATFORM_CIRCLE);
            $filter = self::getContentFilter($params);
            $tabs = self::getTabs($start, $end, $filter);
            $current = self::getPlatformMeta($platform);

            if ($current['source'] === self::SOURCE_CIRCLE) {
                $lists = self::getCircleLists($start, $end, $filter);
            } else {
                $lists = self::getSvLists((int)$current['platform'], $start, $end, $filter);
            }

            self::$returnData = [
                'date' => $date,
                'persona_id' => $filter['persona_id'],
                'platform' => $current['platform'],
                'platform_name' => $current['platform_name'],
                'source' => $current['source'],
                'tabs' => $tabs,
                'lists' => $lists,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 保存当天待发布内容
     */
    public static function save(array $params): bool
    {
        try {
            [$start, $end] = self::getDateRange($params['date'] ?? '');
            $source = (string)$params['source'];
            $filter = self::getContentFilter($params, false);

            if ($source === self::SOURCE_CIRCLE) {
                self::$returnData = self::saveCircle((int)$params['id'], $params, $start, $end, $filter);
                return true;
            }

            self::$returnData = self::saveSv((int)$params['id'], $params, $start, $end, $filter);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 重新生成闪剪视频
     */
    public static function regenerate(array $params): bool
    {
        try {
            $now = time();
            $filter = self::getContentFilter($params, false);
            $source = self::resolveRegenerateSource((int)$params['id'], $params, $filter);
            if ($source === self::SOURCE_CIRCLE) {
                self::$returnData = self::regenerateCircle((int)$params['id'], $params, $now, $filter);
                return true;
            }

            self::$returnData = self::regenerateSv((int)$params['id'], $params, $now, $filter);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function resolveRegenerateSource(int $id, array $params, array $filter): string
    {
        $requestTaskId = (int)($params['shanjian_video_task_id'] ?? 0);
        if ($requestTaskId > 0) {
            return self::getRegenerateSourceByTaskId($requestTaskId);
        }

        $candidates = [];
        $svQuery = SvPublishSettingDetail::where('id', $id)
            ->where('user_id', self::$uid)
            ->where('status', 0)
            ->where('account_type', 'in', [1, 3, 4, 5])
            ->where('material_type', 1);
        self::applyRecordFilter($svQuery, $filter);
        $svTaskId = (int)$svQuery->value('video_task_id');
        if ($svTaskId > 0) {
            $source = self::getRegenerateSourceByTaskId($svTaskId, false);
            if ($source !== '') {
                $candidates[] = ['record_source' => self::SOURCE_SV, 'task_source' => $source];
            }
        }

        $circleQuery = AiWechatCircleTask::where('id', $id)
            ->where('user_id', self::$uid)
            ->where('send_status', 0)
            ->where('attachment_type', 'in', [2, 3]);
        self::applyRecordFilter($circleQuery, $filter);
        $circleRecord = $circleQuery->findOrEmpty();
        if (!$circleRecord->isEmpty()) {
            $circleTaskId = self::getCircleTaskShanjianVideoTaskId($circleRecord);
            if ($circleTaskId > 0) {
                $source = self::getRegenerateSourceByTaskId($circleTaskId, false);
                if ($source !== '') {
                    $candidates[] = ['record_source' => self::SOURCE_CIRCLE, 'task_source' => $source];
                }
            }
        }

        $matched = array_values(array_filter($candidates, function (array $candidate) {
            return $candidate['record_source'] === $candidate['task_source'];
        }));
        if (count($matched) === 1) {
            return $matched[0]['task_source'];
        }
        if (count($matched) > 1 || count($candidates) > 1) {
            throw new \Exception('发布内容来源不明确，请传入壹传媒视频任务id');
        }
        if (count($candidates) === 1) {
            return $candidates[0]['task_source'];
        }

        throw new \Exception('待发布视频任务不存在或未关联壹传媒视频任务');
    }

    private static function getRegenerateSourceByTaskId(int $taskId, bool $throw = true): string
    {
        if ($taskId <= 0) {
            if ($throw) {
                throw new \Exception('壹传媒视频任务id不能为空');
            }
            return '';
        }

        $task = ShanjianVideoTask::field('id,wechat_type')
            ->where('id', $taskId)
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            if ($throw) {
                throw new \Exception('壹传媒视频任务不存在');
            }
            return '';
        }

        return (int)$task->wechat_type === 1 ? self::SOURCE_CIRCLE : self::SOURCE_SV;
    }

    private static function assertPublishTimeNotStarted(string $publishTime, int $now): void
    {
        $publishAt = strtotime($publishTime);
        if (!$publishAt) {
            throw new \Exception('发布时间格式错误');
        }
        if ($publishAt <= $now) {
            throw new \Exception('任务已到发布时间，不能重新生成');
        }
    }

    private static function getContentFilter(array $params, bool $required = true): array
    {
        $personaId = (int)($params['persona_id'] ?? 0);
        if ($personaId <= 0) {
            if (!$required) {
                return [];
            }
            throw new \Exception('IP人设ID不能为空');
        }

        $persona = AiPersona::where('id', $personaId)
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \Exception('IP人设不存在');
        }

        return [
            'persona_id' => $personaId,
        ];
    }

    private static function applySvFilter($query, array $filter): void
    {
        $personaId = (int)($filter['persona_id'] ?? 0);
        if ($personaId <= 0) {
            return;
        }
        $query->where('ps.persona_id', $personaId);
    }

    private static function applyCircleFilter($query, array $filter): void
    {
        $personaId = (int)($filter['persona_id'] ?? 0);
        if ($personaId <= 0) {
            return;
        }
        $query->where(function ($query) use ($personaId) {
            $query->where('t.persona_id', $personaId)
                ->whereOr('tc.persona_id', $personaId);
        });
    }

    private static function applyRecordFilter($query, array $filter): void
    {
        $personaId = (int)($filter['persona_id'] ?? 0);
        if ($personaId <= 0) {
            return;
        }
        $query->where('persona_id', $personaId);
    }

    private static function applySvUnpublishedVideoFilter($query): void
    {
        $query->where(function ($query) {
            $query->whereNull('ps.video_task_id')
                ->whereOr('ps.video_task_id', '<=', 0)
                ->whereOr('ps.video_task_id', 'not in', function ($query) {
                    $query->name('sv_publish_setting_detail')
                        ->where('user_id', self::$uid)
                        ->where('video_task_id', '>', 0)
                        ->where('status', 'in', [1, 2])
                        ->whereNull('delete_time')
                        ->field('video_task_id');
                });
        });
    }

    private static function applyWaitingDeviceTaskFilter($query): void
    {
        $query->whereExists(function ($query) {
            $query->name('sv_device_task')
                ->whereColumn('user_id', 'ps.user_id')
                ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->whereNull('delete_time')
                ->where(function ($query) {
                    $query->whereColumn('sub_data_id', 'ps.id')
                        ->whereOr(function ($query) {
                            $query->where('sub_data_id', '<=', 0)
                                ->whereColumn('sub_task_id', 'ps.publish_account_id')
                                ->whereColumn('account', 'ps.account')
                                ->whereColumn('account_type', 'ps.account_type')
                                ->whereColumn('device_code', 'ps.device_code')
                                ->whereRaw('ps.publish_time BETWEEN FROM_UNIXTIME(start_time) AND FROM_UNIXTIME(end_time)');
                        });
                });
        });
    }

    private static function applyWaitingCircleDeviceTaskFilter($query): void
    {
        $query->whereExists(function ($query) {
            $query->name('sv_device_task')
                ->whereColumn('user_id', 't.user_id')
                ->where('source', DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->whereNull('delete_time')
                ->whereColumn('sub_data_id', 't.id');
        });
    }

    private static function getTabs(string $start, string $end, array $filter): array
    {
        $svCounts = [];
        $svQuery = self::getSvQuery()
            ->field('ps.account_type, COUNT(DISTINCT ps.id) as total')
            ->where('ps.user_id', self::$uid)
            ->where('ps.status', 0)
            ->where('ps.account_type', 'in', [1, 3, 4, 5])
            ->where('ps.material_type', 'in', [1, 2])
            ->whereBetween('ps.publish_time', [$start, $end])
            ->group('ps.account_type');
        self::applySvFilter($svQuery, $filter);
        self::applySvUnpublishedVideoFilter($svQuery);
        self::applyWaitingDeviceTaskFilter($svQuery);
        $rows = $svQuery->select()->toArray();
        foreach ($rows as $row) {
            $svCounts[(int)$row['account_type']] = (int)$row['total'];
        }

        $circleQuery = self::getCircleQuery()
            ->where('t.user_id', self::$uid)
            ->where('t.send_status', 0)
            ->where('t.attachment_type', 'in', [1, 2, 3])
            ->whereBetween('t.send_time', [$start, $end]);
        self::applyCircleFilter($circleQuery, $filter);
        self::applyWaitingCircleDeviceTaskFilter($circleQuery);
        $circleCount = $circleQuery->count();

        return array_map(function (array $tab) use ($svCounts, $circleCount) {
            $count = $tab['source'] === self::SOURCE_CIRCLE
                ? $circleCount
                : ($svCounts[(int)$tab['platform']] ?? 0);
            return $tab + ['count' => $count];
        }, self::PLATFORMS);
    }

    private static function getSvLists(int $platform, string $start, string $end, array $filter): array
    {
        $query = self::getSvQuery()
            ->where('ps.user_id', self::$uid)
            ->where('ps.status', 0)
            ->where('ps.account_type', $platform)
            ->where('ps.material_type', 'in', [1, 2])
            ->whereBetween('ps.publish_time', [$start, $end])
            ->order('ps.publish_time asc, ps.id asc');
        self::applySvFilter($query, $filter);
        self::applySvUnpublishedVideoFilter($query);
        self::applyWaitingDeviceTaskFilter($query);
        $rows = $query->select()->toArray();

        return array_map(function (array $row) {
            return self::formatSvItem($row);
        }, $rows);
    }

    private static function getSvQuery()
    {
        return SvPublishSettingDetail::alias('ps')
            ->field('ps.id,ps.user_id,ps.account,ps.account_type,ps.device_code,ps.publish_account_id,ps.persona_id,ps.auto_type,ps.material_type,ps.material_url,ps.material_title,ps.material_subtitle,ps.material_tag,ps.publish_time,ps.poi,ps.pic,ps.video_task_id,ps.video_setting_id,ps.task_type,sj.persona_id as shanjian_persona_id,sj.shanjian_type,sj.status as shanjian_status,sj.video_result_url as shanjian_video_result_url,sj.pic as shanjian_pic,sj.duration as shanjian_duration,sj.remark as shanjian_remark')
            ->leftJoin('shanjian_video_task sj', 'sj.id = ps.video_task_id AND sj.user_id = ps.user_id');
    }

    private static function getSvItem(int $id, array $filter = []): array
    {
        $filter = $filter ?: ['persona_id' => 0];
        $query = self::getSvQuery()
            ->where('ps.id', $id)
            ->where('ps.user_id', self::$uid);
        self::applySvFilter($query, $filter);
        $item = $query->findOrEmpty();

        return $item->isEmpty() ? [] : self::formatSvItem($item->toArray());
    }

    private static function getCircleLists(string $start, string $end, array $filter): array
    {
        $query = self::getCircleQuery()
            ->where('t.user_id', self::$uid)
            ->where('t.send_status', 0)
            ->where('t.attachment_type', 'in', [1, 2, 3])
            ->whereBetween('t.send_time', [$start, $end])
            ->order('t.send_time asc, t.id asc');
        self::applyCircleFilter($query, $filter);
        self::applyWaitingCircleDeviceTaskFilter($query);
        $rows = $query->select()->toArray();

        return array_map(function (array $row) {
            return self::formatCircleItem($row);
        }, $rows);
    }

    private static function getCircleQuery()
    {
        return AiWechatCircleTask::alias('t')
            ->field('t.id,t.user_id,t.task_config_id,t.device_code,t.wechat_id,t.task_type,t.attachment_type,t.attachment_content,t.content,t.send_time,t.auto_type,t.shanjian_video_task_id,t.persona_id,tc.auto_type as config_auto_type,tc.persona_id as config_persona_id,tc.shanjian_video_task_id as config_shanjian_video_task_id,p.content_publish_config,sj.persona_id as shanjian_persona_id,sj.shanjian_type,sj.status as shanjian_status,sj.video_result_url as shanjian_video_result_url,sj.pic as shanjian_pic,sj.duration as shanjian_duration,sj.remark as shanjian_remark')
            ->leftJoin('ai_wechat_circle_task_config tc', 'tc.id = t.task_config_id')
            ->leftJoin('shanjian_video_task sj', 'sj.id = IF(t.shanjian_video_task_id > 0, t.shanjian_video_task_id, tc.shanjian_video_task_id) AND sj.user_id = t.user_id')
            ->leftJoin('ai_persona p', 'p.id = t.persona_id OR ((t.persona_id IS NULL OR t.persona_id = 0) AND p.id = tc.persona_id)');
    }

    private static function saveSv(int $id, array $params, string $start, string $end, array $filter): array
    {
        $recordQuery = SvPublishSettingDetail::where('id', $id)
            ->where('user_id', self::$uid)
            ->where('status', 0)
            ->where('account_type', 'in', [1, 3, 4, 5])
            ->where('material_type', 'in', [1, 2])
            ->whereBetween('publish_time', [$start, $end]);
        self::applyRecordFilter($recordQuery, $filter);
        $record = $recordQuery->findOrEmpty();

        if ($record->isEmpty()) {
            throw new \Exception('待发布任务不存在或已开始执行');
        }

        $publishTime = $record->getData('publish_time');
        if (array_key_exists('title', $params)) {
            $record->material_title = self::stringValue($params['title']);
        }
        if (array_key_exists('content', $params)) {
            $record->material_subtitle = self::stringValue($params['content']);
        }
        if (array_key_exists('topic', $params)) {
            $record->material_tag = self::stringValue($params['topic']);
        }
        $record->publish_time = $publishTime;
        $record->update_time = time();
        $record->save();

        return self::getSvItem($id, $filter);
    }

    private static function saveCircle(int $id, array $params, string $start, string $end, array $filter): array
    {
        $recordQuery = AiWechatCircleTask::where('id', $id)
            ->where('user_id', self::$uid)
            ->where('send_status', 0)
            ->where('attachment_type', 'in', [1, 2, 3])
            ->whereBetween('send_time', [$start, $end]);
        self::applyRecordFilter($recordQuery, $filter);
        $record = $recordQuery->findOrEmpty();

        if ($record->isEmpty()) {
            throw new \Exception('待发布朋友圈任务不存在或已开始执行');
        }

        $sendTime = $record->getData('send_time');
        if (array_key_exists('content', $params)) {
            $record->content = self::stringValue($params['content']);
        }
        $record->send_time = $sendTime;
        $record->update_time = time();
        $record->save();

        $itemQuery = self::getCircleQuery()
            ->where('t.id', $id)
            ->where('t.user_id', self::$uid);
        self::applyCircleFilter($itemQuery, $filter);
        $item = $itemQuery->findOrEmpty();

        return self::formatCircleItem($item->isEmpty() ? $record->refresh()->toArray() : $item->toArray());
    }

    private static function regenerateSv(int $id, array $params, int $now, array $filter): array
    {
        Db::startTrans();
        try {
            $detailQuery = SvPublishSettingDetail::where('id', $id)
                ->where('user_id', self::$uid)
                ->where('status', 0)
                ->where('account_type', 'in', [1, 3, 4, 5])
                ->where('material_type', 1)
                ->lock(true);
            self::applyRecordFilter($detailQuery, $filter);
            $detail = $detailQuery->findOrEmpty();

            if ($detail->isEmpty()) {
                throw new \Exception('待发布视频任务不存在或已开始执行');
            }

            self::assertPublishTimeNotStarted((string)$detail->getData('publish_time'), $now);
            $detailTaskId = (int)$detail->video_task_id;
            $requestTaskId = (int)($params['shanjian_video_task_id'] ?? 0);
            if ($detailTaskId <= 0) {
                throw new \Exception('当前内容没有关联壹传媒视频任务');
            }

            $task = ShanjianVideoTask::where('id', $detailTaskId)
                ->where('user_id', self::$uid)
                ->lock(true)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('壹传媒视频任务不存在');
            }

            self::resetShanjianTaskForRegenerate(
                $task,
                $requestTaskId,
                self::SOURCE_SV,
                $id,
                (string)$detail->publish_time,
                $now,
                false
            );

            $detailUpdateQuery = SvPublishSettingDetail::where('user_id', self::$uid)
                ->where('video_task_id', $detailTaskId)
                ->where('status', 0)
                ->where('account_type', 'in', [1, 3, 4, 5])
                ->where('publish_time', '>', date('Y-m-d H:i:s', $now));
            self::applyRecordFilter($detailUpdateQuery, $filter);
            $detailUpdateQuery->update([
                'material_url' => '',
                'remark' => '',
                'update_time' => $now,
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return self::getSvItem($id, $filter);
    }

    private static function regenerateCircle(int $id, array $params, int $now, array $filter): array
    {
        Db::startTrans();
        try {
            $recordQuery = AiWechatCircleTask::where('id', $id)
                ->where('user_id', self::$uid)
                ->where('send_status', 0)
                ->where('attachment_type', 'in', [2, 3])
                ->lock(true);
            self::applyRecordFilter($recordQuery, $filter);
            $record = $recordQuery->findOrEmpty();

            if ($record->isEmpty()) {
                throw new \Exception('待发布朋友圈视频任务不存在或已开始执行');
            }

            self::assertPublishTimeNotStarted((string)$record->getData('send_time'), $now);
            $detailTaskId = self::getCircleTaskShanjianVideoTaskId($record);
            if ($detailTaskId <= 0) {
                throw new \Exception('当前朋友圈内容没有关联壹传媒视频任务');
            }

            $task = ShanjianVideoTask::where('id', $detailTaskId)
                ->where('user_id', self::$uid)
                ->where('wechat_type', 1)
                ->lock(true)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('朋友圈壹传媒视频任务不存在');
            }

            self::resetShanjianTaskForRegenerate(
                $task,
                (int)($params['shanjian_video_task_id'] ?? 0),
                self::SOURCE_CIRCLE,
                $id,
                (string)$record->send_time,
                $now,
                true
            );

            $emptyAttachment = json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $taskConfigId = (int)$record->task_config_id;
            $circleUpdateQuery = AiWechatCircleTask::where('user_id', self::$uid)
                ->where('send_status', 0)
                ->where('attachment_type', 'in', [2, 3])
                ->where(function ($query) use ($detailTaskId, $id, $taskConfigId) {
                    $query->where('shanjian_video_task_id', $detailTaskId)
                        ->whereOr('id', $id);
                    if ($taskConfigId > 0) {
                        $query->whereOr('task_config_id', $taskConfigId);
                    }
                })
                ->where('send_time', '>', date('Y-m-d H:i:s', $now));
            self::applyRecordFilter($circleUpdateQuery, $filter);
            $circleUpdateQuery->update([
                    'attachment_content' => $emptyAttachment,
                    'shanjian_video_task_id' => $detailTaskId,
                    'update_time' => $now,
                ]);

            AiWechatCircleTaskConfig::where('user_id', self::$uid)
                ->where(function ($query) use ($detailTaskId, $record) {
                    $query->where('shanjian_video_task_id', $detailTaskId);
                    if ((int)$record->task_config_id > 0) {
                        $query->whereOr('id', (int)$record->task_config_id);
                    }
                })
                ->update([
                    'attachment_content' => $emptyAttachment,
                    'shanjian_video_task_id' => $detailTaskId,
                    'update_time' => $now,
                ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return self::getCircleItem($id, $filter);
    }

    private static function getCircleTaskShanjianVideoTaskId(AiWechatCircleTask $record): int
    {
        $taskId = (int)$record->shanjian_video_task_id;
        if ($taskId > 0) {
            return $taskId;
        }

        if ((int)$record->task_config_id > 0) {
            $taskId = (int)AiWechatCircleTaskConfig::where('id', (int)$record->task_config_id)
                ->where('user_id', self::$uid)
                ->lock(true)
                ->value('shanjian_video_task_id');
            if ($taskId > 0) {
                return $taskId;
            }
        }

        $item = self::getCircleQuery()
            ->where('t.id', (int)$record->id)
            ->where('t.user_id', self::$uid)
            ->findOrEmpty();
        if ($item->isEmpty()) {
            return 0;
        }

        $info = self::getCircleShanjianInfo($item->toArray());
        return (int)$info['shanjian_video_task_id'];
    }

    private static function resetShanjianTaskForRegenerate(
        ShanjianVideoTask $task,
        int $requestTaskId,
        string $source,
        int $publishContentId,
        string $publishTime,
        int $now,
        bool $keepPublishReserved = false
    ): void {
        $detailTaskId = (int)$task->id;
        if ($requestTaskId > 0 && $requestTaskId !== $detailTaskId) {
            throw new \Exception('壹传媒视频任务与发布内容不匹配');
        }

        $oldStatus = (int)$task->status;
        if (in_array($oldStatus, [ShanjianVideoTask::STATUS_PENDING, ShanjianVideoTask::STATUS_PROCESSING], true)) {
            throw new \Exception('视频任务正在生成中，无需重新生成');
        }
        if (!in_array($oldStatus, [ShanjianVideoTask::STATUS_FAILED, ShanjianVideoTask::STATUS_SUCCESS], true)) {
            throw new \Exception('当前视频状态不支持重新生成');
        }

        self::rollbackShanjianSettingForRegenerate($task, $oldStatus);

        $oldTaskId = (string)$task->task_id;
        $oldVideoUrl = (string)$task->getData('video_result_url');
        $newTaskId = generate_unique_task_id();
        $task->save([
            'task_id' => $newTaskId,
            'status' => ShanjianVideoTask::STATUS_PENDING,
            'result_id' => '',
            'video_result_url' => '',
            'video_token' => '0',
            'duration' => '0',
            'tries' => 0,
            'remark' => '',
            'is_publish' => $keepPublishReserved ? 1 : 0,
            'update_time' => $now,
        ]);

        Log::channel('shanjian')->write('重新生成闪剪视频任务' . json_encode([
            'user_id' => self::$uid,
            'source' => $source,
            'publish_content_id' => $publishContentId,
            'shanjian_video_task_id' => $detailTaskId,
            'old_status' => $oldStatus,
            'old_task_id' => $oldTaskId,
            'new_task_id' => $newTaskId,
            'old_video_result_url' => $oldVideoUrl,
            'publish_time' => $publishTime,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function rollbackShanjianSettingForRegenerate(ShanjianVideoTask $task, int $oldStatus): void
    {
        $setting = ShanjianVideoSetting::where('id', (int)$task->video_setting_id)
            ->where('user_id', (int)$task->user_id)
            ->lock(true)
            ->findOrEmpty();
        if ($setting->isEmpty()) {
            throw new \Exception('关联的视频设置不存在');
        }

        if ($oldStatus === ShanjianVideoTask::STATUS_SUCCESS) {
            $setting->success_num = max(0, (int)$setting->success_num - 1);
        } elseif ($oldStatus === ShanjianVideoTask::STATUS_FAILED) {
            $setting->error_num = max(0, (int)$setting->error_num - 1);
        }
        $setting->status = 1;
        $setting->update_time = time();
        $setting->save();
    }

    private static function formatSvItem(array $row): array
    {
        $row = self::fillShanjianFields($row);
        $platform = (int)($row['account_type'] ?? 0);
        $mediaType = self::normalizeSvMediaType((int)($row['material_type'] ?? 0));
        $shanjianVideoTaskId = (int)($row['video_task_id'] ?? 0);
        $shanjianStatus = array_key_exists('shanjian_status', $row) && $row['shanjian_status'] !== null
            ? (int)$row['shanjian_status']
            : null;
        $mediaUrl = $row['material_url'] ?? '';
        if ($shanjianVideoTaskId > 0 && trim((string)($row['shanjian_video_result_url'] ?? '')) !== '') {
            $mediaUrl = $row['shanjian_video_result_url'];
        }
        $coverUrl = self::getCoverUrl($row['pic'] ?? '', $row['shanjian_pic'] ?? '');

        return [
            'source' => self::SOURCE_SV,
            'id' => (int)$row['id'],
            'persona_id' => (int)($row['persona_id'] ?? $row['shanjian_persona_id'] ?? 0),
            'platform' => $platform,
            'platform_name' => self::getPlatformName($platform),
            'publish_time' => (string)($row['publish_time'] ?? ''),
            'media_type' => $mediaType,
            'media_label' => self::MEDIA_LABELS[$mediaType] ?? '',
            'media_urls' => self::normalizeMediaUrls($mediaUrl),
            'account' => (string)($row['account'] ?? ''),
            'title' => (string)($row['material_title'] ?? ''),
            'content' => (string)($row['material_subtitle'] ?? ''),
            'topic' => (string)($row['material_tag'] ?? ''),
            'poi' => (string)($row['poi'] ?? ''),
            'location' => (string)($row['poi'] ?? ''),
            'cover_url' => $coverUrl,
            'auto_type' => (int)($row['auto_type'] ?? 0),
            'task_type' => (int)($row['task_type'] ?? 0),
            'video_setting_id' => (int)($row['video_setting_id'] ?? 0),
            'video_task_id' => $shanjianVideoTaskId,
            'shanjian_video_task_id' => $shanjianVideoTaskId,
            'shanjian_type' => (int)($row['shanjian_type'] ?? 0),
            'shanjian_status' => $shanjianStatus,
            'shanjian_status_text' => self::getShanjianStatusText($shanjianStatus),
            'shanjian_remark' => (string)($row['shanjian_remark'] ?? ''),
            'shanjian_video_url' => trim((string)($row['shanjian_video_result_url'] ?? '')) !== ''
                ? FileService::getFileUrl((string)$row['shanjian_video_result_url'])
                : '',
            'duration' => (string)($row['shanjian_duration'] ?? ''),
            'can_regenerate' => $shanjianVideoTaskId > 0
                && in_array($shanjianStatus, [ShanjianVideoTask::STATUS_FAILED, ShanjianVideoTask::STATUS_SUCCESS], true),
        ];
    }

    private static function fillShanjianFields(array $row): array
    {
        $needFetch = !array_key_exists('video_task_id', $row)
            || ((int)($row['video_task_id'] ?? 0) > 0 && !array_key_exists('shanjian_status', $row));
        if (!$needFetch || empty($row['id'])) {
            return $row;
        }

        $item = self::getSvQuery()
            ->where('ps.id', (int)$row['id'])
            ->where('ps.user_id', self::$uid)
            ->findOrEmpty();
        if ($item->isEmpty()) {
            return $row;
        }

        return array_merge($row, $item->toArray());
    }

    private static function getCircleItem(int $id, array $filter = []): array
    {
        $filter = $filter ?: ['persona_id' => 0];
        $query = self::getCircleQuery()
            ->where('t.id', $id)
            ->where('t.user_id', self::$uid);
        self::applyCircleFilter($query, $filter);
        $item = $query->findOrEmpty();

        return $item->isEmpty() ? [] : self::formatCircleItem($item->toArray());
    }

    private static function formatCircleItem(array $row): array
    {
        $mediaType = self::normalizeCircleMediaType((int)($row['attachment_type'] ?? 0));
        $contentPublishConfig = \app\common\model\aiPersona\AiPersona::normalizeContentPublishConfig($row['content_publish_config'] ?? []);
        $location = (int)$contentPublishConfig['is_content_location'] === 1
            ? (string)$contentPublishConfig['content_location']
            : '';
        $shanjianInfo = self::getCircleShanjianInfo($row);
        $coverUrl = self::getCoverUrl('', $row['shanjian_pic'] ?? '');

        return [
            'source' => self::SOURCE_CIRCLE,
            'id' => (int)$row['id'],
            'persona_id' => (int)($row['persona_id'] ?? $row['config_persona_id'] ?? $row['shanjian_persona_id'] ?? 0),
            'platform' => self::PLATFORM_CIRCLE,
            'platform_name' => '朋友圈',
            'publish_time' => (string)($row['send_time'] ?? ''),
            'media_type' => $mediaType,
            'media_label' => self::MEDIA_LABELS[$mediaType] ?? '',
            'media_urls' => self::normalizeMediaUrls($row['attachment_content'] ?? []),
            'account' => (string)($row['wechat_id'] ?? ''),
            'title' => '',
            'content' => (string)($row['content'] ?? ''),
            'topic' => '',
            'poi' => $location,
            'location' => $location,
            'cover_url' => $coverUrl,
            'auto_type' => (int)($row['auto_type'] ?? 0),
            'task_type' => (int)($row['task_type'] ?? 0),
            'video_setting_id' => 0,
            'video_task_id' => $shanjianInfo['shanjian_video_task_id'],
            'shanjian_video_task_id' => $shanjianInfo['shanjian_video_task_id'],
            'shanjian_type' => $shanjianInfo['shanjian_type'],
            'shanjian_status' => $shanjianInfo['shanjian_status'],
            'shanjian_status_text' => self::getShanjianStatusText($shanjianInfo['shanjian_status']),
            'shanjian_remark' => $shanjianInfo['shanjian_remark'],
            'shanjian_video_url' => $shanjianInfo['shanjian_video_url'],
            'duration' => $shanjianInfo['duration'],
            'can_regenerate' => $shanjianInfo['shanjian_video_task_id'] > 0
                && in_array($shanjianInfo['shanjian_status'], [ShanjianVideoTask::STATUS_FAILED, ShanjianVideoTask::STATUS_SUCCESS], true),
        ];
    }

    private static function getCircleShanjianInfo(array $row): array
    {
        $shanjianVideoTaskId = (int)($row['shanjian_video_task_id'] ?? 0);
        if ($shanjianVideoTaskId <= 0) {
            $shanjianVideoTaskId = (int)($row['config_shanjian_video_task_id'] ?? 0);
        }
        $isAutoMode = (int)($row['auto_type'] ?? 0) === 1
            || (int)($row['config_auto_type'] ?? 0) === 1;

        $info = [
            'shanjian_video_task_id' => $shanjianVideoTaskId,
            'shanjian_type' => array_key_exists('shanjian_type', $row) && $row['shanjian_type'] !== null
                ? (int)$row['shanjian_type']
                : 0,
            'shanjian_status' => array_key_exists('shanjian_status', $row) && $row['shanjian_status'] !== null
                ? (int)$row['shanjian_status']
                : null,
            'shanjian_remark' => (string)($row['shanjian_remark'] ?? ''),
            'shanjian_video_url' => trim((string)($row['shanjian_video_result_url'] ?? '')) !== ''
                ? FileService::getFileUrl((string)$row['shanjian_video_result_url'])
                : '',
            'duration' => (string)($row['shanjian_duration'] ?? ''),
        ];

        if ($info['shanjian_video_task_id'] > 0
            || !$isAutoMode
            || !in_array((int)($row['attachment_type'] ?? 0), [2, 3], true)
        ) {
            return $info;
        }

        $task = self::findCircleShanjianTask($row);
        if (empty($task)) {
            return $info;
        }

        $info['shanjian_video_task_id'] = (int)$task['id'];
        $info['shanjian_type'] = (int)($task['shanjian_type'] ?? 0);
        $info['shanjian_status'] = (int)$task['status'];
        $info['shanjian_remark'] = (string)($task['remark'] ?? '');
        $info['shanjian_video_url'] = trim((string)($task['video_result_url'] ?? '')) !== ''
            ? FileService::getFileUrl((string)$task['video_result_url'])
            : '';
        $info['duration'] = (string)($task['duration'] ?? '');

        self::backfillCircleShanjianTrace($row, $info['shanjian_video_task_id']);
        return $info;
    }

    private static function findCircleShanjianTask(array $row): array
    {
        $urls = self::normalizeMediaUrls($row['attachment_content'] ?? []);
        if (empty($urls)) {
            return [];
        }

        $candidates = [];
        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            foreach ([$url, $path ? ltrim($path, '/') : '', $path ?: ''] as $candidate) {
                $candidate = trim((string)$candidate);
                if ($candidate !== '') {
                    $candidates[] = $candidate;
                }
            }
        }
        $candidates = array_values(array_unique($candidates));

        $task = ShanjianVideoTask::where('user_id', (int)($row['user_id'] ?? 0))
            ->where('device_code', (string)($row['device_code'] ?? ''))
            ->where('auto_type', 1)
            ->where('wechat_type', 1)
            ->where('video_result_url', 'in', $candidates)
            ->order('id desc')
            ->findOrEmpty();
        if (!$task->isEmpty()) {
            return $task->toArray();
        }

        $basename = basename((string)parse_url($urls[0], PHP_URL_PATH));
        if ($basename === '') {
            return [];
        }

        $task = ShanjianVideoTask::where('user_id', (int)($row['user_id'] ?? 0))
            ->where('device_code', (string)($row['device_code'] ?? ''))
            ->where('auto_type', 1)
            ->where('wechat_type', 1)
            ->where('video_result_url', 'like', '%' . $basename . '%')
            ->order('id desc')
            ->findOrEmpty();

        return $task->isEmpty() ? [] : $task->toArray();
    }

    private static function backfillCircleShanjianTrace(array $row, int $shanjianVideoTaskId): void
    {
        if (empty($row['id']) || $shanjianVideoTaskId <= 0) {
            return;
        }

        try {
            $update = [
                'shanjian_video_task_id' => $shanjianVideoTaskId,
                'update_time' => time(),
            ];
            AiWechatCircleTask::where('id', (int)$row['id'])
                ->where('user_id', (int)($row['user_id'] ?? 0))
                ->update($update);

            if (!empty($row['task_config_id'])) {
                AiWechatCircleTaskConfig::where('id', (int)$row['task_config_id'])
                    ->where('user_id', (int)($row['user_id'] ?? 0))
                    ->update($update);
            }
        } catch (\Throwable $e) {
            Log::channel('wechatCircle')->warning('回填朋友圈闪剪任务追溯失败: ' . $e->getMessage());
        }
    }

    private static function normalizeMediaUrls(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = explode(',', $value);
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $urls = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['url'] ?? $item['uri'] ?? $item['content'] ?? $item['path'] ?? $item['src'] ?? $item['fileUrl'] ?? '';
            }
            $url = trim((string)$item);
            if ($url !== '') {
                $urls[] = FileService::getFileUrl($url);
            }
        }

        return array_values(array_unique($urls));
    }

    private static function getDateRange(string $date): array
    {
        $date = trim($date) !== '' ? trim($date) : date('Y-m-d');
        $timestamp = strtotime($date);
        if (!$timestamp || date('Y-m-d', $timestamp) !== $date) {
            throw new \Exception('日期格式错误');
        }

        return [
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            $date,
        ];
    }

    private static function normalizePlatform(mixed $platform): int|string
    {
        if ((string)$platform === self::PLATFORM_CIRCLE) {
            return self::PLATFORM_CIRCLE;
        }

        $platform = (int)$platform;
        return in_array($platform, [1, 3, 4, 5], true) ? $platform : self::PLATFORM_CIRCLE;
    }

    private static function getPlatformMeta(int|string $platform): array
    {
        foreach (self::PLATFORMS as $item) {
            if ((string)$item['platform'] === (string)$platform) {
                return $item;
            }
        }

        return self::PLATFORMS[4];
    }

    private static function getPlatformName(int $platform): string
    {
        foreach (self::PLATFORMS as $item) {
            if ($item['source'] === self::SOURCE_SV && (int)$item['platform'] === $platform) {
                return $item['platform_name'];
            }
        }

        return '';
    }

    private static function getShanjianStatusText(?int $status): string
    {
        return $status === null ? '' : ShanjianVideoTask::getStatusText($status);
    }

    private static function normalizeSvMediaType(int $materialType): int
    {
        return $materialType === 1 ? 1 : 2;
    }

    private static function normalizeCircleMediaType(int $attachmentType): int
    {
        return in_array($attachmentType, [2, 3], true) ? 1 : 2;
    }

    private static function getCoverUrl(mixed ...$values): string
    {
        foreach ($values as $value) {
            $url = trim((string)$value);
            if ($url !== '') {
                return FileService::getFileUrl($url);
            }
        }

        return '';
    }

    private static function stringValue(mixed $value): string
    {
        return trim((string)$value);
    }
}
