<?php

namespace app\api\lists\lianlian;

use app\common\model\lianlian\LlAnalysis;
use app\common\model\lianlian\LlScene;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\api\lists\BaseApiDataLists;

/**
 * 列表
 * Class LlSceneLists
 * @package app\api\lists\lianlian
 */
class LlSceneLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists(): array
    {
        return LlScene::where($this->searchWhere)
            ->when($this->request->get('common') != null, function ($query) {
                if ($this->request->get('common') == 1) {
                    $query->where('user_id', '=', 0);
                } else {
                    $query->where('user_id', '=', $this->userId);
                }
            })
            ->where('status', 1)
            ->field('id, name, logo, description')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['logo']           = $item['logo'] ? FileService::getFileUrl($item['logo']) : '';
                $analysis = LlAnalysis::where([
                                                  'scene_id'=>$item['id'],
                                                  'user_id'=>$this->userId,
                                                  'status'=>0,
                                                  'is_draft'=>1
                                              ])->findOrEmpty();
                $item['is_draft']       = $analysis->isEmpty() ? 0 : 1;
                $item['analysis_id']    = $analysis->id ?? 0;
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
        return LlScene::where($this->searchWhere)
            ->when($this->request->get('common') != null, function ($query) {
                if ($this->request->get('common') == 1) {
                    $query->where('user_id', '=', 0);
                } else {
                    $query->where('user_id', '=', $this->userId);
                }
            })
            ->where('status', 1)
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
            