<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCityExposureRecord;

/**
 * 同城曝光执行记录列表
 */
class CityExposureRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['ps.status', 'ps.task_type'],
            '%like%' => ['ps.account_name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.city_exposure_account_id', '=', $this->request->get('id', '')];

        return SvCityExposureRecord::alias('ps')
                                   ->field('ps.*')
                                   ->where($this->searchWhere)
                                   ->when(
                                       $this->request->get('start_time') && $this->request->get('end_time'),
                                       function ($query) {
                                           $query->whereBetween('ps.exec_time', [
                                               strtotime($this->request->get('start_time')),
                                               strtotime($this->request->get('end_time')),
                                           ]);
                                       }
                                   )
                                   ->order('ps.id', 'desc')
                                   ->limit($this->limitOffset, $this->limitLength)
                                   ->select()
                                   ->each(function ($item) {
                                       // 超时未回报 -> 标记失败
                                       if ((int)$item['status'] === 3 && (time() - $item['exec_time']) > 600) {
                                           $item['status'] = 2;
                                           $item['remark'] = '同城曝光执行超时';
                                           $item->save();
                                       }
                                       if ((int)$item['status'] === 1) {
                                           $item['remark'] = '';
                                       }
                                       return $item;
                                   })
                                   ->toArray();
    }

    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.city_exposure_account_id', '=', $this->request->get('id', '')];

        return SvCityExposureRecord::alias('ps')
                                   ->where($this->searchWhere)
                                   ->count();
    }
}