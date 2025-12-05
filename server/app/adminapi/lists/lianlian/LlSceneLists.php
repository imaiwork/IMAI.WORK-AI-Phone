<?php

namespace app\adminapi\lists\lianlian;

use app\common\model\lianlian\LlScene;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\lianlian\LlAnalysis;


/**
 * 列表
 * Class LlSceneLists
 * @package app\Adminapi\lists\lianlian
 */
class LlSceneLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists(): array
    {

        return LlScene::alias('ls')
            ->leftJoin('user u', 'u.id = ls.user_id and ls.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ls.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->field('ls.id, ls.name, ls.logo, ls.description, ls.coach_name, ls.coach_persona, ls.coach_language, ls.coach_voice, ls.create_time, ls.status, u.nickname as user_name, u.avatar as user_avatar')
            ->order('ls.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['user_name']      = $item['user_name'] ?? '管理员';
                $item['user_avatar']    = $item['user_avatar'] ? FileService::getFileUrl($item['user_avatar']) : '';
                $item['logo']           = $item['logo'] ? FileService::getFileUrl($item['logo']) : '';

                //使用次数
                $item['use_count'] = LlAnalysis::where('scene_id', $item['id'])->count();
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
        return LlScene::alias('ls')
            ->leftJoin('user u', 'u.id = ls.user_id and ls.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ls.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
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
        return [
            "%like%" =>  ['ls.name'],
            '=' => ['ls.status'],
        ];
    }
}
