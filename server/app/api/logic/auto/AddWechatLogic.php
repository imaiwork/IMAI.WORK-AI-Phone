<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;

use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDevice;
use app\common\model\wechat\AiWechat;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceAddWechatConfig;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 养号自动任务逻辑
 * Class AddWechatLogic    
 * @package app\api\logic\auto
 */
class AddWechatLogic extends ApiLogic
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

            $find = AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::$returnData = $find->toArray();
            } else {
                $insertData = [
                    'user_id' => $params['user_id'] ?? $config->user_id,
                    'device_code' => $params['device_code'],
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'exec_time' => '20:30-21:30',
                    'exec_date' => date('Y-m-d', time()),
                    'speech_type' => 1,
                    'remarks' => \app\common\service\ConfigService::get('add_remark', 'wechat', []),
                ];
                $activeConfig = AutoDeviceAddWechatConfig::create($insertData);
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
            $find = AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING){
                    self::setError('加微自动任务正在执行，不可修改，稍后再试');
                    return false;
                }
                $find->exec_time = $params['exec_time'] ?? $find->exec_time;
                $find->remarks = $params['remarks'] ?? $find->remarks;
                $find->speech_type = $params['speech_type'] ?? $find->speech_type;
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->update_time = time();
                if(is_null($find->exec_date)){
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->save();
            }else{
                self::setError('该设备加微自动任务配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
    

    public static function autoAddWechatTaskCron(int $isFrist = 0, $deviceCode = null){
        print_r("\n{$deviceCode}自动化加微任务生成\n");
        try {
            $where = [];
            if($isFrist === 1){
                $where[] = ['is_first', '=', 0];
                $where[] = ['device_code', '=', $deviceCode];
            }else{
                $where[] = ['exec_date', '<=',  date('Y-m-d', time())];
            }
            $items = AutoDeviceAddWechatConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)
            ->where($where)
            ->select();
            \think\facade\Log::channel('auto')->write('自动化加微任务生成' . $items->isEmpty() ? \think\facade\Db::getLastSql() : $items->count() . '条', 'add_wechat');
            if($items->isEmpty()){
                return true;
            }
            $count = $isFrist === 1 ? 1 : 2;
            foreach ($items as $item) {
                for($i = 0; $i < $count; $i++){
                    self::createAutoAddWechatTask($item);
                }
                $item->exec_date = date('Y-m-d', strtotime('+' . $count .' day'));
                $item->is_first = 1;
                $item->save();
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'add_wechat');
            return false;
        }
    }

    private static function createAutoAddWechatTask(AutoDeviceAddWechatConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $device = SvDevice::where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
            
            if($device->isEmpty()){
                throw new \Exception('该设备没有绑定账号');
            }

            if($device->wechat_device_code == null){
                throw new \Exception('该设备没有绑定个微设备号');
            }   

            $wechat = AiWechat::where('user_id', $item->user_id)->where('device_code', $device->wechat_device_code)->findOrEmpty();
           
            if($wechat->isEmpty()){
                throw new \Exception('该设备绑定的微号不存在');
            }   

            $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $item->device_code)
                ->where('task_type', DeviceEnum::AUTO_TYPE_WECHAT_FRIEND)
                ->where('source', DeviceEnum::TASK_SOURCE_FRIENDS)
                ->where('auto_type', 1)
                ->where('account', $wechat->wechat_id)
                ->where('account_type', 1)
                ->order('day', 'desc')
                ->limit(1)
                ->value('day');
            $date = is_null($maxDay) ? date('Y-m-d', time()) : date('Y-m-d', (strtotime($maxDay) + (24 * 60 * 60)));
            $times = explode('-', $item->exec_time);

            $startTime = strtotime($date . ' ' . $times[0] . ':00') > time() ? strtotime($date . ' ' . $times[0] . ':00') : strtotime(date('Y-m-d ' . $times[0] . ':00', strtotime('+1 day')));
            $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 180;

            $deviceTask = [
                    'user_id' => $item->user_id,
                    'device_code' => $item->device_code,
                    'task_type' => DeviceEnum::AUTO_TYPE_WECHAT_FRIEND,
                    'account' => $wechat->wechat_id,
                    'account_type' => 1,
                    'auto_type' => 1,
                    'task_name' => '自动化加微任务',
                    'time_config' => json_encode([$item->exec_time], JSON_UNESCAPED_UNICODE),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $startTime),
                    'status' => 0,
                    'sub_task_id' => 0,
                    'source' => DeviceEnum::TASK_SOURCE_FRIENDS,
                    'create_time' => time(),
            ];
            \app\common\model\sv\SvDeviceTask::create($deviceTask);
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'add_wechat');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->result = $th->getMessage();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}