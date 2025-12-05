<?php

namespace app\adminapi\lists\assistants;

use app\common\model\chat\Scene;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;


/**
 * 列表
 * Class RechargeLists
 * @package app\Adminapi\lists\assistants
 */
class SceneLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['pid', '=', 0];
        return Scene::where($this->searchWhere)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->when($this->request->get('name'), function ($query) {
                $query->where('name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                $data['sub_list']   = Scene::where('pid', $data['id'])->select()->toArray();
                $data['logo']       = FileService::getFileUrl($data['logo']);
                return $data;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function count(): int
    {
        $this->searchWhere[] = ['pid', '=', 0];
        return Scene::where($this->searchWhere)
            ->when($this->request->get('name'), function ($query) {
                $query->where('name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function setSearch(): array
    {
        return [];
    }
}
