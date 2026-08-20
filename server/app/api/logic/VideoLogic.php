<?php

namespace app\api\logic;

use app\common\model\human\HumanVideoTask;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sora\SoraVideoTask;
use app\common\model\storyboard\StoryboardVideoTask;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\ShanjianQueueService;
use think\facade\Db;

/**
 * VideoLogic
 * @desc 视频创作记录
 */
class VideoLogic extends ApiLogic
{
    public static function getVideoCreationRecordLists($params, $userId): array
    {
        // 分页
        $pageNo   = isset($params['page_no']) && $params['page_no'] > 0 ? (int)$params['page_no'] : 1;
        $pageSize = isset($params['page_size']) && $params['page_size'] > 0 ? (int)$params['page_size'] : 10;
        $offset   = ($pageNo - 1) * $pageSize;

        // 支持单个 type、逗号分隔（如 1,9）、数组
        $types = self::parseCreationRecordTypes($params['type'] ?? null);
        // 仅「纯 type=9」走 packaging JOIN；与其它 type 混合时用简单查询，避免 UNION 列不一致导致查不到 9
        $onlyType9 = (count($types) === 1 && $types[0] === 9);
        $success         = !empty($params['success']) ? 1 : 0;
        $shanjianWhere   = [];
        $humanWhere      = [['is_ai', '=', 0]];
        $soraWhere       = [];
        $storyboardWhere = [];
        // 创作记录 type=8 仅展示视频仿写，排除小红书图文（media_type=2）
        $imitationWhere  = [
            ['media_type', '=', VideoImitationTask::MEDIA_TYPE_VIDEO],
        ];

        // type → shanjian_type：2→1, 3→2, 4→3, 5→4, 9→5
        $shanjianTypeMap = [2 => 1, 3 => 2, 4 => 3, 5 => 4, 9 => 5];
        $selectedShanjianTypes = [];
        foreach ($types as $t) {
            if (isset($shanjianTypeMap[$t])) {
                $selectedShanjianTypes[] = $shanjianTypeMap[$t];
            }
        }
        $selectedShanjianTypes = array_values(array_unique($selectedShanjianTypes));
        if (count($selectedShanjianTypes) === 1) {
            $shanjianWhere = [['shanjian_type', '=', $selectedShanjianTypes[0]]];
        } elseif (count($selectedShanjianTypes) > 1) {
            $shanjianWhere = [['shanjian_type', 'in', $selectedShanjianTypes]];
        }

        if ($success) {
            $humanWhere      = [
                ['status', '=', 1],
                ['is_ai', '=', 0]
            ];
            $shanjianWhere[] = ['status', '=', 3];
            $soraWhere       = [
                ['status', '=', 3],
            ];
            $storyboardWhere = [
                ['status', '=', 3]
            ];
            $imitationWhere[] = ['status', '=', 3];
        }

        // 查询条件
        $where = [['user_id', '=', $userId], ['delete_time', '=', null]];

        // 蝉镜 type5 桥接：同一 task_id 会同时有 human_video_task 与 shanjian_video_task(type=5)。
        // 创作记录保留闪剪侧 type=9（含包装状态），过滤掉对应的 human 记录，避免重复。
        $query1 = Db::name('human_video_task')
                    ->alias('hvt')
                    ->field([
                                'hvt.id',
                                'hvt.name',
                                'hvt.task_id',
                                'hvt.model_version',
                                'hvt.status',
                                'hvt.pic',
                                'hvt.result_url as video_result_url',
                                'hvt.automatic_clip',
                                'hvt.clip_status',
                                'hvt.clip_result_url',
                                'hvt.create_time',
                                'hvt.update_time',
                                'hvt.remark',
                                "'1' as type",
                                'hvt.duration',
                                "'' as queue_status",
                                '0 as queue_position',
                                '0 as queue_updated_time',
                                // 非闪剪任务无成片转存，按下载成功处理，前端不展示下载态
                                '2 as download_status'
                            ])
                    ->where(self::prefixHumanWhere($where, 'hvt'))
                    ->where(self::prefixHumanWhere($humanWhere, 'hvt'))
                    ->whereNotExists(function ($sub) {
                        $sub->name('shanjian_video_task')
                            ->alias('sj_bridge')
                            ->whereRaw('sj_bridge.task_id = hvt.task_id')
                            ->where('sj_bridge.shanjian_type', 5)
                            ->whereNull('sj_bridge.delete_time');
                    })
                    ->buildSql();

        if ($onlyType9) {
            $shanjianQueryWhere = array_map(function ($condition) {
                if (isset($condition[0]) && in_array($condition[0], ['shanjian_type', 'status'], true)) {
                    $condition[0] = 'sj.' . $condition[0];
                }
                return $condition;
            }, $shanjianWhere);

            $query2 = Db::name('shanjian_video_task')
                        ->alias('sj')
                        ->leftJoin('shanjian_video_task package_task', 'package_task.id = sj.packaging_task_id AND package_task.delete_time IS NULL AND package_task.shanjian_type = 2')
                        ->field([
                                    'sj.id',
                                    'sj.name',
                                    'sj.task_id',
                                    "'8' as model_version",
                                    'sj.status',
                                    'sj.pic',
                                    'sj.video_result_url',
                                    "'0' as automatic_clip",
                                    "'1' as clip_status",
                                    'sj.video_result_url as clip_result_url',
                                    'sj.create_time',
                                    'sj.update_time',
                                    'sj.remark',
                                    "'9' as type",
                                    'sj.duration',
                                    'sj.extra',
                                    'sj.packaging_task_id',
                                    'package_task.status as packaging_status',
                                    'package_task.video_result_url as packaging_video_result_url',
                                    'sj.queue_status',
                                    'sj.queue_position',
                                    'sj.queue_updated_time',
                                    'sj.download_status'
                                ])
                        ->where([['sj.user_id', '=', $userId], ['sj.delete_time', '=', null]])
                        ->where($shanjianQueryWhere)
                        ->buildSql();
        } else {
            $query2 = Db::name('shanjian_video_task')
                        ->field([
                                    'id',
                                    'name',
                                    'task_id',
                                    "'8' as model_version",
                                    'status',
                                    'pic',
                                    'video_result_url',
                                    "'0' as automatic_clip",
                                    "'1' as clip_status",
                                    'video_result_url as clip_result_url',
                                    'create_time',
                                    'update_time',
                                    'remark',
                                    // shanjian_type 1~4 → type 2~5；shanjian_type=5（数字人口播无包装）→ type=9，避免与 Sora(type=6) 冲突
                                    'CASE WHEN shanjian_type = 5 THEN 9 ELSE shanjian_type + 1 END as type',
                                    'duration',
                                    'queue_status',
                                    'queue_position',
                                    'queue_updated_time',
                                    'download_status'
                                ])
                        ->where($where)
                        ->where($shanjianWhere)
                        ->buildSql();
        }

        $query3 = Db::name('sora_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                "'9' as model_version",
                                'status',
                                'pic',
                                'video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_result_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "'6' as type",
                                'duration',
                                "'' as queue_status",
                                '0 as queue_position',
                                '0 as queue_updated_time',
                                '2 as download_status'
                            ])
                    ->where($where)
                    ->where($soraWhere)
                    ->buildSql();

