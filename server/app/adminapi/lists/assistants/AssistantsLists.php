<?php


namespace app\adminapi\lists\assistants;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Assistants;
use app\common\service\FileService;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class AssistantsLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['a.name', 'a.description'],
            '=' => ['a.status']
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
        $this->searchWhere[] = ['a.user_id', '=', 0];
        $this->searchWhere[] = ['a.scene_id', '<>', 0];
        return Assistants::alias('a')
            ->leftJoin('scene s', 's.id = a.scene_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('scene_name'), function ($query) {
                $query->where('s.name', 'like', '%' . $this->request->get('scene_name') . '%');
            })
            ->field('a.*,s.name as scene_name')
            ->json(['tools'], true)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                $data['logo']               = FileService::getFileUrl($data['logo']);
                return $data;
            })
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
        $this->searchWhere[] = ['a.user_id', '=', 0];
        $this->searchWhere[] = ['a.scene_id', '<>', 0];
        return Assistants::alias('a')
            ->leftJoin('scene s', 's.id = a.scene_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('scene_name'), function ($query) {
                $query->where('s.name', 'like', '%' . $this->request->get('scene_name') . '%');
            })
            ->count();
    }
}
