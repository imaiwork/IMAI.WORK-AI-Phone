<?php


namespace app\api\lists\chat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Assistants;
use app\common\model\chat\Scene;

/**
 * 场景助手列表
 * Class SceneAssistantLists
 * @package app\api\lists\chat
 */
class SceneAssistantLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name']
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
        $sceneId = $this->request->get('scene_id', '');

        //判断scene_id是否是一级
        if ($sceneId && Scene::where('id', $sceneId)->where('pid', 0)->where('status', 1)->count()) {
            $sceneId = Scene::where('pid', $sceneId)->where('status', 1)->column('id');
        }

        return  Assistants::field('id,logo,name,description,create_time')
            ->where('status', 1)
            ->where($this->searchWhere)
            ->when($sceneId, function ($query) use ($sceneId) {
                if (is_array($sceneId)) {
                    $query->whereIn('scene_id', $sceneId);
                } else {
                    $query->where('scene_id', $sceneId);
                }
            })
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $sceneId = $this->request->get('scene_id', '');

        //判断scene_id是否是一级
        if ($sceneId && Scene::where('id', $sceneId)->where('pid', 0)->where('status', 1)->count()) {
            $sceneId = Scene::where('pid', $sceneId)->where('status', 1)->column('id');
        }

        return Assistants::where($this->searchWhere)
            ->where('status', 1)
            ->when($sceneId, function ($query) use ($sceneId) {
                if (is_array($sceneId)) {
                    $query->whereIn('scene_id', $sceneId);
                } else {
                    $query->where('scene_id', $sceneId);
                }
            })->count();
    }
}
