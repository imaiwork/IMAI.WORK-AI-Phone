<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCityExposureRecord;
use app\common\model\sv\SvCityExposureTaskAccount;

/**
 * 同城曝光子任务列表
 */
class CityExposureTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['ps.status', 'ps.task_type'],
            '%like%' => ['ps.name', 'a.account'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];

        return SvCityExposureTaskAccount::alias('ps')
                                        ->field('ps.*, a.nickname, a.avatar as account_avatar')
                                        ->join('sv_account a', 'a.account = ps.account AND a.device_code = ps.device_code AND a.type = ps.account_type', 'left')
                                        ->where($this->searchWhere)
                                        ->when(
                                            $this->request->get('start_time') && $this->request->get('end_time'),
                                            function ($query) {
                                                $query->whereBetween('ps.create_time', [
                                                    strtotime($this->request->get('start_time')),
                                                    strtotime($this->request->get('end_time')),
                                                ]);
                                            }
                                        )
                                        ->order('ps.id', 'desc')
                                        ->limit($this->limitOffset, $this->limitLength)
                                        ->select()
                                        ->each(function ($item) {
                                            // 统计已曝光数
                                            $item['record_count'] = SvCityExposureRecord::where('city_exposure_account_id', $item['id'])
                                                                                        ->where('status', 1)
                                                                                        ->count();
                                            return $item;
                                        })
                                        ->toArray();
    }

    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];

        return SvCityExposureTaskAccount::alias('ps')
                                        ->join('sv_account a', 'a.account = ps.account AND a.device_code = ps.device_code AND a.type = ps.account_type', 'left')
                                        ->where($this->searchWhere)
                                        ->count();
    }
}