<?php

namespace app\adminapi\lists\videoImitation;

use app\adminapi\lists\BaseAdminDataLists;
use app\api\logic\videoImitation\TaskLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use app\common\service\ShanjianQueueService;
use app\common\service\videoImitation\VideoImitationImageBilling;

/**
 * 视频复刻任务列表
 */
class TaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    /**
     * 构建基础查询条件
     * @return array
     */
    public function where(): array
    {
        $where = [];

        // 软删除过滤
        $where[] = ['t.task_delete', '=', 0];

        // 状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] > 2) {
                $where[] = ['t.status', '=', intval($this->params['status'])];
            } else {
                $status = explode(',', $this->params['status']);
                $where[] = ['t.status', 'in', $status];
            }
        }

        // 媒体类型：1视频仿写 2图文仿写；不传则全部返回
        if (isset($this->params['media_type']) && $this->params['media_type'] !== '') {
            $mediaType = (int)$this->params['media_type'];
            if (in_array($mediaType, [VideoImitationTask::MEDIA_TYPE_VIDEO, VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT], true)) {
                $where[] = ['t.media_type', '=', $mediaType];
            }
        }

        // 平台：3小红书 4抖音（与 AppType 对齐）；不传则全部返回
        if (isset($this->params['platform_type']) && $this->params['platform_type'] !== '') {
            $platformType = (int)$this->params['platform_type'];
            if (in_array($platformType, [3, 4], true)) {
                $where[] = ['t.platform_type', '=', $platformType];
            }
        }

        // 用户信息组合筛选（用户主键ID，用户sn，用户名）
        if (isset($this->params['keyword']) && $this->params['keyword'] !== '') {
            $userWhere = User::where(function ($query) {
                $keyword = '%' . $this->params['keyword'] . '%';
                $query->where('sn', 'like', $keyword)
                    ->whereOr('nickname', 'like', $keyword)
                    ->whereOr('mobile', 'like', $keyword);
                if (is_numeric($this->params['keyword'])) {
                     $query->whereOr('id', '=', $this->params['keyword']);
                }
            })->column('id');
            $where[] = !empty($userWhere) ? ['t.user_id', 'in', $userWhere] : ['t.user_id', '=', 0];
        }

        // 时间筛选
        if (isset($this->params['start_time']) && $this->params['start_time'] !== '') {
            $where[] = ['t.create_time', '>=', strtotime($this->params['start_time'])];
        }
        if (isset($this->params['end_time']) && $this->params['end_time'] !== '') {
            $where[] = ['t.create_time', '<=', strtotime($this->params['end_time'])];
        }

        return $where;
    }

    /**
     * 获取列表数据
     * @return array
     */
    public function lists(): array
    {
        $model = new VideoImitationTask();
        $lists = $model->withSearch($this->setSearch(), $this->params)
            ->alias('t')
            ->field('t.*')
            ->where($this->where())
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('t.id desc')
            ->select()
            ->toArray();

        $userIds = array_unique(array_column($lists, 'user_id'));
        $taskIds = array_column($lists, 'id');

        // 批量获取用户信息
        $users = [];
        if (!empty($userIds)) {
            $users = User::where('id', 'in', $userIds)->column('nickname, sn, account', 'id');
        }

        // 批量获取消耗记录并分组汇总 + 明细（视频/图文分口径，同源同批）
        $logsGrouped = [];
        $detailsByTask = [];
        if (!empty($taskIds)) {
            $imageTextTaskIds = [];
            $videoTaskIds = [];
            $billingRoundByTaskId = [];
            foreach ($lists as $row) {
                $id = (int)$row['id'];
                if ((int)($row['media_type'] ?? VideoImitationTask::MEDIA_TYPE_VIDEO) === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
                    $imageTextTaskIds[] = $id;
                    $billingRoundByTaskId[$id] = (int)($row['billing_round'] ?? 1);
                } else {
                    $videoTaskIds[] = $id;
                }
            }

            // 视频：保持原白名单 + 数字 task_id（不扩展生成/素材等）
            if (!empty($videoTaskIds)) {
                $videoLogs = UserTokensLog::where('task_id', 'in', $videoTaskIds)
                    ->where('change_type', 'in', [
                        AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION,
                        AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_ADD,
                        AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND,
                        AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE,
                    ])
                    ->select()
                    ->toArray();
                foreach ($videoLogs as $log) {
                    $tid = (int)$log['task_id'];
                    self::accumulateTokenCost($logsGrouped, $tid, $log);
                    self::appendTokenCostDetail($detailsByTask, $tid, $log);
                }
            }

            // 图文：仅抓取(10321)+改写(2002)；忽略视频文案提取/视频生成等侧流水
            // 新数据：数字 task_id + 判别；旧数据：枚举复合键 IN，不再 LIKE
            if (!empty($imageTextTaskIds)) {
                $imageLogsByTask = self::collectImageTextTokenLogs($imageTextTaskIds, $billingRoundByTaskId);
                foreach ($imageLogsByTask as $tid => $taskLogs) {
                    foreach ($taskLogs as $log) {
                        self::accumulateTokenCost($logsGrouped, (int)$tid, $log);
                        self::appendTokenCostDetail($detailsByTask, (int)$tid, $log);
                    }
                }
            }

            foreach ($detailsByTask as $tid => $rows) {
                usort($rows, static function (array $a, array $b): int {
                    $ta = (int)($a['_sort_time'] ?? 0);
                    $tb = (int)($b['_sort_time'] ?? 0);
                    if ($ta === $tb) {
                        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
                    }
                    return $ta <=> $tb;
                });
                foreach ($rows as &$row) {
                    unset($row['_sort_time']);
                }
                unset($row);
                $detailsByTask[$tid] = array_values($rows);
            }
        }

        // 统一收集所有的形象ID和音色ID
        $avatarIdsToFetch = [];
        $voiceIdsToFetch = [];
        foreach ($lists as $item) {
            if (!empty($item['avatar_id'])) {
                $avatarIdsToFetch[] = $item['avatar_id'];
            }
            if (($item['is_material'] ?? 0) == 0) {
                if (!empty($item['voice_id'])) {
                    $avatarIdsToFetch[] = $item['voice_id'];
                }
            } else {
                if (!empty($item['voice_id'])) {
                    $voiceIdsToFetch[] = $item['voice_id'];
                }
            }
        }
        
        $avatarIdsToFetch = array_unique($avatarIdsToFetch);
        $voiceIdsToFetch = array_unique($voiceIdsToFetch);

        $avatarsMap = [];
        if (!empty($avatarIdsToFetch)) {
            $avatarsMap = \app\common\model\aiPersona\AiPersonaDigitalAvatar::where('id', 'in', $avatarIdsToFetch)
                ->column('avatar_name, voice_name', 'id');
        }

        $voicesMap = [];
        if (!empty($voiceIdsToFetch)) {
            $voicesMap = \app\common\model\aiPersona\AiPersonaDigitalVoice::where('voice_id', 'in', $voiceIdsToFetch)
                ->column('voice_id, voice_name', 'voice_id');
        }

        foreach ($lists as &$item) {
            $item['queue_status_text'] = ShanjianQueueService::statusText(
                (string)($item['queue_status'] ?? ''),
                (int)($item['queue_position'] ?? 0)
            );
            // 装载用户信息
            $item['nickname'] = $users[$item['user_id']]['nickname'] ?? '';
            $item['user_sn'] = $users[$item['user_id']]['sn'] ?? '';
            $item['account'] = $users[$item['user_id']]['account'] ?? '';
            
            // 资源链接处理
            $item['thumbnail'] = !empty($item['thumbnail']) ? FileService::getFileUrl($item['thumbnail']) : '';
            $item['video_url'] = !empty($item['video_url']) ? FileService::getFileUrl($item['video_url']) : '';

            // 图文相关字段（对齐用户端 TaskLists）
            $taskModel = new VideoImitationTask();
            $taskModel->data($item);
            $originalImages = is_array($taskModel->original_images) ? $taskModel->original_images : [];
            $selectedImages = is_array($taskModel->selected_images) ? $taskModel->selected_images : [];
            $rewrittenImages = is_array($taskModel->rewritten_images) ? $taskModel->rewritten_images : [];
            if (empty($originalImages) && !empty($item['original_images']) && is_string($item['original_images'])) {
                $originalImages = json_decode($item['original_images'], true) ?: [];
            }
            if (empty($selectedImages) && !empty($item['selected_images']) && is_string($item['selected_images'])) {
                $selectedImages = json_decode($item['selected_images'], true) ?: [];
            }
            if (empty($rewrittenImages) && !empty($item['rewritten_images']) && is_string($item['rewritten_images'])) {
                $rewrittenImages = json_decode($item['rewritten_images'], true) ?: [];
            }
            $item['original_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $originalImages
            ));
            $item['selected_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $selectedImages
            ));
            $item['rewritten_images'] = array_values(array_map(
                static fn($u) => FileService::getFileUrl((string)(is_array($u) ? ($u['url'] ?? '') : $u)),
                $rewrittenImages
            ));
            $item['image_count'] = count($item['rewritten_images']) > 0
                ? count($item['rewritten_images'])
                : count($item['original_images']);
            $item['platform_type'] = (int)($item['platform_type'] ?? 4);
            $item['media_type'] = (int)($item['media_type'] ?? 1);
            $item['platform_type_text'] = match ($item['platform_type']) {
                3 => '小红书',
                4 => '抖音',
                1 => '视频号',
                5 => '快手',
                default => '未知平台',
            };
            $item['media_type_text'] = $item['media_type'] === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
                ? '图文'
                : '视频';
            $item['image_rewrite_status'] = (int)($item['image_rewrite_status'] ?? 0);
            $item['progress_steps'] = TaskLogic::buildProgressSteps($taskModel);
            
            if (!empty($item['analysis_tags']) && is_string($item['analysis_tags'])) {
                $item['analysis_tags'] = json_decode($item['analysis_tags'], true) ?: [];
            } else {
                $item['analysis_tags'] = [];
            }
            if (!empty($item['publish_topic']) && is_string($item['publish_topic'])) {
                $item['publish_topic'] = json_decode($item['publish_topic'], true) ?: [];
            } else {
                $item['publish_topic'] = [];
            }
            
            // 时间转换
            $item['create_time_desc'] = !empty($item['create_time']) ? date('Y-m-d H:i:s', (int)$item['create_time']) : '';
            
            // 装载消耗算力（汇总 + 明细，同源口径）
            $item['total_tokens_cost'] = round(max(0, $logsGrouped[$item['id']] ?? 0), 2);
            $item['tokens_cost_details'] = $detailsByTask[(int)$item['id']] ?? [];

            // 装载形象与音色名称
            $item['avatar_name'] = '';
            if (!empty($item['avatar_id'])) {
                $item['avatar_name'] = $avatarsMap[$item['avatar_id']]['avatar_name'] ?? '';
            }

            $item['voice_name'] = '';
            if (($item['is_material'] ?? 0) == 0) {
                if (!empty($item['voice_id'])) {
                    $item['voice_name'] = $avatarsMap[$item['voice_id']]['voice_name'] ?? '';
                }
            } else {
                if (!empty($item['voice_id'])) {
                    $item['voice_name'] = $voicesMap[$item['voice_id']]['voice_name'] ?? '';
                }
            }
        }

        return $lists;
    }

    /**
     * 统计总数
     * @return int
     */
    public function count(): int
    {
        return (new VideoImitationTask())->alias('t')
            ->where($this->where())
            ->where($this->searchWhere)
            ->count('t.id');
    }

    /**
     * 图文算力流水一次归集：数字 task_id + 枚举旧复合键 IN
     *
     * @param list<int> $imageTextTaskIds
     * @param array<int,int> $billingRoundByTaskId  任务id => billing_round
     * @return array<int, list<array<string,mixed>>>  任务id => 流水行
     */
    public static function collectImageTextTokenLogs(array $imageTextTaskIds, array $billingRoundByTaskId): array
    {
        $normalizedIds = [];
        foreach ($imageTextTaskIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $normalizedIds[] = $id;
            }
        }
        $imageTextTaskIds = array_values(array_unique($normalizedIds));
        if ($imageTextTaskIds === []) {
            return [];
        }

        $taskIdKeys = [];
        $sourceSns = [];
        foreach ($imageTextTaskIds as $taskId) {
            $maxRound = (int)($billingRoundByTaskId[$taskId] ?? 1);
            $taskIdKeys[] = (string)$taskId;
            foreach (VideoImitationImageBilling::legacyTaskIds($taskId, $maxRound) as $key) {
                $taskIdKeys[] = $key;
            }
            foreach (VideoImitationImageBilling::legacySourceSns($taskId, $maxRound) as $key) {
                $sourceSns[] = $key;
            }
        }
        $taskIdKeys = array_values(array_unique($taskIdKeys));
        $sourceSns = array_values(array_unique($sourceSns));

        $query = UserTokensLog::where('change_type', 'in', [
            AccountLogEnum::TOKENS_DEC_IMAGES_EXPLOSION_REWRITE,
            AccountLogEnum::TOKENS_DEC_IMAGE_TO_IMAGE,
        ]);
        $query->where(function ($q) use ($taskIdKeys, $sourceSns) {
            $q->where('task_id', 'in', $taskIdKeys);
            if ($sourceSns !== []) {
                $q->whereOr('source_sn', 'in', $sourceSns);
            }
        });
        $logs = $query->select()->toArray();

        $consumedLogIds = [];
        $result = [];
        foreach ($logs as $log) {
            $logId = (int)($log['id'] ?? 0);
            if ($logId > 0 && isset($consumedLogIds[$logId])) {
                continue;
            }

            $rowTaskId = (string)($log['task_id'] ?? '');
            if ($rowTaskId !== '' && ctype_digit($rowTaskId) && !VideoImitationImageBilling::isImageTextLog($log)) {
                continue;
            }

            $resolvedId = self::resolveImageTextTaskIdFromLog($log);
            if ($resolvedId === null || !in_array($resolvedId, $imageTextTaskIds, true)) {
                continue;
            }

            if ($logId > 0) {
                $consumedLogIds[$logId] = true;
            }
            if (!isset($result[$resolvedId])) {
                $result[$resolvedId] = [];
            }
            $result[$resolvedId][] = $log;
        }

        return $result;
    }

    /**
     * 从图文流水解析任务主键（task_id / source_sn 计费键，或 extra.task_id）
     */
    private static function resolveImageTextTaskIdFromLog(array $log): ?int
    {
        foreach ([(string)($log['task_id'] ?? ''), (string)($log['source_sn'] ?? '')] as $billingKey) {
            if ($billingKey !== '' && preg_match('/^video_imitation_(?:info_extract|img)_(\d+)_/', $billingKey, $matches) === 1) {
                return (int)$matches[1];
            }
        }

        $extra = $log['extra'] ?? null;
        if (is_string($extra) && $extra !== '') {
            $extra = json_decode($extra, true);
        }
        if (is_array($extra) && isset($extra['task_id']) && is_numeric($extra['task_id'])) {
            return (int)$extra['task_id'];
        }

        if (VideoImitationImageBilling::isImageTextLog($log)) {
            $numericTaskId = (string)($log['task_id'] ?? '');
            if ($numericTaskId !== '' && ctype_digit($numericTaskId)) {
                return (int)$numericTaskId;
            }
        }

        return null;
    }

    /**
     * 按流水 action 累加/扣减消耗算力
     */
    private static function accumulateTokenCost(array &$logsGrouped, int $taskId, array $log): void
    {
        if ($taskId <= 0) {
            return;
        }
        if (!isset($logsGrouped[$taskId])) {
            $logsGrouped[$taskId] = 0;
        }
        $amount = floatval($log['change_amount'] ?? 0);
        if ((int)($log['action'] ?? 0) === AccountLogEnum::DEC) {
            $logsGrouped[$taskId] += $amount;
        } elseif ((int)($log['action'] ?? 0) === AccountLogEnum::INC) {
            $logsGrouped[$taskId] -= $amount;
        }
    }

    /**
     * 收集单条算力明细（与汇总同源；单条异常跳过）
     *
     * @param array<int, list<array<string, mixed>>> $detailsByTask
     */
    private static function appendTokenCostDetail(array &$detailsByTask, int $taskId, array $log): void
    {
        if ($taskId <= 0) {
            return;
        }
        try {
            if (!isset($detailsByTask[$taskId])) {
                $detailsByTask[$taskId] = [];
            }
            $detailsByTask[$taskId][] = self::buildTokenCostDetail($log);
        } catch (\Throwable $e) {
            // 单条坏数据不影响整页
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildTokenCostDetail(array $log): array
    {
        $action = (int)($log['action'] ?? 0);
        $amount = round(floatval($log['change_amount'] ?? 0), 2);
        $signed = 0.0;
        if ($action === AccountLogEnum::DEC) {
            $signed = $amount;
        } elseif ($action === AccountLogEnum::INC) {
            $signed = -$amount;
        }

        $extra = $log['extra'] ?? null;
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            $extra = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($extra)) {
            $extra = [];
        }

        $rawTime = $log['create_time'] ?? 0;
        $sortTime = 0;
        $createTime = '';
        if (is_numeric($rawTime) && (int)$rawTime > 0) {
            $sortTime = (int)$rawTime;
            $createTime = date('Y-m-d H:i:s', $sortTime);
        } elseif (is_string($rawTime) && trim($rawTime) !== '' && trim($rawTime) !== '0') {
            $ts = strtotime($rawTime);
            if ($ts !== false && $ts > 0) {
                $sortTime = $ts;
                $createTime = date('Y-m-d H:i:s', $ts);
            }
        }

        $changeType = (int)($log['change_type'] ?? 0);
        $actionDesc = '其他';
        if ($action === AccountLogEnum::DEC) {
            $actionDesc = '扣除';
        } elseif ($action === AccountLogEnum::INC) {
            $actionDesc = '退还';
        }

        return [
            'id' => (int)($log['id'] ?? 0),
            'change_type' => $changeType,
            'change_type_desc' => (string)(AccountLogEnum::getChangeTypeDesc($changeType) ?: ''),
            'action' => $action,
            'action_desc' => $actionDesc,
            'change_amount' => $amount,
            'signed_amount' => round($signed, 2),
            'create_time' => $createTime,
            'remark' => (string)($log['remark'] ?? ''),
            'extra' => $extra,
            '_sort_time' => $sortTime,
        ];
    }
}
