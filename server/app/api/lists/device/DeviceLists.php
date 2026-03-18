<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsExtendInterface;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\wechat\AiWechat;
use think\facade\Cache;

use app\common\model\auto\AutoDeviceConfig;

use app\common\model\kb\KbRobot;


/**
 * 设备列表
 * Class DeviceLists
 * @package app\api\lists\device
 * @author Qasim
 */
class DeviceLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
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
                if ($wechatCode && empty($item->wechat_device_code)) {
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
                $item['tasks'] = SvDeviceTask::where('device_code',$item['device_code'])
                    ->where('user_id', $this->userId)
                    ->where('status', 'in', [0, 1])
                    ->where('auto_type', $item['auto_type'])
                    ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                    ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))
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
                    $item['task_waiting_count'] = 0;
                } else {
                    $item['task_waiting_count'] = SvDeviceTask::where('device_code', $item['device_code'])
                        ->where('user_id', $this->userId)
                        ->where('auto_type', $item['auto_type'])
                        ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                        ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))
                        ->where('status', 'in', [0, 1])
                        ->count();

                    $item['task_count'] = SvDeviceTask::where('device_code', $item['device_code'])
                        ->where('user_id', $this->userId)
                        ->where('auto_type', $item['auto_type'])
                        ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                        ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();

                    $item['task_complete'] = SvDeviceTask::where('device_code', $item['device_code'])
                        ->where('user_id', $this->userId)
                        ->where('auto_type', $item['auto_type'])
                        ->where('status', '=', 2)
                        ->where('start_time', '<=', strtotime(date('Y-m-d 23:59:59')))
                        ->where('end_time', '>=', strtotime(date('Y-m-d 00:00:00')))->count();
                }

                //$this->addDeviceRpa($item);

                $item['device_name'] = is_null($item['device_name']) ? $item['device_model'] : $item['device_name'];
                $item['is_auto_setting'] = 0;
                if ($item['auto_type'] === 1) {
                    list($setting, $task_status, $is_config) = self::getAutoConfigStatus($item);
                    $item['is_auto_setting'] = $is_config;
                }

                if ($item['status'] !== 2) {
                    Cache::store('redis')->handler()->select(env('redis.WS_SELECT', 8));
                    $status = Cache::store('redis')->handler()->get("xhs:device:{$item['device_code']}:status");
                    $item['status'] = unserialize($status) === 'online' ? 1 : 0;
                    $item->save();
                }

                $item['is_empty'] =  \app\common\model\auto\AutoDeviceConfig::where('user_id', $item->user_id)->where('device_code', $item['device_code'])->findOrEmpty()->isEmpty() ? 1 : 0;
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

    public function extend(): array
    {
        $result = [];
        $items = SvDevice::field('count(device_code) as num, status')
            ->where('user_id', $this->userId)
            ->group('status')
            ->select();
        $result = [
            [
                'num' => 0,
                'status' => 0,
            ],
            [
                'num' => 0,
                'status' => 1,
            ],
            [
                'num' => 0,
                'status' => 2,
            ],
        ];
        if (!$items->isEmpty()) {
            foreach ($items as $item) {
                $result[$item['status']]['num'] = $item['num'];
            }
        }
        return [
            'statistics' => $result
        ];
    }

    private  function getAutoConfigStatus($find)
    {
        $setting = array(
            'clues_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'touch_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'takeover_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->where('robot_id', '>', 0)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->where('robot_id', '>', 0)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            // 'active_setting' => [
            //     'task_status' => ($status = \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
            //     'is_config' => \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            // ],
            'publish_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceSetting::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceSetting::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'add_wechat_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'circle_like_reply_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', $this->userId)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'wechat_circle_setting' => [
                'task_status' => ($status = \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->value('status')) !== null ? $status : 0,
                'is_config' => \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1,
            ],
            'analysis' => [
                'task_status' => 2,
                'is_config' => self::checkAnalysis($find),
            ]
        );
        $status = [];
        $isConfig = [];
        foreach ($setting as $key => $value) {
            array_push($status, $value['task_status']);
            array_push($isConfig, $value['is_config']);
        }
        $status = array_values(array_unique($status));
        sort($status);
        $isConfig = array_values(array_unique($isConfig));

        $task_status = function ($status) {
            if(count($status) > 1 && (in_array(0, $status) || in_array(1, $status) || in_array(3, $status))){
                return $status[0];
            }
            return 2;
        };
        $is_config = count($isConfig) > 1 ? 2 : ($isConfig[0] == 1 ? 1 : 0);
        return [$setting, $task_status($status), $is_config];
    }

    private static function checkAnalysis(SvDevice $device): int
    {
        $find = AutoDeviceConfig::where('user_id', $device->user_id)->where('device_code', $device->device_code)->findOrEmpty();
        if($find->isEmpty()){
            return 0;
        }
        if(empty($find->analysis)){
            return 0;
        }
        $analysis = json_decode($find->analysis, true);

        $is_config = 1;
        foreach ($analysis as $key => $value) {
            if ($value == '') {
                $is_config = 0;
                break;
            }
        }
        return $is_config;
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
