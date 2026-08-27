<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\FileService;

class ShanjianClipTemplateLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['name', 'scene', 'auto_type'],
            'like' => ['name'],
        ];
    }

    public function lists(): array
    {
        $list = ShanjianClipTemplate::where($this->searchWhere)
            // 按中台下发的 sort 倒序（越大越靠前）；sort 相同（含未下发的 0）再按 id 兜底，保证分页稳定不重不漏
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item->cover_url = FileService::getFileUrl((string)$item->cover_url, '', true);
                $item->demo_url = FileService::getFileUrl((string)$item->demo_url, '', true);
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return ShanjianClipTemplate::where($this->searchWhere)->count();
    }
}