        $query4 = Db::name('storyboard_video_task')
                    ->field([
                                'id',
                                'name',
                                'task_id',
                                "'10' as model_version",
                                'status',
                                'pic',
                                'video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_result_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remark',
                                "'7' as type",
                                'duration',
                                "'' as queue_status",
                                '0 as queue_position',
                                '0 as queue_updated_time',
                                '2 as download_status'
                            ])
                    ->where($where)
                    ->where($storyboardWhere)
                    ->buildSql();

        $query5 = Db::name('video_imitation_task')
                    ->field([
                                'id',
                                'title as name',
                                'shanjian_task_id as task_id',
                                "'11' as model_version",
                                'status',
                                "thumbnail as pic",
                                'video_url as video_result_url',
                                "'0' as automatic_clip",
                                "'1' as clip_status",
                                'video_url as clip_result_url',
                                'create_time',
                                'update_time',
                                'remarks as remark',
                                "'8' as type",
                                'duration',
                                'queue_status',
                                'queue_position',
                                'queue_updated_time',
                                '2 as download_status'
                            ])
                    ->where($where)
                    ->where($imitationWhere)
                    ->buildSql();

        // 按选中 type 拼接 UNION（空 = 全部）
        $unionParts = [];
        if (empty($types) || in_array(1, $types, true)) {
            $unionParts[] = $query1;
        }
        if (empty($types) || !empty(array_intersect($types, [2, 3, 4, 5, 9]))) {
            $unionParts[] = $query2;
        }
        if (empty($types) || in_array(6, $types, true)) {
            $unionParts[] = $query3;
        }
        if (empty($types) || in_array(7, $types, true)) {
            $unionParts[] = $query4;
        }
        if (empty($types) || in_array(8, $types, true)) {
            $unionParts[] = $query5;
        }
        if (empty($unionParts)) {
            return [
                'count'      => 0,
                'lists'      => [],
                'page_no'    => $pageNo,
                'page_size'  => $pageSize,
                'total_page' => 0,
            ];
        }
        $unionSql = '(' . implode(' UNION ALL ', $unionParts) . ') AS t';

