<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvWechatStrategy;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvAccount;
use app\common\enum\DeviceEnum;
use think\facade\Db;


/**
 * 设备任务逻辑
 * Class WechatLogic    
 * @package app\api\logic\device
 */
class WechatLogic extends ApiLogic
{
    public static function add($params)
    {
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            if (empty($params['time_config']) && $params['is_free_time'] == 0) {
                self::setError('请选择时间配置');
                return false;
            }

            if ((int)$params['is_free_time'] === 1) {
                $params['time_config'] = [];
                if (empty($params['custom_date'])) {
                    $params['custom_date'] = self::getDatesByFrep($params['task_frep']);
                }
            }
            //print_r($params);die;
            $allTaskInstall = [];
            foreach ($params['accounts'] as $account) {
                $account['device_code'] = SvAccount::where('account', $account['account'])->where('type', 1)->where('user_id', self::$uid)->value('device_code') ?? '';
                if (empty($account['device_code'])) {
                    self::setError('账号不存在');
                    return false;
                }

                $find = SvWechatStrategy::where('device_code', $account['device_code'])->limit(1)->findOrEmpty();
                if (!$find->isEmpty()) {
                    // self::setError('设备' . $account['device_code'] . '个微rpa任务已存在');
                    // return false;
                    SvDeviceTask::where('sub_task_id', $find->id)->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_RPA)->where('user_id', self::$uid)->select()->delete();
                    $find->delete();
                }

                $params['user_id'] = self::$uid;
                $params['create_time'] = time();
                $params['account'] = $account['account'];
                $params['account_type'] = $account['type'];
                $params['nickname'] = SvAccount::where('account', $account['account'])->where('type', 1)->where('user_id', self::$uid)->value('nickname') ?? '';
                $params['avatar'] = SvAccount::where('account', $account['account'])->where('type', 1)->where('user_id', self::$uid)->value('avatar') ?? '';
                $params['task_name'] = $params['task_name'] ?? '个微rpa任务' . date('YmdHi');
                $params['device_code'] = $account['device_code'] ?? '';

                $params['is_init'] = 0;
                $strategy = SvWechatStrategy::create($params);
                if (empty($strategy)) {
                    self::setError('创建任务失败');
                    return false;
                }

                if ((int)$params['is_free_time'] === 0) {
                    $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
                    foreach ($times as $time) {
                        list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_WECHAT_RPA, $time['start_time'], $time['end_time'], self::$uid);
                        if (!$isOverlap) {
                            $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                            $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                            throw new \Exception($msg);
                        }

                        array_push($allTaskInstall, [
                            'user_id' => self::$uid,
                            'device_code' => $account['device_code'],
                            'task_type' => DeviceEnum::TASK_TYPE_WECHAT_RPA,
                            'account' => $account['account'],
                            'account_type' => $account['type'],
                            'task_name' => '个微rpa任务',
                            'status' => 0,
                            'day' => date('Y-m-d', $time['start_time']),
                            'start_time' => $time['start_time'],
                            'end_time' => $time['end_time'],
                            'time_config' => json_encode($params['time_config'], JSON_UNESCAPED_UNICODE),
                            'sub_task_id' => $strategy->id,
                            'source' => DeviceEnum::TASK_SOURCE_WECHAT_RPA,
                            'create_time' => time(),
                        ]);
                    }
                }
            }
            if (!empty($allTaskInstall)) {
                TaskLogic::add($allTaskInstall);
            }

            Db::commit();
            self::$returnData = [];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();

            self::setError($th->getMessage());
            return false;
        }
    }


    public static function detail($params)
    {
        $find = SvWechatStrategy::where('device_code', $params['device_code'])->limit(1)->findOrEmpty();
        if ($find->isEmpty()) {
            self::setError('设备' . $params['device_code'] . '个微rpa任务不存在');
            return false;
        }
        self::$returnData = $find->toArray();
        return true;
    }


    public static function update($params)
    {
        $find = SvWechatStrategy::where('device_code', $params['device_code'])->where('user_id', self::$uid)->limit(1)->findOrEmpty();
        if ($find->isEmpty()) {
            self::setError('设备' . $params['device_code'] . '个微rpa任务不存在');
            return false;
        }

        if ($find->status == DeviceEnum::TASK_STATUS_RUNNING) {
            self::setError('任务正在运行，不能更新');
            return false;
        }

        if (empty($params['time_config']) && $params['is_free_time'] == 0) {
            self::setError('请选择时间配置');
            return false;
        }

        Db::startTrans();
        try {
            SvDeviceTask::where('sub_task_id', $find->id)->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_RPA)->where('user_id', self::$uid)->select()->delete();

            if ((int)$params['is_free_time'] === 0) {

                $account = SvAccount::where('device_code', $params['device_code'])->where('type', 1)->where('user_id', self::$uid)->findOrEmpty();
                if ($account->isEmpty()) {
                    throw new \Exception('设备' . $params['device_code'] . '个微账号不存在');
                }
                $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
                $allTaskInstall = [];
                foreach ($times as $time) {
                    list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_WECHAT_RPA, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }

                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_RPA,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'task_name' => '个微rpa任务',
                        'status' => 0,
                        'day' => date('Y-m-d', $time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'time_config' => json_encode($params['time_config'], JSON_UNESCAPED_UNICODE),
                        'sub_task_id' => $find->id,
                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_RPA,
                        'create_time' => time(),
                    ]);
                }
                if (!empty($allTaskInstall)) {
                    TaskLogic::add($allTaskInstall);
                }
            }


            $params['update_time'] = time();
            $params['is_init'] = 0;
            $result = $find->save($params);
            if (empty($result)) {
                throw new \Exception('更新任务失败');
            }
            Db::commit();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            Db::rollback();

            self::setError($th->getMessage());
            return false;
        }
    }


    public static function wechatRpaCron()
    {
        try {
            $strategies = SvWechatStrategy::where('is_free_time', 1)
                ->where('device_code', 'in', function ($query) {
                    $query->name('sv_device')->where('auto_type', 0)->field('device_code');
                })
                //->where('is_init', 0)
                ->select();
            foreach ($strategies as $strategy) {
                $dates = !empty($strategy->custom_date) ? $strategy->custom_date : self::getDatesByFrep($strategy->task_frep);
                foreach ($dates as $date) {
                    //$date = '2026-03-07';
                    $tasks = SvDeviceTask::field('start_time, end_time')
                        ->where('device_code', $strategy->device_code)
                        ->where('auto_type', 0)
                        ->where('day', $date)
                        // ->where('start_time', '<=', time())
                        //->where('end_time', '>', time())
                        ->select();
                    $times = [];
                    foreach ($tasks as $time) {
                        $times[] = [$time['start_time'], $time['end_time']];
                    }
                    // 1. 合并重叠的时间段
                    $mergedBusyRanges = self::mergeTimeRanges($times);
                    // 2. 计算所有空闲时间段
                    $allFreeSlots = self::findFreeTimeSlots($mergedBusyRanges, strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59'));
                    // 3. 过滤出时间间隔大于10分钟的空闲时间段
                    $freeSlotsOver10Mins = self::filterByMinDuration($allFreeSlots, 10);
                    // print_r($freeSlotsOver10Min);
                    // die;
                    if (count($freeSlotsOver10Mins) >= 1) {
                        foreach ($freeSlotsOver10Mins as $freeSlotsOver10Min) {
                            $time = $freeSlotsOver10Min;
                            list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping(
                                $strategy->device_code,
                                DeviceEnum::TASK_TYPE_WECHAT_RPA,
                                $time[0],
                                $time[1],
                                $strategy->user_id,
                                0,
                                $date,
                            );
                            if ($isOverlap) {
                                $timeConfig = [
                                    date('H:i', $time[0]) . '-' . date('H:i', $time[1]),
                                ];
                                TaskLogic::add([
                                    [
                                        'user_id' => $strategy->user_id,
                                        'device_code' => $strategy->device_code,
                                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_RPA,
                                        'account' => $strategy->account,
                                        'account_type' => $strategy->account_type,
                                        'task_name' => $strategy->task_name,
                                        'task_scene' => 10,
                                        'status' => 0,
                                        'day' => date('Y-m-d', $time[0]),
                                        'start_time' => $time[0],
                                        'end_time' => $time[1] - 180,
                                        'time_config' => json_encode($timeConfig, JSON_UNESCAPED_UNICODE),
                                        'sub_task_id' => $strategy->id,
                                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_RPA,
                                        'create_time' => time(),
                                    ]
                                ]);
                                $strategy->save([
                                    'is_init' => 1,
                                    'update_time' => time(),
                                ]);
                            } else {
                                \think\facade\Log::channel('device')->write(json_encode($lap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'wechat');
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('device')->write($th->__toString(), 'wechat');
        }
    }

    private static function getDatesByFrep(int $days)
    {
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime(date('Y-m-d', time()) . '+ ' . $i . ' day'));
        }
        return $dates;
    }

    /**
     * 合并时间范围
     * @param array $ranges 时间范围数组，每个元素为 [开始时间, 结束时间]
     * @return array 合并后的时间范围数组
     */
    private  static function mergeTimeRanges($ranges)
    {
        if (empty($ranges)) return [];

        // 按开始时间排序
        usort($ranges, function ($a, $b) {
            return $a[0] - $b[0];
        });

        $merged = [];
        $current = $ranges[0];

        for ($i = 1; $i < count($ranges); $i++) {
            $next = $ranges[$i];

            // 如果当前时间段的结束时间 >= 下一个时间段的开始时间（有重叠或相邻）
            if ($current[1] >= $next[0]) {
                // 合并时间段，取最大的结束时间
                $current[1] = max($current[1], $next[1]);
            } else {
                // 没有重叠，将当前时间段加入结果
                $merged[] = $current;
                $current = $next;
            }
        }

        // 加入最后一个时间段
        $merged[] = $current;

        return $merged;
    }

    /**
     * 计算空闲时间段
     * @param array $busyRanges 占用时间段数组，每个元素为 [开始时间, 结束时间]
     * @param int $dayStart 分析的开始时间（秒级时间戳）
     * @param int $dayEnd 分析的结束时间（秒级时间戳）
     * @return array 空闲时间段数组，每个元素为 [开始时间, 结束时间]
     */
    private static function findFreeTimeSlots($busyRanges, $dayStart = null, $dayEnd = null)
    {
        // 默认分析当天的00:00到23:59
        if ($dayStart === null) {
            // 2026-01-31 00:00:00
            $dayStart = strtotime(date('Y-m-d H:i:s', time()));
        }
        if ($dayEnd === null) {
            // 2026-01-31 23:59:59
            $dayEnd = strtotime(date('Y-m-d', time()) . ' 23:59:59');
        }

        // 如果没有占用时间段，全天都是空闲
        if (empty($busyRanges)) {
            return [[$dayStart, $dayEnd]];
        }

        $freeSlots = [];

        // 检查开始时间之前是否有空闲
        if ($dayStart < $busyRanges[0][0]) {
            $freeSlots[] = [$dayStart, $busyRanges[0][0]];
        }

        // 检查各个占用时间段之间的空闲
        for ($i = 0; $i < count($busyRanges) - 1; $i++) {
            $currentEnd = $busyRanges[$i][1];
            $nextStart = $busyRanges[$i + 1][0];

            if ($currentEnd < $nextStart) {
                $freeSlots[] = [$currentEnd, $nextStart];
            }
        }

        // 检查最后一个占用时间段之后是否有空闲
        $lastBusy = end($busyRanges);
        if ($lastBusy[1] < $dayEnd) {
            $freeSlots[] = [$lastBusy[1], $dayEnd];
        }

        return $freeSlots;
    }

    /**
     * 过滤空闲时间段，只保留持续时间大于等于指定分钟数的时间段
     * @param array $freeSlots 空闲时间段数组，每个元素为 [开始时间, 结束时间]
     * @param int $minMinutes 最小持续时间（分钟）
     * @return array 符合条件的空闲时间段数组
     */
    private static function filterByMinDuration($freeSlots, $minMinutes = 10)
    {
        $minSeconds = $minMinutes * 60;
        $filtered = [];

        foreach ($freeSlots as $slot) {
            $duration = $slot[1] - $slot[0];
            if ($duration >= $minSeconds) {
                $filtered[] = $slot;
            }
        }

        return $filtered;
    }
}
