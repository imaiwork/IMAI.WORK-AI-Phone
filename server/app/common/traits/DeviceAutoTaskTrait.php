<?php

declare(strict_types=1);

namespace app\common\traits;

use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\AutomationEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAddWechatRecord;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceActive;
use app\common\model\sv\SvDeviceActiveAccount;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\user\User;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\sv\SvDeviceCircleLikeReply;
use app\common\model\sv\SvDeviceCircleLikeReplyAccount;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\service\FileService;
use Channel\Client as ChannelClient;
use think\cache\driver\Redis;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

trait DeviceAutoTaskTrait
{
    private static $redisInstance = null;
    private static $logtitle = '';
    private static $redisSelect = 8;

    // 自动化功能常量定义已迁移到 AutomationEnum 枚举类

    public static function sphCluesStartTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            TokenLogService::checkToken($dtask->user_id,'');
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
                'auto_type' => $task->auto_type,
                'platform' => DeviceEnum::getAccountTypeDesc((int)$find['type']),
                'task_type' => 'auto',
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
            TokenLogService::checkToken($task->user_id,'');
            self::checkOnline($task->device_code, 'ws');
            $data = array(
                'type' => 25,
                'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                'content' => json_encode(array(
                    'task_id' => $task->sub_task_id,
                    'auto_type' => $task->auto_type,
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
            TokenLogService::checkToken($task->user_id,'');
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
                // self::setLog('暂时没有可发布的内容', 'publish');
                // self::setLog(Db::getLastSql(), 'publish');
                return $callback([
                    'status' => -1,
                    'remark' => '暂时没有需要执行的发布任务',
                ]);
            }

