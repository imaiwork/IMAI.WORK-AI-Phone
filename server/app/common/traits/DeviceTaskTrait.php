<?php

declare(strict_types=1);

namespace app\common\traits;

use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvCrawlingWechatTask;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\sv\SvWechatStrategy;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\service\FileService;
use Channel\Client as ChannelClient;
use think\cache\driver\Redis;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

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
                'time_interval' => ceil(($dtask->end_time - $dtask->start_time) / 60),
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
            self::checkOnline($task->device_code, 'ws');

            $publish = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting_account pa', 'ps.publish_account_id = pa.id')
                ->where('pa.id', $task->sub_task_id)
                ->where('ps.device_code', $task->device_code)
                ->where('ps.status', 'in', [0, 5])
                ->where('ps.account_type', 1)
                ->where('ps.publish_time', '<=', date('Y-m-d H:i:s', time()))
                ->order('ps.publish_time asc')
                ->limit(1)
                ->findOrEmpty();
            if ($publish->isEmpty()) {
                return $callback([
                    'status' => -1,
                    'remark' => '暂时没有需要执行的发布任务',
                ]);
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
                    'publish_platform' => DeviceEnum::PUBLISH_PLATFORM_SPH,
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

    public static function rpaPublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            $accountTypeName = DeviceEnum::getAccountTypeDesc($task->account_type);
            self::$logtitle = "RPA [{$accountTypeName}]发布任务[{$task->device_code}]";

            self::checkOnline($task->device_code, 'ws');

            $publish = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
                ->where('s.id', $task->sub_task_id)
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
                    'publish_platform' => $task->account_type,
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
                    'tag' => $publish['material_tag'] ?? '',


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


    public static function wechatCirclePublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "微圈发布任务[{$task->device_code}]";

            self::checkOnline($task->device_code, 'ws');

            $publish = AiWechatCircleTask::where('id', $task->sub_data_id)->where('send_status', 0)->where('auto_type', $task->auto_type)->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setLog("微圈发布任务不存在:\n" . Db::getLastSql(), 'publish');
                return $callback([
                    'status' => -1,
                    'remark' => '微圈发布任务不存在',
                ]);
            }

            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => 5,
                'deviceId' => $task->device_code,
                'appVersion' => '2.4.0',
                'content' => json_encode([
                    'publish_platform' => DeviceEnum::PUBLISH_PLATFORM_WX,
                    'material_id' => $publish['id'],
                    'title' => $publish['content'] ?? '',
                    'type' => $publish['attachment_type'] === 1 ? 2 : 1,
                    'list' => $publish['attachment_content'],
                    'isLocation' => !empty($publish['poi']) ? 1 : 0,
                    'location' => $publish['poi'] ?? '',
                    'isScheduledTime' => true,
                    'scheduledTime' => $publish['send_time'],
                    'taskId' => $publish['task_id'],
                    'body' => $publish['content'],
                    'tag' => $publish['tag'] ?? '',
                    'comment' => $publish['comment'] ?? '',

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
            $channel = "device.{$publish['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            $publish->send_status = 1;
            $publish->update_time = time();
            $publish->save();


            AiWechatCircleTaskConfig::where('id', $publish['task_config_id'])->update([
                'status' => 2,
                'update_time' => time(),
            ]);
            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
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

    public static function wechatCirclePublishCompletedTask(SvDeviceTask $task, Output $output)
    {
        try {
            $publish = AiWechatCircleTaskConfig::where('id', $task->sub_task_id)->where('status', 2)->where('auto_type', $task->auto_type)->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setLog("微圈发布任务不存在:\n" . Db::getLastSql(), 'publish');
            }
            $publish->status = 3;
            $publish->update_time = time();
            $publish->save();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public static function wechatCircleThumbCommentTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "微圈点赞评论任务[{$task->device_code}]";

            self::checkOnline($task->device_code, 'ws');

            $comment = SvDeviceCircleLikeReplyAccount::where('id', $task->sub_data_id)->where('status', 0)->where('auto_type', $task->auto_type)->findOrEmpty();
            if ($comment->isEmpty()) {
                self::setLog("微圈点赞评论任务不存在:\n" . Db::getLastSql(), 'thumb_comment');
                return $callback([
                    'status' => -1,
                    'remark' => '微圈点赞评论任务不存在',
                ]);
            }
            $option = SvDeviceCircleLikeReply::where('id', $comment['circle_like_reply_id'])->findOrEmpty();
            if ($option->isEmpty()) {
                self::setLog("微圈点赞评论选项不存在:\n" . Db::getLastSql(), 'thumb_comment');
                return $callback([
                    'status' => -1,
                    'remark' => '微圈点赞评论选项不存在',
                ]);
            }


            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::WECHAT_CIRCLE_LIKE_COMMENT,
                'deviceId' => $task->device_code,
                'appVersion' => '2.4.0',
                'content' => json_encode([
                    'taskId' => $comment->id,
                    "hasLiked" => ($option->action === 1 || $option->action === 3) ? 1 : 0, //点赞
                    "hasComment" => ($option->action === 2 || $option->action === 3) ? 1 : 0, //评论
                    "planCoverage" => $option->range, //当天   1、3天内   2、7天内
                    "interactionConut" => $option->number,  //互动数量
                    "timeInterval" => $option->interval,  //互动间隔/分钟
                    "commentType" => $option->comment_type,  //AI识别并评论   1、不评论   2、固定评论
                    "commentContent" =>  $option->comment ?? '', //固定评论内容
                    'account' => $task->account,
                    'account_type' => $task->account_type,
                    'start_time' => $task->start_time,
                    'end_time' => $task->end_time,
                    'time_interval' => ($task->end_time - $task->start_time) / 60,

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'thumb_comment');
            $channel = "device.{$comment['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            $comment->status = 1;
            $comment->update_time = time();
            $comment->save();

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'thumb_comment');
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

    public static function wechatCircleThumbCommentCompletedTask(SvDeviceTask $task, Output $output)
    {
        try {
            $comment = SvDeviceCircleLikeReplyAccount::where('id', $task->sub_data_id)->where('status', 1)->where('auto_type', $task->auto_type)->findOrEmpty();
            if ($comment->isEmpty()) {
                self::setLog("微圈点赞评论任务不存在:\n" . Db::getLastSql(), 'thumb_comment');
            }
            $comment->status = 2;
            $comment->update_time = time();
            $comment->save();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }



    public static function wechatRPATask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            $strategy = SvWechatStrategy::where('id', $task->sub_task_id)->findOrEmpty();
            if ($strategy->isEmpty()) {
                throw new \Exception("微账号RPA策略不存在:\n" . Db::getLastSql());
            }

            // $payload = array(
            //     'appType' => $task->account_type,
            //     'messageId' => 0,
            //     'type' => DeviceEnum::WECHAT_RPA_TASK,
            //     'deviceId' => $task->device_code,
            //     'appVersion' => '2.4.0',
            //     'content' => json_encode([
            //         'task_id' => $strategy->id,
            //         'is_manual_agree' => $strategy->is_manual_agree,
            //         'greet_strategy' => $strategy->greet_strategy,
            //         'greet_content' => $strategy->greet_content,
            //         'paragraph_enable' => $strategy->paragraph_enable,
            //         'multiple_type' => $strategy->multiple_type,
            //         'number_chat_rounds' => $strategy->number_chat_rounds,
            //         'voice_enable' => $strategy->voice_enable,
            //         'voice_reply_type' => $strategy->voice_reply_type,
            //         'voice_reply' => $strategy->voice_reply,
            //         'image_enable' => $strategy->image_enable,
            //         'image_reply_type' => $strategy->image_reply_type,
            //         'image_reply' => $strategy->image_reply,
            //         'stop_enable' => $strategy->stop_enable,
            //         'stop_keywords' => $strategy->stop_keywords,
            //         'device_code' => $task->device_code,
            //         'account' => $task->account,
            //         'account_type' => $task->account_type,
            //         'task_frep' => $strategy->task_frep,
            //         'is_free_time' => $strategy->is_free_time,
            //         'start_time' => $task->start_time,
            //         'end_time' => $task->end_time,
            //         'time_interval' => ($task->end_time - $task->start_time) / 60,
            //         'msg' => $strategy->task_name,

            //     ], JSON_UNESCAPED_UNICODE)
            // );

            $payload = array(
                'type' => DeviceEnum::getTakeOverType($task->account_type), // 接管任务启动
                'appType' => $task->account_type,
                'content' => json_encode(array(
                    'task_id' => $task->id,
                    'deviceId' => $task->device_code,
                    'account' => $task->account,
                    'account_type' => $task->account_type,
                    'start_time' => $task->start_time,
                    'end_time' => $task->end_time,
                    'time_interval' => ceil(($task->end_time - $task->start_time) / 60),
                    'is_manual_agree' => $strategy->is_manual_agree,
                    'greet_strategy' => $strategy->greet_strategy,
                    'greet_content' => $strategy->greet_content,
                    'msg' => '个微接管任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $task->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($payload, 'take_over');

            self::setLog(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
            $channel = "device.{$task->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);
            $strategy->status = 1;
            $strategy->update_time = time();
            $strategy->save();

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'wechat');
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



    public static function cluesAddWechatFriendTask(SvDeviceTask $dtask, Output $output, bool $isFirst, callable $callback)
    {
        try {
            self::$logtitle = "自动批量加好友任务[{$dtask->device_code}]";
            self::checkOnline($dtask->device_code, 'ws');

            $records = SvCrawlingManualTaskRecord::alias('a')
                ->field('a.*')
                ->field('t.add_type, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_manual_task t', 'a.task_id = t.id')
                ->where('t.status', 'in', [0, 1])
                ->where('a.status', 4)
                ->where('t.id', $dtask->sub_task_id)
                ->order('a.id', 'desc')
                //->limit(10)
                ->select()
                ->toArray();

            //print_r(Db::getLastSql()); die;
            $sendWechatIds = [];
            $add_interval_time = 10;
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

                $add_interval_time = (int)$record['add_interval_time'] > 0 ? (int)$record['add_interval_time'] : $add_interval_time;

                $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                if (preg_match($wxPattern, $record['clue_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['clue_wechat'],
                    ]);
                    if (isset($response['code']) && (int)$response['code'] === 10005) {
                        self::setLog($response, 'add_wechat');
                        continue;
                    }
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
                // $wechat_ids = explode(',', $record['wechat_id']);
                // $useWechat = [];
                // foreach ($wechat_ids as $wechat_id) {
                //     //计算微信加微间隔
                //     $interval_find = AiWechatLog::where('user_id', $record['user_id'])
                //         ->where('log_type', 0)
                //         ->where('wechat_id', $wechat_id)
                //         ->where('create_time', '>', (time() - ((int)$record['add_interval_time'] * 60)))
                //         ->order('id', 'desc')
                //         ->findOrEmpty();
                //     if (!$interval_find->isEmpty()) {
                //         self::setLog('当前微信' . $wechat_id . '加微间隔未到', 'add_wechat');
                //         continue;
                //     }

                //     $addCount = AiWechatLog::where('user_id', $record['user_id'])
                //         ->where('log_type', 0)
                //         ->where('wechat_id', $wechat_id)
                //         ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                //         ->count();
                //     if ($addCount >= $record['add_number']) {
                //         self::setLog('当前微信' . $wechat_id . '今日加微信次数已到', 'add_wechat');
                //         continue;
                //     }
                //     array_push($useWechat, $wechat_id);
                // }

                // if (empty($useWechat)) {
                //     self::setLog('当前无可以使用的微信账号', 'add_wechat');
                //     SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                //         'status' => 4,
                //         'result' => '冷却中，等待后可继续添加',
                //         'update_time' => time(),
                //     ]);
                //     continue;
                // }

                // $currentTime = time(); // 获取当前时间戳
                // $coolingThreshold = $currentTime - 1800; // 2小时前的时间戳（7200秒）
                // $wechat = AiWechat::field('*')
                //     ->where('wechat_id', 'in', $useWechat)
                //     ->where(function ($query) use ($coolingThreshold) {
                //         $query->where('is_cooling', 0)->whereOr('cooling_time', '<', $coolingThreshold);
                //     })
                //     ->where('wechat_status', 1)
                //     ->order('update_time asc')->limit(1)->findOrEmpty();
                // if (!$wechat->isEmpty()) {
                //     self::setLog(Db::getLastSql(), 'add_wechat');
                //     self::setLog($wechat, 'add_wechat');
                //     $addRemark = self::_createGreetingMessage($record);
                //     $request = self::_sendChannelAddWechatMessage([
                //         'WechatId' => $wechat['wechat_id'],
                //         'DeviceCode' => $wechat['device_code'],
                //         'Phones' => $record['clue_wechat'],
                //         'message' => $addRemark, //ai生成打招呼消息
                //     ], $wechat, $record);
                //     array_push($sendWechatIds, [
                //         'wechatId' => $wechat['wechat_id'],
                //         'deviceCode' => $wechat['device_code'],
                //         'friendWechatId' => $record['clue_wechat'],
                //         'message' => $addRemark, //ai生成打招呼消息
                //         'taskId' => $request['TaskId'],
                //         'recordId' => $record['id'],
                //         'isManual' => 1,
                //     ]);
                // } else {
                //     SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                //         'status' => 0,
                //         'result' => '当前账号存在安全风险，暂停添加',
                //         'update_time' => time(),
                //     ]);
                //     self::setLog('冷却中，等待后可继续添加', 'add_wechat');
                //     continue;
                // }
                

                $wechat = SvAccount::where('device_code', $dtask->device_code)->where('type', 1)->limit(1)->findOrEmpty();
                if ($wechat->isEmpty()) {
                    SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '设备' . $dtask->device_code . ' 没有获取微信信息',
                        'update_time' => time(),
                    ]);
                    continue;
                }

                $addRemark = self::_createGreetingMessage($record);
                $request = self::_sendChannelAddWechatMessage([
                    'WechatId' => $wechat['account'],
                    'DeviceCode' => $wechat['device_code'],
                    'Phones' => $record['clue_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                ], $wechat, $record);
                array_push($sendWechatIds, [
                    // 'wechatId' => $wechat['account'],
                    // 'deviceCode' => $wechat['device_code'],
                    'friendWechatId' => $record['clue_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                    //'taskId' => $request['TaskId'],
                    'recordId' => $record['id'],
                    'isManual' => 1,
                ]);
            }
            if (empty($sendWechatIds)) {
                $dtask->end_time = time() + 60;
                $dtask->remark = '无加微账号可加,任务结束';
                $dtask->save();
            }
            if (!empty($sendWechatIds) || $isFirst) {
                $data = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, // 自动加微任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => $dtask->id,
                        'deviceId' => $dtask->device_code,
                        'account' => $dtask->account,
                        'account_type' => $dtask->account_type,
                        'start_time' => $dtask->start_time,
                        'end_time' => $dtask->end_time,
                        'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
                        'send_wechat_ids' => $sendWechatIds,
                        'add_interval_time' => $add_interval_time,
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
            }

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

    public static function cluesWechatTask(SvDeviceTask $dtask, Output $output, bool $isFirst, callable $callback)
    {
        try {
            self::$logtitle = "自动加好友任务[{$dtask->device_code}]";
            self::checkOnline($dtask->device_code, 'ws');
            $row = SvCrawlingWechatTask::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($row->isEmpty()) {
                throw new \Exception("加微任务不存在:\n" . Db::getLastSql());
            }

            $records = SvAddWechatRecord::alias('r')
                ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
                ->where('t.add_type', 1)
                ->where('r.device_code', $dtask->device_code)
                ->where('r.crawling_task_id', 'in', $row->craw_task_ids)
                ->where('t.auto_type', 0)
                ->where('r.channel', 1)
                ->where('r.status', 'in', [3, 4, 5])
                ->where('t.wechat_id', 'not in', ['', null]) // 过滤掉wechat_id为空的记录
                //->where('t.status', 'in', [1, 2]) // 过滤掉已完成、已暂停、已删除的任务
                ->whereRaw('t.exec_add_count > t.completed_add_count') // 过滤掉已执行加微次数大于等于注册类型的记录
                //->limit(10)
                ->order('r.id', 'desc')
                ->select()
                ->toArray();

            //print_r(Db::getLastSql());die;
            $sendWechatIds = [];
            $add_interval_time = 10;
            foreach ($records as $record) {
                $task = SvCrawlingTask::where('id', $record['crawling_task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setLog("线索任务不存在：" . Db::getLastSql(), 'add_wechat');
                    continue;
                }
                if ($task->completed_add_count >= $task->exec_add_count) {
                    SvAddWechatRecord::where('crawling_task_id', $record['crawling_task_id'])
                        ->where('status', 'in', [3, 4, 5])
                        ->update([
                            'status' => 0,
                            'result' => '已完成当前添加任务',
                            'update_time' => time(),
                        ]);
                    self::setLog('已完成当前添加任务', 'add_wechat');
                    continue;
                }

                $add_interval_time = (int)$record['add_interval_time'] > 0 ? (int)$record['add_interval_time'] : $add_interval_time;
                $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                if (preg_match($wxPattern, $record['reg_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['reg_wechat'],
                    ]);
                    if (isset($response['code']) && (int)$response['code'] === 10005) {
                        continue;
                    }

                    if (isset($response['code']) && (int)$response['code'] === 10000) {
                        if (is_null($response['data'])) {
                            self::setLog($record['reg_wechat'] . '该账号还未开始验证', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['reg_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['status']) && (int)$response['data']['status'] === 0) {
                            self::setLog($record['reg_wechat'] . '该账号还未完成验证,稍后再试', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['reg_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['valid']) && (bool)$response['data']['valid'] === false) {
                            self::setLog($record['reg_wechat'] . '该账号不是有效的微信号,忽略', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            SvAddWechatRecord::where('id', $record['id'])->update([
                                'status' => 0,
                                'result' => '该线索经过校验为无效线索',
                                'update_time' => time(),
                            ]);
                            $find = SvCrawlingRecord::where('user_id', $record['user_id'])
                                ->where('task_id', $record['crawling_task_id'])
                                ->where('reg_content', 'like', "%{$record['reg_wechat']}%")
                                ->limit(1)->findOrEmpty();
                            if (!$find->isEmpty()) {
                                $find->status = 2; //无效
                                $find->update_time = time();
                                $find->save();
                            }
                            continue;
                        } else {
                            $find = SvCrawlingRecord::where('user_id', $record['user_id'])
                                ->where('task_id', $record['crawling_task_id'])
                                ->where('reg_content', 'like', "%{$record['reg_wechat']}%")
                                ->limit(1)->findOrEmpty();
                            if (!$find->isEmpty()) {
                                $wx = explode(',', $find->reg_content);
                                $find->status = count($wx) > 1 ? 3 : 1; //3既有无效又有有效 1有效
                                $find->update_time = time();
                                $find->save();
                            }
                        }
                    }
                }


                // 处理加微逻辑
                // $wechat_ids = explode(',', $record['wechat_id']);
                // $useWechat = [];
                // foreach ($wechat_ids as $wechat_id) {
                //     //计算微信加微间隔
                //     $interval_find = AiWechatLog::where('user_id', $record['user_id'])
                //         ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                //         ->where('wechat_id', $wechat_id)
                //         ->where('create_time', '>', (time() - ((int)$record['add_interval_time'] * 60)))
                //         ->order('id', 'desc')
                //         ->findOrEmpty();
                //     if (!$interval_find->isEmpty()) {
                //         self::setLog('当前微信' . $wechat_id . '加微间隔未到', 'add_wechat');
                //         continue;
                //     }

                //     $addCount = AiWechatLog::where('user_id', $record['user_id'])
                //         ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                //         ->where('wechat_id', $wechat_id)
                //         ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                //         ->count();
                //     if ($addCount >= $record['add_number']) {
                //         self::setLog('当前微信' . $wechat_id . '今日加微信次数已到', 'add_wechat');
                //         continue;
                //     }
                //     array_push($useWechat, $wechat_id);
                // }

                // if (empty($useWechat)) {
                //     //self::setLog('当前无可以使用的微信账号', 'add_wechat');
                //     SvAddWechatRecord::where('id', $record['id'])->update([
                //         'status' => 5,
                //         'result' => '冷却中，等待后可继续添加',
                //         'update_time' => time(),
                //     ]);
                //     continue;
                // }

                // $currentTime = time(); // 获取当前时间戳
                // $coolingThreshold = $currentTime - 1800; // 2小时前的时间戳（7200秒）
                // $wechat = AiWechat::field('*')
                //     ->where('wechat_id', 'in', $useWechat)
                //     ->where(function ($query) use ($coolingThreshold) {
                //         $query->where('is_cooling', 0)->whereOr('cooling_time', '<', $coolingThreshold);
                //     })
                //     ->where('wechat_status', 1)
                //     ->order('update_time asc')->limit(1)->findOrEmpty();

                // if (!$wechat->isEmpty()) {
                //     self::setLog(Db::getLastSql(), 'add_wechat');
                //     self::setLog($wechat, 'add_wechat');
                //     $addRemark = self::_createGreetingMessage($record, $record['user_id']);
                //     self::sendCluesWechatMessage([
                //         'WechatId' => $wechat['wechat_id'],
                //         'DeviceCode' => $wechat['device_code'],
                //         'Phones' => $record['reg_wechat'],
                //         'message' => $addRemark, //ai生成打招呼消息
                //     ], $wechat, $record);
                //     array_push($sendWechatIds, [
                //         'wechatId' => $wechat['wechat_id'],
                //         'deviceCode' => $wechat['device_code'],
                //         'friendWechatId' => $record['reg_wechat'],
                //         'message' => $addRemark, //ai生成打招呼消息
                //         'taskId' => $record['task_id'],
                //         'recordId' => $record['id'],
                //         'isManual' => 0
                //     ]);
                // } else {
                //     SvAddWechatRecord::where('id', $record['id'])->update([
                //         'status' => 3,
                //         'result' => '当前账号存在安全风险，暂停添加',
                //         'update_time' => time(),
                //     ]);
                //     //self::setLog('冷却中，等待后可继续添加', 'add_wechat');
                //     continue;
                // }
                

                $wechat = SvAccount::where('device_code', $dtask->device_code)->where('type', 1)->limit(1)->findOrEmpty();
                if ($wechat->isEmpty()) {
                    SvAddWechatRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '设备' . $dtask->device_code . ' 没有获取微信信息',
                        'update_time' => time(),
                    ]);
                    continue;
                }
                $addRemark = self::_createGreetingMessage($record, $record['user_id']);
                self::sendCluesWechatMessage([
                    'WechatId' => $wechat['account'],
                    'DeviceCode' => $wechat['device_code'],
                    'Phones' => $record['reg_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                ], $wechat, $record);
                array_push($sendWechatIds, [
                    // 'wechatId' => $wechat['account'],
                    // 'deviceCode' => $wechat['device_code'],
                    'friendWechatId' => $record['reg_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                    //'taskId' => $record['task_id'],
                    'recordId' => $record['id'],
                    'isManual' => 0
                ]);
            }

            if (empty($sendWechatIds)) {
                $dtask->end_time = time() + 60;
                $dtask->remark = '无加微账号可加,任务结束';
                $dtask->save();
            }

            if (!empty($sendWechatIds) || $isFirst) {
                $data = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, // 自动加微任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => $dtask->id,
                        'deviceId' => $dtask->device_code,
                        'account' => $dtask->account,
                        'account_type' => $dtask->account_type,
                        'start_time' => $dtask->start_time,
                        'end_time' => $dtask->end_time,
                        'time_interval' => ($dtask->end_time - $dtask->start_time) / 60,
                        'send_wechat_ids' => $sendWechatIds,
                        'add_interval_time' => $add_interval_time,
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
            }

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'wechat');
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



            $data = array(
                'type' => DeviceEnum::getTakeOverType($dtask->account_type), // 接管任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode(array(
                    'task_id' => $dtask->id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'time_interval' => ceil(($dtask->end_time - $dtask->start_time) / 60),
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

            $data = array(
                'type' => DeviceEnum::getMaintainAccountType($dtask->account_type), // 养号任务启动
                'appType' => $dtask->account_type,
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


    // 评论区评论任务
    public static function touchCommentToCommentTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "评论区评论任务{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvLeadScrapingSettingAccount::where('id', $dtask->sub_task_id)->where('task_type', 1)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('评论区评论任务不存在：' . Db::getLastSql(), 'comment');
                throw new \Exception('评论区评论任务不存在');
            }

            $setting = SvLeadScrapingSetting::where('id', $account->scraping_id)->where('task_type', 1)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('评论区评论任务设置不存在：' . Db::getLastSql(), 'comment');
                throw new \Exception('评论区评论任务设置不存在');
            }

            $data = array(
                'type' => DeviceEnum::TASK_COMMENT_TO_COMMENT, // 评论区评论任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode(array(
                    'task_id' => $dtask->sub_task_id,
                    'auto_type' => $dtask->auto_type,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'startTime' => $dtask->start_time,
                    'endTime' => $dtask->end_time,
                    'timeInterval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'keyword' => self::getKeywords($setting->industry, $setting->city ?? ''),
                    'gender' => $setting->gender ?? '不限',
                    'hasLiked' => $setting->is_like,
                    'hasFollowed' => $setting->is_follow,
                    'commentContents' => !empty($setting->content) ? json_decode($setting->content, true) : [],
                    'filteredKeywords' => !empty($setting->filter) ? json_decode($setting->filter, true) : [], // ,
                    'commentCount' => $setting->send_num ?? 30,
                    'dmCount' => $setting->send_num ?? 30,
                    'noteViewCount' => $setting->industry_num ?? 5,
                    'industry_type' => $setting->industry_type ?? 0,
                    'city' => $setting->city ?? '',
                    'is_content_author' => $setting->is_content_author ?? 0,
                    'is_execed_clues' => $setting->is_execed_clues ?? 0,
                    'is_touch_like' => $setting->is_like ?? 0,
                    'is_touch_follow' => $setting->is_follow ?? 0,
                    'content_publish_day' => $setting->content_publish_day ?? 0,
                    'comment_publish_day' => $setting->comment_publish_day ?? 0,
                    'ip_address' => $setting->ip_address ?? [],
                    'is_note_like' => $setting->is_like ?? 0,
                    'msg' => '评论区评论任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'comment');
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
            self::setLog($th->getTraceAsString(), 'comment');
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




    // 评论区私信任务
    public static function touchCommentToMsgTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "评论区私信任务{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvLeadScrapingSettingAccount::where('id', $dtask->sub_task_id)->where('task_type', 2)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('评论区私信任务不存在：' . Db::getLastSql(), 'msg');
                throw new \Exception('评论区私信任务不存在');
            }

            $setting = SvLeadScrapingSetting::where('id', $account->scraping_id)->where('task_type', 2)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('评论区私信任务设置不存在：' . Db::getLastSql(), 'msg');
                throw new \Exception('评论区私信任务设置不存在');
            }

            $data = array(
                'type' => DeviceEnum::TASK_COMMENT_TO_MSG, // 评论区私信任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode(array(
                    'task_id' => $dtask->sub_task_id,
                    'auto_type' => $dtask->auto_type,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'startTime' => $dtask->start_time,
                    'endTime' => $dtask->end_time,
                    'timeInterval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'keyword' => self::getKeywords($setting->industry, $setting->city ?? ''),
                    'gender' => $setting->gender ?? '不限',
                    'commentContents' => !empty($setting->content) ? json_decode($setting->content, true) : [],
                    'filteredKeywords' => !empty($setting->filter) ? json_decode($setting->filter, true) : [], // ,
                    'commentCount' => $setting->send_num ?? 30,
                    'dmCount' => $setting->send_num ?? 30,
                    'noteViewCount' => $setting->industry_num ?? 5,
                    'industry_type' => $setting->industry_type ?? 0,
                    'city' => $setting->city ?? '',
                    'is_content_author' => $setting->is_content_author ?? 0,
                    'is_execed_clues' => $setting->is_execed_clues ?? 0,
                    'is_touch_like' => $setting->is_like ?? 0,
                    'is_touch_follow' => $setting->is_follow ?? 0,
                    'content_publish_day' => $setting->content_publish_day ?? 0,
                    'comment_publish_day' => $setting->comment_publish_day ?? 0,
                    'ip_address' => $setting->ip_address ?? [],
                    'is_note_like' => $setting->is_like ?? 0,
                    'msg' => '评论区私信任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'msg');
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
            self::setLog($th->getTraceAsString(), 'msg');
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


    // 留痕获客任务
    public static function touchCommentToMarkClueTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "留痕获客任务{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvLeadScrapingSettingAccount::where('id', $dtask->sub_task_id)->where('task_type', 3)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('留痕获客任务不存在：' . Db::getLastSql(), 'mark');
                throw new \Exception('留痕获客任务不存在');
            }

            $setting = SvLeadScrapingSetting::where('id', $account->scraping_id)->where('task_type', 3)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('留痕获客任务设置不存在：' . Db::getLastSql(), 'mark');
                throw new \Exception('留痕获客任务设置不存在');
            }

            $data = array(
                'type' => DeviceEnum::TASK_COMMENT_TO_MARK_CLUE, // 留痕获客任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode(array(
                    'task_id' => $dtask->sub_task_id,
                    'auto_type' => $dtask->auto_type,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'startTime' => $dtask->start_time,
                    'endTime' => $dtask->end_time,
                    'timeInterval' => ($dtask->end_time - $dtask->start_time) / 60,
                    'keyword' => self::getKeywords($setting->industry, $setting->city ?? ''),
                    'hasLiked' => $setting->is_like,
                    'hasFollowed' => $setting->is_follow,
                    'commentContents' => !empty($setting->content) ? json_decode($setting->content, true) : [],
                    'filteredKeywords' => !empty($setting->filter) ? json_decode($setting->filter, true) : [],
                    'commentCount' => $setting->send_num ?? 30,
                    'dmCount' => $setting->send_num ?? 30,
                    'noteViewCount' => $setting->industry_num ?? 5,
                    'industry_type' => $setting->industry_type ?? 0,
                    'city' => $setting->city ?? '',
                    'is_content_author' => $setting->is_content_author ?? 0,
                    'is_execed_clues' => $setting->is_execed_clues ?? 0,
                    'content_publish_day' => (int)$setting->content_publish_day ? (int)$setting->content_publish_day : 360,
                    'comment_publish_day' => (int)$setting->comment_publish_day ? (int)$setting->comment_publish_day : 360,
                    'ip_address' => $setting->ip_address ?? [],
                    'is_touch_like' => in_array(1, $setting->marker_method) ? 1 : 0,
                    'is_touch_follow' => in_array(2, $setting->marker_method) ? 1 : 0,
                    'is_note_like' => in_array(3, $setting->marker_method) ? 1 : 0, //点赞作品
                    'is_note_comment' => in_array(4, $setting->marker_method) ? 1 : 0, //评论作品
                    'is_note_collect' => in_array(5, $setting->marker_method) ? 1 : 0, //收藏作品
                    'msg' => '留痕获客任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => '2.1.1',
                'messageId' => 0,
            );
            self::setLog($data, 'mark');
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
            self::setLog($th->getTraceAsString(), 'mark');
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

    public static function getKeywords(string $keywords, string $city): array
    {
        $keywords = json_decode($keywords, true) ?? [];
        foreach ($keywords as $key => $item) {
            $keywords[$key] = "{$city}{$item}";
        }
        return $keywords;
    }

    private static function _sendChannelAddWechatMessage(array $payload, SvAccount $wechat, array $record)
    {
        $request = [
            'DeviceId' => $payload['DeviceCode'],
            'WeChatId' => $payload['WechatId'],
            'Phones' => [$payload['Phones']],
            'Message' => $payload['message'],
            'TaskId' => time() . (1000 + (int)$record['id']),
            'Remark' => $payload['Remark'] ?? '',
        ];
        try {
            //进程通信

            self::setLog($request, 'add_wechat');
            // $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($request);
            // $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            // $message->setMsgType($content['MsgType']);
            // $any = new \Google\Protobuf\Any();
            // $any->pack($content['Content']);
            // $message->setContent($any);
            // $pushMessage = $message->serializeToString();

            // $channel = "socket.{$payload['DeviceCode']}.message";
            // self::setLog('channel: ' . $channel, 'add_wechat');

            // \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            // \Channel\Client::publish($channel, [
            //     'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            // ]);
            //$wechat->add_num += 1;
            // $wechat->is_cooling = 0;
            // $wechat->cooling_time = 0;
            // $wechat->update_time = time();
            // $wechat->save();

            AiWechatLog::create([
                'user_id' => $wechat->user_id,
                'wechat_id' => $wechat->account,
                'log_type' => 0,
                'friend_id' => $payload['Phones'],
                'create_time' => time()
            ]);
            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->account,
                'wechat_name' => $wechat->nickname,
                'wechat_avatar' => $wechat->avatar,
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
            return $request;
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e->getTraceAsString(), 'add_wechat');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $request;
    }

    private static function sendCluesWechatMessage(array $payload, SvAccount $wechat, array $record): void
    {
        try {
            //进程通信
            $message = [
                'DeviceId' => $payload['DeviceCode'],
                'WeChatId' => $payload['WechatId'],
                'Phones' => [$payload['Phones']],
                'Message' => $payload['message'],
                'TaskId' => $record['task_id'],
                'Remark' => $payload['Remark'] ?? '',
            ];
            self::setLog($message, 'add_wechat');
            // $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($message);
            // $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            // $message->setMsgType($content['MsgType']);
            // $any = new \Google\Protobuf\Any();
            // $any->pack($content['Content']);
            // $message->setContent($any);
            // $pushMessage = $message->serializeToString();

            // $channel = "socket.{$payload['DeviceCode']}.message";
            // self::setLog('channel: ' . $channel, 'add_wechat');

            // \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            // \Channel\Client::publish($channel, [
            //     'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            // ]);
            //$wechat->add_num += 1;
            $wechat->is_cooling = 0;
            $wechat->cooling_time = 0;
            $wechat->update_time = time();
            $wechat->save();

            AiWechatLog::create([
                'user_id' => $wechat->user_id,
                'wechat_id' => $wechat->account,
                'log_type' => AiWechatLog::TYPE_ACCEPT_FRIEND,
                'friend_id' => $payload['Phones'],
                'create_time' => time()
            ]);
            SvAddWechatRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->account,
                'wechat_name' => $wechat->nickname,
                'wechat_avatar' => $wechat->avatar,
                'remark' => $payload['message'] ?? '',
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);

            $completed_add_count = SvCrawlingTask::where('id', $record['crawling_task_id'])->value('completed_add_count');
            SvCrawlingTask::where('id', $record['crawling_task_id'])->update([
                'completed_add_count' => $completed_add_count + 1,
                'update_time' => time(),
            ]);
            //return $message;
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e, 'add_wechat');
        }
        //return [];
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
                if (empty($isOnline) || $isOnline !== 'online') {
                    throw new \Exception("设备:{$deviceCode} 不在线");
                }
            } else {
                self::setWsSelect();
                $isOnline = self::redis()->get("xhs:device:{$deviceCode}:status");
                if (empty($isOnline) || $isOnline !== 'online') {
                    throw new \Exception("设备:{$deviceCode} 不在线");
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
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
