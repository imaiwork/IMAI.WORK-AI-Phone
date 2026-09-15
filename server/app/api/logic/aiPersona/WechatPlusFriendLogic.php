<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;

use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 微信加好友逻辑
 * Class WechatPlusFriendLogic    
 * @package app\api\logic\aiPersona
 */
class WechatPlusFriendLogic extends ApiLogic
{

    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                1 => [
                    '07:00-07:15',
                    '09:30-09:45',
                    '12:30-12:45',
                    '14:30-14:45',
                    '18:15-18:30',
                    '20:15-20:30',
                    '22:30-22:45'
                ],
            ],
            2 => [
                1 => [
                    '10:45-11:00',
                    '13:15-13:30',
                    '16:45-17:00',
                    '20:45-21:00'
                ]
            ],
            3 => [
                1 => [
                    '11:15-11:30',
                    '14:30-14:45',
                    '17:45-18:00',
                    '18:45-18:50',
                    '22:30-22:45'
                ],
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }


    public static function autoAddWechatTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化加微任务生成\n");
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在:' . \think\facade\Db::getLastSql());
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            $where[] = ['exec_date', '<=', date('Y-m-d', time())];

            $item = AiPersonaWechatInteractionConfig::where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write('自动化加微任务生成' . $item->isEmpty() ? \think\facade\Db::getLastSql() : $item . '条', 'add_wechat');
            if ($item->isEmpty()) {
                return true;
            }
            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            self::createAutoAddWechatTask($item);

            $item->exec_date = date('Y-m-d', strtotime('+1 day'));
            $item->is_first = 1;
            $item->save();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'add_wechat');
            return false;
        }
    }

    private static function createAutoAddWechatTask(AiPersonaWechatInteractionConfig $item)
    {

        Db::startTrans();
        try {

            $wechat = SvAccount::where('user_id', $item->user_id)->where('device_code', $item->device_code)->where('type', 1)->findOrEmpty();
            if ($wechat->isEmpty()) {
                throw new \Exception('该设备绑定的微号不存在' . $item->device_code . ' ' . \think\facade\Db::getLastSql());
            }

            $date = date('Y-m-d', time());
            $times = self::getTimesByType($item->persona_type, 1);
            foreach ($times as $time) {
                list($st, $et) = explode('-', $time);
                $startTime = strtotime($date . ' ' . $st . ':00');
                $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $et . ':00');
                if($endTime < time()){
                    continue;
                }
                $deviceTask = [
                    'user_id' => $item->user_id,
                    'device_code' => $item->device_code,
                    'task_type' => DeviceEnum::AUTO_TYPE_WECHAT_FRIEND,
                    'account' => $wechat->account,
                    'account_type' => 1,
                    'nickname' => $wechat->nickname,
                    'avatar' => $wechat->avatar,
                    'persona_id' => $item->persona_id,
                    'auto_type' => 1,
                    'task_name' => '自动化加微任务' . date('YmdHis', time()),
                    'time_config' => json_encode([$time], JSON_UNESCAPED_UNICODE),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $startTime),
                    'status' => 0,
                    'sub_task_id' => 0,
                    'source' => DeviceEnum::TASK_SOURCE_FRIENDS,
                    'create_time' => time(),
                ];
                \app\common\model\sv\SvDeviceTask::create($deviceTask);
            }


            $item->update_time = time();
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化加微任务生成' . $item->device_code . ' ' . $th->__toString() . 'add_wechat');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->result = $th->getMessage();
            $item->update_time = time();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