            if (is_null($publish['material_url'])) {
                self::setLog('视频号发布任务素材url为空', 'publish');
                self::setLog(Db::getLastSql(), 'publish');
                throw new \Exception('视频号发布任务素材url为空');
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
            TokenLogService::checkToken($task->user_id,'');
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

            if ($publish->isEmpty()) {
                if (is_callable($callback)) {
                    return $callback([
                        'status' => -1,
                        'remark' => '暂时没有可发布的内容',
                    ]);
                }
            }

            if (is_null($publish['material_url'])) {
                self::setLog('发布任务素材url为空', 'publish');
                self::setLog(Db::getLastSql(), 'publish');
                throw new \Exception('发布任务素材url为空');
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
                    'auto_type' => $task->auto_type,
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


    public static function wechatCirclePublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            TokenLogService::checkToken($task->user_id,'');
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
                    'title' => $publish['title'] ?? '',
                    'type' => $publish['attachment_type'] === 1 ? 2 : 1,
                    'list' => $publish['attachment_content'],
                    'isLocation' => !empty($publish['poi']) ? 1 : 0,
                    'location' => $publish['poi'] ?? '',
                    'isScheduledTime' => true,
                    'scheduledTime' => $publish['send_time'],
                    'taskId' => $publish['task_id'],
                    'body' => $publish['content'],
                    'tag' => $publish['tag'] ?? '',
                    'comment' => $publish['comment'] ?? [],

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
            $scene = AutomationEnum::FRIENDS_CIRCLE_RELEASED;
            self::requestUrl($payload, $scene, $publish['user_id'], $task->id,  $task->device_code);
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
                    'auto_type' => 1,
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

    public static function cluesAddWechatFriendTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "自动加好友任务[{$dtask->device_code}]";
            self::checkOnline($dtask->device_code, 'ws');
            TokenLogService::checkToken($dtask->user_id,'');    
            // $records = SvCrawlingManualTaskRecord::alias('a')
            //     ->field('a.*')
            //     ->field('t.add_type, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
            //     ->join('sv_crawling_manual_task t', 'a.task_id = t.id')
            //     ->where('t.status', 'in', [0, 1])
            //     ->where('a.status', 4)
            //     ->where('t.id', $dtask->sub_task_id)
            //     ->order('a.update_time', 'asc')
            //     ->limit(10)
            //     ->select()
            //     ->toArray();

            $records = SvAddWechatRecord::alias('r')
                ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
                ->where('r.device_code', $dtask->device_code)
                //->where('t.add_type', 1)
                ->where('t.auto_type', 1)
                ->where('r.channel', 1)
                ->where('r.status', 'in', [3, 4, 5])
                ->where('t.wechat_id', 'not in', ['', null]) // 过滤掉wechat_id为空的记录
                //->where('t.status', 'in', [1, 2]) // 过滤掉已完成、已暂停、已删除的任务
                //->whereRaw('t.exec_add_count > t.completed_add_count') // 过滤掉已执行加微次数大于等于注册类型的记录
                //->limit(10)
                ->where('r.create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                ->order('r.id', 'desc')
                ->select()
                ->toArray();

            //print_r(Db::getLastSql()); die;
            $sendWechatIds = [];
            $add_interval_time = 10;
            foreach ($records as $record) {
                $task = SvCrawlingTask::where('id', $record['crawling_task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setLog("线索任务不存在:\n" . Db::getLastSql(), 'add_wechat');
                    $output->writeln("线索任务不存在:\n" . Db::getLastSql());
                    continue;
                }
                if ($task->exec_add_count == 0) {
                    $task->exec_add_count = 10;
                    $task->save();
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
                if (preg_match($wxPattern, $record['reg_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['reg_wechat'],
                    ]);
                    if (isset($response['code']) && (int)$response['code'] === 10005) {
                        self::setLog($response, 'add_wechat');
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
                //     self::_sendChannelAddWechatMessage([
                //         'WechatId' => $wechat['wechat_id'],
                //         'DeviceCode' => $wechat['device_code'],
                //         'Phones' => $record['clue_wechat'],
                //         'message' => self::_createGreetingMessage($record, $dtask), //ai生成打招呼消息
                //     ], $wechat, $record);
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
                    SvAddWechatRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '设备' . $dtask->device_code . ' 没有获取微信信息',
                        'update_time' => time(),
                    ]);
                    continue;
                }
                $addRemark = self::_createGreetingMessage($record, $dtask);
                self::_sendChannelAddWechatMessage([
                    'WechatId' => $wechat['account'],
                    'DeviceCode' => $wechat['device_code'],
                    'Phones' => $record['reg_wechat'],
                    'message' =>  $addRemark, //ai生成打招呼消息
                ], $wechat, $record);

                array_push($sendWechatIds, [
                    // 'wechatId' => $wechat['account'],
                    // 'deviceCode' => $wechat['device_code'],
                    'friendWechatId' => $record['reg_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                    //'taskId' => $request['TaskId'],
                    'recordId' => $record['id'],
                    'isManual' => 0,
                ]);
            }

            // if(empty($sendWechatIds)){
            //     $dtask->end_time = time() + 60;
            //     $dtask->remark = '无加微账号可加,任务结束';
            //     $dtask->save();
            // }

            if (!empty($sendWechatIds)) {
                $data = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, // 接管任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => $dtask->id,
                        'auto_type' => $dtask->auto_type,
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


    public static function rpaMaintainAccountTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "养号任务{$dtask->device_code}";
            self::checkOnline($dtask->device_code, 'ws');
            TokenLogService::checkToken($dtask->user_id,'');    
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
                    'auto_type' => $dtask->auto_type,
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
            TokenLogService::checkToken($dtask->user_id,'');    
            $account = SvDeviceActiveAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                throw new \Exception('养号任务不存在');
            }

            // $data = array(
            //     'type' => 41, // 养号任务执行结束
            //     'appType' => DeviceEnum::TASK_TYPE_ACTIVE,
            //     'content' => json_encode(array(
            //         'task_id' => $dtask->sub_task_id,
            //         'deviceId' => $dtask->device_code,
            //         'account' => $dtask->account,
            //         'account_type' => $dtask->account_type,
            //         'start_time' => $dtask->start_time,
            //         'end_time' => $dtask->end_time,
            //         'msg' => '养号任务执行结束'
            //     ), JSON_UNESCAPED_UNICODE),
            //     'deviceId' => $dtask->device_code,
            //     'appVersion' => '2.1.1',
            //     'messageId' => 0,
            // );
            // self::setLog($data, 'active');
            // $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // $channel = "device.{$dtask->device_code}.message";
            // ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            // ChannelClient::publish($channel, [
            //     'data' => json_encode($data)
            // ]);

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
            TokenLogService::checkToken($dtask->user_id,'');    
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
                    'keyword' => json_decode($setting->industry, true),
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
            //            $scene = AutomationEnum::SHUT_OFF_COMMENTS;
            //            self::requestUrl($data,$scene, $setting->user_id, $dtask->id,  $dtask->device_code);
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
            TokenLogService::checkToken($dtask->user_id,'');
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
                    'keyword' => json_decode($setting->industry, true),
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

            $scene = AutomationEnum::SHUT_OFF_OBTAIN;
            self::requestUrl($data, $scene, $setting->user_id, $dtask->id,  $dtask->device_code);
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
            TokenLogService::checkToken($dtask->user_id,'');    
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
                    'keyword' => json_decode($setting->industry, true),
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
                    'is_touch_like' => $setting->is_touch_like ?? 0,
                    'is_touch_follow' => $setting->is_touch_follow ?? 0,
                    'content_publish_day' => $setting->content_publish_day ?? 0,
                    'comment_publish_day' => $setting->comment_publish_day ?? 0,
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


    // 接管任务
    public static function rpaTakeoverTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "接管任务{$dtask->device_code}";
            TokenLogService::checkToken($dtask->user_id,'');    
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceTakeOverTaskAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('接管账号任务不存在：' . Db::getLastSql(), 'take_over');
                throw new \Exception('接管账号任务不存在');
            }
            \app\common\model\sv\SvSetting::where('user_id', $dtask->user_id)
                ->where('account', $dtask->account)
                ->update([
                    'open_ai' => 1,
                    'takeover_mode' => 1,
                    'robot_id' => $account->robot_id,
                ]);


            $data = array(
                'type' => DeviceEnum::getTakeOverType($dtask->account_type), // 接管任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode(array(
                    'task_id' => $dtask->id,
                    'deviceId' => $dtask->device_code,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'auto_type' => $dtask->auto_type,
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





    private static function _sendChannelAddWechatMessage(array $payload, SvAccount $wechat, array $record)
    {
        try {
            TokenLogService::checkToken( $wechat->user_id,'');
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
            // //$wechat->add_num += 1;
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
            SvAddWechatRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->account,
                'wechat_name' => $wechat->nickname,
                'wechat_avatar' => $wechat->avatar,
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);

            $scene = AutomationEnum::WECHAT_ADD_FRIEND;
            self::requestUrl([
                'wechat_no' => $wechat->account,
                'wechat_name' => $wechat->nickname,
                'remark' => $request['Message'],
                'exec_task_id' => $request['TaskId'],
                'exec_time' => date('Y-m-d H:i:s', time()),
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ], $scene, $wechat->user_id, $request['TaskId'], $payload['DeviceCode']);

            $completed_add_count = SvCrawlingTask::where('id', $record['crawling_task_id'])->value('completed_add_count');
            SvCrawlingTask::where('id', $record['crawling_task_id'])->update([
                'completed_add_count' => $completed_add_count + 1,
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e->getTraceAsString(), 'add_wechat');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    private static function _createGreetingMessage(array $task, SvDeviceTask $dtask)
    {


        $remarks = \app\common\model\auto\AutoDeviceAddWechatConfig::where('device_code', $dtask->device_code)->value('remarks');
        $remarks = is_string($remarks) ? json_decode($remarks, true) : $remarks;
        $remark = $remarks[array_rand($remarks)] ?? '您好！';
        return $remark;
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
            TokenLogService::checkToken( $detail['user_id'],'');

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
                $scene = AutomationEnum::SOCIAL_MEDIA_RELEASED;
                $request = $detail->toArray();
                self::requestUrl($request, $scene, $detail['user_id'], $detail['task_id'], $detail['device_code']);

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
        Log::channel('auto')->{$level}(self::$logtitle . "\n" . $content);
    }

    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string $taskId
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId,  $taskId, $device_code)
    {
        $autoType = SvDevice::where('device_code', $device_code)->value('auto_type') ?? 0;

        self::setLog('自动化扣费' . $scene . '----设备号--' . $device_code . '----任务id--' . $taskId);
        if ($autoType == 0) {
            return [];
        }
        $requestService = \app\common\service\ToolsService::Automation();

        [$tokenScene, $tokenCode] = match ($scene) {
            // 自动化功能场景
            AutomationEnum::SOCIAL_MEDIA_RELEASED => ['automation_social_media_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED],
            AutomationEnum::SHUT_OFF_COMMENTS => ['automation_shut_off_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS],
            AutomationEnum::SHUT_OFF_OBTAIN => ['automation_shut_off_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN],
            AutomationEnum::SHUT_OFF_PRIVATE_LETTER => ['automation_shut_off_private_letter', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER],
            AutomationEnum::FRIENDS_CIRCLE_COMMENTS => ['automation_friends_circle_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS],
            AutomationEnum::FRIENDS_CIRCLE_RELEASED => ['automation_friends_circle_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED],
            AutomationEnum::FRIENDS_CIRCLE_PRAISE => ['automation_friends_circle_praise', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE],
            AutomationEnum::WECHAT_ADD_FRIEND => ['automation_wechat_add_friend', AccountLogEnum::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND],
            AutomationEnum::SOCIAL_MEDIA_OBTAIN => ['automation_social_media_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN],
            AutomationEnum::SOCIAL_MEDIA_NURSING => ['automation_social_media_nursing', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING],
            AutomationEnum::OCR_LOCAL => ['automation_orc_local', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_LOCAL],
            AutomationEnum::OCR_IMG => ['automation_orc_img', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_IMG],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        $points = $unit;
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        $extra = ['算力单价' => $unit, '实际消耗算力' => $unit];
        switch ($scene) {
            // 自动化功能处理
            case AutomationEnum::SOCIAL_MEDIA_RELEASED:
                $response = $requestService->socialMediaReleased($request);
                break;
            case AutomationEnum::SHUT_OFF_COMMENTS:
                $response = $requestService->shutOffComments($request);
                break;
            case AutomationEnum::SHUT_OFF_OBTAIN:
                $response = $requestService->shutOffObtain($request);
                break;
            case AutomationEnum::SHUT_OFF_PRIVATE_LETTER:
                $response = $requestService->shutOffPrivateLetter($request);
                break;

            case AutomationEnum::FRIENDS_CIRCLE_RELEASED:
                $response = $requestService->friendsCircleReleased($request);
                break;

            case AutomationEnum::WECHAT_ADD_FRIEND:
                $response = $requestService->wechatAddFriend($request);
                break;
            case AutomationEnum::SOCIAL_MEDIA_OBTAIN:
                $response = $requestService->socialMediaObtain($request);
                break;
            case AutomationEnum::SOCIAL_MEDIA_NURSING:
                $points = $request['time_difference_minutes'] * $unit;
                $extra = ['执行时长（分钟）' => $request['time_difference_minutes'], '算力单价' => $unit, '实际消耗算力' => $points];
                $response = $requestService->socialMediaNursing($request);
                break;

            default:
        }

        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {
            if ($points > 0) {
                //token扣除
                User::userTokensChange($userId, $points);
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response['data'] ?? [];
    }
}
