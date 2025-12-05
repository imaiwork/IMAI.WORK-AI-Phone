<?php


namespace app\api\lists\knowledge;


use app\common\lists\BaseDataLists;
use app\common\model\knowledge\KnowledgeBind;
use app\common\lists\ListsSearchInterface;


/**
 * KnowledgeBind列表
 * Class KnowledgeBindLists
 * @package app\api\listsknowledge
 */
class KnowledgeBindLists extends BaseDataLists implements ListsSearchInterface
{


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author my
     * @date 2025/04/18 16:19
     */
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'kid', 'data_id', 'type', 'index_id', 'rerank_min_score'],
        ];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author my
     * @date 2025/04/18 16:19
     */
    public function lists(): array
    {
        return KnowledgeBind::where($this->searchWhere)
            ->field(['id', 'user_id', 'kid', 'data_id', 'type', 'index_id', 'rerank_min_score'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();
    }


    /**
     * @notes 获取数量
     * @return int
     * @author my
     * @date 2025/04/18 16:19
     */
    public function count(): int
    {
        return KnowledgeBind::where($this->searchWhere)->count();
    }

}