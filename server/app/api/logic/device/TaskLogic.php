<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechat;
use app\common\traits\{DeviceTaskTrait};
use think\facade\Db;

/**
 * 设备任务逻辑
 * Class TaskLogic
 * @package app\api\logic\device
 */
class TaskLogic extends ApiLogic
{
    use DeviceTaskTrait;

    public static function add(array $data)
    {
        $result = SvDeviceTask::insertAll($data);
        if (!$result) {
            throw new \Exception('设备任务添加失败');
        }
    }

    public static function checkAccounts(array $accounts)
    {
        $result = [];
        foreach ($accounts as $account) {
            $find = SvAccount::field('id, device_code')->where('account', $account['account'])->where('type', $account['type'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception( $account['account'] . '账号不存在');
            }
            if(array_key_exists($find->device_code, $result)){
                $result[$find->device_code] +=  1;
            }else{
                $result[$find->device_code] = 1;
            }
        }

        $values = array_sum(array_unique(array_values($result)));
        if($values > 1){
            throw new \Exception('该任务只能选择同一种平台的账号');
        }
    }

    public static function checkExecTime(string $date, int $taskType, array $times = [], array $devices = []): array
    {
        try {
            $date = $date == '' ? date('Y-m-d', time()) : $date;
            foreach ($times as $time) {
                $time = explode('-', $time);
                if (count($time) != 2) {
                    throw new \Exception('任务执行时间区间格式错误');
                }
                if (strtotime($date . ' ' . $time[0]) >= strtotime($date . ' ' . $time[1])) {
                    throw new \Exception('任务执行时间区间结束时间不能小于开始时间');
                }
                if (strtotime($date . ' ' . $time[0]) < time()) {
                    throw new \Exception('任务执行时间区间不能小于当前时间');
                }
            }
            $resTimes = [];
            foreach ($devices as $device) {
                foreach ($times as $time) {
                    $time = explode('-', $time);
                    $newStartTime = strtotime($date . ' ' . $time[0] . ':00');
                    $newEndTime = strtotime($date . ' ' . $time[1] . ':00');
                    list($isOverlap, $lap) = self::isTaskTimeOverlapping($device, $taskType, $newStartTime, $newEndTime, $task['id'] ?? null);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type']) . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }
                    $resTimes[] = [
                        'device_code' => $device,
                        'task_type' => $taskType,
                        'start_time' => strtotime($newStartTime),
                        'end_time' => strtotime($newEndTime),
                    ];
                }
            }
            return [true, $resTimes];
        } catch (\Throwable $th) {
            //throw $th;
            return [false, $th->getMessage()];
        }
        return [false, '任务执行时间校验失败'];
    }

    /**
     * 检查设备任务时间是否与现有任务重叠
     * @param string $deviceCode 设备编码
     * @param int $taskType 任务类型
     * @param int $startTime 开始时间
     * @param int $endTime 结束时间
     * @param int|null $excludeTaskId 排除的任务ID（编辑时使用）
     * @return bool 是否存在重叠
     */
    public static function isTaskTimeOverlapping(string $deviceCode, int $taskType, int $startTime, int $endTime, int $userId): array
    {
        $query = SvDeviceTask::where('device_code', $deviceCode)->where('user_id', $userId)->where('start_time', '<', $endTime)->where('end_time', '>', $startTime);

        $find = $query->findOrEmpty();

        if ($find->isEmpty()) {
            return [true, []];
        }
        return [false, $find->toArray()];
    }

    public static function getTimes(array $timeConfigs, string $startOrgDate, int $days, array $customDates = [])
    {
        $resTimes = [];
        if (!empty($customDates)) {
            foreach ($customDates as $date) {
                foreach ($timeConfigs as $time) {
                    $time = explode('-', $time);
                    $newStartTime = $date . ' ' . $time[0] . ':00';
                    $newEndTime = $date . ' ' . $time[1] . ':00';
                    if (count($time) != 2) {
                        throw new \Exception('任务执行时间区间格式错误');
                    }
                    if (strtotime($newStartTime) >= strtotime($newEndTime)) {
                        throw new \Exception('任务执行时间区间结束时间不能小于开始时间');
                    }
                    // if (strtotime($newStartTime) < time()) {
                    //     //throw new \Exception('任务执行时间区间不能小于当前时间');
                    //     continue;
                    // }

                    $resTimes[] = [
                        'start_time' => strtotime($newStartTime),
                        'end_time' => strtotime($newEndTime),
                    ];
                }
            }
        } else {
            $start_times = [];
            for ($i = 0; $i < $days; $i++) {
                foreach ($timeConfigs as $time) {
                    $startDate = $startOrgDate;
                    $startDate = date('Y-m-d', strtotime("{$startDate} +" . $i . " day"));

                    $time = explode('-', $time);
                    $newStartTime = $startDate . ' ' . $time[0] . ':00';
                    if(strtotime($newStartTime) < time()){
                        $startDate = date('Y-m-d', strtotime("{$startDate} +" . ($i + 1) ." day"));
                        $newStartTime = $startDate . ' ' . $time[0] . ':00';
                    }
                    $newEndTime = $time[1] == '00:00' ? date('Y-m-d 00:00:00', strtotime("{$startDate} +1 day")) : $startDate . ' ' . $time[1] . ':00';

                    if (count($time) != 2) {
                        throw new \Exception('任务执行时间区间格式错误');
                    }
                    if (strtotime($newStartTime) >= strtotime($newEndTime)) {
                        throw new \Exception('任务执行时间区间结束时间不能小于开始时间1');
                    }

                    if (in_array($newStartTime, $start_times)) {
                        continue;
                    }
                    $start_times[] = $newStartTime;

                    $resTimes[] = [
                        'start_time' => strtotime($newStartTime),
                        'end_time' => strtotime($newEndTime),
                    ];
                }
                //$startDate = date('Y-m-d', strtotime("{$startDate} +1 day"));
            }
            array_multisort(array_column($resTimes, 'start_time'), SORT_ASC, $resTimes);
        }
        //print_r($resTimes);die;
        return $resTimes;
    }

    public static function statistics($day, $device_code)
    {
        if (!$day) {
            $day = date('Y-m-d');
        }
        $where = [
            'dt.day' => $day,
            'dt.user_id' => self::$uid,
        ];
        if ($device_code) {
            $where['dt.device_code'] = $device_code;
        }


        $all = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();
        $where['dt.status'] = 0;
        $waiting = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();

        $where['dt.status'] = 1;
        $execution = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();

        $where['dt.status'] = 2;
        $completed = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();

        $where['dt.status'] = 3;
        $failure = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();

        $where['dt.status'] = 4;
        $interrupt = SvDeviceTask::alias('dt')
            ->join('sv_device d', 'd.device_code = dt.device_code', 'left')
            ->where($where)
            ->count();


        self::$returnData = [
            'all' => $all,
            'waiting' => $waiting,
            'execution' => $execution,
            'completed' => $completed,
            'failure' => $failure,
            'interrupt' => $interrupt,
            'all_completed' => $completed + $failure + $interrupt
        ];
        return true;
    }


    public static function deleteTask($params)
    {
        $source = $params['source'] ?? '';
        $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type')
            ->where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty()->toArray();
        if (!$task) {
            self::setError('数据不存在');
            return false;
        }
        try {
            Db::startTrans();

            switch ($source) {

                case DeviceEnum::TASK_SOURCE_PUBLISH:
                    //sv_publish_setting_account
                    $taskinfo = SvPublishSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('任务不存在');
                    }
                    $start_time = date('Y-m-d H:i:s', $task['start_time']);
                    $end_time = date('Y-m-d H:i:s', $task['end_time']);
                    $count = SvPublishSettingDetail::where('publish_id', $taskinfo['publish_id'])->where('publish_account_id', $taskinfo['id'])->where('user_id', self::$uid)->count();
                    if ($count == 1){
                        SvPublishSettingAccount::where('id', $taskinfo['id'])->where('user_id', self::$uid)->select()->delete();
                    }
                    SvPublishSettingDetail::where('publish_id', $taskinfo['publish_id'])->where('publish_account_id', $taskinfo['id'])->where('publish_time', 'between', [$start_time, $end_time])->where('user_id', self::$uid)->select()->delete();
                    break;

                case DeviceEnum::TASK_SOURCE_TAKEOVER:
                    //sv_device_take_over_task_account
                    $taskinfo = SvDeviceTakeOverTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('接管任务不存在');
                    }
                    SvDeviceTakeOverTaskAccount::where('id', $params['sub_task_id'])->delete();
                    SvDeviceTakeOverTask::where('id', $taskinfo['take_over_id'])->select()->delete();
                    break;

                case DeviceEnum::TASK_SOURCE_ACTIVE:
                    //sv_device_active_account
                    $taskinfo = SvDeviceActiveAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('养号任务不存在');
                    }
                    $count = SvDeviceActiveAccount::where('active_id', $taskinfo['active_id'])->where('user_id', self::$uid)->count();
                    if ($count == 1) {
                        SvDeviceActive::where('id', $taskinfo['active_id'])->select()->delete();
                    }
                    SvDeviceActiveAccount::where('id', $taskinfo['id'])->select()->delete();

                    break;

                case DeviceEnum::TASK_SOURCE_FRIENDS:
                    //sv_crawling_manual_task
                    $taskinfo = SvCrawlingManualTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('自动加好友任务不存在');
                    }
                    SvCrawlingManualTask::where('id', $params['sub_task_id'])->select()->delete();
                    break;

