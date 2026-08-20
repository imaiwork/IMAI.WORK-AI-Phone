<?php

namespace app\adminapi\lists\deviceauth;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\model\deviceauth\DeviceAuthPlan;

class DeviceAuthPlanLists extends BaseAdminDataLists
{
    private function getWhere(): array
    {
        $where = [];
        if (isset($this->params['name']) && $this->params['name'] !== '') {
            $where[] = ['name', 'like', '%' . $this->params['name'] . '%'];
        }
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', $this->params['status']];
        }
        if (isset($this->params['type']) && $this->params['type'] !== '') {
            $where[] = ['type', '=', $this->params['type']];
        }
        return $where;
    }

    public function lists(): array
    {
        $lists = DeviceAuthPlan::where($this->getWhere())
            ->limit($this->limitOffset, $this->limitLength)
            ->order('sort desc, id desc')
            ->select()
            ->toArray();
        foreach ($lists as &$item) {
            $item['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['type']);
        }
        unset($item);
        return $lists;
    }

    public function count(): int
    {
        return DeviceAuthPlan::where($this->getWhere())->count();
    }
}
