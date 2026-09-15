<?php

namespace app\api\lists\tutorial;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\tutorial\TutorialCategory;

class TutorialCategoryLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $list = TutorialCategory::where($this->searchWhere)
            ->where('status', 1)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        return $list;
    }

    public function count(): int
    {
        return TutorialCategory::where($this->searchWhere)
            ->where('status', 1)
            ->count();
    }
}
