<?php


namespace app\api\lists\chat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;


/**
 * 模型列表
 * Class ModelLists
 * @package app\api\lists\chat
 */
class ModelLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
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
        return Models::field('id, type, logo, name, create_time, update_time')
            ->where($this->searchWhere)
            ->where('type', 1)
            ->select()
            ->each(function ($item) {
                $item['model_name'] = ModelsCost::where('model_id', $item['id'])->value('name');
                $item['configs'] = [];
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
        return Models::where($this->searchWhere)->where('type', 1)->count();
    }
}
