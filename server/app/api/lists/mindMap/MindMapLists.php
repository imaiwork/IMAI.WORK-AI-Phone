<?php


namespace app\api\lists\mindMap;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\mindMap\MindMap;
use think\db\exception\DbException;


/**
 * 文章列表
 * Class ArticleLists
 * @package app\api\lists\article
 */
class MindMapLists extends BaseApiDataLists implements ListsSearchInterface
{

    /**
     * @notes 搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2022/9/16 18:54
     */
    public function setSearch(): array
    {
        return [
            "%like%" => ["ask"]
        ];
    }

    /**
     * @notes 获取思维导图对话列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/16 18:55
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return MindMap::where($this->searchWhere)
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }


    /**
     * @notes 获取数量
     * @return int
     * @throws DbException
     * @author 段誉
     * @date 2022/9/16 18:55
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return MindMap::where($this->searchWhere)->count();
    }
}
