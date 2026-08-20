<?php

namespace app\adminapi\lists\marketing;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\service\FileService;
use app\common\model\user\User;

class MarketingTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['mt.status'],
            '%like%' => ['mt.name'],
        ];
    }

    public function lists(): array
    {
        //$this->searchWhere['user_id'] = 0;
        $list = MarketingTemplate::alias('mt')->field('mt.*,mc.name as category_name')
            ->join('marketing_category mc', 'mc.id = mt.category_id', 'left')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('mt.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('category_name'), function ($query) {
                $query->where('mc.name', 'like', '%' . $this->request->get('category_name') . '%');
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->order('mt.id', 'desc')
            ->select()
            ->each(function ($item) {
                $item->schedule_count = MarketingTemplateSchedule::where('template_id', $item['id'])->order('start_time', 'asc')->select()->count();
                $item->user_info = User::field('id,nickname,avatar')->where('id', $item['user_id'])->findOrEmpty();
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return MarketingTemplate::alias('mt')->field('mt.*,mc.name as category_name')
            ->join('marketing_category mc', 'mc.id = mt.category_id', 'left')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('mt.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('category_name'), function ($query) {
                $query->where('mc.name', 'like', '%' . $this->request->get('category_name') . '%');
            })->count();
    }
}
