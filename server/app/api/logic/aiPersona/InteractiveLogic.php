<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 私域互动管家逻辑
 * Class InteractiveLogic    
 * @package app\api\logic\aiPersona
 */
class InteractiveLogic extends ApiLogic
{
    public static function detail($params)
    {
        ini_set('max_execution_time', 0);
        try {
            $config = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($config->isEmpty()) {
                self::setError('设备自动化配置不存在');
                return false;
            }

            $personaRule = self::getPersonaRule($config);

            $find = AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->add_friend_script = empty($find->add_friend_script) ? implode("\n", $personaRule->wechat_add_friend_script) : $find->add_friend_script;
                $find->comment_speech = empty($find->comment_speech) ? $personaRule->wechat_comment_speech : $find->comment_speech;
                $find->save();
                $find->clue_count = self::getClues();
                self::$returnData = $find->toArray();
            } else {


                // $payload = array(
                //     'keywords' => $personaRule->clue_content,
                // );
                // $response = \app\common\service\ToolsService::Coze()->wechatTouch($payload);
                // // continue;
                // if ((int)$response['code'] !== 10000 || !isset($response['data']['content'])) {
                //     self::setError($response['msg'] ?? '获取微信话术失败');
                //     return false;
                // }
                // $result = json_decode($response['data']['content'], true);
                // $output = json_decode($result['output'], true);
                $insertData = [
                    'user_id' => self::$uid,
                    'persona_id' => $params['persona_id'],
                    'add_friend_enabled' => $params['add_friend_enabled'] ?? 1,
                    'add_friend_source' => $params['add_friend_source'] ?? 1,
                    'add_friend_script' => implode("\n", $personaRule->wechat_add_friend_script ?? ''),
                    'is_like' => $params['is_like'] ?? 0,
                    'is_comment' => $params['is_comment'] ?? 0,
                    'comment_method' => $params['comment_method'] ?? 1,
                    'comment_robot_prompt' => $params['comment_robot_prompt'] ?? AiPersonaWechatInteractionConfig::getCommentRobotPrompt(),
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
                    'comment_speech' => $personaRule->wechat_comment_speech,
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'exec_time' =>  [],
                    'exec_date' => $params['exec_date'] ?? date('Y-m-d', time()),
                ];
                $config = AiPersonaWechatInteractionConfig::create($insertData);
                $config->clue_count = self::getClues();
                self::$returnData = $config->toArray();
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
            $find = AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    self::setError('互动管家任务正在执行，不可修改，稍后再试');
                    return false;
                }
                $find->add_friend_enabled = $params['add_friend_enabled'] ?? 1;
                $find->add_friend_source = $params['add_friend_source'] ?? 1;
                $find->add_friend_script = $params['add_friend_script'] ?? '';
                $find->is_like = $params['is_like'] ?? 0;
                $find->is_comment = $params['is_comment'] ?? 0;
                $find->comment_method = $params['comment_method'] ?? 1;
                $find->comment_robot_prompt = $params['comment_robot_prompt'] ?? AiPersonaWechatInteractionConfig::getCommentRobotPrompt();
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
            $find->clue_count = self::getClues();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function getClues()
    {
        $count = SvAddWechatRecord::where('user_id', self::$uid)->where('status', 4)->group('reg_wechat')->count();
        return $count;
    }

    public static function autoInteractiveTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化互动管家任务生成\n");
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在:' . \think\facade\Db::getLastSql());
            }


            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            //$where[] = ['exec_date', '<=', date('Y-m-d', time())];
            $item = AiPersonaWechatInteractionConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write('自动化互动管家任务生成' . $item->isEmpty() ? \think\facade\Db::getLastSql() : $item->id, 'like_reply');
            if ($item->isEmpty()) {
                return true;
            }

            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            self::createAutoLikeReplyTask($item);
            self::createAutoAddWechatTask($item);
            $item->exec_date = date('Y-m-d', strtotime('+1 day'));
            $item->is_first = 0;
            $item->save();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'like_reply');
            return false;
        }
    }

    private static function createAutoLikeReplyTask(AiPersonaWechatInteractionConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $accounts = SvAccount::field('id,account,type,nickname,avatar')->where('type', '=', 1)->where('user_id', $item->user_id)->where('device_code', $item->device_code)->select();
            if ($accounts->isEmpty()) {
                \think\facade\Log::channel('auto')->write('该设备没有绑定微信账号' . $item->device_code, 'like_reply');
            } else {
                $task = SvDeviceCircleLikeReply::create([
                    'user_id' => $item->user_id,
                    'auto_type' => 1,
                    'persona_id' => $item->persona_id,
                    'task_name' => '自动化朋友圈点赞评论任务' . date('YmdHis', time()),
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
                    $date = date('Y-m-d', time());

                    $exec_times = $item->getTimesByType($item->persona_type, $account->type);
                    foreach ($exec_times as $key => $exec_time) {
                        $times = explode('-', $exec_time);

                        $startTime = strtotime($date . ' ' . $times[0] . ':00');
                        $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 120;
                        // print_r(date('Y-m-d H:i:s', $startTime));
                        // print_r(date('Y-m-d H:i:s', $endTime));die;

                        $row = SvDeviceCircleLikeReplyAccount::create([
                            'circle_like_reply_id' => $task->id,
                            'persona_id' => $item->persona_id,
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
                        //$row->refresh();

                        array_push($deviceTask, [
                            'user_id' => $item->user_id,
                            'device_code' => $item->device_code,
                            'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'nickname' => $account->nickname,
                            'avatar' => $account->avatar,
                            'auto_type' => 1,
                            'task_name' => '自动化朋友圈点赞评论任务' . date('YmdHis', time()),
                            'time_config' => json_encode([$exec_time], JSON_UNESCAPED_UNICODE),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'day' => date('Y-m-d', $startTime),
                            'status' => 0,
                            'sub_task_id' => $task->id,
                            'sub_data_id' => $row->id,
                            'persona_id' => $item->persona_id,
                            'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT,
                            'create_time' => time(),
                        ]);
                    }
                }
                //print_r($deviceTask);die;
                (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);
                $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
                $item->remark = '任务执行成功' . date('Y-m-d H:i:s', time());
                $item->update_time = time();
                $item->save();
            }

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

    private static function createAutoAddWechatTask(AiPersonaWechatInteractionConfig $item)
    {

        Db::startTrans();
        try {

            $wechat = SvAccount::where('user_id', $item->user_id)->where('device_code', $item->device_code)->where('type', 1)->findOrEmpty();
            if ($wechat->isEmpty()) {
                \think\facade\Log::channel('auto')->write('该设备绑定的微号不存在' . $item->device_code . ' ' . \think\facade\Db::getLastSql() . 'add_wechat');
            } else {
                $date = date('Y-m-d', time());
                $times = self::getTimesByAddWechat($item->persona_type, 1);
                foreach ($times as $time) {
                    list($st, $et) = explode('-', $time);
                    $startTime = strtotime($date . ' ' . $st . ':00');
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $et . ':00') - 120;

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
            }


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

    public static function getTimesByAddWechat(int $personaType, int $accountType)
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
}
