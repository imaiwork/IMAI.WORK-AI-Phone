<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\wechat\AiWechat;
use think\facade\Cache;

use app\common\model\kb\KbRobot;


/**
 * 设备列表
 * Class DeviceLists
 * @package app\api\lists\device
 * @author Qasim
 */
class DeviceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['device_name', 'device_code']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return SvDevice::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                Cache::store('redis')->select(env('redis.WS_SELECT', 8));
                $wechatCode = Cache::store('redis')->get("xhs:device:{$item['device_code']}:wechat_code");
                if ($wechatCode) {
                    $item->wechat_device_code = $wechatCode;
                    $item->save();
                }

                $item['accounts'] =  SvAccount::alias('w')
                    ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
                    ->leftJoin('sv_setting s', 's.account = w.account')
                    ->where('w.device_code', '=', $item['device_code'])
                    ->order('w.id', 'desc')
                    ->select()
                    ->each(function ($item) {
                        if (empty($item['takeover_mode'])) {
                            $item['takeover_mode'] = 0;
                        }

                        if (empty($item['robot_id'])) {
                            $item['robot_id'] = 0;
                        }

                        $item['robot_name'] = KbRobot::where('id', $item['robot_id'])->where('user_id', $this->userId)->value('name', '');

                        if (!empty($item['extra'])) {
                            $extraArray = json_decode($item['extra'], true);
                        } else {
                            $extraArray = [];
                        }
                        foreach ($extraArray  as $key => $v) {
                            $item[$key] = $v;
                        }

                        return $item;
                    })
                    ->toArray();
                // 查询离当前时间节点最近的3个任务（涵盖在执行中的）
                $currentTime = time();
                $item['tasks'] = SvDeviceTask::where(['device_code' => $item['device_code'], 'user_id' => $this->userId])
                    ->where('status', 'in', [0, 1])
                    ->order('start_time asc, status desc')
                    ->orderRaw('ABS(start_time - ' . $currentTime . ') ASC')
                    ->limit(3)
                    ->select()
                    ->each(function ($task) use ($currentTime) {
                        $task['start_time'] = date('Y-m-d H:i:s', $task['start_time']);
                        $task['end_time'] = date('Y-m-d H:i:s', $task['end_time']);
                        return $task;
                    })
                    ->toArray();
                if (count($item['tasks']) == 0) {
                    $item['task_count'] = 0;
                    $item['task_complete'] = 0;
                } else {
                    $item['task_count'] = SvDeviceTask::where('device_code', $item['device_code'])
                        ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                        ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();
                    $item['task_complete'] = SvDeviceTask::where('device_code', $item['device_code'])
                        ->where('status', '=', 2)
                        ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                        ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();
                }

                //$this->addDeviceRpa($item);

                $item['device_name'] = is_null($item['device_name']) ? $item['device_model'] : $item['device_name'];
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
        return SvDevice::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }

    private  function addDeviceRpa(SvDevice $device)
    {
        $maps = array(
            ['app_icon' => '', 'app_type' => 1, 'app_name' => '视频号', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 1],
            ['app_icon' => '', 'app_type' => 3, 'app_name' => '小红书', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 0],
            ['app_icon' => '', 'app_type' => 4, 'app_name' => '抖音', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 2],
            ['app_icon' => '', 'app_type' => 5, 'app_name' => '快手', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 3],
        );

        $appCount = SvDeviceRpa::where('device_code', $device->device_code)->count();
        if ($appCount == 0) {
            foreach ($maps as &$item) {
                $item['device_code'] = $device->device_code;
                $item['user_id'] = $this->userId;
                $item['create_time'] = time();
            }
            $model = new SvDeviceRpa();
            $model->insertAll($maps);
        }
    }
}
