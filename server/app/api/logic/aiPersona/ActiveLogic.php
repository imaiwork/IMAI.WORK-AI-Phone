<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;

use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;

use app\common\model\sv\SvDevice;
use app\common\model\aiPersona\AiPersona;
use app\common\service\sv\SvDeviceTaskExistenceService;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 养号自动任务逻辑
 * Class ActiveLogic    
 * @package app\api\logic\auto
 */
class ActiveLogic extends BasePersonaLogic
{
    public static function autoActiveTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化养号任务生成\n");
        \think\facade\Log::channel('auto')->write($device->device_code . '自动化养号任务生成', 'create');
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        Db::startTrans();
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception( $device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql());
                \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql(), 'create');
                Db::commit();
                return $result;
            }
            //print_r($persona->toArray());die;s
            $schedules = self::getAutoSchedule($persona, 10);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$device->device_code, '养号任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());
            foreach ($schedules as $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = $schedule->platform;
                if (empty($platforms) || !is_array($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);

                $execTime = $schedule->start_time . '-' . $schedule->end_time;
                foreach ($platforms as $index => $platform) {
                    if ((int)$platform['account_type'] === DeviceEnum::ACCOUNT_TYPE_XHS) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $device->user_id)->where('device_code', $device->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$device->device_code,
                            (int)$platform['account_type'],
                            '养号任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($device->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$device->user_id,
                        (string)$device->device_code,
                        (int)$device->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_ACTIVE,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '养号任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $task = SvDeviceActive::create([
                        'user_id' => $device->user_id,
                        'task_name' => '自动化养号任务' . date('YmdHis', time()),
                        'auto_type' => 1,
                        'persona_id' => $device->persona_id,
                        'accounts' => json_encode($account->toArray(), JSON_UNESCAPED_UNICODE),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'status' => 0,
                        'task_frep' => 0,
                        'create_time' => time(),
                    ]);

                    // print_r(date('Y-m-d H:i:s', $startTime));
                    // print_r(date('Y-m-d H:i:s', $endTime));die;
                    $row = SvDeviceActiveAccount::create([
                        'active_id' => $task->id,
                        'user_id' => $device->user_id,
                        'persona_id' => $device->persona_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $device->device_code,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $device->user_id,
                        'device_code' => $device->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_ACTIVE,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化养号任务' . date('YmdHis', time()),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'persona_id' => $device->persona_id,
                        'sub_task_id' => $row->id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_ACTIVE,
                        'source' => DeviceEnum::TASK_SOURCE_ACTIVE,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'create');
            Db::rollback();
            return $result;
        }
    }
    
}
