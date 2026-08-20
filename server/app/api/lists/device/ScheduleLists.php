<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceExecutionSchedule;
use app\common\model\sv\SvDeviceExecutionScheduleUser;

/**
 * 设备执行计划列表
 * Class ScheduleLists
 * @package app\api\lists\device
 * @author Qasim
 */
class ScheduleLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['s.persona_type']
        ];
    }

    /**
     * @notes 获取设备执行计划列表
     * @return array
     */
    public function lists(): array
    {
        return SvDeviceExecutionSchedule::alias('s') 
            ->field('s.*, 1 as status')
            ->where($this->searchWhere)
            ->order('s.id', 'asc')
            ->select()
            ->each(function ($item) {
                $item->time = [$item['start_time'], $item['end_time']];
                $find = SvDeviceExecutionScheduleUser::alias('u') 
                    ->field('u.status')
                    ->where('u.schedule_id', $item['id'])
                    ->where('u.persona_type', $item['persona_type'])
                    ->where('u.device_code', $this->request->get('device_code'))
                    ->where('u.user_id', $this->userId)
                    ->findOrEmpty();
                $item->status = $find->isEmpty() ? $item->status : $find->status;
                return $item;
            })
            ->toArray();

    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvDeviceExecutionSchedule::alias('s') 
            ->field('s.id')
            ->where($this->searchWhere)
            ->count();
    }
}