        $lists = Db::table($unionSql)
                   ->order('create_time', 'desc')  // 按创建时间倒序
                   ->limit($offset, $pageSize)      // 分页：偏移量, 每页条数
                   ->select()
                   ->toArray();

        if ($onlyType9) {
            $lists = self::resolveType5AiClipFields($lists);
        } elseif (empty($types) || in_array(9, $types, true)) {
            // 混合查询中补齐 type=9 的 AI 剪辑字段
            $lists = self::enrichType9AiClipFields($lists);
        }

        $video = [];
        foreach ($lists as $key => $item) {
            $typeValue = (int)$item['type'];
            $automaticClip = $typeValue == 1 ? $item['automatic_clip'] : '';
            $clipStatus = $typeValue == 1 ? ((int)$item['automatic_clip'] == 0 ? '' : $item['clip_status']) : '';
            if ($typeValue == 9 && (int)($item['automatic_clip'] ?? 0) == 1) {
                $automaticClip = 1;
                $clipStatus = (int)$item['clip_status'];
            }

            $video[$key] = [
                'id'               => $item['id'],
                'name'             => $item['name'],
                'task_id'          => $item['task_id'],
                'model_version'    => (int)$item['model_version'],
                'status'           => (int)$item['status'],
                'pic'              => empty($item['pic']) ? FileService::getFileUrl('/static/images/creationRecord.jpg') : FileService::getFileUrl($item['pic']),
                'video_result_url' => empty($item['video_result_url']) ? '' : FileService::getFileUrl($item['video_result_url']),
                'automatic_clip'   => $automaticClip,
                'clip_status'      => $clipStatus,
                'clip_result_url'  => empty($item['clip_result_url']) ? '' : FileService::getFileUrl($item['clip_result_url']),
                'create_time'      => date('Y-m-d H:i:s', $item['create_time']),
                'update_time'      => date('Y-m-d H:i:s', $item['update_time']),
                'remark'           => $item['remark'],
                'type'             => (int)$item['type'],
                'duration'         => $item['duration'],
                'queue_status'     => (string)($item['queue_status'] ?? ''),
                'queue_position'   => (int)($item['queue_position'] ?? 0),
                'queue_updated_time' => (int)($item['queue_updated_time'] ?? 0),
                'queue_status_text' => ShanjianQueueService::statusText(
                    (string)($item['queue_status'] ?? ''),
                    (int)($item['queue_position'] ?? 0)
                ),
                // 0待下载 1下载中 2成功 3失败；非闪剪任务固定为 2
                'download_status'  => (int)($item['download_status'] ?? 2),
            ];
        }

        $total = self::getTotalCount($where, $shanjianWhere, $humanWhere, $soraWhere, $storyboardWhere, $imitationWhere, $types, $success);

