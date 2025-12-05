<?php

namespace app\api\logic\wechat;

use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechat;
use think\facade\Queue;

use app\common\traits\WechatTrait;

/**
 * CircleLogic
 * @desc 微信朋友圈
 * @author Qasim
 */
class CircleLogic extends WechatBaseLogic
{

    /**
     * @desc 添加发圈任务
     * @param array $params
     * @return bool
     */
    public static function addTask(array $params)
    {

        try
        {
            $data = [];

            $wechatIds = array_unique($params['wechat_ids'] ?? []);
            if (empty($wechatIds))
            {
                self::setError('请选择微信账号');
                return false;
            }
            unset($params['wechat_ids']);
            // 即时
            if ($params['task_type'] == 0)
            {

                $params['send_time'] = date('Y-m-d H:i');
            }

            foreach ($wechatIds as $wechatId)
            {
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
        }
        catch (\Exception $e)
        {
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

        try
        {
            $task = AiWechatCircleTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty())
            {
                self::setError('任务不存在');
                return false;
            }

            if ($task->task_type == 0)
            {
                self::setError('即时发圈任务不可修改');
                return false;
            }

            $params['comment'] = isset($params['comment']) ? explode("##", $params['comment']) : [];

            $task->save($params);

            self::$returnData = $task->toArray();
            return true;
        }
        catch (\Exception $e)
        {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除发圈任务
     * @param array $params
     * @return bool
     */
    public static function deleteTask(array $params)
    {
        try
        {
            $task = AiWechatCircleTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty())
            {
                self::setError('任务不存在');
                return false;
            }

            $task->delete();
            return true;
        }
        catch (\Exception $e)
        {
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
        try
        {
            $task = AiWechatCircleTask::where('id', $taskId)->where('user_id', self::$uid)->findOrEmpty();

            if ($task->isEmpty())
            {
                self::setError('任务不存在');
                return false;
            }

            self::$returnData = $task->toArray();
            return true;
        }
        catch (\Exception $e)
        {
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
        try
        {
            //print_r('定时推送朋友圈');
            $wechatIds = AiWechat::alias('w')
                ->join('ai_wechat_device d', 'd.user_id = w.user_id and d.device_code = w.device_code')
                ->where('w.wechat_status', 1)
                ->where('d.device_status', 1)
                ->column('w.wechat_id');
            if (empty($wechatIds))
            {
                return;
            }
            // 即时发圈
            AiWechatCircleTask::where('task_type', $taskType)
                ->when($uid, function ($query) use ($uid)
                {
                    $query->where('user_id', $uid);
                })
                ->where('wechat_id', 'in', $wechatIds)
                ->where('send_status', 0)
                ->select()
                ->each(function ($item)
                {

                    // 获取微信设备码
                    $deviceCode = AiWechat::where('user_id', $item->user_id)->where('wechat_id', $item->wechat_id)->value('device_code', '');
                    if (!$deviceCode)
                    {
                        $item->task_status = 2;
                        $item->save();
                        return true;
                    }

                    // 定时发圈
                    if ($item->task_type == 1)
                    {
                        $sendTime = strtotime($item->send_time);
                        // 未到执行时间
                        if ($sendTime > time())
                        {
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
        }
        catch (\Exception $e)
        {
            print_r($e->getMessage());
            return false;
        }
    }
}
