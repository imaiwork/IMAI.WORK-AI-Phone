<?php


namespace app\api\lists\tools;

use app\api\lists\BaseApiDataLists;
use app\common\model\assistants\Assistants;
use app\common\model\tools\Tools;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class ToolsLists extends BaseApiDataLists
{
    /**
     * @notes 获取工具列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['status', '=', 1];
        $list['tools']       = Tools::where($this->searchWhere)
            ->order(['sort' => "desc", 'id' => "desc"])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        $list['assistants']  = Assistants::where([['user_id', '=', $this->userId], ['status', '=', 1],])
            ->where('type', 1)
            ->where('is_show', 1)
            ->field('id,logo,name,description,instructions,use_time,assistants_id')
            ->order('use_time', 'desc')
            ->limit(0, 3)
            ->select()
            ->toArray();
        return $list;
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $this->searchWhere[] = ['status', '=', 1];
        return Tools::where($this->searchWhere)->count();
    }
}
