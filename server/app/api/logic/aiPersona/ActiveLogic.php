<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;

use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;

use app\common\model\sv\SvDevice;
use app\common\model\aiPersona\AiPersona;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 养号自动任务逻辑
 * Class ActiveLogic    
 * @package app\api\logic\auto
 */
class ActiveLogic extends ApiLogic
{
    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                3 => [
                    '11:30-12:00',
                    '16:00-17:00',
                    '21:00-21:15',
                ],
                4 => [
                    '12:00-12:30',
                    '14:45-16:00',
                    '20:30-20:45',
                ],
                5 => [
                    '11:00-11:30',
                    '21:30-21:45',
                ],
            ],
            2 => [
                3 => [
                    '08:00-08:30',
                    '17:00-17:30',
                ],
                4 => [
                    '08:30-09:00',
                    '19:30-20:00',
                ],
            ],
            3 => [
                4 => [
                    '08:00-09:00',
                    '20:00-21:00',
                ],
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }



    public static function autoActiveTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化养号任务生成\n");
        Db::startTrans();
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在:' . \think\facade\Db::getLastSql());
            }

            $accounts = SvAccount::field('id,account,type,nickname,avatar')->where('type', '<>', 1)->where('user_id', $device->user_id)->where('device_code', $device->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('该设备没有绑定账号' . $device->device_code);
            }


            $task = SvDeviceActive::create([
                'user_id' => $device->user_id,
                'task_name' => '自动化养号任务' . date('YmdHis', time()),
                'auto_type' => 1,
                'persona_id' => $device->persona_id,
                'accounts' => json_encode($accounts->toArray(), JSON_UNESCAPED_UNICODE),
                'time_config' => json_encode([]),
                'status' => 0,
                'task_frep' => 0,
                'create_time' => time(),
            ]);

            $deviceTask = [];
            foreach ($accounts as $key => $account) {
                $date = date('Y-m-d', time());
                $times = self::getTimesByType($persona->persona_type, $account->type);
                if (empty($times)) {
                    continue;
                }

                foreach ($times as $key => $time) {
                    $tmp = explode('-', $time);
                    $startTime = strtotime($date . ' ' . $tmp[0] . ':00');
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $tmp[1] . ':00') - 120;
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
                    array_push($deviceTask, [
                        'user_id' => $device->user_id,
                        'device_code' => $device->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_ACTIVE,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化养号任务'.date('YmdHis', time()),
                        'time_config' => json_encode([$time], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'persona_id' => $device->persona_id,
                        'sub_task_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_ACTIVE,
                        'create_time' => time(),
                    ]);
                }
            }
            (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);
            Db::commit();
            return true;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'active');
            Db::rollback();
            return false;
        }
    }
}
