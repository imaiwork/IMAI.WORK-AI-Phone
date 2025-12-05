<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\kb\KbRobot;
use app\common\model\kb\KbRobotCategory;

/**
 * 机器人分类列表
 */
class KbRobotCateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbRobotCategory();
        $lists = $model
            ->withoutField('delete_time')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();

        $modelKbRobot = new KbRobot();
        foreach ($lists as &$item) {
            $item['example_sum'] = $modelKbRobot->where(['cate_id'=>$item['id']])->count();
        }

        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbRobotCategory();
        return $model
            ->withoutField('delete_time')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->count();
    }

    /**
     * @notes 搜索
     * @return array|array[]
     * @author kb
     */
    public function setSearch(): array
    {
        return [
            '='      => ['is_enable'],
            '%like%' => ['name']
        ]??[];
    }
}