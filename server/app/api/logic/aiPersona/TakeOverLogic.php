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
use app\common\model\aiPersona\AiPersonaAgentConfig;

use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 私域接管逻辑
 * Class TakeOverLogic    
 * @package app\api\logic\aiPersona
 */
class TakeOverLogic extends ApiLogic
{


    public static function autoTakeOverTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化私信接管任务生成\n");
        try {

            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在:' . \think\facade\Db::getLastSql());
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];

            $item = AiPersonaAgentConfig::where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write('自动化私信接管任务生成' . $item->isEmpty() ? \think\facade\Db::getLastSql() : $item . '条', 'take_over');
            if ($item->isEmpty()) {
                return true;
            }


            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;

            self::createAutoTakeOverTask($item);
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'take_over');
            return false;
        }
    }

    private static function createAutoTakeOverTask(AiPersonaAgentConfig $item)
    {

        Db::startTrans();
        try {
            $accounts = SvAccount::field('id,account,type,nickname,avatar')->where('user_id', $item->user_id)->where('device_code', $item->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('该设备没有绑定账号' . $item->device_code);
            }

            $task = SvDeviceTakeOverTask::create([
                'user_id' => $item->user_id,
                'task_name' => '自动化私信接管任务' . date('mdHis', time()),
                'persona_id' => $item->persona_id,
                'auto_type' => 1,
                'accounts' => json_encode($accounts->toArray(), JSON_UNESCAPED_UNICODE),
                'time_config' => json_encode([], JSON_UNESCAPED_UNICODE),
                'status' => 0,
                'task_frep' => 0,
                'create_time' => time(),
            ]);

            $deviceTask = [];
            foreach ($accounts as $key => $account) {

                $setting = SvSetting::where('user_id', $item->user_id)->where('account', $account->account)->findOrEmpty();
                if ($setting->isEmpty()) {
                    //throw new \Exception('该账号没有绑定设置');
                    continue;
                }
                $date = date('Y-m-d', time());
                $exec_times = $item->getTimesByType($item->persona_type, $account->type);

                foreach ($exec_times as $key => $exec_time) {
                    list($st, $et) = explode('-', $exec_time);

                    $startTime = strtotime($date . ' ' . $st . ':00');
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $et . ':00') - 120;
                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => $item->user_id,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'robot_id' => (int)$account->type === 1 ? $item->wechat_chat_agent_id : $item->dm_agent_id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    array_push($deviceTask, [
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_TAKE_OVER,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化私信接管任务' . date('YmdHis', time()),
                        'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'source' => DeviceEnum::TASK_SOURCE_TAKEOVER,
                        'create_time' => time(),
                    ]);
                }

                //生成接管任务时，同步将账号设置中的智能体id更新并开启ai回复
                $setting->robot_id = $account->type === 1 ? $item->wechat_chat_agent_id : $item->dm_agent_id;
                $setting->open_ai = 1;
                $setting->takeover_mode = 1;
                $setting->update_time = time();
                $setting->save();
            }
            //print_r($deviceTask);die;
            (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);
            $item->update_time = time();
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化私信接管任务生成' . $item->device_code . ' ' . $th->__toString() . 'take_over');
            Db::rollback();
            $item->update_time = time();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
