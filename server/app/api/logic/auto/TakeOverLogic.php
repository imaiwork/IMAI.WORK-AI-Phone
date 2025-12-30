<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;

use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceTakeOverConfig;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 设备自动任务-私信接管自动任务
 * Class TakeOverLogic    
 * @package app\api\logic\auto
 */
class TakeOverLogic extends ApiLogic
{
    public static function detail($params)
    {
        ini_set('max_execution_time', 0);
        try {
            $config = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if ($config->isEmpty()) {
                self::setError('设备自动化配置不存在');
                return false;
            }

            $find = AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::$returnData = $find->toArray();
            } else {
                $insertData = [
                    'user_id' => self::$uid,
                    'device_code' => $params['device_code'],
                    'robot_id' => $params['robot_id'] ?? 0,
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'exec_date' => date('Y-m-d', time()),
                    'exec_time' =>  [
                        3 => [
                            '06:00-06:30',
                            '17:00-17:30'
                        ],
                        4 => [
                            '06:30-07:00',
                            '12:00-12:30'
                        ],
                        5 => [
                            '07:00-07:30',
                            '20:00-20:30'
                        ]
                    ]
                ];
                $activeConfig = AutoDeviceTakeOverConfig::create($insertData);
                self::$returnData = $activeConfig->toArray();
            }

            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            $find = AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING){
                    self::setError('私信接管自动任务正在执行，不可修改，稍后再试');
                    return false;
                }
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->robot_id = $params['robot_id'] ?? 0;
                $find->update_time = time();
                if(is_null($find->exec_date)){
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->save();
            }else{
                self::setError('该设备私信接管自动任务配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function autoTakeOverTaskCron(int $isFrist = 0, $deviceCode = null)
    {
        print_r("\n{$deviceCode}自动化私信接管任务生成\n");
        try {
            $where = [];
            if($isFrist === 1){
                $where[] = ['is_first', '=', 0];
                $where[] = ['device_code', '=', $deviceCode];
            }else{
                $where[] = ['exec_date', '<=', date('Y-m-d', time())];
            }
            $items = AutoDeviceTakeOverConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->select();
            \think\facade\Log::channel('auto')->write('自动化私信接管任务生成' . $items->isEmpty() ? \think\facade\Db::getLastSql() : $items->count() . '条', 'take_over');
            if($items->isEmpty()){
                return true;
            }
            $count = $isFrist === 1 ? 1 : 2;
            foreach ($items as $item) {
                for($i = 0; $i < $count; $i++){
                    self::createAutoTakeOverTask($item);
                }
                $item->exec_date = date('Y-m-d', strtotime('+' . $count .' day'));
                $item->is_first = 1;
                $item->save();
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'take_over');
            return false;
        }
    }

    private static function createAutoTakeOverTask(AutoDeviceTakeOverConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $accounts = SvAccount::field('id,account,type')->where('type', '<>', 1)->where('user_id', $item->user_id)->where('device_code', $item->device_code)->select();
            if($accounts->isEmpty()){
                throw new \Exception('该设备没有绑定账号');
            }

            $task = SvDeviceTakeOverTask::create([
                'user_id' => $item->user_id,
                'task_name' => '自动化私信接管任务'. date('mdHis', time()),
                'auto_type' => 1,
                'accounts' => json_encode($accounts->toArray(), JSON_UNESCAPED_UNICODE),
                'time_config' => json_encode($item->exec_time, JSON_UNESCAPED_UNICODE),
                'status' => 0,
                'task_frep' => 0,
                'create_time' => time(),
            ]);

            $deviceTask = [];
            foreach ($accounts as $key => $account) {
                $setting = SvSetting::where('user_id', $item->user_id)->where('account', $account->account)->findOrEmpty();
                if($setting->isEmpty()){
                    throw new \Exception('该账号没有绑定设置');
                }
                $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $item->device_code)
                    ->where('task_type', DeviceEnum::AUTO_TYPE_TAKE_OVER)
                    ->where('source', DeviceEnum::TASK_SOURCE_TAKEOVER)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('day', 'desc')
                    ->limit(1)
                    ->value('day');
                $date = is_null($maxDay) ? date('Y-m-d', time()) : date('Y-m-d', (strtotime($maxDay) + (24 * 60 * 60)));

                $exec_times = $item->exec_time[$account->type];
                foreach ($exec_times as $key => $exec_time) {
                    $times = explode('-', $exec_time);

                    $startTime = strtotime($date . ' ' . $times[0] . ':00') > time() ? strtotime($date . ' ' . $times[0] . ':00') : strtotime(date('Y-m-d ' . $times[0] . ':00', strtotime('+1 day')));
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 180;
                    // print_r(date('Y-m-d H:i:s', $startTime));
                    // print_r(date('Y-m-d H:i:s', $endTime));die;
                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => $item->user_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'device_code' => $item->device_code,
                        'robot_id' => $item->robot_id,
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
                        'auto_type' => 1,
                        'task_name' => '自动化私信接管任务',
                        'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_TAKEOVER,
                        'create_time' => time(),
                    ]);
                }

                //生成接管任务时，同步将账号设置中的智能体id更新并开启ai回复
                $setting->robot_id = $item->robot_id;
                $setting->open_ai = 1;
                $setting->takeover_mode = 1;
                $setting->update_time = time();
                $setting->save();
            }
            //print_r($deviceTask);die;
            (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'take_over');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
