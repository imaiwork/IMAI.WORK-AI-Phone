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
        // 后台隐藏（status=0）的模板不进前台列表：手动创作、24 小时创作与热点/爆款复刻都读这里
        $list = ShanjianClipTemplate::where($this->searchWhere)
            ->where('status', 1)
            // 后台设过本站排序优先，否则用模板中心的 sort 倒序（越大越靠前）；同序再按 id 兜底，保证分页稳定不重不漏
            ->orderRaw(ShanjianClipTemplate::SORT_EXPRESSION . ' DESC, id DESC')
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
        return ShanjianClipTemplate::where($this->searchWhere)->where('status', 1)->count();
    }
}
