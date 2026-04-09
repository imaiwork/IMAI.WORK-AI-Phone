<?php

namespace app\api\lists\videoImitation;

use app\api\lists\BaseApiDataLists;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;

/**
 * 视频复刻任务列表
 */
class TaskLists extends BaseApiDataLists
{
    /**
     * 搜索条件
     * @return array
     */
    public function queryWhere(): array
    {
        $where = [];
        $where[] = ['user_id', '=', $this->userId];

        // 状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            if ($this->params['status'] == 3) {
                $where[] = ['status', '=', $this->params['status']];
            } else {
                $status = explode(',', $this->params['status']);
                $where[] = ['status', 'in', $status];
            }
        }

        // 人设筛选
        if (!empty($this->params['persona_id'])) {
            $where[] = ['persona_id', '=', $this->params['persona_id']];
        }

        $where[] = ['task_delete', '=', 0];

        return $where;
    }

    /**
     * 获取列表
     * @return array
     */
    public function lists(): array
    {
        $lists = VideoImitationTask::where($this->queryWhere())
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->withTrashed()
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['video_url'] = !empty($item['video_url']) ? FileService::getFileUrl($item['video_url']) : '';
            $item['thumbnail'] = !empty($item['thumbnail']) ? FileService::getFileUrl($item['thumbnail']) : '';
            if (!empty($item['analysis_tags']) && is_string($item['analysis_tags'])) {
                $item['analysis_tags'] = json_decode($item['analysis_tags'], true) ?: [];
            }
        }

        return $lists;
    }

    /**
     * 获取总数
     * @return int
     */
    public function count(): int
    {
        return VideoImitationTask::where($this->queryWhere())->count();
    }
}