                case DeviceEnum::TASK_SOURCE_CLUES:
                    //sv_crawling_task
                    $taskinfo = SvCrawlingTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('爬取任务不存在');
                    }
                    $deviceIds = json_decode($taskinfo['device_codes'], true);
                    \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
                    foreach ($deviceIds as $_deviceId) {
                        $isRun = SvDeviceTask::where('sub_task_id', $params['sub_task_id'])->where('device_code', $_deviceId)->where('task_type', 4)->where('status', 1)->findOrEmpty();
                        if (!$isRun->isEmpty()) {
                            $data = array(
                                'type' => 22,
                                'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                                'content' => json_encode(array(
                                    'task_id' => $params['sub_task_id'],
                                    'deviceId' => $_deviceId,
                                    'msg' => '任务删除'

                                ), JSON_UNESCAPED_UNICODE),
                                'deviceId' => $_deviceId,
                                'appVersion' => '2.1.1',
                                'messageId' => 0,
                            );

                            $channel = "device.{$_deviceId}.message";
                            \Channel\Client::publish($channel, [
                                'data' => json_encode($data)
                            ]);
                        }
                    }
                    SvCrawlingTask::where('id', $params['sub_task_id'])->select()->delete();
                    SvCrawlingTaskDeviceBind::where('task_id', $params['sub_task_id'])->select()->delete();
                    SvDeviceTask::Where('sub_task_id', $params['sub_task_id'])->where('user_id', self::$uid)->where('task_type', DeviceEnum::TASK_SOURCE_CLUES)->select()->delete();
                    break;

                default:

                    throw new \Exception('参数错误');
                    break;
            }

            SvDeviceTask::Where('id', $params['id'])->where('user_id', self::$uid)->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }


    public static function subtasks(array $params)
    {
        try {

            $source = $params['source'] ?? 0;
            $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty()->toArray();
            if (!$task) {
                self::setError('数据不存在');
                return false;
            }
            $task['detail'] = '';
            switch ($source) {

                case DeviceEnum::TASK_SOURCE_PUBLISH:
                    //sv_publish_setting_account
                    $taskinfo = SvPublishSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();

                    if (!$taskinfo) {
                        throw new \Exception('任务不存在');
                    }
                    $start_time = date('Y-m-d H:i:s', $task['start_time']);
                    $end_time = date('Y-m-d H:i:s', $task['end_time']);
                    $detail =  SvPublishSettingDetail::where('publish_id', $taskinfo['publish_id'])->where('user_id', self::$uid)
                        ->where('publish_account_id', $taskinfo['id'])->where('publish_time', 'between', [$start_time, $end_time])->findOrEmpty()->toArray();
                    if ($detail) {
                        $material_tag = $detail['material_tag'] = trim($detail['material_tag']);
                        if ($material_tag && !empty($material_tag)) {
                            $detail['material_tag'] =  explode(",", $detail['material_tag']);;
                        }
                        $detail['name'] =  $taskinfo['name'] ?? '';
                        $task['detail'] = $detail;
                    }
                    break;

                case DeviceEnum::TASK_SOURCE_TAKEOVER:
                    //sv_device_take_over_task_account
                    $taskinfo = SvDeviceTakeOverTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('接管任务不存在');
                    }
                    break;

                case DeviceEnum::TASK_SOURCE_ACTIVE:
                    //sv_device_active_account
                    $taskinfo = SvDeviceActiveAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('养号任务不存在');
                    }
                    $detail = SvDeviceActive::where('id', $taskinfo['active_id'])->findOrEmpty()->toArray();
                    if ($detail) {
                        $detail['name'] =  $detail['task_name'] ?? '';
                        $task['detail'] = $detail;
                    }
                    break;

                case DeviceEnum::TASK_SOURCE_FRIENDS:
                    //sv_crawling_manual_task
                    $taskinfo = SvCrawlingManualTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('自动加好友任务不存在');
                    }

                    $detail = SvCrawlingManualTask::where('id', $params['sub_task_id'])->findOrEmpty()->toArray();
                    if ($detail) {
                        $detail['name'] =  $taskinfo['name'] ?? '';
                        $task['detail'] = $detail;
                    }
                    break;

                case DeviceEnum::TASK_SOURCE_CLUES:
                    //sv_crawling_task
                    $taskinfo = SvCrawlingTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('爬取任务不存在');
                    }
                    $detail = SvCrawlingTaskDeviceBind::where('task_id', $taskinfo['id'])->where('device_code', $task['device_code'])->findOrEmpty()->toArray();
                    if ($detail) {
                        $detail['name'] =  $taskinfo['name'] ?? '';
                        $detail['keywords'] = json_decode($detail['keywords'], true);
                        $task['detail'] = $detail;
                    }
                    break;

                default:

                    throw new \Exception('参数错误');
                    break;
            }

            $task['device_info'] = SvDevice::where('device_code', $task['device_code'])->findOrEmpty()->toArray();


            if ($task['account_type'] == 1) {

                $where = [
                    'device_code' =>  $task['device_info']['wechat_device_code'],
                    'wechat_id' => $task['account']
                ];
                $account_info =  AiWechat::where($where)->findOrEmpty()->toArray();
                if (!$account_info) {
                    $task['account_info'] = '';
                } else {
                    $task['account_info'] = $account_info;
                }
            } else {
                $where = [
                    'device_code' => $task['device_code'],
                    'account' => $task['account']
                ];
                $task['account_info'] = SvAccount::where($where)->findOrEmpty()->toArray();
            }
            $task['task_category'] = DeviceEnum::getAccountTypeDesc($task['account_type']) . DeviceEnum::getTaskTypeDesc($task['task_type']);
            $task['start_time'] = date('H:i', $task['start_time']);
            $task['end_time'] = date('H:i', $task['end_time']);
            self::$returnData = $task;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function updateName($params)
    {
        $source = $params['source'] ?? '';
        $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type')
            ->where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty()->toArray();
        if (!$task) {
            self::setError('数据不存在');
            return false;
        }
        try {
            Db::startTrans();

            switch ($source) {

                case DeviceEnum::TASK_SOURCE_PUBLISH:
                    //sv_publish_setting_account
                    $taskinfo = SvPublishSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['name'];
                    SvPublishSettingAccount::where('id', $taskinfo['id'])->update(['name' => $name]);
                    break;

                case DeviceEnum::TASK_SOURCE_TAKEOVER:
                    //sv_device_take_over_task_account
                    $taskinfo = SvDeviceTakeOverTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('接管任务不存在');
                    }
                    $name =  $params['name'] ?? '接管任务';
                    SvDeviceTakeOverTask::where('id', $taskinfo['take_over_id'])->update(['name' => $name]);
                    break;

                case DeviceEnum::TASK_SOURCE_ACTIVE:
                    //sv_device_active_account
                    $taskAccountInfo = SvDeviceActiveAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskAccountInfo) {
                        throw new \Exception('养号任务账号不存在');
                    }
                    $taskinfo = SvDeviceActive::where('id', $taskAccountInfo['active_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('养号任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvDeviceActive::where('id', $taskAccountInfo['active_id'])->update(['task_name' => $name]);
                    break;

                case DeviceEnum::TASK_SOURCE_FRIENDS:
                    //sv_crawling_manual_task
                    $taskinfo = SvCrawlingManualTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('自动加好友任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['name'];
                    SvCrawlingManualTask::where('id', $taskinfo['id'])->update(['name' => $name]);
                    break;

                case DeviceEnum::TASK_SOURCE_CLUES:
                    //sv_crawling_task
                    $taskinfo = SvCrawlingTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('爬取任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['name'];
                    SvCrawlingTask::where('id', $taskinfo['id'])->update(['name' => $name]);
                    break;

                default:

                    throw new \Exception('参数错误');
                    break;
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage());
        }
    }
}
