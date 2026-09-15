<?php

namespace app\api\lists\tutorial;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\tutorial\Tutorial;
use app\common\model\tutorial\TutorialCategory;

class TutorialLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['tutorial_category_id', 'main_type'],
            '%like%' => ['title'],
        ];
    }

    public function lists(): array
    {
        $list = Tutorial::where($this->searchWhere)
            ->where('status', 1)
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['cate_name'] = TutorialCategory::where('id', $item['tutorial_category_id'])
                ->where('status', 1)
                ->value('name');
            $item['main_type_text'] = $item['main_type'] == 1 ? '视频' : '图片';
        }

        return $list;
    }

    public function count(): int
    {
        return Tutorial::where($this->searchWhere)
            ->where('status', 1)
            ->count();
    }
}
