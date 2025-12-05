<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;

/**
 * 设备任务列表
 * Class TaskLists
 * @package app\api\lists\device
 * @author Qasim
 */
class CalendarTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['task_type', 'dt.device_code', 'day'],
            '%like%' => ['task_name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];
        return SvDeviceTask::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['start_time'] = date('H:i', $item['start_time']);
                $item['end_time'] = date('H:i', $item['end_time']);
                $item['task_category'] = DeviceEnum::getAccountTypeDesc($item['account_type']) . DeviceEnum::getTaskTypeDesc($item['task_type']);
                $item['name'] = '';
                switch ($item['source']) {

                    case DeviceEnum::TASK_SOURCE_PUBLISH:
                        //sv_publish_setting_account
                        $taskinfo = SvPublishSettingAccount::where('id', $item['sub_task_id'])->findOrEmpty()->toArray();
                        $item['name'] = $taskinfo['name'] ?? '';
                        break;

                    case DeviceEnum::TASK_SOURCE_TAKEOVER:
                        //sv_device_take_over_task_account
                        $taskinfo = SvDeviceTakeOverTaskAccount::where('id', $item['sub_task_id'])->findOrEmpty()->toArray();
                        if (!$taskinfo) {
                            $item['name'] = '';
                            break;
                        }
                        $item['name'] = SvDeviceTakeOverTask::where('id', $item['take_over_id'])->value('task_name') ?? '';
                        break;

                    case DeviceEnum::TASK_SOURCE_ACTIVE:
                        //sv_device_active_account

                        $taskinfo = SvDeviceActiveAccount::where('id', $item['sub_task_id'])->findOrEmpty()->toArray();
                        if (!$taskinfo) {
                            $item['name'] = '';
                            break;
                        }
                        $detail = SvDeviceActive::where('id', $taskinfo['active_id'])->findOrEmpty()->toArray();
                        $item['name'] = $detail['task_name'] ?? '';
                        break;

                    case DeviceEnum::TASK_SOURCE_FRIENDS:
                        //sv_crawling_manual_task
                        $taskinfo = SvCrawlingManualTask::where('id', $item['sub_task_id'])->findOrEmpty()->toArray();
                        $item['name'] = $taskinfo['name'] ?? '';
                        break;

                    case DeviceEnum::TASK_SOURCE_CLUES:
                        //sv_crawling_task
                        $taskinfo = SvCrawlingTask::where('id', $item['sub_task_id'])->findOrEmpty()->toArray();
                        $item['name'] = $taskinfo['name'] ?? '';
                        break;

                    default:
                        break;
                }


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
        return SvDeviceTask::alias('dt')
            ->where($this->searchWhere)
            ->count();
    }
}

