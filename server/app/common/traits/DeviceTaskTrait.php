<?php

declare(strict_types=1);

namespace app\common\traits;

use think\facade\Log;
use think\facade\Db;
use think\console\Output;
use Channel\Client as ChannelClient;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;

use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;

use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\enum\DeviceEnum;
use app\common\service\FileService;
use think\cache\driver\Redis;

trait DeviceTaskTrait
{
    private static $redisInstance = null;
    private static $logtitle = '';
    private static $redisSelect = 8;

    public static function sphCluesStartTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "视频号线索任务[{$dtask->device_code}]";
            $task = SvCrawlingTask::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($task->isEmpty()) {
                $output->writeln("获客任务不存在：\n" . Db::getLastSql());
                self::setLog("获客任务不存在：\n" . Db::getLastSql(), 'clues');
                throw new \Exception('获客任务不存在');
            }

            self::checkOnline($dtask->device_code, 'ws');

            $find = SvCrawlingTask::alias('ct')
                ->field('ct.*, b.device_code,b.keywords')
                ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id and b.exec_keyword = ""')
                ->where('ct.id', $task->id)
                ->where('b.device_code', $dtask->device_code)
                ->where('ct.status', 'in', [0, 1])
                ->fetchSql(false)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln("暂时没有需要执行的设备：\n" . Db::getLastSql());
                self::setLog("暂时没有需要执行的设备：\n" . Db::getLastSql(), 'clues');
                throw new \Exception('暂时没有需要执行的设备');
            }

            // $_deviceTaskStatus = self::redis()->get("xhs:device:{$find->device_code}:taskStatus");
            // if (!empty($_deviceTaskStatus)) {
            //     $deviceTaskStatus = json_decode(($_deviceTaskStatus), true);
            //     if (is_null($deviceTaskStatus)) {
            //         $deviceTaskStatus = json_decode(unserialize($_deviceTaskStatus), true);
            //     }
            //     if (is_array($deviceTaskStatus) && $deviceTaskStatus['taskStatus'] == 'running') {
            //         $datetime = date('Y-m-d H:i:s', strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration']);
            //         $msg = "设备正在执行小红书任务，请在【{$datetime}】秒后重试";
            //         $time = strtotime($deviceTaskStatus['time']) + (int)$deviceTaskStatus['duration'];
            //         if (time() < $time) {
            //             throw new \Exception($msg);
            //         }
            //     }
            // }

            // self::sendAppExec($find->device_code, DeviceEnum::ACCOUNT_TYPE_SPH, $output);
            // usleep(200 * 1000); //200毫秒
            $task = [
                'id' => $find['id'],
                'task_id' => $task->id,
                'platform' => DeviceEnum::getAccountTypeDesc((int)$find['type']),
                'device_code' => $find['device_code'],
                'keywords' => json_decode($find['keywords'], true),
                'exec_number' => 10000,
                'is_chat' => $find['chat_type'],
                'chat_number' => $find['chat_number'],
                'chat_interval_time' => $find['chat_interval_time'],
                'add_type' => $find['add_type'],
                'remarks' => json_decode($find['remarks'], true),
                'add_remark_enable' => $find['add_remark_enable'] ?? 0,
                'add_number' => $find['add_number'],
                'add_interval_time' => $find['add_interval_time'],
                'greeting_content' => $find['greeting_content'],
                //'greeting_content' => self::createGreetingContents($row, $row['user_id']),
                'status' => 0,
                'ocr_type' => $find['ocr_type'],
                'crawl_type' => $find['crawl_type'],
                'create_time' => $find['create_time'],
                'start_time' => $dtask->start_time,
                'end_time' => $dtask->end_time,
                'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
            ];

            $data = array(
                'type' => 20,
                'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                'content' => json_encode($task, JSON_UNESCAPED_UNICODE),
                'deviceId' => $find['device_code'],
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'clues');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$find['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            SvCrawlingTask::where('id', $dtask->sub_task_id)->update(['status' => 1, 'update_time' => time()]);
            self::setWsSelect();
            self::redis()->set("xhs:device:{$find['device_code']}:taskStatus", json_encode([
                'taskStatus' => 'standby',
                'taskType' => 'setSph',
                'msg' => '执行视频号',
                'duration' => 0,
                'time' => date('Y-m-d H:i:s', time()),
                'scene' => 'sph'
            ], JSON_UNESCAPED_UNICODE));

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'clues');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function sphCluesEndTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "视频号线索任务[{$task->device_code}]";

            self::checkOnline($task->device_code, 'ws');

            // $find = SvCrawlingTask::alias('ct')
            //     ->field('ct.*, b.device_code, b.keywords')
            //     ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id')
            //     ->where('ct.id', $task->sub_task_id)
            //     ->where('b.device_code', $task->device_code)
            //     ->where('ct.status', 'in', [1, 3])
            //     ->where('b.status', 1)
            //     ->fetchSql(false)
            //     ->findOrEmpty();
            // if ($find->isEmpty()) {
            //     $output->writeln(Db::getLastSql());
            //     throw new \Exception('暂时没有需要执行的设备');
            // }

            $data = array(
                'type' => 25,
                'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                'content' => json_encode(array(
                    'task_id' => $task->sub_task_id,
                    'deviceId' => $task->device_code,
                    'msg' => '执行时间结束，任务结束'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $task->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$task->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            SvCrawlingTaskDeviceBind::where('task_id', $task->sub_task_id)->where('device_code', $task->device_code)->update(['status' => 3, 'update_time' => time()]);
            self::setLog($data, 'clues');
            self::setWsSelect();
            self::redis()->set("xhs:device:{$task->device_code}:taskStatus", json_encode([
                'taskStatus' => 'standby',
                'taskType' => 'setSph',
                'msg' => '执行视频号',
                'duration' => 0,
                'time' => date('Y-m-d H:i:s', time()),
                'scene' => 'sph'
            ], JSON_UNESCAPED_UNICODE));

            if (is_callable($callback)) {
                return $callback([
                    'status' => 2,
                    'remark' => '任务执行结束',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'clues');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    public static function sphPublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "视频号发布任务[{$task->device_code}]";
            $wechat = AiWechat::where('wechat_id', $task->account)->where('user_id', $task->user_id)->findOrEmpty();
            if ($wechat->isEmpty()) {
                return $callback([
                    'status' => 1,
                    'remark' => '微信账号不存在',
                ]);
            }

            self::checkOnline($wechat->device_code, 'wx');

            $publish = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting ss', 'ps.publish_id = ss.id')
                ->join('sv_publish_setting_account pa', 'ps.publish_account_id = pa.id')
                ->where('pa.id', $task->sub_task_id)
                ->where('ps.device_code', $task->device_code)
                ->where('ps.status', 'in', [0, 5])
                ->where('ss.status', 'in', [1, 2])
                ->where('ps.account_type', 1)
                ->where('ps.publish_time', '<=', date('Y-m-d H:i:s', time()))
                ->order('ps.publish_time asc')
                ->limit(1)
                ->findOrEmpty();
            if ($publish->isEmpty()) {
                // self::setLog('暂时没有可发布的内容', 'publish');
                // self::setLog(Db::getLastSql(), 'publish');
                return $callback([
                    'status' => -1,
                    'remark' => '暂时没有需要执行的发布任务',
                ]);
            }



            $output->writeln(Db::getLastSql());

            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => 60,
                'deviceId' => $task->device_code,
                'appVersion' => '2.4.0',
                'content' => json_encode([
                    'deviceId' => $task->device_code,
                    'taskId' => $publish['id'],
                    'msg' => '执行视频号发布,拉起app',

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
            $channel = "device.{$task->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);
            sleep(2);

            $interval_find = \app\common\model\wechat\AiWechatLog::where('user_id', $publish['user_id'])
                ->where('log_type', \app\common\model\wechat\AiWechatLog::TYPE_SPH_POST)
                ->where('wechat_id', $publish['account'])
                ->order('id', 'desc')
                ->limit(1)
                //->fetchSql(true)
                ->findOrEmpty();
            if (!$interval_find->isEmpty() && ((time() - strtotime($interval_find->create_time)) < 150)) {
                $output->writeln('间隔时间未到');
                self::setLog('间隔时间未到', 'publish');
                return $callback([
                    'status' => 1,
                    'remark' => '间隔时间未到',
                ]);
            }
            $payload = [
                'WeChatId' => $publish['account'],
                'Content' => $publish['material_subtitle'],
                'MediaType' => 1,
                'Medias' => explode(',', $publish['material_url']),
                'Cover' => FileService::getFileUrl($publish['pic']),
                'TaskId' => $publish['sub_task_id'],
                'Poi' => [],
            ];



            self::setLog($payload, 'publish');
            $output->writeln(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            // 3. 构建消息发送请求
            $content = \app\common\workerman\wechat\handlers\client\SphPostTaskHandler::handle($payload);
            // 4. 构建protobuf消息
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $data = $message->serializeToString();
            // 5. 发送到设备端
            $channel = "socket.{$wechat['device_code']}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => $data
            ]);
            // 6. 更新发布记录
            SvPublishSettingDetail::where('id', $publish['id'])->update([
                'status' => 3,
                'exec_time' => time(),
                'update_time' => time(),
            ]);
            \app\common\model\wechat\AiWechatLog::create([
                'user_id' => $publish['user_id'],
                'wechat_id' => $publish['account'],
                'log_type' => \app\common\model\wechat\AiWechatLog::TYPE_SPH_POST,
                'friend_id' => $payload['WeChatId'],
                'create_time' => time()
            ]);


            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                    'publish_id' => $publish['id'],
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'publish');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function rpaPublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            $accountTypeName = DeviceEnum::getAccountTypeDesc($task->account_type);
            self::$logtitle = "RPA [{$accountTypeName}]发布任务[{$task->device_code}]";

            self::checkOnline($task->device_code, 'ws');

            // $account = self::redis()->get("xhs:{$task->device_code}:accountNo");
            // if (empty($account)) {
            //     $msg = "设备:{$task->device_code} 没有绑定[{$accountTypeName}]账号";
            //     throw new \Exception($msg);
            // }

            $publish = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
                ->where('ps.device_code', '=', $task->device_code)
                ->where('ps.account', $task->account)
                ->where('ps.status', 'in', [0, 5])
                ->where('s.status', 'in', [1, 2])
                ->where('s.account_type', $task->account_type)
                ->where('ps.data_type', 0)
                ->where('ps.publish_time', '<=', date('Y-m-d H:i:s', time()))
                ->order('ps.publish_time asc')
                ->limit(1)
                ->findOrEmpty();

            if (!$publish->isEmpty()) {
                // self::sendAppExec($task->device_code, $task->account_type, $output);
                // sleep(30);

                self::setWsSelect();
                self::redis()->set("xhs:device:" . $task->device_code . ":taskStatus", json_encode([
                    'taskStatus' => 'running',
                    'taskType' => 'setCrontab',
                    'scene' => 'xhs',
                    'msg' => '小红书正在发布笔记内容',
                    'duration' => 90,
                    'time' => date('Y-m-d H:i:s', time()),
                ], JSON_UNESCAPED_UNICODE));
            } else {
                // self::setLog('暂时没有可发布的内容', 'publish');
                // self::setLog(Db::getLastSql(), 'publish');
                if (is_callable($callback)) {
                    return $callback([
                        'status' => -1,
                        'remark' => '暂时没有可发布的内容',
                    ]);
                }
            }

            $material_url = explode(',', $publish['material_url']);
            if (count($material_url) > 12) {
                $material_url = array_slice($material_url, 0, 12);
            }
            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => 5,
                'deviceId' => $task->device_code,
                'appVersion' => '2.4.0',
                'content' => json_encode([
                    'material_id' => $publish['id'],
                    'title' => $publish['material_title'],
                    'type' => $publish['material_type'] ?? 1,
                    'list' => $material_url,
                    'isLocation' => !empty($publish['poi']) ? 1 : 0,
                    'location' => $publish['poi'],
                    'isScheduledTime' => true,
                    'scheduledTime' => $publish['publish_time'],
                    'taskId' => $publish['task_id'],
                    'body' => $publish['material_subtitle'],
                    'tag' => $publish['material_tag'] ?? ''

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
            $channel = "device.{$publish['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);
            self::setRpaPublishStatus($publish);


            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                    'publish_id' => $publish['id'],
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'publish');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function cluesAddWechatFriendTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "自动加好友任务[{$dtask->device_code}]";
            //self::checkOnline($dtask->device_code, 'wx');

            $records = SvCrawlingManualTaskRecord::alias('a')
                ->field('a.*')
                ->field('t.add_type, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_manual_task t', 'a.task_id = t.id')
                ->where('t.status', 'in', [0, 1])
                ->where('a.status', 4)
                ->where('t.id', $dtask->sub_task_id)
                ->order('a.update_time', 'asc')
                ->limit(10)
                ->select()
                ->toArray();

            //print_r(Db::getLastSql()); die;

            foreach ($records as $record) {
                $task = SvCrawlingManualTask::where('id', $record['task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setLog("线索任务不存在:\n" . Db::getLastSql(), 'add_wechat');
                    $output->writeln("线索任务不存在:\n" . Db::getLastSql());
                    continue;
                }
                if ($task->completed_add_count >= $task->exec_add_count) {
                    $task->status = 3;
                    $task->update_time = time();
                    $task->save();
                    continue;
                } else {
                    if (is_null($task->start_time)) {
                        $task->start_time = time();
                    }
                    $task->status = 1;
                    $task->update_time = time();
                    $task->save();
                }

                $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                if (preg_match($wxPattern, $record['clue_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['clue_wechat'],
                    ]);
                    if (isset($response['code']) && (int)$response['code'] === 10000) {
                        if (is_null($response['data'])) {
                            self::setLog($record['clue_wechat'] . '该账号还未开始验证', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['clue_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['status']) && (int)$response['data']['status'] === 0) {
                            self::setLog($record['clue_wechat'] . '该账号还未完成验证,稍后再试', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['clue_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['valid']) && (bool)$response['data']['valid'] === false) {
                            self::setLog($record['clue_wechat'] . '该账号不是有效的微信号,忽略', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                                'status' => 0,
                                'result' => '该线索经过校验为无效线索',
                                'update_time' => time(),
                            ]);
                            continue;
                        }
                    }
                }


                // 处理加微逻辑
                $wechat_ids = explode(',', $record['wechat_id']);
                $useWechat = [];
                foreach ($wechat_ids as $wechat_id) {
                    //计算微信加微间隔
                    $interval_find = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', 0)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', '>', (time() - ((int)$record['add_interval_time'] * 60)))
                        ->order('id', 'desc')
                        ->findOrEmpty();
                    if (!$interval_find->isEmpty()) {
                        self::setLog('当前微信' . $wechat_id . '加微间隔未到', 'add_wechat');
                        continue;
                    }

                    $addCount = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', 0)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                        ->count();
                    if ($addCount >= $record['add_number']) {
                        self::setLog('当前微信' . $wechat_id . '今日加微信次数已到', 'add_wechat');
                        continue;
                    }
                    array_push($useWechat, $wechat_id);
                }

                if (empty($useWechat)) {
                    self::setLog('当前无可以使用的微信账号', 'add_wechat');
                    SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                        'status' => 4,
                        'result' => '冷却中，等待后可继续添加',
                        'update_time' => time(),
                    ]);
                    continue;
                }

                $currentTime = time(); // 获取当前时间戳
                $coolingThreshold = $currentTime - 1800; // 2小时前的时间戳（7200秒）
                $wechat = AiWechat::field('*')
                    ->where('wechat_id', 'in', $useWechat)
                    ->where(function ($query) use ($coolingThreshold) {
                        $query->where('is_cooling', 0)->whereOr('cooling_time', '<', $coolingThreshold);
                    })
                    ->where('wechat_status', 1)
                    ->order('update_time asc')->limit(1)->findOrEmpty();
                if (!$wechat->isEmpty()) {
                    self::setLog(Db::getLastSql(), 'add_wechat');
                    self::setLog($wechat, 'add_wechat');
                    self::_sendChannelAddWechatMessage([
                        'WechatId' => $wechat['wechat_id'],
                        'DeviceCode' => $wechat['device_code'],
                        'Phones' => $record['clue_wechat'],
                        'message' => self::_createGreetingMessage($record), //ai生成打招呼消息
                    ], $wechat, $record);
                } else {
                    SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '当前账号存在安全风险，暂停添加',
                        'update_time' => time(),
                    ]);
                    self::setLog('冷却中，等待后可继续添加', 'add_wechat');
                    continue;
                }
            }

            $data = array(
                'type' => 50, // 接管任务启动
                'appType' => 0,
                'content' => json_encode(array(
                    'task_id' => $dtask->id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'msg' => '加微任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'add_wechat');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'add_wechat');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    // 接管任务
    public static function rpaTakeoverTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "接管任务{$dtask->device_code}";

            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceTakeOverTaskAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('接管账号任务不存在：' . Db::getLastSql(), 'take_over');
                throw new \Exception('接管账号任务不存在');
            }

            // self::sendAppExec($account->device_code, $account->account_type, $output);
            // usleep(200 * 1000); //200毫秒

            $data = array(
                'type' => 30, // 接管任务启动
                'appType' => DeviceEnum::TASK_TYPE_TAKEOVER,
                'content' => json_encode(array(
                    'task_id' => $dtask->id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'msg' => '接管任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'take_over');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);

            SvDeviceTakeOverTask::where('id', $account->take_over_id)->update([
                'status' => DeviceEnum::TASK_STATUS_RUNNING,
                'update_time' => time(),
            ]);

            $account->status = DeviceEnum::TASK_STATUS_RUNNING;
            $account->update_time = time();
            $account->save();



            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'take_over');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    // 接管任务完成
    public static function rpaTakeoverEndTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "接管任务结束{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceTakeOverTaskAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                throw new \Exception('接管账号任务不存在');
            }

            $data = array(
                'type' => 31, // 接管任务执行结束
                'appType' => DeviceEnum::TASK_TYPE_TAKEOVER,
                'content' => json_encode(array(
                    'task_id' => $dtask->id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '接管任务执行结束'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'take_over');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);

            $account->status = DeviceEnum::TASK_STATUS_FINISHED;
            $account->update_time = time();
            $account->save();

            if (is_callable($callback)) {
                return $callback([
                    'status' => 2,
                    'remark' => '接管任务执行结束',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'take_over');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function rpaMaintainAccountTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "养号任务{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceActiveAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('养号任务不存在：' . Db::getLastSql(), 'active');
                throw new \Exception('养号任务不存在');
            }
            // self::sendAppExec($account->device_code, $account->account_type, $output);
            // usleep(200 * 1000); //200毫秒

            $data = array(
                'type' => 40, // 养号任务启动
                'appType' => DeviceEnum::TASK_TYPE_ACTIVE,
                'content' => json_encode(array(
                    'task_id' => $dtask->sub_task_id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'msg' => '养号任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'active');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);

            SvDeviceActive::where('id', $account->active_id)->update([
                'status' => DeviceEnum::TASK_STATUS_RUNNING,
                'update_time' => time(),
            ]);

            $account->status = DeviceEnum::TASK_STATUS_RUNNING;
            $account->update_time = time();
            $account->save();



            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'active');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    // 养号任务完成
    public static function rpaMaintainAccountEndTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "养号任务结束{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceActiveAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                throw new \Exception('养号任务不存在');
            }

            $data = array(
                'type' => 41, // 养号任务执行结束
                'appType' => DeviceEnum::TASK_TYPE_ACTIVE,
                'content' => json_encode(array(
                    'task_id' => $dtask->sub_task_id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '养号任务执行结束'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'active');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);

            $account->status = DeviceEnum::TASK_STATUS_FINISHED;
            $account->update_time = time();
            $account->save();



            if (is_callable($callback)) {
                return $callback([
                    'status' => 2,
                    'remark' => '养号任务执行结束',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'active');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . $th->getMessage(),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private static function _sendChannelAddWechatMessage(array $payload, AiWechat $wechat, array $record)
    {
        try {
            //进程通信
            $request = [
                'DeviceId' => $payload['DeviceCode'],
                'WeChatId' => $payload['WechatId'],
                'Phones' => [$payload['Phones']],
                'Message' => $payload['message'],
                'TaskId' => time() . (1000 + (int)$record['id']),
                'Remark' => $payload['Remark'] ?? '',
            ];
            self::setLog($request, 'add_wechat');
            $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($request);
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            $channel = "socket.{$payload['DeviceCode']}.message";
            self::setLog('channel: ' . $channel, 'add_wechat');

            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            ]);
            //$wechat->add_num += 1;
            $wechat->is_cooling = 0;
            $wechat->cooling_time = 0;
            $wechat->update_time = time();
            $wechat->save();

            AiWechatLog::create([
                'user_id' => $wechat->user_id,
                'wechat_id' => $wechat->wechat_id,
                'log_type' => 0,
                'friend_id' => $payload['Phones'],
                'create_time' => time()
            ]);
            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->wechat_id,
                'wechat_name' => $wechat->wechat_nickname,
                'remark' => $request['Message'],
                'exec_task_id' => $request['TaskId'],
                'exec_time' => date('Y-m-d H:i:s', time()),
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);

            $completed_add_count = SvCrawlingManualTask::where('id', $record['task_id'])->value('completed_add_count');
            SvCrawlingManualTask::where('id', $record['task_id'])->update([
                'completed_add_count' => $completed_add_count + 1,
                'status' => 1,
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e->getTraceAsString(), 'add_wechat');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    private static function _createGreetingMessage(array $task)
    {
        if (isset($task['add_remark_enable']) && (int)$task['add_remark_enable'] === 1) {
            $remarks = json_decode($task['remarks'], true) ?? [];
            $remark = $remarks[array_rand($remarks)] ?? '您好！';
            return $remark;
        }
        return '';
    }


    private static function setRpaPublishStatus($publish)
    {
        try {

            $detail = SvPublishSettingDetail::where('id', $publish['id'])->findOrEmpty();
            if (!$detail->isEmpty()) {
                $detail->save([
                    'status' => 3,
                    'update_time' => time(),
                    'exec_time' => time()
                ]);
                self::setLog('发布数据状态更新成功:' . $publish['id'], 'publish');
            } else {
                $publish['message'] = '待发布数据丢失:';
                self::setLog($publish, 'publish');
            }


            $account = SvPublishSettingAccount::where('id', $publish['publish_account_id'])->findOrEmpty();
            if (!$account->isEmpty()) {
                $count = SvPublishSettingDetail::where('publish_account_id', $detail['publish_account_id'])->where('status', 0)->count();
                $account->save([
                    'status' => $count > 0 ? 1 : 2,
                    'update_time' => time(),
                    'published_count' => Db::raw('published_count+1'),
                ]);

                SvPublishSetting::where('id', $detail['publish_id'])->update([
                    'update_time' => time(),
                    'status' => 2,
                ]);
                self::setLog('发布账号数据更新成功:' . $publish['publish_account_id'], 'publish');
            } else {

                $account['message'] = '待发布账号数据丢失:';
                self::setLog($account, 'publish');
            }
        } catch (\Exception $e) {
            self::setLog('_setPublishStatus' . $e, 'error');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    private static function checkOnline($deviceCode, $type = 'wx')
    {
        try {
            if ($type == 'wx') {
                self::setWxSelect();

                $isOnline = self::redis()->get("device:{$deviceCode}:status");
                if (empty($isOnline)) {
                    throw new \Exception("设备:{$deviceCode} 不在线");
                }
            } else {
                self::setWsSelect();
                $isOnline = self::redis()->get("xhs:device:{$deviceCode}:status");
                if (empty($isOnline)) {
                    throw new \Exception("设备:{$deviceCode} 不在线");
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private static function sendAppExec(string $deviceCode, int $appType, Output $output)
    {
        try {
            $app = SvDeviceRpa::where('device_code', $deviceCode)->where('app_type', $appType)->findOrEmpty();
            if ($app->isEmpty()) {
                $output->writeln(Db::getLastSql());
                throw new \Exception('当前设备未绑定app');
            }
            $payload = [
                "messageId" => 2,
                "type" => 90, //执行那个app指令
                "appType" => $appType,
                "content" => json_encode([
                    "deviceId" => $deviceCode,
                    "appType" => $appType,
                    'msg' => DeviceEnum::getAccountTypeDesc($appType),
                    'task_id' => $app->id
                ], JSON_UNESCAPED_UNICODE),
                "deviceId" => $deviceCode,
                "appVersion" => "2.1.2"
            ];
            $output->writeln(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'clues');
            $channel = "device.{$deviceCode}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            SvDeviceRpa::where('device_code', $deviceCode)->where('app_type', '<>', $appType)->update(['status' => 0, 'update_time' => time()]);
            SvDeviceRpa::where('device_code', $deviceCode)->where('app_type', $appType)->update([
                'status' => 1,
                'update_time' => time(),
                'start_time' => date('Y-m-d H:i:s', time()),
            ]);
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private static function redis(): Redis
    {
        self::$redisInstance = new Redis([
            'host'        => env('redis.HOST', '127.0.0.1'),
            'port'        => env('redis.PORT', 6379),
            'password'    => env('redis.PASSWORD', '123456'),
            'select'      => self::$redisSelect,
            //'select'      => env('redis.WS_SELECT', 9),
            'timeout'     => 0,
            'persistent'  => true,
        ]);
        return self::$redisInstance;
    }

    private static function setWxSelect()
    {
        self::$redisSelect = env('redis.WX_SELECT', 9);
    }

    private static function setWsSelect()
    {
        self::$redisSelect = env('redis.WS_SELECT', 9);
    }


    private static function setLog($content, $level = 'info')
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('device')->{$level}(self::$logtitle . "\n" . $content);
    }
}
