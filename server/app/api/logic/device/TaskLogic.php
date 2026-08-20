<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCityExposureTask;
use app\common\model\sv\SvCityExposureTaskAccount;
use app\common\model\sv\SvCityTouchTask;
use app\common\model\sv\SvCityTouchTaskAccount;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvCrawlingWechatTask;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceTaskLog;
use app\common\model\sv\SvGroupBuyTaskAccount;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvWechatStrategy;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
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

    public static function check(array $params)
    {
        $device_codes = !empty($params['accounts']) ? SvAccount::field('device_code')->where('account', 'in', array_column($params['accounts'], 'account'))->column('device_code') : ($params['device_codes'] ?? []);
        $device_codes = !empty($params['wechat_ids']) ? SvAccount::field('device_code')->where('account', 'in', array_column($params['wechat_ids'], 'account'))->where('type', 1)->column('device_code') :  $device_codes;

        $taskIds = [];
        if (isset($params['time_config']) && !empty($params['time_config'])) {
            $dates = array_column($params['time_config'], 'date');
            if (in_array(date('Y-m-d', time()), $dates)) {
                list($checkStatus, $checkMessage, $taskIds) = self::checkTaskExecTime($device_codes, time(), time() + (60 * (int)$params['minutes']));
            }
        } else {
            list($checkStatus, $checkMessage, $taskIds) = self::checkTaskExecTime($device_codes, time(), time() + (60 * (int)$params['minutes']));
        }


        $errors = [];
        if (isset($params['time_config']) && !empty($params['time_config'])) {

            $times = \app\api\logic\device\TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), 0, [], 1);
            foreach ($params['accounts'] as $account) {
                $account = SvAccount::where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                if ($account->isEmpty()) {
                    throw new \Exception("账号{$account['account']}不存在");
                }
                foreach ($times as $time) {
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping(
                        $account['device_code'],
                        DeviceEnum::TASK_TYPE_PUBLISH,
                        $time['start_time'],
                        $time['end_time'],
                        self::$uid,
                        0,
                        '',
                        $taskIds
                    );
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        $errors[] = $msg;
                    }
                }
            }
        }

        self::$returnData = [
            'count' => count($checkMessage ?? []),
            'messages' => $checkMessage ?? [],
            'task_ids' => $taskIds,
            'errors' => $errors,
        ];
        return true;
    }
    public static function updateTaskStatusByIds(array $taskIds)
    {
        if (!empty($taskIds)) {
            SvDeviceTask::where('id', 'in', $taskIds)->update([
                'status' => DeviceEnum::TASK_STATUS_FAILED,
                'remark' => '任务被其他任务覆盖'
            ]);
        }
    }

    public static function checkAccounts(array $accounts)
    {
        $result = [];
        foreach ($accounts as $account) {
            $find = SvAccount::field('id, device_code')->where('account', $account['account'])->where('type', $account['type'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception($account['account'] . '账号不存在');
            }
            if (array_key_exists($find->device_code, $result)) {
                $result[$find->device_code] +=  1;
            } else {
                $result[$find->device_code] = 1;
            }
        }

        $values = array_sum(array_unique(array_values($result)));
        if ($values > 1) {
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
    public static function isTaskTimeOverlapping(string $deviceCode, int $taskType, int $startTime, int $endTime, int $userId, int $taskScene = 10, string $date = '', array $taskIds = []): array
    {
        $query = SvDeviceTask::where('device_code', $deviceCode)
            ->where('auto_type', 0)
            //->where('user_id', $userId)
            ->where('status', 'in', [0, 1])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        // if ($taskScene > 0) {
        //     $query->where('task_scene', '<>', $taskScene);
        // }

        if (!empty($date)) {
            $query->where('day', $date);
        }
        if (!empty($taskIds)) {
            $query->where('id', 'not in', $taskIds);
        }
        //$query->where('task_type', $taskType);
        $find = $query->fetchSql(false)->findOrEmpty();
        if ($find->isEmpty()) {
            return [true, []];
        }
        return [false, $find->toArray()];
    }


    public static function checkTaskExecTime(array $deviceCodes, int $startTime, int $endTime)
    {
        $rows = SvDeviceTask::where('device_code', 'in', $deviceCodes)
            ->where('auto_type', 0)
            //->where('user_id', $userId)
            ->where('status', 'in', [0, 1])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->fetchSql(false)
            ->select();
        $messages = [];
        $taskIds = [];
        foreach ($rows as $row) {
            $timeMsg = "【" . date('Y-m-d H:i', $row['start_time']) . "-" . date('Y-m-d H:i', $row['end_time']) . "】";
            $messages[] = "您有一个{$timeMsg}的【" . $row['task_name'] . "-" . DeviceEnum::getAccountTypeDesc($row['account_type']) . "】(" . DeviceEnum::getTaskStatusDesc($row['status']) . ")与当前所选时间冲突";
            $taskIds[] = $row['id'];
        }
        if (!empty($messages)) {
            return [false, $messages, $taskIds];
        }
        return [true, $messages, $taskIds];
    }

    public static function updateWechatRpaTaskTime(string $deviceCode,  int $endTime)
    {

        SvDeviceTask::where('device_code', $deviceCode)
            ->where('status', 'in', [0, 1, 3])
            ->where('auto_type', 0)
            ->where('account_type', 1)
            ->where('task_scene', 6)
            ->where('day', date('Y-m-d', $endTime))
            ->delete();
        return;


        if ($endTime > time()) {
            $find = SvDeviceTask::where('device_code', $deviceCode)
                ->where('status', 'in', [0, 1, 3])
                ->where('task_scene', 10)
                ->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->delete();
                // $find->end_time = $endTime - 120;
                // if (($find->end_time - $find->start_time) < 600) {
                //     $find->delete();
                // } else {
                //     $find->time_config = json_encode([
                //         date('H:i', $find->start_time) . '-' . date('H:i', ($endTime - 120)),
                //     ], JSON_UNESCAPED_UNICODE);
                //     $find->update_time = time();
                //     $find->save();
                // }
            }
        }
    }




    public static function getTimes(array $timeConfigs, string $startOrgDate, int $days, array $customDates = [], int $timeType = 0)
    {
        $resTimes = [];
        if (!empty($customDates)) {
            if ($timeType == 0) {
                foreach ($customDates as $date) {
                    $date = date('Y-m-d', strtotime($date));
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
                        $resTimes[] = [
                            'start_time' => strtotime($newStartTime),
                            'end_time' => strtotime($newEndTime),
                        ];
                    }
                }
            } elseif ($timeType == 1) {
                foreach ($timeConfigs as $config) {
                    $date = $config['date'];
                    $times = $config['times'];
                    foreach ($times as $time) {
                        $time = explode('-', $time);
                        $newStartTime = $date . ' ' . $time[0] . ':00';
                        $newEndTime = $date . ' ' . $time[1] . ':00';
                        if (count($time) != 2) {
                            throw new \Exception('任务执行时间区间格式错误');
                        }
                        if (strtotime($newStartTime) >= strtotime($newEndTime)) {
                            throw new \Exception('任务执行时间区间结束时间不能小于开始时间');
                        }
                        $resTimes[] = [
                            'start_time' => strtotime($newStartTime),
                            'end_time' => strtotime($newEndTime),
                        ];
                    }
                }
            }
        } else {
            $start_times = [];
            if ($timeType == 0) {
                for ($i = 0; $i < $days; $i++) {
                    foreach ($timeConfigs as $time) {
                        $startDate = $startOrgDate;
                        $startDate = date('Y-m-d', strtotime("{$startDate} +" . $i . " day"));

                        $time = explode('-', $time);
                        $newStartTime = $startDate . ' ' . $time[0] . ':00';
                        if (strtotime($newStartTime) < time()) {
                            $startDate = date('Y-m-d', strtotime("{$startDate} +" . ($i + 1) . " day"));
                            $newStartTime = $startDate . ' ' . $time[0] . ':00';
                        }
                        $newEndTime = $time[1] == '00:00' ? date('Y-m-d 00:00:00', strtotime("{$startDate} +1 day")) : $startDate . ' ' . $time[1] . ':00';

                        if (count($time) != 2) {
                            throw new \Exception('任务执行时间区间格式错误');
                        }
                        if (strtotime($newStartTime) >= strtotime($newEndTime)) {
                            //throw new \Exception('任务执行时间区间结束时间不能小于开始时间1');
                            $newEndTime = date('Y-m-d H:i:s',  strtotime("{$startDate} +1 day"));
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
                    //print_r($resTimes);die;
                    //$startDate = date('Y-m-d', strtotime("{$startDate} +1 day"));
                }
                $tmpKeys = array_column($resTimes, 'start_time');
                array_multisort($tmpKeys, SORT_ASC, $resTimes);
            } else if ($timeType == 1) {
                foreach ($timeConfigs as $config) {
                    $date = $config['date'];
                    $times = $config['times'];
                    foreach ($times as $time) {
                        if ((int)$time === 1 && strpos($time, ':') === false) {
                            continue;
                        }
                        $startDate = $date;

                        $time = explode('-', $time);
                        $newStartTime = $startDate . ' ' . $time[0] . ':00';
                        if (strtotime($newStartTime) < time()) {
                            //$startDate = date('Y-m-d', strtotime("{$startDate} +1day"));
                            $newStartTime = $startDate . ' ' . $time[0] . ':00';
                        }
                        $newEndTime = $time[1] == '00:00' ? date('Y-m-d 00:00:00', strtotime("{$startDate} +1 day")) : $startDate . ' ' . $time[1] . ':00';

                        if (count($time) != 2) {
                            throw new \Exception('任务执行时间区间格式错误');
                        }
                        if (strtotime($newStartTime) >= strtotime($newEndTime)) {

                            //throw new \Exception('任务执行时间区间结束时间不能小于开始时间1');
                            $newEndTime = date('Y-m-d H:i:s',  strtotime("{$newEndTime} +1 day"));
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
                }
                $tmpKeys = array_column($resTimes, 'start_time');
                array_multisort($tmpKeys, SORT_ASC, $resTimes);
            }
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
            $where['dt.auto_type'] = SvDevice::where('device_code', $device_code)->value('auto_type');
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
        $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type,sub_data_id')
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
                    if ($count == 1) {
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
                                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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

                    //删除获客任务时同步删除对应的加微任务
                    $taskinfo = SvCrawlingWechatTask::where('device_code', $task['device_code'])->where('craw_task_ids', 'like', "%{$params['sub_task_id']}%")->where('user_id', self::$uid)->fetchSql(false)->findOrEmpty();
                    if (!$taskinfo->isEmpty()) {
                        // 获取执行微信聚合
                        $crawTaskIds = $taskinfo->craw_task_ids;

                        if (in_array($params['sub_task_id'], $crawTaskIds)) {
                            // 删除
                            unset($crawTaskIds[array_search($params['sub_task_id'], $crawTaskIds)]);
                            $taskinfo->craw_task_ids = $crawTaskIds;
                            if (empty($crawTaskIds)) {
                                $taskinfo->delete();
                                SvDeviceTask::Where('sub_task_id', $taskinfo['id'])->where('user_id', self::$uid)->where('task_type', DeviceEnum::TASK_SOURCE_CLUES_WECHAT)->select()->delete();
                            } else {
                                $taskinfo->save();
                            }
                        }
                    }


                    break;
                case DeviceEnum::TASK_SOURCE_TOUCH:

                    $taskinfo = SvLeadScrapingSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('自动截流任务不存在');
                    }
                    SvLeadScrapingSettingAccount::where('id', $params['sub_task_id'])->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_EXPOSURE:
                    $taskinfo = SvCityExposureTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城曝光任务不存在');
                    }
                    SvCityExposureTaskAccount::where('id', $params['sub_task_id'])->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF:
                    $taskinfo = SvCityTouchTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城截流任务不存在');
                    }
                    SvCityTouchTaskAccount::where('id', $params['sub_task_id'])->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_GROUP_BUY:
                    $taskinfo = SvGroupBuyTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('团购截流任务不存在');
                    }
                    SvGroupBuyTaskAccount::where('id', $params['sub_task_id'])->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH:
                    $taskinfo = AiWechatCircleTaskConfig::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty();
                    if ($taskinfo->isEmpty()) {
                        throw new \Exception('朋友圈发布任务不存在');
                    }
                    AiWechatCircleTaskConfig::where('id', $params['sub_task_id'])->select()->delete();
                    AiWechatCircleTask::where('id', $task['sub_data_id'])->select()->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT:
                    // $taskinfo = SvDeviceCircleLikeReplyAccount::where('id', $task['sub_data_id'])->where('user_id', self::$uid)->findOrEmpty();
                    // if ($taskinfo->isEmpty()) {
                    //     throw new \Exception('点赞评论任务不存在');
                    // }
                    //SvDeviceCircleLikeReply::where('id', $params['sub_task_id'])->select()->delete();
                    SvDeviceCircleLikeReplyAccount::where('id', $task['sub_data_id'])->where('user_id', self::$uid)->select()->delete();
                    $count = SvDeviceCircleLikeReplyAccount::where('circle_like_reply_id', $params['sub_task_id'])->where('user_id', self::$uid)->count();
                    if ($count == 0) {
                        SvDeviceCircleLikeReply::where('id', $params['sub_task_id'])->select()->delete();
                    }
                    break;

                case DeviceEnum::TASK_SOURCE_CLUES_WECHAT:

                    $taskinfo = SvCrawlingWechatTask::where('id', $params['sub_task_id'])->where('device_code', $task['device_code'])->where('user_id', self::$uid)->findOrEmpty();
                    if ($taskinfo->isEmpty()) {
                        throw new \Exception('获客加微任务不存在');
                    }
                    SvCrawlingWechatTask::where('id', $params['sub_task_id'])->select()->delete();
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_RPA:
                    // $taskinfo = SvWechatStrategy::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    // if (!$taskinfo) {
                    //     throw new \Exception('个微rpa任务不存在');
                    // }
                    //SvWechatStrategy::where('id', $params['sub_task_id'])->select()->delete();
                    break;
                default:

                    throw new \Exception('参数错误');
                    break;
            }

            SvDeviceTask::Where('id', $params['id'])->where('user_id', self::$uid)->delete();
            \app\common\model\sv\SvDeviceTaskLog::where('task_id', $params['id'])->select()->delete();

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
            $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type,auto_type,sub_data_id,source,task_scene')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty()->toArray();
            //print_r($task);die;
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
                        $material_tag = trim((string)($detail['material_tag'] ?? ''));
                        if ($material_tag !== '') {
                            $detail['material_tag'] = array_values(array_filter(array_map(function ($tag) {
                                $tag = trim((string)$tag);
                                if ($tag === '') {
                                    return '';
                                }
                                return str_starts_with($tag, '#') ? $tag : '#' . $tag;
                            }, explode(',', $material_tag))));
                        } else {
                            $detail['material_tag'] = [];
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
                        $detail['name'] = $task['task_name'] ?? '';
                        $task['detail'] = $detail;
                    } else {
                        $detail = SvCrawlingManualTask::where('id', $params['sub_task_id'])->findOrEmpty()->toArray();
                        if ($detail) {
                            $detail['name'] =  $taskinfo['name'] ?? '';
                            $task['detail'] = $detail;
                        }
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

                case DeviceEnum::TASK_SOURCE_TOUCH:
                    $taskinfo = SvLeadScrapingSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('截流任务不存在');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_EXPOSURE:
                    $taskinfo = SvCityExposureTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城曝光任务');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF:
                    $taskinfo = SvCityTouchTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城截流任务');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_GROUP_BUY:
                    $taskinfo = SvGroupBuyTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('团购截流任务');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH:
                    $taskinfo = AiWechatCircleTaskConfig::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('朋友圈发布任务不存在');
                    }
                    $detail = AiWechatCircleTask::where('task_config_id', $taskinfo['id'])->findOrEmpty()->toArray();
                    if ($detail) {
                        $detail['name'] =  $taskinfo['task_name'] ?? '';
                        $task['detail'] = $detail;
                    }
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT:
                    $taskinfo = SvDeviceCircleLikeReply::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('朋友圈点赞评论任务不存在');
                    }
                    $detail = SvDeviceCircleLikeReplyAccount::where('id', $task['sub_data_id'])->findOrEmpty()->toArray();
                    if ($detail) {
                        $detail['name'] =  $taskinfo['task_name'] ?? '';
                        $task['detail'] = $detail;
                    }
                    break;
                case DeviceEnum::TASK_SOURCE_CLUES_WECHAT:
                    //sv_crawling_manual_task
                    $taskinfo = SvCrawlingWechatTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('加微任务不存在');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_RPA:
                    //sv_wechat_strategy
                    $taskinfo = SvWechatStrategy::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('个微rpa任务不存在');
                    }
                    $taskinfo['account_type'] = 2;
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_SPH_THUMB:
                    //sv_device_take_over_task_account
                    $taskinfo = SvDeviceTakeOverTaskAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('点赞任务不存在');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_VIRAL_REWRITER:
                    //sv_viral_rewriter_task_account
                    $taskinfo = \app\common\model\sv\SvDeviceViralAccount::where('id', $task['sub_data_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('爆款仿写任务不存在');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                case DeviceEnum::TASK_SOURCE_PRECISE_CLUES:
                    //sv_precise_clues_task_account
                    $taskinfo = \app\common\model\sv\SvDevicePreciseCluesAccount::where('id', $task['sub_data_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('精准线索任务不存在');
                    }
                    $task['detail'] = $taskinfo;
                    break;
                default:

                    throw new \Exception('参数错误');
                    break;
            }

            $task['device_info'] = SvDevice::where('device_code', $task['device_code'])->findOrEmpty()->toArray();


            $where = [
                'device_code' => $task['device_code'],
                'type' => $task['account_type']
            ];
            $account_info = SvAccount::where($where)->findOrEmpty();
            if (!$account_info->isEmpty()) {
                $account_info['type'] = (int)$source === DeviceEnum::TASK_SOURCE_WECHAT_RPA ? 2 : $account_info['type'];
                $task['account_info'] = $account_info;
            } else {
                $account_info['type'] = $task['account_type'];
                $task['account_info'] = [
                    'device_code' => $task['device_code'],
                    'account' => $task['account'],
                    'account_no' => $task['account'],
                    'nickname' => $task['nickname'],
                    'avatar' => $task['avatar'],
                    'type' => $task['account_type']
                ];
            }
            $task['account_type'] = (int)$source === DeviceEnum::TASK_SOURCE_WECHAT_RPA ? 2 : $task['account_type'];

            //$task['task_category'] = !in_array($task['source'], [5, 7, 8]) ? DeviceEnum::getAccountTypeDesc($task['account_type']) . DeviceEnum::getTaskTypeDesc($task['task_type']) : DeviceEnum::getTaskSceneDesc($task['task_scene']);
            $task['task_category'] = DeviceEnum::getAccountTypeDesc($task['account_type']) . DeviceEnum::getTaskSceneDesc($task['task_scene']);
            if (
                in_array($task['task_type'], [DeviceEnum::TASK_TYPE_TAKEOVER, DeviceEnum::AUTO_TYPE_TAKE_OVER]) &&
                $task['source'] == DeviceEnum::TASK_SOURCE_TAKEOVER &&
                $task['account_type'] == DeviceEnum::ACCOUNT_TYPE_SPH
            ) {
                $task['task_category'] = '微信私信接管';
            }
            if ($task['task_scene'] == DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER) {
                $task['task_category'] = DeviceEnum::getAccountTypeDesc($task['account_type']) . '评论' . ((int)$task['account_type'] ===1 ? '点赞' : '接管');
            }
            if($task['task_scene'] == DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH){
                $task['task_category'] = strpos($task['task_name'], '图文') !== false ? str_replace('视频', '图文', $task['task_category']) : $task['task_category'];
            }

            $task['start_time'] = date('H:i', $task['start_time']);
            $task['end_time'] = date('H:i', $task['end_time']);
            $task['task_type'] = DeviceEnum::getTaskTypeByAuto($task['task_type']);
            $task['log'] = SvDeviceTaskLog::where('task_id', $params['id'])->order('create_time', 'asc')->group('task_id,task_source,device_code,message')->select()->toArray();

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
        $task = SvDeviceTask::field('start_time,end_time,account_type,account,status,device_code,task_name,task_type,sub_data_id')
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
                    SvDeviceTakeOverTask::where('id', $taskinfo['take_over_id'])->update(['task_name' => $name]);
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

                case DeviceEnum::TASK_SOURCE_TOUCH:
                    //sv_lead_scraping_setting_account
                    $taskinfo = SvLeadScrapingSettingAccount::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('截流任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['name'];
                    SvLeadScrapingSettingAccount::where('id', $taskinfo['id'])->update(['name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH:
                    $taskinfo = AiWechatCircleTaskConfig::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('朋友圈发布任务不存在');
                    }

                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    AiWechatCircleTaskConfig::where('id', $taskinfo['id'])->update(['task_name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT:
                    $taskinfo = SvDeviceCircleLikeReplyAccount::where('id', $task['sub_data_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('朋友圈点赞评论任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvDeviceCircleLikeReplyAccount::where('id', $taskinfo['id'])->update(['task_name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_CLUES_WECHAT:
                    //sv_crawling_manual_task
                    $taskinfo = SvCrawlingWechatTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('加微任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['name'];
                    SvCrawlingWechatTask::where('id', $taskinfo['id'])->update(['name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_WECHAT_RPA:
                    $taskinfo = SvWechatStrategy::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('个微rpa任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvWechatStrategy::where('id', $taskinfo['id'])->update(['task_name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_EXPOSURE:
                    $taskinfo = SvCityExposureTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城曝光任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvCityExposureTask::where('id', $taskinfo['id'])->update(['task_name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF:
                    $taskinfo = SvCityTouchTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('同城截流任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvCityTouchTask::where('id', $taskinfo['id'])->update(['task_name' => $name]);
                    break;
                case DeviceEnum::TASK_SOURCE_GROUP_BUY:
                    $taskinfo = SvCityTouchTask::where('id', $params['sub_task_id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
                    if (!$taskinfo) {
                        throw new \Exception('团购截流任务不存在');
                    }
                    $name =  $params['name'] ?? $taskinfo['task_name'];
                    SvCityTouchTask::where('id', $taskinfo['id'])->update(['task_name' => $name]);
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
