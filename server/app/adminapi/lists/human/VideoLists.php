<?php

namespace app\adminapi\lists\human;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\service\FileService;
use app\common\enum\user\AccountLogEnum;
use app\common\model\human\HumanVideoTask;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\UserTokensLog;
use app\common\enum\ChatEnum;
use app\common\model\chat\Models;
use think\facade\Db;

/**
 * 视频列表
 */
class VideoLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * 剪辑消耗对应的 change_type
     */
    protected const CLIP_CHANGE_TYPE = 5101;

    /**
     * 禅境 / 闪剪在任务表中的 model_version
     */
    protected const MODEL_VERSION_CHANJING = 7;
    protected const MODEL_VERSION_SHANJIAN = 8;

    /**
     * 闪剪在 la_models 中的主键（model_version=8）
     */
    protected const MODEL_ID_SHANJIAN = 9;

    /**
     * 闪剪数字人口播(type=5)合成扣费
     */
    protected const SHANJIAN_VIDEO_CHANGE_TYPES = [
        AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
        AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN_ADD,
        AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING,
        AccountLogEnum::TOKENS_DEC_HUMAN_EXT,
        AccountLogEnum::TOKENS_INC_HUMAN,
    ];

    /**
     * 闪剪智剪包装(type=2)扣费
     */
    protected const SHANJIAN_CLIP_CHANGE_TYPES = [
        AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
        AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN_ADD,
    ];

    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
            "=" => ['model_version'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lists(): array
    {
        $modelVersion = $this->request->get('model_version');

        // 指定闪剪：只查闪剪任务表
        if ($modelVersion == 8) {
            return $this->getShanjianLists();
        }

        // 指定其它模型：只查普通数字人视频表
        if (!empty($modelVersion)) {
            return $this->getNormalLists();
        }

        // 未指定 model_version：两个来源要按 create_time 做「全局」排序分页。
        // 全局前 offset+length 名的记录，必定落在各自来源的前 offset+length 条之内，
        // 所以两边各取这么多行的排序键即可，合并排序后再切出当页。
        $take = $this->limitOffset + $this->limitLength;

        $keys = [];
        foreach ($this->normalKeys($take) as $id => $createTime) {
            $keys[] = ['source' => 'normal', 'id' => $id, 'create_time' => $createTime];
        }
        foreach ($this->shanjianKeys($take) as $id => $createTime) {
            $keys[] = ['source' => 'shanjian', 'id' => $id, 'create_time' => $createTime];
        }

        // 排序规则必须与两条 SQL 的 ORDER BY 一致，否则同秒记录会在翻页时错位
        usort($keys, function ($a, $b) {
            return ($b['create_time'] <=> $a['create_time']) ?: ($b['id'] <=> $a['id']);
        });

        $page = array_slice($keys, $this->limitOffset, $this->limitLength);
        if (empty($page)) {
            return [];
        }

        $normalIds = $shanjianIds = [];
        foreach ($page as $key) {
            if ($key['source'] === 'normal') {
                $normalIds[] = $key['id'];
            } else {
                $shanjianIds[] = $key['id'];
            }
        }

        // 只对当页命中的记录取详情
        $normalMap   = $normalIds ? array_column($this->getNormalLists($normalIds), null, 'id') : [];
        $shanjianMap = $shanjianIds ? array_column($this->getShanjianLists($shanjianIds), null, 'id') : [];

        $list = [];
        foreach ($page as $key) {
            $row = $key['source'] === 'normal'
                ? ($normalMap[$key['id']] ?? null)
                : ($shanjianMap[$key['id']] ?? null);
            if ($row !== null) {
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * 普通数字人视频的排序键（id => create_time）
     */
    protected function normalKeys(int $take): array
    {
        return $this->buildQuery()
            ->order(['hv.create_time' => 'desc', 'hv.id' => 'desc'])
            ->limit(0, $take)
            ->column('hv.create_time', 'hv.id');
    }

    /**
     * 闪剪视频任务的排序键（id => create_time）
     */
    protected function shanjianKeys(int $take): array
    {
        return $this->shanjianQuery()
            ->order(['hv.create_time' => 'desc', 'hv.id' => 'desc'])
            ->limit(0, $take)
            ->column('hv.create_time', 'hv.id');
    }

    /**
     * 获取普通数字人视频列表
     *
     * @param array|null $ids 传入时只取这批 id（合并分页场景），为 null 时按 limit/offset 自行分页
     */
    protected function getNormalLists(?array $ids = null): array
    {
        // 模型列表（以 id 为键，便于 O(1) 查找）
        $modelMap = $this->getModelMap();

        // 主播列表预加载（避免循环内多次查库）
        $anchorMap = $this->getAnchorMap();

        $query = $this->buildQuery()
            ->field('hv.voice_name,hv.id,hv.name,hv.user_id,hv.model_version,hv.anchor_id,
                hv.create_time,hv.update_time,hv.pic,hv.clip_result_url,hv.clip_status,hv.automatic_clip,
                hv.clip_type,hv.result_url,hv.gender,hv.status,hv.audio_type,hv.task_id,
                u.nickname,u.avatar,hv.remark');

        if ($ids === null) {
            $query->order(['hv.create_time' => 'desc', 'hv.id' => 'desc'])
                ->limit($this->limitOffset, $this->limitLength);
        } else {
            $query->whereIn('hv.id', $ids);
        }

        return $query
            ->select()
            ->toArray();
    }

    /**
     * 获取闪剪视频任务列表（model_version=8）
     *
     * @param array|null $ids 传入时只取这批 id（合并分页场景），为 null 时按 limit/offset 自行分页
     */
    protected function getShanjianLists(?array $ids = null): array
    {
        $query = $this->shanjianQuery()
            ->field('hv.id,hv.name,hv.user_id,8 as model_version,hv.anchor_id,hv.voice_id,
                hv.create_time,hv.update_time,hv.status,hv.task_id,hv.pic,hv.remark,
                hv.audio_type,hv.duration,hv.video_token,hv.extra,hv.packaging_task_id,
                u.nickname,u.avatar,hv.video_result_url as result_url');

        if ($ids === null) {
            $query->order(['hv.create_time' => 'desc', 'hv.id' => 'desc'])
                ->limit($this->limitOffset, $this->limitLength);
        } else {
            $query->whereIn('hv.id', $ids);
        }

        $list = $query->select()->toArray();

        $packagingMap = $this->getPackagingMap($list);
        $costTaskIds = array_merge($this->collectTaskIds($list), $this->collectTaskIds($packagingMap));
        $costMap = $this->batchTokensCost($costTaskIds);
        $voiceMap = $this->getVoiceNameMap($list);
        $anchorMap = $this->getAnchorMapByIds(array_column($list, 'anchor_id'));
        $shanjianAnchorMap = $this->getShanjianAnchorMap($list);
        $modelMap = $this->getModelMap();
        $shanjianName = $this->resolveModelName($modelMap, self::MODEL_VERSION_SHANJIAN) ?: '闪剪';

        foreach ($list as &$item) {
            $item['pic']        = FileService::getFileUrl($item['pic'] ?? '');
            $item['result_url'] = FileService::getFileUrl($item['result_url'] ?? '');
            $item['avatar']     = FileService::getFileUrl($item['avatar'] ?? '');

            $item['anchor_name']   = $anchorMap[$item['anchor_id'] ?? '']
                ?? $shanjianAnchorMap[$item['anchor_id'] ?? '']
                ?? '';
            $item['anchor_id']    = $item['anchor_id'] ?? 0;
            $item['model_version'] = self::MODEL_VERSION_SHANJIAN;
            $item['model_name']   = $shanjianName;
            $item['voice_name']   = $voiceMap[$item['voice_id'] ?? ''] ?? '';
            $item['gender']       = 0;
            $item['clip_type']    = 0;
            $item['audio_type']   = (int)($item['audio_type'] ?? 0);
            $item['remark']       = $item['remark'] ?? '';
            $item['duration']     = $item['duration'] ?? 0;
            $item['status']       = $this->mapShanjianStatusToHuman((int)($item['status'] ?? 0));

            $item['video_points'] = $this->resolveShanjianVideoPoints($item, $costMap);

            $clip = $this->resolveShanjianClipFields($item, $packagingMap, $costMap);
            $item['automatic_clip']  = $clip['automatic_clip'];
            $item['clip_status']     = $clip['clip_status'];
            $item['clip_points']     = $clip['clip_points'];
            $item['clip_result_url'] = $clip['clip_result_url'];

            unset($item['video_token'], $item['extra'], $item['packaging_task_id'], $item['voice_id']);
        }
        unset($item);

        return $list;
    }

    /**
     * 空串视为不筛选；models.id=9 兼容为闪剪
     */
    protected function normalizedModelVersion(): ?int
    {
        $modelVersion = $this->request->get('model_version');

        // model_version=8 时，统计闪剪视频任务数量
        if ($modelVersion == 8) {
            return $this->shanjianQuery()->count();
        }

        $count = $this->buildQuery()->count();

        // 未指定 model_version 时，加上闪剪视频数量
        if (empty($modelVersion)) {
            $count += $this->shanjianQuery()->count();
        }

        return $count;
    }

    /**
     * 把搜索条件挂到 hv 别名上，避免 join user 后字段歧义
     */
    protected function buildQuery()
    {
        $user         = $this->request->get('user');
        $startTime    = $this->request->get('start_time');
        $endTime      = $this->request->get('end_time');
        $modelVersion = $this->request->get('model_version');

        // model_version=8 时，查询闪剪视频任务（shanjian_type=5）
        if ($modelVersion == 8) {
            return $this->buildShanjianQuery($user, $startTime, $endTime);
        }

        return HumanVideoTask::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->when($user, function ($query) use ($user) {
                $query->where('u.nickname', 'like', '%' . $user . '%');
            })
            ->when($startTime && $endTime, function ($query) use ($startTime, $endTime) {
                $query->whereBetween('hv.create_time', [strtotime($startTime), strtotime($endTime)]);
            })
            // 蝉镜 type5 桥接：同一 task_id 会同时出现在 human 与闪剪列表，后台只保留闪剪侧
            // 用 NOT IN 让子查询只物化一次；原 NOT EXISTS 是相关子查询，human 每行都要全表扫一遍闪剪表
            ->whereNotIn('hv.task_id', function ($query) {
                $query->name('shanjian_video_task')
                    ->where('shanjian_type', 5)
                    ->whereNull('delete_time')
                    ->field('task_id');
            })
            ->where($this->searchWhere);
    }

    /**
     * 按当前请求参数构建闪剪视频任务查询
     */
    protected function shanjianQuery()
    {
        return $this->buildShanjianQuery(
            $this->request->get('user'),
            $this->request->get('start_time'),
            $this->request->get('end_time')
        );
    }

    /**
     * 构建闪剪视频任务查询（model_version=8）
     */
    protected function buildShanjianQuery($user, $startTime, $endTime)
    {
        return ShanjianVideoTask::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->where('hv.shanjian_type', 5)
            ->when($user, function ($query) use ($user) {
                $query->where('u.nickname', 'like', '%' . $user . '%');
            })
            ->when($startTime && $endTime, function ($query) use ($startTime, $endTime) {
                $query->whereBetween('hv.create_time', [strtotime($startTime), strtotime($endTime)]);
            });
    }

    /**
     * 获取模型列表，同时以 id、model_version 为键
     */
    protected function getModelMap(): array
    {
        $list = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'is_enable', 'model_version'])
            ->where(['type' => ChatEnum::MODEL_TYPE_HUMAN])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($list as $row) {
            $map[(int)$row['id']] = $row;
            $version = (int)($row['model_version'] ?? 0);
            if ($version > 0) {
                $map[$version] = $row;
            }
        }

        return $map;
    }

    protected function resolveModelName(array $modelMap, int $modelVersion): string
    {
        if ($modelVersion <= 0) {
            return '';
        }

        return (string)($modelMap[$modelVersion]['name'] ?? '');
    }

    /**
     * 只加载当前页涉及的主播名称
     */
    protected function getAnchorMapByIds(array $anchorIds): array
    {
        $ids = [];
        foreach ($anchorIds as $anchorId) {
            $anchorId = trim((string)$anchorId);
            if ($anchorId !== '' && $anchorId !== '0') {
                $ids[] = $anchorId;
            }
        }
        if ($ids === []) {
            return [];
        }

        return HumanAnchor::whereIn('anchor_id', array_unique($ids))->column('name', 'anchor_id');
    }

    /**
     * 根据 model_version 获取对应的 change_type
     */
    protected function getChangeType(int $modelVersion): ?int
    {
        return match ($modelVersion) {
            1       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO,
            2       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO,
            4       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM,
            6       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT,
            7       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING,
            8       => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
            default => null,
        };
    }

    /**
     * 一次性查出本页任务流水，避免逐条扫 user_tokens_log
     *
     * @return array<string, array<int, array{points:float|int, duration:mixed}>>
     */
    protected function batchTokensCost(array $taskIds): array
    {
        $taskIds = $this->collectTaskIds($taskIds);
        if ($taskIds === []) {
            return [];
        }

        $changeTypes = array_values(array_unique(array_filter(array_merge(
            [
                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO,
                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO,
                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM,
                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT,
                AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING,
                self::CLIP_CHANGE_TYPE,
            ],
            self::SHANJIAN_VIDEO_CHANGE_TYPES,
            self::SHANJIAN_CLIP_CHANGE_TYPES
        ))));

        $logs = UserTokensLog::whereIn('task_id', $taskIds)
            ->whereIn('change_type', $changeTypes)
            ->field('task_id,extra,change_type,action,change_amount')
            ->select()
            ->toArray();

        $map = [];
        foreach ($logs as $log) {
            $taskId = (string)($log['task_id'] ?? '');
            $changeType = (int)($log['change_type'] ?? 0);
            if ($taskId === '' || $changeType === 0) {
                continue;
            }
            if (!isset($map[$taskId][$changeType])) {
                $map[$taskId][$changeType] = ['points' => 0, 'duration' => 0];
            }
            if ((int)$log['action'] === AccountLogEnum::INC) {
                $map[$taskId][$changeType]['points'] -= $log['change_amount'] ?? 0;
                continue;
            }
            $map[$taskId][$changeType]['points'] += $log['change_amount'] ?? 0;
            $extra = is_array($log['extra'] ?? null)
                ? $log['extra']
                : json_decode((string)($log['extra'] ?? ''), true);
            if (is_array($extra)) {
                $map[$taskId][$changeType]['duration'] = $extra['音视频时长']
                    ?? $extra['实际视频时长']
                    ?? $map[$taskId][$changeType]['duration'];
            }
        }

        return $map;
    }

    /**
     * @param  int|array  $changeType
     * @return array{0:float|int,1:mixed}
     */
    protected function sumCost(array $costMap, string $taskId, int|array $changeType): array
    {
        if ($taskId === '') {
            return [0, 0];
        }

        $points = 0;
        $duration = 0;
        foreach ((array)$changeType as $type) {
            $row = $costMap[$taskId][(int)$type] ?? null;
            if (!$row) {
                continue;
            }
            $points += $row['points'] ?? 0;
            if (!empty($row['duration'])) {
                $duration = $row['duration'];
            }
        }

        return [$points, $duration];
    }

    /**
     * 统计某任务的 tokens 消耗（单条兜底）
     *
     * @param  int        $userId
     * @param  mixed      $taskId
     * @param  int|array  $changeType
     * @return array  [points, duration]
     */
    protected function getTokensCost(int $userId, $taskId, int|array $changeType): array
    {
        $points   = 0;
        $duration = 0;

        if ($taskId === '' || $taskId === null) {
            return [$points, $duration];
        }

        $query = UserTokensLog::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->field('extra,change_type,action,change_amount');
        if (is_array($changeType)) {
            $query->whereIn('change_type', $changeType);
        } else {
            $query->where('change_type', $changeType);
        }

        $query->select()->each(function ($log) use (&$points, &$duration) {
            if ($log['action'] == AccountLogEnum::INC) {
                $points -= $log['change_amount'];
            } else {
                $points += $log['change_amount'] ?? 0;
                $extra   = is_array($log['extra']) ? $log['extra'] : json_decode((string)$log['extra'], true);
                $duration = $extra['音视频时长'] ?? $extra['实际视频时长'] ?? $duration;
            }
        });

        return [$points, $duration];
    }

    /**
     * 闪剪合成算力：优先任务表已结算的 video_token，否则按流水净消耗回填
     */
    protected function resolveShanjianVideoPoints(array $item, array $costMap = []): float|int
    {
        $videoToken = (float)($item['video_token'] ?? 0);
        if ($videoToken > 0) {
            return $videoToken;
        }

        $taskId = (string)($item['task_id'] ?? '');
        if ($costMap !== []) {
            [$points] = $this->sumCost($costMap, $taskId, self::SHANJIAN_VIDEO_CHANGE_TYPES);
        } else {
            [$points] = $this->getTokensCost(
                (int)$item['user_id'],
                $taskId,
                self::SHANJIAN_VIDEO_CHANGE_TYPES
            );
        }

        return $points > 0 ? $points : 0;
    }

    /**
     * 闪剪智剪字段：type=5 派生的 type=2 包装任务对应后台「剪辑」
     */
    protected function resolveShanjianClipFields(array $item, array $packagingMap, array $costMap = []): array
    {
        $extra = $item['extra'] ?? [];
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?: [];
        }
        $aiClipEnabled = !empty($extra['ai_clip_enabled']);
        $packagingId = (int)($item['packaging_task_id'] ?? 0);
        $packaging = $packagingId > 0 ? ($packagingMap[$packagingId] ?? null) : null;

        if (!$aiClipEnabled && !$packaging) {
            return [
                'automatic_clip'  => 0,
                'clip_status'     => 0,
                'clip_points'     => 0,
                'clip_result_url' => '',
            ];
        }

        $clipStatus = 1;
        $clipResultUrl = '';
        $clipPoints = 0;
        if ($packaging) {
            $clipStatus = match ((int)($packaging['status'] ?? 0)) {
                0, 1 => 2,
                2    => 4,
                3    => 3,
                default => 1,
            };
            if ($clipStatus === 3) {
                $clipResultUrl = FileService::getFileUrl($packaging['video_result_url'] ?? '');
                $clipToken = (float)($packaging['video_token'] ?? 0);
                if ($clipToken > 0) {
                    $clipPoints = $clipToken;
                } elseif ($costMap !== []) {
                    $clipPoints = $this->sumCost(
                        $costMap,
                        (string)($packaging['task_id'] ?? ''),
                        self::SHANJIAN_CLIP_CHANGE_TYPES
                    )[0];
                } else {
                    $clipPoints = $this->getTokensCost(
                        (int)($packaging['user_id'] ?? $item['user_id']),
                        $packaging['task_id'] ?? '',
                        self::SHANJIAN_CLIP_CHANGE_TYPES
                    )[0];
                }
                $clipPoints = $clipPoints > 0 ? $clipPoints : 0;
            }
        }

        return [
            'automatic_clip'  => 1,
            'clip_status'     => $clipStatus,
            'clip_points'     => $clipPoints,
            'clip_result_url' => $clipResultUrl,
        ];
    }

    /**
     * 闪剪任务状态 → 后台数字人视频状态
     */
    protected function mapShanjianStatusToHuman(int $status): int
    {
        return match ($status) {
            ShanjianVideoTask::STATUS_SUCCESS => 1,
            ShanjianVideoTask::STATUS_FAILED  => 2,
            default => 0,
        };
    }

    protected function getType5BridgeMap(array $taskIds): array
    {
        $taskIds = $this->collectTaskIds($taskIds);
        if ($taskIds === []) {
            return [];
        }

        $rows = ShanjianVideoTask::whereIn('task_id', $taskIds)
            ->where('shanjian_type', 5)
            ->field('id,task_id,user_id,status,video_token,duration,video_result_url,packaging_task_id,extra,name')
            ->select()
            ->toArray();

        return array_column($rows, null, 'task_id');
    }

    protected function getPackagingMap(array $list): array
    {
        $ids = [];
        foreach ($list as $item) {
            $id = (int)($item['packaging_task_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $rows = ShanjianVideoTask::whereIn('id', array_unique($ids))
            ->field('id,task_id,user_id,status,video_token,video_result_url')
            ->select()
            ->toArray();

        return array_column($rows, null, 'id');
    }

    protected function getVoiceNameMap(array $list): array
    {
        $voiceIds = [];
        foreach ($list as $item) {
            $voiceId = trim((string)($item['voice_id'] ?? ''));
            if ($voiceId !== '') {
                $voiceIds[] = $voiceId;
            }
        }
        if ($voiceIds === []) {
            return [];
        }

        return HumanVoice::whereIn('voice_id', array_unique($voiceIds))->column('name', 'voice_id');
    }

    protected function getShanjianAnchorMap(array $list): array
    {
        $anchorIds = [];
        foreach ($list as $item) {
            $anchorId = trim((string)($item['anchor_id'] ?? ''));
            if ($anchorId !== '') {
                $anchorIds[] = $anchorId;
            }
        }
        if ($anchorIds === []) {
            return [];
        }

        return ShanjianAnchor::whereIn('anchor_id', array_unique($anchorIds))->column('name', 'anchor_id');
    }

    /**
     * @param array $items
     * @return string[]
     */
    protected function collectTaskIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $taskId = trim((string)($item['task_id'] ?? ''));
            } else {
                $taskId = trim((string)$item);
            }
            if ($taskId !== '') {
                $ids[] = $taskId;
            }
        }

        return array_values(array_unique($ids));
    }
}
