<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\wechat\AiWechat;
use app\common\model\sv\SvAccount;
use app\common\enum\DeviceEnum;
use think\facade\Db;


/**
 * 设备接管任务逻辑
 * Class TakeOverLogic    
 * @package app\api\logic\device
 */
class TakeOverLogic extends ApiLogic
{
    public static function add($params)
    {
        Db::startTrans();
        try {

            $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            //print_r($times);die;
            $params['user_id'] = self::$uid;
            $accounts = $params['accounts'];
            $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            $params['task_name'] =  $params['task_name'] ??  '设备接管任务' . date('mdHis', time()) ;
            $task = SvDeviceTakeOverTask::create($params);
            $allTaskInstall = [];
            foreach ($accounts as $account) {
                if ($account['type'] == 1) {
                    $find = AiWechat::where('wechat_id', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                } else{
                    $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                }

                foreach ($times as $time) {
                    list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_TAKEOVER, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }

                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,   
                        'user_id' => self::$uid,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'device_code' => $account['device_code'],
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'status' => 0,
                    ]);
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_TAKEOVER,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'task_name' => '设备接管任务',
                        'status' => 0,
                        'day' => date('Y-m-d',$time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_TAKEOVER,//sv_device_take_over_task_account
                        'create_time' => time(),
                    ]);
                }
            }
            //print_r($allTaskInstall);die;
            TaskLogic::add($allTaskInstall);

            Db::commit();
            self::$returnData = $task->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            //print_r($th->__toString());die;
            self::setError($th->getMessage());
            return false;
        }
    }
}
