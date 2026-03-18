<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;

use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceCircleLikeReplyConfig;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 自动点赞评论任务逻辑
 * Class LikeReplyLogic    
 * @package app\api\logic\auto
 */
class LikeReplyLogic extends ApiLogic
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

            $find = AutoDeviceCircleLikeReplyConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::$returnData = $find->toArray();
            } else {
                $insertData = [
                    'user_id' => self::$uid,
                    'device_code' => $params['device_code'],
                    'is_like' => $params['is_like'] ?? 0,
                    'is_comment' => $params['is_comment'] ?? 0,
                    'comment_method' => $params['comment_method'] ?? 1,
                    'comment_robot_prompt' => $params['comment_robot_prompt'] ?? AutoDeviceCircleLikeReplyConfig::getCommentRobotPrompt(),
                    'robot_params' => [
                        'model' => 'gpt-4o',
                        'temperature' => 0.3,
                        'top_p' => 0.8,
                        'presence_penalty' => 0,
                        'frequency_penalty' => 0,
                        'max_tokens' => 4096,
                        'context_num' => 0,
                        'stream' => false,
                    ],
                    'number' => $params['number'] ?? 15,
                    'comment_speech' => $params['comment_speech'] ?? \app\common\service\ConfigService::get('wechat_circle', 'comment_speech', []),
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'exec_time' =>  [
                        '09:00-10:00',
                        '14:00-15:00'
                    ],
                    'exec_date' => $params['exec_date'] ?? date('Y-m-d', time()),
                ];
                $activeConfig = AutoDeviceCircleLikeReplyConfig::create($insertData);
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
            $find = AutoDeviceCircleLikeReplyConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    self::setError('点赞评论任务任务正在执行，不可修改，稍后再试');
                    return false;
                }
                $find->is_like = $params['is_like'] ?? 0;
                $find->is_comment = $params['is_comment'] ?? 0;
                $find->comment_method = $params['comment_method'] ?? 1;
                $find->comment_robot_prompt = $params['comment_robot_prompt'] ?? AutoDeviceCircleLikeReplyConfig::getCommentRobotPrompt();
                $find->number = $params['number'] ?? 15;
                $find->comment_speech = (isset($params['comment_speech']) && !empty($params['comment_speech'])) ? $params['comment_speech'] : \app\common\service\ConfigService::get('wechat_circle', 'comment_speech', []);
                $find->update_time = time();
                if (is_null($find->exec_date)) {
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->save();
            } else {
                self::setError('该设备点赞评论任务任务配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function autoLikeReplyTaskCron(int $isFrist = 0, $deviceCode = null)
    {
        print_r("\n{$deviceCode}自动化点赞评论任务生成\n");
        try {
            $where = [];
            $where[] = ['device_code', '=', $deviceCode];
            if ($isFrist === 1) {
                $where[] = ['is_first', '=', 0];
            } else {
                $where[] = ['exec_date', '<=', date('Y-m-d', time())];
            }
            $items = AutoDeviceCircleLikeReplyConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->select();
            \think\facade\Log::channel('auto')->write('自动化点赞评论任务生成' . $items->isEmpty() ? \think\facade\Db::getLastSql() : $items->count() . '条', 'like_reply');
            if ($items->isEmpty()) {
                return true;
            }
            $count = $isFrist === 1 ? 1 : 2;
            foreach ($items as $item) {
                for ($i = 0; $i < $count; $i++) {
                    self::createAutoLikeReplyTask($item);
                    sleep(1);
                }
                $item->exec_date = date('Y-m-d', strtotime('+' . $count . ' day'));
                $item->is_first = 1;
                $item->save();
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'like_reply');
            return false;
        }
    }

    private static function createAutoLikeReplyTask(AutoDeviceCircleLikeReplyConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $accounts = SvAccount::field('id,account,type,nickname,avatar')->where('type', '=', 1)->where('user_id', $item->user_id)->where('device_code', $item->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('该设备没有绑定微信账号' . $item->device_code);
            }
            $task = SvDeviceCircleLikeReply::create([
                'user_id' => $item->user_id,
                'auto_type' => 1,
                'task_name' => '自动化朋友圈点赞评论任务' . date('mdHis', time()),
                'accounts' => $accounts->toArray(),
                'time_config' => $item->exec_time,
                'action' => ($item->is_like === 1 && $item->is_comment === 1) ? 3 : ($item->is_like === 1 ? 1 : ($item->is_comment === 1 ? 2 : 0)),
                'number' => $item->number,
                'interval' => 2,
                'range' => 0,
                'robot_id' => 0,
                'auto_reply_config_id' => $item->id,
                'comment_type' => 1,
                'comment' => '',
                'task_frep' => 0,
                'create_time' => time(),
            ]);

            $deviceTask = [];
            foreach ($accounts as $key => $account) {

                $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $item->device_code)
                    ->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT)
                    ->where('source', DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('day', 'desc')
                    ->limit(1)
                    ->value('day');
                $date = is_null($maxDay) ? date('Y-m-d', time()) : date('Y-m-d', (strtotime($maxDay) + (24 * 60 * 60)));

                $exec_times = $item->exec_time;
                foreach ($exec_times as $key => $exec_time) {
                    $times = explode('-', $exec_time);

                    $startTime = strtotime($date . ' ' . $times[0] . ':00') > time() ? strtotime($date . ' ' . $times[0] . ':00') : strtotime(date('Y-m-d ' . $times[0] . ':00', strtotime('+1 day')));
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 180;
                    // print_r(date('Y-m-d H:i:s', $startTime));
                    // print_r(date('Y-m-d H:i:s', $endTime));die;

                    $row = SvDeviceCircleLikeReplyAccount::create([
                        'circle_like_reply_id' => $task->id,
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'auto_type' => 1,
                        'task_name' => $task->task_name,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    $row->refresh();

                    array_push($deviceTask, [
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化朋友圈点赞评论任务',
                        'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $task->id,
                        'sub_data_id' => $row->id,
                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'create_time' => time(),
                    ]);
                }
            }
            //print_r($deviceTask);die;
            (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->remark = '任务执行成功'. date('Y-m-d H:i:s', time());
            $item->update_time = time();
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('点赞评论任务生成失败：' . $item->device_code . "  \n. " . $th->__toString(), 'like_reply');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