        return [
            'count'      => $total,
            'lists'      => $video,
            'page_no'    => $pageNo,
            'page_size'  => $pageSize,
            'total_page' => (int)ceil($total / $pageSize)
        ];
    }

    /**
     * 解析创作记录 type 参数，支持 1 / 1,9 / [1,9]
     * @return int[] 空数组表示查全部
     */
    private static function parseCreationRecordTypes($typeParam): array
    {
        if ($typeParam === null || $typeParam === '' || $typeParam === 0 || $typeParam === '0') {
            return [];
        }
        if (is_array($typeParam)) {
            $parts = $typeParam;
        } else {
            // 兼容中文逗号、空格
            $normalized = str_replace(['，', ' '], [',', ''], (string)$typeParam);
            $parts = explode(',', $normalized);
        }
        $types = [];
        foreach ($parts as $part) {
            $type = (int)trim((string)$part);
            if ($type > 0) {
                $types[] = $type;
            }
        }
        return array_values(array_unique($types));
    }

    /**
     * 混合查询时补齐 type=9 的 packaging / AI 剪辑字段
     */
    private static function enrichType9AiClipFields(array $lists): array
    {
        $type9Ids = [];
        foreach ($lists as $item) {
            if ((int)($item['type'] ?? 0) === 9) {
                $type9Ids[] = (int)$item['id'];
            }
        }
        if (empty($type9Ids)) {
            return $lists;
        }

        $rows = Db::name('shanjian_video_task')
                  ->alias('sj')
                  ->leftJoin('shanjian_video_task package_task', 'package_task.id = sj.packaging_task_id AND package_task.delete_time IS NULL AND package_task.shanjian_type = 2')
                  ->whereIn('sj.id', $type9Ids)
                  ->field([
                              'sj.id',
                              'sj.extra',
                              'sj.packaging_task_id',
                              'package_task.status as packaging_status',
                              'package_task.video_result_url as packaging_video_result_url',
                          ])
                  ->select()
                  ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }

        foreach ($lists as &$item) {
            if ((int)($item['type'] ?? 0) !== 9) {
                continue;
            }
            $extra = $map[(int)$item['id']] ?? null;
            if ($extra === null) {
                continue;
            }
            $item['extra'] = $extra['extra'] ?? '';
            $item['packaging_task_id'] = $extra['packaging_task_id'] ?? 0;
            $item['packaging_status'] = $extra['packaging_status'] ?? null;
            $item['packaging_video_result_url'] = $extra['packaging_video_result_url'] ?? '';
        }
        unset($item);

        return self::resolveType5AiClipFields($lists);
    }

    private static function resolveType5AiClipFields(array $lists): array
    {
        foreach ($lists as &$item) {
            $extra = json_decode((string)($item['extra'] ?? ''), true) ?: [];
            $aiClipEnabled = filter_var($extra['ai_clip_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$aiClipEnabled) {
                continue;
            }

            $item['automatic_clip'] = 1;
            $item['clip_result_url'] = '';

            if ((int)($item['packaging_task_id'] ?? 0) <= 0 || $item['packaging_status'] === null) {
                $item['clip_status'] = 1;
                continue;
            }

            switch ((int)$item['packaging_status']) {
                case 0:
                case 1:
                    $item['clip_status'] = 2;
                    break;
                case 2:
                    $item['clip_status'] = 4;
                    break;
                case 3:
                    $item['clip_status'] = 3;
                    $item['clip_result_url'] = $item['packaging_video_result_url'] ?? '';
                    break;
                default:
                    $item['clip_status'] = 1;
                    break;
            }
        }
        unset($item);

        return $lists;
    }

    /**
     * where 条件字段加表别名（仅处理首段字段名）
     */
    private static function prefixHumanWhere(array $where, string $alias): array
    {
        $prefixed = [];
        foreach ($where as $condition) {
            if (is_array($condition) && isset($condition[0]) && is_string($condition[0]) && strpos($condition[0], '.') === false) {
                $condition[0] = $alias . '.' . $condition[0];
            }
            $prefixed[] = $condition;
        }
        return $prefixed;
    }

    /**
     * 统计 human_video_task，排除已桥接到闪剪 type5 的蝉镜任务（与列表过滤一致）
     */
    private static function countHumanVideoTaskWithoutType5Bridge(array $where, array $humanWhere = []): int
    {
        $query = Db::name('human_video_task')
            ->alias('hvt')
            ->where(self::prefixHumanWhere($where, 'hvt'));
        if (!empty($humanWhere)) {
            $query->where(self::prefixHumanWhere($humanWhere, 'hvt'));
        }
        $query->whereNotExists(function ($sub) {
            $sub->name('shanjian_video_task')
                ->alias('sj_bridge')
                ->whereRaw('sj_bridge.task_id = hvt.task_id')
                ->where('sj_bridge.shanjian_type', 5)
                ->whereNull('sj_bridge.delete_time');
        });
        return (int)$query->count();
    }

    /**
     * 计算选中类型的总记录数，$types 为空表示全部
     */
    private static function getTotalCount(array $where, $shanjianWhere, $humanWhere, $soraWhere, $storyboardWhere, $imitationWhere, array $types, $success): int
    {
        if (empty($types)) {
            if ($success) {
                $count1 = self::countHumanVideoTaskWithoutType5Bridge($where, $humanWhere);
                $count2 = Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
                $count3 = Db::name('sora_video_task')->where($where)->where($soraWhere)->count();
                $count4 = Db::name('storyboard_video_task')->where($where)->where($storyboardWhere)->count();
                $count5 = Db::name('video_imitation_task')->where($where)->where($imitationWhere)->count();
            } else {
                // 与列表一致：默认仍排除 is_ai!=0 时由调用方 humanWhere 控制；此处非 success 原先未带 humanWhere，保持兼容并叠加桥接过滤
                $count1 = self::countHumanVideoTaskWithoutType5Bridge($where, [['is_ai', '=', 0]]);
                $count2 = Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
                $count3 = Db::name('sora_video_task')->where($where)->count();
                $count4 = Db::name('storyboard_video_task')->where($where)->count();
                $count5 = Db::name('video_imitation_task')->where($where)->where($imitationWhere)->count();
            }
            return $count1 + $count2 + $count3 + $count4 + $count5;
        }

        $total = 0;
        if (in_array(1, $types, true)) {
            if ($success) {
                $total += self::countHumanVideoTaskWithoutType5Bridge($where, $humanWhere);
            } else {
                $total += self::countHumanVideoTaskWithoutType5Bridge($where, [['is_ai', '=', 0]]);
            }
        }
        if (!empty(array_intersect($types, [2, 3, 4, 5, 9]))) {
            $total += Db::name('shanjian_video_task')->where($where)->where($shanjianWhere)->count();
        }
        if (in_array(6, $types, true)) {
            if ($success) {
                $total += Db::name('sora_video_task')->where($where)->where($soraWhere)->count();
            } else {
                $total += Db::name('sora_video_task')->where($where)->count();
            }
        }
        if (in_array(7, $types, true)) {
            if ($success) {
                $total += Db::name('storyboard_video_task')->where($where)->where($storyboardWhere)->count();
            } else {
                $total += Db::name('storyboard_video_task')->where($where)->count();
            }
        }
        if (in_array(8, $types, true)) {
            $total += Db::name('video_imitation_task')->where($where)->where($imitationWhere)->count();
        }
        return $total;
    }

    /**
     * 删除视频任务
     * @param array $data
     * @return bool
     */
    public static function creationRecordDelete($data): bool
    {
        try {
            if (!isset($data['type']) || !isset($data['id']) || !isset($data['task_id'])) {
                throw new \Exception('参数错误');
            }
            $type    = $data['type'];
            $id      = $data['id'];
            $task_id = $data['task_id'];
            if ($type == 1) {
                $task = HumanVideoTask::where('id', $id)
                                      ->where('task_id', $task_id)
                                      ->where('user_id', self::$uid)
                                      ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if (in_array($type, [2, 3, 4, 5, 9])) {
                $task = ShanjianVideoTask::where('id', $id)
                                         ->where('task_id', $task_id)
                                         ->where('user_id', self::$uid)
                                         ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if ($type == 6) {
                $task = SoraVideoTask::where('id', $id)
                                     ->where('task_id', $task_id)
                                     ->where('user_id', self::$uid)
                                     ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if ($type == 7) {
                $task = StoryboardVideoTask::where('id', $id)
                                           ->where('task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            if ($type == 8) {
                $task = VideoImitationTask::where('id', $id)
                                           ->where('shanjian_task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->where('media_type', VideoImitationTask::MEDIA_TYPE_VIDEO)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function creationRecordUpdate(array $data): bool
    {
        try {
            if (!isset($data['type']) || !isset($data['id']) || !isset($data['task_id']) || !isset($data['name'])) {
                throw new \Exception('参数错误');
            }
            $type    = $data['type'];
            $id      = $data['id'];
            $task_id = $data['task_id'];
            $name    = $data['name'];
            if ($type == 1) {
                $task = HumanVideoTask::where('id', $id)
                                      ->where('task_id', $task_id)
                                      ->where('user_id', self::$uid)
                                      ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if (in_array($type, [2, 3, 4, 5, 9])) {
                $task = ShanjianVideoTask::where('id', $id)
                                         ->where('task_id', $task_id)
                                         ->where('user_id', self::$uid)
                                         ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if ($type == 6) {
                $task = SoraVideoTask::where('id', $id)
                                     ->where('task_id', $task_id)
                                     ->where('user_id', self::$uid)
                                     ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if ($type == 7) {
                $task = StoryboardVideoTask::where('id', $id)
                                           ->where('task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->name = $name;
                $task->save();
            }
            if ($type == 8) {
                $task = VideoImitationTask::where('id', $id)
                                           ->where('shanjian_task_id', $task_id)
                                           ->where('user_id', self::$uid)
                                           ->where('media_type', VideoImitationTask::MEDIA_TYPE_VIDEO)
                                           ->find();
                if (!$task) {
                    throw new \Exception('视频任务不存在');
                }
                $task->title = $name;
                $task->save();
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
}
