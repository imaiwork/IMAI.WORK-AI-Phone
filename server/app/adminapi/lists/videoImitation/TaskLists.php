<?php

namespace app\adminapi\lists\videoImitation;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\enum\user\AccountLogEnum;
use app\common\service\FileService;

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

        // 批量获取消耗记录并分组汇总
        $logsGrouped = [];
        if (!empty($taskIds)) {
            $logs = UserTokensLog::where('task_id', 'in', $taskIds)
                ->where('change_type', 'in', [
                    AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION,
                    AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_ADD,
                    AccountLogEnum::TOKENS_INC_VIDEO_IMITATION_REFUND
                ])
                ->select()
                ->toArray();

            foreach ($logs as $log) {
                $taskId = $log['task_id'];
                if (!isset($logsGrouped[$taskId])) {
                    $logsGrouped[$taskId] = 0;
                }
                if ($log['action'] == AccountLogEnum::DEC) {
                    $logsGrouped[$taskId] += floatval($log['change_amount']);
                } else if ($log['action'] == AccountLogEnum::INC) {
                    $logsGrouped[$taskId] -= floatval($log['change_amount']);
                }
            }
        }

        // 统一收集所有的形象ID和音色ID
        $avatarIdsToFetch = [];
        $voiceIdsToFetch = [];
        foreach ($lists as $item) {
            if (!empty($item['avatar_id'])) {
                $avatarIdsToFetch[] = $item['avatar_id'];
            }
            if ($item['is_material'] == 0) {
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
            // 装载用户信息
            $item['nickname'] = $users[$item['user_id']]['nickname'] ?? '';
            $item['user_sn'] = $users[$item['user_id']]['sn'] ?? '';
            $item['account'] = $users[$item['user_id']]['account'] ?? '';
            
            // 资源链接处理
            $item['thumbnail'] = !empty($item['thumbnail']) ? FileService::getFileUrl($item['thumbnail']) : '';
            $item['video_url'] = !empty($item['video_url']) ? FileService::getFileUrl($item['video_url']) : '';
            
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
            
            // 装载消耗算力
            $item['total_tokens_cost'] = round(max(0, $logsGrouped[$item['id']] ?? 0), 2);

            // 装载形象与音色名称
            $item['avatar_name'] = '';
            if (!empty($item['avatar_id'])) {
                $item['avatar_name'] = $avatarsMap[$item['avatar_id']]['avatar_name'] ?? '';
            }

            $item['voice_name'] = '';
            if ($item['is_material'] == 0) {
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
}
