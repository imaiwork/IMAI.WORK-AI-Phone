<?php


namespace app\adminapi\lists\hd;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\hd\HdCueWord;
use app\common\lists\ListsSearchInterface;


/**
 * HdCueWordl列表
 * Class HdCueWordLists
 * @package app\adminapi\listsmp
 */
class HdCueWordLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @desc 设置搜索条件
     * @return array[]
     * @date 2024/5/23 11:52
     * @author dagouzi
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['title'],
        ];
    }


    /**
     * @desc 获取列表
     * @return array
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {
        return HdCueWord::with(['category'])
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select()
            ->toArray();
    }


    /**
     * @desc 获取数量
     * @return int
     * @date 2024/5/23 11:52
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return HdCueWord::where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}
