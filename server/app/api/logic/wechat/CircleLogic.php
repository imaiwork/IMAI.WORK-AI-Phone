<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\wechat\AiWechat;
use think\facade\Queue;
use app\common\enum\DeviceEnum;
use app\common\traits\WechatTrait;
use think\facade\Db;

/**
 * CircleLogic
 * @desc 微信朋友圈
 * @author Qasim
 */
class CircleLogic extends WechatBaseLogic
{

    public static function check(array $params)
    {
        try {
            self::checkAutoDevice($params);
            $wechatIds = array_unique($params['wechat_ids'] ?? []);
            $times = \app\api\logic\device\TaskLogic::getTimes([$params['time_config']], $params['date'], 1);
            foreach ($wechatIds as $wechatId) {
                $account = \app\common\model\sv\SvAccount::where('account', $wechatId)->where('type', 1)->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                if ($account->isEmpty()) {
                    throw new \Exception("{$wechatId} 该微信账号信息未找到");
                }
                foreach ($times as $time) {
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($account->device_code, DeviceEnum::TASK_TYPE_WECHAT_CIRCLE, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }
                }
            }

            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
    /**
     * @desc 添加发圈任务
     * @param array $params
     * @return bool
     */
    public static function addTask(array $params)
    {
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            $params['status'] = (empty($params['wechat_ids']) || empty($params['time_config'])) ? 0 : 1;
            $params['user_id'] = self::$uid;
            $params['task_name'] = $params['task_name'] ?? '朋友圈发布任务' . date('YmdHis');

            if(empty($params['attachment_content'])){
                self::setError('请选择媒体素材');
                return false;
            }
            $taskConfig = AiWechatCircleTaskConfig::create($params);
            self::createCircleTask($params, $taskConfig);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function addTask1(array $params)
    {

        try {
            $data = [];

            $wechatIds = array_unique($params['wechat_ids'] ?? []);
            if (empty($wechatIds)) {
                self::setError('请选择微信账号');
                return false;
            }
            unset($params['wechat_ids']);
            // 即时
            if ($params['task_type'] == 0) {

                $params['send_time'] = date('Y-m-d H:i');
            }

            foreach ($wechatIds as $wechatId) {
                $params['comment'] = json_encode($params['comment'] ? explode("##", $params['comment']) : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $params['attachment_content'] = json_encode($params['attachment_content'] ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $data[] = array_merge($params, ['task_id' => time() . rand(100, 999), 'wechat_id' => $wechatId, 'user_id' => self::$uid, 'create_time' => time(), 'update_time' => time()]);
            }
            //print_r($data);die;
            // 添加
            AiWechatCircleTask::insertAll($data);

            //即时发圈
            self::sendCircleCron(0, self::$uid);

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新发圈任务
     * @param array $params
     * @return bool
     */
    public static function updateTask(array $params)
    {

        try {
            $task = AiWechatCircleTaskConfig::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            if ($task->status === 2) {
                self::setError('任务正在执行中，不能更新');
                return false;
            }

            $params['status'] = (empty($params['wechat_ids']) || empty($params['time_config'])) ? 0 : 1;

            $task->save($params);
            AiWechatCircleTask::where('task_config_id', $task->id)->select()->delete();
            \app\common\model\sv\SvDeviceTask::where('user_id', $task->user_id)->where('sub_task_id', $task->id)->where('source', 7)->where('task_type', 7)->select()->delete();
            self::createCircleTask($params, $task);

            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createCircleTask(array $params, AiWechatCircleTaskConfig $taskConfig)
    {
        try {
            if ($params['status'] === 1) {
                $times = \app\api\logic\device\TaskLogic::getTimes([$params['time_config']], $params['date'], 1);
                $wechatIds = array_unique($params['wechat_ids'] ?? []);
                $insertData = [];
                $allTaskInstall = [];
                foreach ($wechatIds as $wechatId) {
                    $account = \app\common\model\sv\SvAccount::where('account', $wechatId)->where('type', 1)->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                    if ($account->isEmpty()) {
                        throw new \Exception("{$wechatId} 该微信账号信息未找到");
                    }

                    foreach ($times as $time) {
                        list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($account->device_code, DeviceEnum::TASK_TYPE_WECHAT_CIRCLE, $time['start_time'], $time['end_time'], self::$uid);
                        if (!$isOverlap) {
                            $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                            $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                            throw new \Exception($msg);
                        }
                        // $params['attachment_content'] = json_encode($params['attachment_content'] ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        // $params['comment'] = json_encode($params['comment'] ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        $task = AiWechatCircleTask::create([
                            'user_id' => self::$uid,
                            'task_name' => $params['task_name'],
                            'task_config_id' => $taskConfig->id,
                            'device_code' => $account->device_code,
                            'wechat_id' => $wechatId,
                            'task_id' => time() . rand(100, 999),
                            'task_type' => 1,
                            'content' => $params['content'],
                            'attachment_type' => $params['attachment_type'],
                            'attachment_content' => $params['attachment_content'],
                            //'comment' => $params['comment'],
                            'send_time' => date('Y-m-d H:i:s', $time['start_time']),
                            'send_status' => 0,
                            'create_time' => time()
                        ]);
                        array_push($allTaskInstall, [
                            'user_id' => self::$uid,
                            'device_code' => $account->device_code,
                            'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'task_name' => '设备朋友圈发布任务',
                            'status' => 0,
                            'day' => date('Y-m-d', $time['start_time']),
                            'time_config' => json_encode([$params['time_config']], JSON_UNESCAPED_UNICODE),
                            'start_time' => $time['start_time'],
                            'end_time' => $time['end_time'] - 180,
                            'sub_task_id' => $taskConfig->id,
                            'sub_data_id' => $task->id,
                            'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH, //sv_device_active_account
                            'create_time' => time(),
                        ]);
                        \app\api\logic\device\TaskLogic::updateWechatRpaTaskTime($account->device_code, $time['start_time']);
                    }
                }
                AiWechatCircleTask::insertAll($insertData);
                \app\api\logic\device\TaskLogic::add($allTaskInstall);
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }



    /**
     * @desc 删除发圈任务
     * @param array $params
     * @return bool
     */
    public static function deleteTask(array $params)
    {
        try {
            $task = AiWechatCircleTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            
            AiWechatCircleTask::where('task_config_id', $task->id)->select()->delete();
            \app\common\model\sv\SvDeviceTask::where('user_id', $task->user_id)->where('sub_data_id', $task->id)->where('source', 7)->where('task_type', 7)->select()->delete();
            $task->delete();

            $count = AiWechatCircleTask::where('task_config_id', $task->task_config_id)->where('user_id', self::$uid)->count();
            if($count === 0){
                AiWechatCircleTaskConfig::where('id', $task->task_config_id)->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取发圈任务
     * @param int $taskId
     * @return bool
     */
    public static function taskInfo(int $taskId)
    {
        try {
            $task = AiWechatCircleTask::where('id', $taskId)->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 推送消息
     * @param array $params
     * @return bool
     */
    public static function sendCircleCron(int $taskType = 1, int $uid = 0)
    {
        try {
            //print_r('定时推送朋友圈');
            $wechatIds = AiWechat::alias('w')
                ->join('ai_wechat_device d', 'd.user_id = w.user_id and d.device_code = w.device_code')
                ->where('w.wechat_status', 1)
                ->where('d.device_status', 1)
                ->column('w.wechat_id');
            if (empty($wechatIds)) {
                return;
            }
            // 即时发圈
            AiWechatCircleTask::where('task_type', $taskType)
                ->when($uid, function ($query) use ($uid) {
                    $query->where('user_id', $uid);
                })
                ->where('wechat_id', 'in', $wechatIds)
                ->where('send_status', 0)
                ->select()
                ->each(function ($item) {

                    // 获取微信设备码
                    $deviceCode = AiWechat::where('user_id', $item->user_id)->where('wechat_id', $item->wechat_id)->value('device_code', '');
                    if (!$deviceCode) {
                        $item->task_status = 2;
                        $item->save();
                        return true;
                    }

                    // 定时发圈
                    if ($item->task_type == 1) {
                        $sendTime = strtotime($item->send_time);
                        // 未到执行时间
                        if ($sendTime > time()) {
                            return true;
                        }
                    }

                    $item->send_status = 1;
                    $item->save();

                    // 任务数据
                    $data = [
                        'device_code' => $deviceCode,
                        'wechat_id' => $item->wechat_id,
                        'content' => $item->content,
                        'attachment_type' => $item->attachment_type,
                        'attachment_content' => $item->attachment_content,
                        'comment'   => explode('##', $item->comment),
                        'task_id' => $item->task_id,
                        'uid' => $item->user_id,
                    ];

                    $response = self::wxCircle($data);

                    // if ($response['code'] == 10000) {
                    //     $item->send_status = 2;
                    //     $item->finish_time = date('Y-m-d H:i');
                    //     $item->save();
                    // } else {
                    //     $item->send_status = 3;
                    //     $item->save();
                    // }
                    // 推送到队列
                    //Queue::later(3, 'app\common\Jobs\WechatSendCircleJob@fire', $data);
                });

            return true;
        } catch (\Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }
}
