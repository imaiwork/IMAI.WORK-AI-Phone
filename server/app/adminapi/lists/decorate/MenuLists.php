<?php


namespace app\adminapi\lists\decorate;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\MenuEnum;
use app\common\model\decorate\Menu;

/**
 * 菜单列表
 * Class MenuLists
 * @package app\adminapi\lists\decorate
 */
class MenuLists extends BaseAdminDataLists
{
    /**
     * @notes 菜单列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author ljj
     * @date 2022/2/14 11:29 上午
     */
    public function lists(): array
    {
        $lists = (new Menu())->field('id,name,image,link_type,link_address,sort,status')
            ->order(['sort' => 'asc', 'id' => 'desc'])
            ->append(['link_address_desc', 'status_desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$list) {
            $list['link_address_desc'] = MenuEnum::getLinkDesc($list['link_type']) . ':' . $list['link_address_desc'];
        }

        return $lists;
    }

    /**
     * @notes 菜单总数
     * @return int
     * @author ljj
     * @date 2022/2/14 11:29 上午
     */
    public function count(): int
    {
        return (new Menu())->count();
    }
}
