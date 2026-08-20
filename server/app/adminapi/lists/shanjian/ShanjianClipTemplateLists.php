<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\FileService;

/**
 * 闪剪视频模板列表（与用户端 api 列表字段对齐，供后台模板选择）
 */
class ShanjianClipTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['scene', 'auto_type'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        return ShanjianClipTemplate::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item->cover_url = FileService::getFileUrl((string)$item->cover_url, '', true);
                $item->demo_url = FileService::getFileUrl((string)$item->demo_url, '', true);
            })
            ->toArray();
    }

    public function count(): int
    {
        return ShanjianClipTemplate::where($this->searchWhere)->count();
    }
}
