<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\wechat\AiWechat;
use think\facade\Db;


/**
 * 设备养号任务逻辑
 * Class ActiveLogic    
 * @package app\api\logic\device
 */
class ActiveLogic extends ApiLogic
{
    public static function add($params)
    {
        Db::startTrans();
        try {

            $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            $params['user_id'] = self::$uid;
            $accounts = $params['accounts'];
            $params['task_name'] =  $params['task_name'] ??  '养号任务' . date('mdHis', time()) ;
            $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            $task = SvDeviceActive::create($params);
            $allTaskInstall = [];
            foreach ($accounts as $account) {
                if ($account['type'] == 1) {
                    $find = AiWechat::where('wechat_id', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                } else {
                    $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                }

                foreach ($times as $time) {
                    list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_ACTIVE, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }

                    $row = SvDeviceActiveAccount::create([
                        'active_id' => $task->id,
                        'user_id' => self::$uid,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'device_code' => $account['device_code'],
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'exec_content' => self::getExecContent($account['type'], $account['account']),
                        'status' => 0,
                    ]);
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_ACTIVE,  
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'task_name' => '设备养号任务',
                        'status' => 0,
                        'day' => date('Y-m-d',$time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_ACTIVE,//sv_device_active_account
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

    private static function getExecContent(int $type, string $account)
    {
        if ($type == 1) {
            return '{"wechat_id":"' . $account . '","msg_type":"text","msg_content":"设备养号"}';
        } elseif ($type == 3) {
            return '{"account":"' . $account . '","msg_type":"text","msg_content":"设备养号"}';
        }
    }



    public static function subtasks(array $params)
    {
        try {
            // 检查机器人是否存在
            $info = SvDeviceActiveAccount::field('*')

                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty()->toArray();
            if (!$info) {
                self::setError('任务不存在');
                return false;
            }
            if ($info['account_type'] == 1){
                $where = [
                    'device_code'=> $info['device_code'],
                    'wechat_id' => $info['account']
                ];
                $info['account_info'] = AiWechat::where($where)->findOrEmpty()->toArray();
            }else{
                $where = [
                    'device_code'=> $info['device_code'],
                    'account' => $info['account']
                ];
                $info['account_info'] = SvAccount::where($where)->findOrEmpty()->toArray();

            }
            $info['task_name'] = $params['task_name'];
            $info['task_category'] = $params['task_category'];
            $info['start_time'] = date('H:i',$info['start_time']);
            $info['end_time'] = date('H:i',$info['end_time']);
            $info['device_info'] = SvDevice::where('device_code', $info['device_code'])->findOrEmpty()->toArray();
            self::$returnData = $info;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
