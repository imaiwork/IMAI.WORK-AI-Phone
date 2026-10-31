<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\service\FileService;

/**
 * 闪剪视频模板列表。
 *
 * 两种用途共用：
 *   - 后台模板选择器（人设合成等）：默认只出前台可见（status=1）的模板，行为与改造前一致；
 *   - 模板管理页（manage=1）：包含已隐藏的行，并返回中心排序/本站排序/生效排序。
 */
class ShanjianClipTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['scene', 'auto_type', 'status'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        return $this->baseQuery()
            ->orderRaw(ShanjianClipTemplate::SORT_EXPRESSION . ' DESC, id DESC')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item->cover_url = FileService::getFileUrl((string)$item->cover_url, '', true);
                $item->demo_url = FileService::getFileUrl((string)$item->demo_url, '', true);
                // 前端只读展示用：中心排序 / 本站覆盖 / 生效排序
                $sort = (int)$item->getData('sort');
                $sortOverride = $item->getData('sort_override');
                $item->sort = $sort;
                $item->sort_override = $sortOverride === null ? null : (int)$sortOverride;
                $item->effective_sort = $sortOverride === null ? $sort : (int)$sortOverride;
                // 类型统一成整型：前端用 === 1 判断开关，别依赖数据库驱动的返回类型
                $item->status = (int)$item->getData('status');
                $item->auto_type = (int)$item->getData('auto_type');
            })
            ->toArray();
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function baseQuery()
    {
        $query = ShanjianClipTemplate::where($this->searchWhere);

        // 选择器场景默认不返回已隐藏模板；管理页显式传 manage=1 才看得到全部
        if (!$this->isManage() && !$this->hasStatusFilter()) {
            $query->where('status', 1);
        }

        return $query;
    }

    private function hasStatusFilter(): bool
    {
        foreach ($this->searchWhere as $where) {
            if (is_array($where) && ($where[0] ?? '') === 'status') {
                return true;
            }
        }

        return false;
    }

    private function isManage(): bool
    {
        return (int)($this->request->get('manage', 0)) === 1;
    }
}
