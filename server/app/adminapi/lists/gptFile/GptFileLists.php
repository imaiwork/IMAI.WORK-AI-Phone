<?php


namespace app\adminapi\lists\gptFile;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\file\GptFile;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class GptFileLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
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
        $this->searchWhere[] = ['user_id', '=', 0];
        return GptFile::field('id,file_id,file_name,create_time,purpose,file_id')
            ->where($this->searchWhere)
            ->order('id', 'desc')
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
        $this->searchWhere[] = ['user_id', '=', 0];
        return GptFile::where($this->searchWhere)->count();
    }
}
