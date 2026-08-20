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
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        $modelVersion = $this->request->get('model_version');
        
        // model_version=8 时，只使用闪剪视频任务列表
        if ($modelVersion == 8) {
            return $this->getShanjianLists();
        }

        // 模型列表（以 id 为键，便于 O(1) 查找）
        $modelMap = $this->getModelMap();

        // 主播列表预加载（避免循环内多次查库）
        $anchorMap = $this->getAnchorMap();

        // 获取普通数字人视频列表
        $normalList = $this->buildQuery()
            ->field('hv.voice_name,hv.id,hv.name,hv.user_id,hv.model_version,hv.anchor_id,
                hv.create_time,hv.update_time,hv.pic,hv.clip_result_url,hv.clip_status,hv.automatic_clip,
                hv.clip_type,hv.result_url,hv.gender,hv.status,hv.audio_type,hv.task_id,
                u.nickname,u.avatar,hv.remark')
            ->order(['hv.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelMap, $anchorMap) {
                // 文件 URL
                $item['pic']             = FileService::getFileUrl($item['pic']);
                $item['clip_result_url'] = FileService::getFileUrl($item['clip_result_url']);
                $item['result_url']      = FileService::getFileUrl($item['result_url']);
                $item['avatar']          = FileService::getFileUrl($item['avatar']);

                // 主播名称
                $item['anchor_name'] = $anchorMap[$item['anchor_id']] ?? '';

                // 模型名称
                $item['model_name'] = $modelMap[$item['model_version']]['name'] ?? '';

                // 视频生成消耗
                $changeType = $this->getChangeType((int)$item['model_version']);
                [$videoPoints, $duration] = $changeType
                    ? $this->getTokensCost($item['user_id'], $item['task_id'], $changeType)
                    : [0, 0];

                $item['video_points'] = $videoPoints;
                $item['duration']     = $duration;

                // 剪辑消耗（仅当剪辑完成时统计）
                $item['clip_points'] = $item['clip_status'] == 3
                    ? $this->getTokensCost($item['user_id'], $item['task_id'], self::CLIP_CHANGE_TYPE)[0]
                    : 0;
            })
            ->toArray();

        // 如果未指定 model_version，同时获取闪剪视频列表并合并
        if (empty($modelVersion)) {
            $shanjianList = $this->getShanjianLists();
            $normalList = array_merge($normalList, $shanjianList);
            
            // 按创建时间倒序排序
            usort($normalList, function ($a, $b) {
                return $b['create_time'] <=> $a['create_time'];
            });
            
            // 重新分页（取当前页数据）
            $normalList = array_slice($normalList, $this->limitOffset, $this->limitLength);
        }

        return $normalList;
    }

    /**
     * 获取闪剪视频任务列表（model_version=8）
     */
    protected function getShanjianLists(): array
    {
        $list = $this->buildShanjianQuery(
                $this->request->get('user'),
                $this->request->get('start_time'),
                $this->request->get('end_time')
            )
            ->field('hv.id,hv.name,hv.user_id,8 as model_version,hv.anchor_id,hv.voice_id,
                hv.create_time,hv.update_time,hv.status,hv.task_id,hv.pic,hv.remark,
                hv.audio_type,hv.duration,hv.video_token,hv.extra,hv.packaging_task_id,
                u.nickname,u.avatar,hv.video_result_url as result_url')
            ->order(['hv.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $packagingMap = $this->getPackagingMap($list);
        $voiceMap = $this->getVoiceNameMap($list);
        $anchorMap = $this->getAnchorMap();
        $shanjianAnchorMap = $this->getShanjianAnchorMap($list);

        foreach ($list as &$item) {
            $item['pic']        = FileService::getFileUrl($item['pic'] ?? '');
            $item['result_url'] = FileService::getFileUrl($item['result_url'] ?? '');
            $item['avatar']     = FileService::getFileUrl($item['avatar'] ?? '');

            $item['anchor_name'] = $anchorMap[$item['anchor_id']]
                ?? $shanjianAnchorMap[$item['anchor_id']]
                ?? '';
            $item['anchor_id']  = $item['anchor_id'] ?? 0;
            $item['model_name'] = '闪剪';
            $item['voice_name'] = $voiceMap[$item['voice_id'] ?? ''] ?? '';
            $item['gender']     = 0;
            $item['clip_type']  = 0;
            $item['audio_type'] = (int)($item['audio_type'] ?? 0);
            $item['remark']     = $item['remark'] ?? '';
            $item['duration']   = $item['duration'] ?? 0;

            // 后台前端按 human 任务状态渲染：1成功 2失败 0/5生成中
            $item['status'] = $this->mapShanjianStatusToHuman((int)($item['status'] ?? 0));

            $item['video_points'] = $this->resolveShanjianVideoPoints($item);

            $clip = $this->resolveShanjianClipFields($item, $packagingMap);
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
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $modelVersion = $this->request->get('model_version');
        
        // model_version=8 时，统计闪剪视频任务数量
        if ($modelVersion == 8) {
            return $this->buildShanjianQuery(
                $this->request->get('user'),
                $this->request->get('start_time'),
                $this->request->get('end_time')
            )->count();
        }
        
        $count = $this->buildQuery()->count();
        
        // 未指定 model_version 时，加上闪剪视频数量
        if (empty($modelVersion)) {
            $count += $this->buildShanjianQuery(
                $this->request->get('user'),
                $this->request->get('start_time'),
                $this->request->get('end_time')
            )->count();
        }
        
        return $count;
    }

    /**
     * 构建公共查询条件
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
            ->whereNotExists(function ($query) {
                $query->name('shanjian_video_task')
                    ->alias('sj_bridge')
                    ->whereRaw('sj_bridge.task_id = hv.task_id')
                    ->where('sj_bridge.shanjian_type', 5)
                    ->whereNull('sj_bridge.delete_time');
            })
            ->where($this->searchWhere);
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
     * 获取模型列表，以 id 为键
     */
    protected function getModelMap(): array
    {
        // 如果项目里模型版本来自数据库，用 Models 表
        $list = (new Models())
            ->field(['id', 'type', 'channel', 'logo', 'name', 'is_enable'])
            ->where(['type' => ChatEnum::MODEL_TYPE_HUMAN])
            ->order('sort asc, id desc')
            ->select()
            ->toArray();

        return array_column($list, null, 'id');

        // 如果你的原始数据真的来自 ConfigService，改成这样：
        // $list = ConfigService::get('model', 'list', []);
        // return array_column($list['channel'] ?? [], null, 'id');
    }

    /**
     * 预加载当前页涉及的主播名称
     * 注：这里简单返回全部，如数据量大可改为只查当前页涉及的 anchor_id
     */
    protected function getAnchorMap(): array
    {
        return HumanAnchor::column('name', 'anchor_id');
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
     * 统计某任务的 tokens 消耗
     *
     * @param  int        $userId
     * @param  mixed      $taskId
     * @param  int|array  $changeType
     * @return array  [points, duration]
     *   - action=1 退还:  points -= change_amount
     *   - action=2 扣减:  points += change_amount，并从 extra.音视频时长 中提取 duration
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
    protected function resolveShanjianVideoPoints(array $item): float|int
    {
        $videoToken = (float)($item['video_token'] ?? 0);
        if ($videoToken > 0) {
            return $videoToken;
        }

        [$points] = $this->getTokensCost(
            (int)$item['user_id'],
            $item['task_id'] ?? '',
            self::SHANJIAN_VIDEO_CHANGE_TYPES
        );

        return $points > 0 ? $points : 0;
    }

    /**
     * 闪剪智剪字段：type=5 派生的 type=2 包装任务对应后台「剪辑」
     */
    protected function resolveShanjianClipFields(array $item, array $packagingMap): array
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
                $clipPoints = $clipToken > 0
                    ? $clipToken
                    : $this->getTokensCost(
                        (int)($packaging['user_id'] ?? $item['user_id']),
                        $packaging['task_id'] ?? '',
                        self::SHANJIAN_CLIP_CHANGE_TYPES
                    )[0];
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

    protected function getPackagingMap(array $list): array
    {
        $ids = [];
        foreach ($list as $item) {
            $id = (int)($item['packaging_task_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
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
        if (empty($voiceIds)) {
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
        if (empty($anchorIds)) {
            return [];
        }

        return ShanjianAnchor::whereIn('anchor_id', array_unique($anchorIds))->column('name', 'anchor_id');
    }
}
