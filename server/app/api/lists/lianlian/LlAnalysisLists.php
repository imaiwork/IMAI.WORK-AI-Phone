<?php

namespace app\api\lists\lianlian;

use app\common\model\lianlian\LlAnalysis;
use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\model\lianlian\LlChat;


/**
 * 列表
 * Class LlAnalysisLists
 * @package app\api\lists\lianlian
 */

 class LlAnalysisLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists(): array
    {

        return LlAnalysis::alias('la')
            ->join('ll_scene ls', 'ls.id = la.scene_id')
            ->where($this->searchWhere)
            ->whereIn('la.status', [1, 2, 3])
            ->where('la.user_id', $this->userId)
            ->field('la.id, la.scene_id, la.total_score, la.task_id, ls.name as scene_name, ls.logo as scene_logo, la.remark, la.start_time, la.end_time, la.status')
            ->order('la.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {

                $item['scene_logo'] = $item['scene_logo'] ? FileService::getFileUrl($item['scene_logo']) : '';

                //时长 end_time - start_time
                $item['duration']   = $item['end_time'] - $item['start_time'];
                $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
                $item['end_time']   = date('Y-m-d H:i:s', $item['end_time']);
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function count(): int
    {
        return LlAnalysis::alias('la')
            ->join('ll_scene ls', 'ls.id = la.scene_id')
            ->where($this->searchWhere)
            ->whereIn('la.status', [1, 2, 3])
            ->where('la.user_id', $this->userId)
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function setSearch(): array
    {
        return [];
    }
}
            