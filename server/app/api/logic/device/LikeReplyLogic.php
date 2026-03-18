<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\wechat\AiWechat;
use app\common\model\sv\SvAccount;
use app\common\enum\DeviceEnum;
use think\facade\Db;


/**
 * 设备点赞回复任务逻辑
 * Class LikeReplyLogic    
 * @package app\api\logic\device
 */
class LikeReplyLogic extends ApiLogic
{
    public static function add($params)
    {   
        // 开启事务
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            TaskLogic::checkAccounts($params['accounts']);
            $times = TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            $params['user_id'] = self::$uid;
            $params['task_name'] =  $params['task_name'] ??  '朋友圈点赞评论任务' . date('mdHis', time());
            $params['range'] = $params['range'] ?? 0;
            $params['range'] = $params['range'] > 0 ? ($params['range'] - 1) : $params['range'];
            $task = SvDeviceCircleLikeReply::create($params);
            $allTaskInstall = [];
            foreach ($params['accounts'] as $account) {
                $find = SvAccount::where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                $account = array_merge($account, $find);

                foreach ($times as $time) {
                    list($isOverlap, $lap) = TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }

                    $time['end_time'] = $time['end_time'] - 180;

                    $row = SvDeviceCircleLikeReplyAccount::create([
                        'circle_like_reply_id' => $task->id,
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'auto_type' => 0,
                        'task_name' => $params['task_name'],
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'nickname' => $account['nickname'],
                        'avatar' => $account['avatar'],
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'status' => DeviceEnum::TASK_STATUS_WAIT,
                        'create_time' => time(),
                    ]);

                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $account['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'auto_type' => 0,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'nickname' => $account['nickname'],
                        'avatar' => $account['avatar'],
                        'task_name' => '朋友圈点赞评论任务',
                        'status' => 0,
                        'day' => date('Y-m-d',$time['start_time']),
                        'time_config' => json_encode($params['time_config'], JSON_UNESCAPED_UNICODE),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $task->id,
                        'sub_data_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT,//sv_device_take_over_task_account
                        'create_time' => time(),
                    ]);
                    \app\api\logic\device\TaskLogic::updateWechatRpaTaskTime($account['device_code'], $time['start_time']);
                }
            }
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