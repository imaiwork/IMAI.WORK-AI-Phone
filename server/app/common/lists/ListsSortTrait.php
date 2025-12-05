<?php



namespace app\common\lists;


trait ListsSortTrait
{

    protected string $orderBy;
    protected string $field;

    /**
     * @notes 生成排序条件
     * @param $sortField
     * @param $defaultOrder
     * @return array|string[]
     * @author 令狐冲
     * @date 2021/7/16 00:06
     */
    private function createOrder($sortField, $defaultOrder)
    {
        if (empty($sortField) || empty($this->orderBy) || empty($this->field) || !in_array($this->field, array_keys($sortField))) {
            return $defaultOrder;
        }

        if (isset($sortField[$this->field])) {
            $field = $sortField[$this->field];
        } else {
            return $defaultOrder;
        }

        if ($this->orderBy == 'desc') {
            return [$field => 'desc'];
        }
        if ($this->orderBy == 'asc') {
            return [$field => 'asc'];
        }
        return $defaultOrder;
    }
}
