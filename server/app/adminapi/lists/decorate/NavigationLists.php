<?php


namespace app\adminapi\lists\decorate;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\decorate\Navigation;

/**
 * 底部导航列表
 * Class NavigationLists
 * @package app\adminapi\lists\decorate
 */
class NavigationLists extends BaseAdminDataLists
{
    /**
     * @notes 底部导航列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author ljj
     * @date 2022/2/14 10:12 上午
     */
    public function lists(): array
    {
        return (new Navigation())->select()->toArray();
    }

    /**
     * @notes 底部导航总数
     * @return int
     * @author ljj
     * @date 2022/2/14 10:13 上午
     */
    public function count(): int
    {
        return (new Navigation())->count();
    }
}
