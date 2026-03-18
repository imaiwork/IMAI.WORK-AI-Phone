<?php
namespace app\adminapi\lists\cardcode;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\cardcode\CardPackage;

/**
 * 卡密套餐列表
 * Class CardPackageLists
 * @package app\adminapi\lists\cardcode
 */
class CardPackageLists extends BaseAdminDataLists
{
    /**
     * @notes 获取查询条件
     * @return array
     */
    private function getWhere()
    {
        $where = [];
        if (isset($this->params['name']) && $this->params['name'] !== '') {
            $where[] = ['name', 'like', '%' . $this->params['name'] . '%'];
        }
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', $this->params['status']];
        }
        return $where;
    }

    public function lists(): array
    {
        $lists = CardPackage::where($this->getWhere())
            ->limit($this->limitOffset, $this->limitLength)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();

        return $lists;
    }

    public function count(): int
    {
        return CardPackage::where($this->getWhere())->count();
    }
}
