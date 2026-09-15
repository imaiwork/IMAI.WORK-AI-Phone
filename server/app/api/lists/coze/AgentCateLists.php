<?php


namespace app\api\lists\coze;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\coze\AgentCate;


/**
 */
class AgentCateLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "=" => ['id', 'pid'],
            "%like%" => ['name']
        ];
    }

    /**
     * @desc 获取列表
     * @return array
     * @date 2024/7/6 10:52
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {
        if (!empty($this->request->get('type'))) {
            $this->searchWhere[] = ['type', '=', $this->request->get('type')];
        }
        return AgentCate::where($this->searchWhere)
            ->order('sort DESC,id DESC')
            ->where('status', 1)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                $data['sub_list'] = AgentCate::where('pid', $data['id'])->select()->toArray();
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
        return AgentCate::where($this->searchWhere)->where('status', 1)->count();
    }
}
