<?php

declare(strict_types=1);

namespace app\common\traits;

use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\AutomationEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAddWechatRecord;
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
use app\common\model\wechat\AiWechatLog;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\aiPersona\SphClueKeywordService;
use app\common\service\sv\CircleInteractionActionService;
use Channel\Client as ChannelClient;
use think\cache\driver\Redis;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

trait DeviceAutoTaskTrait
{
    /** @var Redis|null */
    private static $redisInstance = null;
    private static $logtitle = '';
    private static $redisSelect = 8;

    // 自动化功能常量定义已迁移到 AutomationEnum 枚举类

    public static function sphCluesStartTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            TokenLogService::checkToken($dtask->user_id, '');
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

            self::publishSphCluesDispatch(
                $find->toArray(),
                (int)$dtask->sub_task_id,
                (int)$dtask->auto_type,
                (int)$dtask->start_time,
                (int)$dtask->end_time,
                $output
            );
            SvCrawlingTask::where('id', $dtask->sub_task_id)->update(['status' => 1, 'update_time' => time()]);

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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 向设备下发视频号获客任务（type=20）
     *
     * @param array $crawlingRow ct.* + device_code + bind keywords
     */
    public static function publishSphCluesDispatch(
        array $crawlingRow,
        int $deviceTaskId,
        int $autoType,
        int $startTime,
        int $endTime,
        ?Output $output = null
    ): void {
        $deviceCode = (string)($crawlingRow['device_code'] ?? '');
        $bindKeywords = $crawlingRow['keywords'] ?? [];
        if (is_string($bindKeywords)) {
            $bindKeywords = json_decode($bindKeywords, true) ?: [];
        }
        if (!is_array($bindKeywords)) {
            $bindKeywords = [];
        }

        $remarks = $crawlingRow['remarks'] ?? [];
        if (is_string($remarks)) {
            $remarks = json_decode($remarks, true) ?: [];
        }

        $payloadTask = [
            'id' => $crawlingRow['id'],
            'task_id' => $deviceTaskId,
            'auto_type' => $autoType,
            'platform' => DeviceEnum::getAccountTypeDesc((int)($crawlingRow['type'] ?? 1)),
            'task_type' => 'auto',
            'device_code' => $deviceCode,
            'keywords' => $bindKeywords,
            'exec_number' => 10000,
            'is_chat' => $crawlingRow['chat_type'] ?? 0,
            'chat_number' => $crawlingRow['chat_number'] ?? 0,
            'chat_interval_time' => $crawlingRow['chat_interval_time'] ?? 0,
            'add_type' => $crawlingRow['add_type'] ?? 1,
            'remarks' => $remarks,
            'add_remark_enable' => $crawlingRow['add_remark_enable'] ?? 0,
            'add_number' => $crawlingRow['add_number'] ?? 0,
            'add_interval_time' => $crawlingRow['add_interval_time'] ?? 0,
            'greeting_content' => $crawlingRow['greeting_content'] ?? '',
            'status' => 0,
            'ocr_type' => $crawlingRow['ocr_type'] ?? 1,
            'crawl_type' => $crawlingRow['crawl_type'] ?? 1,
            'create_time' => $crawlingRow['create_time'] ?? time(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_interval' => max(1, (int)ceil(($endTime - $startTime) / 60)),
        ];

        $data = [
            'type' => 20,
            'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
            'content' => json_encode($payloadTask, JSON_UNESCAPED_UNICODE),
            'deviceId' => $deviceCode,
            'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
            'messageId' => 0,
        ];
        self::setLog($data, 'clues');
        if ($output) {
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $channel = "device.{$deviceCode}.message";
        ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
        ChannelClient::publish($channel, [
            'data' => json_encode($data)
        ]);
        self::setWsSelect();
        self::redis()->set("xhs:device:{$deviceCode}:taskStatus", json_encode([
            'taskStatus' => 'standby',
            'taskType' => 'setSph',
            'msg' => '执行视频号',
            'duration' => 0,
            'time' => date('Y-m-d H:i:s', time()),
            'scene' => 'sph'
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 自动化获客任务结束后，幂等移除本槽已分配词语
     */
    public static function removeSphClueKeywordsForTask(SvCrawlingTask $task): void
    {
        if ((int)($task->source ?? 0) !== 2 && (int)($task->auto_type ?? 0) !== 1) {
            return;
        }

        $personaId = (int)($task->persona_id ?? 0);
        if ($personaId <= 0) {
            return;
        }

        $usedKeywords = SphClueKeywordService::decodeTaskKeywords($task->keywords);
        if (empty($usedKeywords)) {
            return;
        }

        $config = AiPersonaTrafficConfig::where('user_id', $task->user_id)
            ->where('persona_id', $personaId)
            ->findOrEmpty();
        if ($config->isEmpty()) {
            return;
        }

        SphClueKeywordService::removeKeywords($config, $usedKeywords);
    }

    /**
     * 词1完成后若未到 end_time，取词2并重新下发
     *
     * @return bool true=已续派第二词
     */
    public static function tryContinueSphClueWithNextKeyword(
        SvCrawlingTask $crawlingTask,
        string $deviceCode
    ): bool {
        if ((int)($crawlingTask->source ?? 0) !== 2 && (int)($crawlingTask->auto_type ?? 0) !== 1) {
            return false;
        }

        $deviceTask = SvDeviceTask::where('sub_task_id', $crawlingTask->id)
            ->where('device_code', $deviceCode)
            ->where('task_type', DeviceEnum::AUTO_TYPE_CLUES)
            ->order('id', 'desc')
            ->findOrEmpty();
        if ($deviceTask->isEmpty()) {
            return false;
        }
        if ((int)$deviceTask->end_time <= time()) {
            return false;
        }

        $assigned = SphClueKeywordService::decodeTaskKeywords($crawlingTask->keywords);
        if (count($assigned) >= SphClueKeywordService::MAX_KEYWORDS_PER_SLOT) {
            return false;
        }

        $personaId = (int)($crawlingTask->persona_id ?? 0);
        if ($personaId <= 0) {
            return false;
        }

        $config = AiPersonaTrafficConfig::where('user_id', $crawlingTask->user_id)
            ->where('persona_id', $personaId)
            ->findOrEmpty();
        if ($config->isEmpty()) {
            return false;
        }

        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return false;
        }

        try {
            $nextKeyword = SphClueKeywordService::takeKeyword($config, $persona, $assigned);
        } catch (\Throwable $th) {
            self::setLog('续派第二词失败: ' . $th->getMessage(), 'clues');
            return false;
        }

        $assigned[] = $nextKeyword;
        $crawlingTask->keywords = json_encode($assigned, JSON_UNESCAPED_UNICODE);
        $crawlingTask->implementation_keywords_number = count($assigned);
        $crawlingTask->status = 1;
        $crawlingTask->update_time = time();
        $crawlingTask->save();

        SvCrawlingTaskDeviceBind::where('task_id', $crawlingTask->id)
            ->where('device_code', $deviceCode)
            ->update([
                'keywords' => json_encode([$nextKeyword], JSON_UNESCAPED_UNICODE),
                'exec_keyword' => '',
                'status' => 1,
                'update_time' => time(),
            ]);

        $row = $crawlingTask->toArray();
        $row['device_code'] = $deviceCode;
        $row['keywords'] = [$nextKeyword];

        self::publishSphCluesDispatch(
            $row,
            (int)$deviceTask->id,
            (int)$deviceTask->auto_type,
            (int)$deviceTask->start_time,
            (int)$deviceTask->end_time,
            null
        );

        return true;
    }

    public static function sphCluesEndTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "视频号线索任务[{$task->device_code}]";
            TokenLogService::checkToken($task->user_id, '');
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
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$task->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
            ]);
            SvCrawlingTaskDeviceBind::where('task_id', $task->sub_task_id)->where('device_code', $task->device_code)->update(['status' => 3, 'update_time' => time()]);

            $crawlingTask = SvCrawlingTask::where('id', $task->sub_task_id)->findOrEmpty();
            if (!$crawlingTask->isEmpty()) {
                self::removeSphClueKeywordsForTask($crawlingTask);
            }

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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    public static function sphPublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        $publishClaimed = false;
        $publishDispatched = false;
        try {
            self::$logtitle = "视频号发布任务[{$task->device_code}]";
            self::checkOnline($task->device_code, 'ws');
            TokenLogService::checkToken($task->user_id, '');
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

            if (!self::claimRpaPublishDetail($publish)) {
                return $callback([
                    'status' => -1,
                    'remark' => 'publish detail already claimed',
                ]);
            }
            $publishClaimed = true;
            $publish['status'] = 3;
            $publish['exec_time'] = time();
            $publish['update_time'] = time();

            $material_url = explode(',', $publish['material_url']);
            if (count($material_url) > 12) {
                $material_url = array_slice($material_url, 0, 12);
            }

            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => 5,
                'deviceId' => $task->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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

            self::setLog($payload, 'publish');
            $channel = "device.{$publish['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);
            $publishDispatched = true;
            //self::setRpaPublishStatus($publish);


            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                    'publish_id' => $publish['id'],
                ]);
            }
        } catch (\Throwable $th) {
            $failRemark = '任务执行失败：' . self::getErrorMsg($th);
            if ($publishClaimed && isset($publish) && !$publish->isEmpty()) {
                self::failRpaPublishDetail($publish, $failRemark, $th, !$publishDispatched);
            }
            self::setLog($th->getTraceAsString(), 'publish');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => $failRemark,
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function rpaPublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        $publishClaimed = false;
        $publishDispatched = false;
        try {
            $accountTypeName = DeviceEnum::getAccountTypeDesc($task->account_type);
            self::$logtitle = "RPA [{$accountTypeName}]发布任务[{$task->device_code}]";
            TokenLogService::checkToken($task->user_id, '');
            self::checkOnline($task->device_code, 'ws');

            $publish = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
                ->where('s.id', $task->sub_task_id)
                ->where('ps.device_code', '=', $task->device_code)
                ->where('ps.account', $task->account)
                ->where('ps.status', 'in', [0, 5])
                //->where('s.status', 'in', [1, 2])
                ->where('s.account_type', $task->account_type)
                ->where('ps.data_type', 0)
                ->where('ps.publish_time', '<=', date('Y-m-d H:i:s', time()))
                ->where('ps.publish_time', 'between', [date('Y-m-d H:i:s', $task->start_time), date('Y-m-d H:i:s', $task->end_time)])
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

            $persona = \app\common\model\aiPersona\AiPersona::where('id', $task->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setLog('人设不存在：' . Db::getLastSql(), 'publish');
                throw new \Exception('人设不存在');
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

            if (!self::claimRpaPublishDetail($publish)) {
                return $callback([
                    'status' => -1,
                    'remark' => 'publish detail already claimed',
                ]);
            }
            $publishClaimed = true;
            $publish['status'] = 3;
            $publish['exec_time'] = time();
            $publish['update_time'] = time();

            $contentPublishConfig = \app\common\model\aiPersona\AiPersona::normalizeContentPublishConfig($persona['content_publish_config'] ?? []);
            $canUseLocation = in_array($task->account_type, [3, 4]);

            $locationConfig = $contentPublishConfig['platform_configs'][$task->account_type] ?? [
                'is_content_location' => 0,
                'content_location' => '',
            ];

            $location = (int)($locationConfig['is_content_location'] ?? 0) === 1
                ? (string)($locationConfig['content_location'] ?? '')
                : '';
            $isLocation = $location !== '' ? 1 : 0;
            $storePosition = $canUseLocation ? (string)($persona->store_position ?? '') : '';
            $isStorePosition = $canUseLocation ? (int)($persona->is_store_position ?? 0) : 0;
            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => 5,
                'deviceId' => $task->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'publish_platform' => $task->account_type,
                    'material_id' => $publish['id'],
                    'auto_type' => $task->auto_type,
                    'title' => $publish['material_title'],
                    'type' => $publish['material_type'] ?? 1,
                    'list' => $material_url,
                    'isLocation' => $isLocation,
                    'location' => $location,
                    'isScheduledTime' => true,
                    'scheduledTime' => $publish['publish_time'],
                    'taskId' => $task->id,
                    'body' => $publish['material_subtitle'],
                    'tag' => $publish['material_tag'] ?? '',

                    'is_shopping_cart' => in_array($task->account_type, [4]) ? ($persona->is_shopping_cart ?? 0) : 0,
                    'goods_name' => in_array($task->account_type, [4]) ? ($persona->goods_name ?? '') : '',
                    'is_content_location' => $isLocation,
                    'content_location' => $location,
                    'is_store_position' => $isStorePosition,
                    'store_position' => $storePosition,

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'publish');
            self::setRpaPublishStatus($publish);

            $channel = "device.{$publish['device_code']}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);
            $publishDispatched = true;
            
            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                    'publish_id' => $publish['id'],
                ]);
            }
        } catch (\Throwable $th) {
            $failRemark = '任务执行失败：' . self::getErrorMsg($th);
            if ($publishClaimed && isset($publish) && !$publish->isEmpty()) {
                self::failRpaPublishDetail($publish, $failRemark, $th, !$publishDispatched);
            }
            self::setLog($th->getTraceAsString(), 'publish');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => $failRemark,
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    public static function wechatCirclePublishTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            TokenLogService::checkToken($task->user_id, '');
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
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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
                    'taskId' => $task->id,
                    'body' => $publish['content'],
                    'tag' => $publish['tag'] ?? '',
                    'comment' => $publish['comment'] ?? [],

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'publish');
            $scene = AutomationEnum::FRIENDS_CIRCLE_RELEASED;
            self::requestUrl($payload, $scene, $publish['user_id'], $task->id,  $task->device_code);

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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function wechatCircleThumbCommentTask(SvDeviceTask $task, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "微圈点赞评论任务[{$task->device_code}]";
            TokenLogService::checkToken($task->user_id, '');
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


            // action 口径：1仅点赞 2仅评论 3点赞+评论；优先 live 配置覆盖任务快照
            $deviceFlags = CircleInteractionActionService::resolveDeviceFlagsFromOption($option);
            if ($deviceFlags['hasLiked'] !== 1 && $deviceFlags['hasComment'] !== 1) {
                self::setLog('微圈点赞评论未开启任何动作，终止下发', 'thumb_comment');
                return $callback([
                    'status' => -1,
                    'remark' => '未开启点赞或评论',
                ]);
            }
            $payload = array(
                'appType' => $task->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::WECHAT_CIRCLE_LIKE_COMMENT,
                'deviceId' => $task->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $comment->id,
                    'auto_type' => 1,
                    "hasLiked" => $deviceFlags['hasLiked'], //点赞
                    "hasComment" => $deviceFlags['hasComment'], //评论
                    "planCoverage" => $option->range, //当天   1、3天内   2、7天内
                    "interactionConut" => $option->number,  //互动数量
                    "timeInterval" => $option->interval,  //互动间隔/分钟
                    "commentType" => $option->comment_type,  //AI识别并评论   1、不评论   2、固定评论
                    "commentContent" =>  $option->comment ?? '', //固定评论内容
                    'account' => $task->account,
                    'account_type' => $task->account_type,
                    'start_time' => $task->start_time,
                    'end_time' => $task->end_time,
                    'time_interval' => ceil(($task->end_time - $task->start_time) / 60),

                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'thumb_comment');
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');

            $records = SvAddWechatRecord::alias('r')
                ->field('r.*')
                //->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
                ->where('r.device_code', $dtask->device_code)
                //->where('t.auto_type', 1)
                //->where('r.channel', 1)
                ->where('r.status', 'in', [3, 4, 5])
                //->where('r.create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                ->order('r.channel desc, r.id desc')
                ->limit(50)
                ->select()
                ->toArray();

            //print_r(Db::getLastSql()); die;
            $sendWechatIds = [];
            $add_interval_time = 10;
            foreach ($records as $record) {
                if (count($sendWechatIds) >= 2) {
                    break;
                }
                // $task = SvCrawlingTask::where('id', $record['crawling_task_id'])->findOrEmpty();
                // if ($task->isEmpty()) {
                //     self::setLog("线索任务不存在:\n" . Db::getLastSql(), 'add_wechat');
                //     $output->writeln("线索任务不存在:\n" . Db::getLastSql());
                //     continue;
                // }
                // if ($task->exec_add_count == 0) {
                //     $task->exec_add_count = 10;
                //     $task->save();
                // }
                // if ($task->completed_add_count >= $task->exec_add_count) {
                //     // $task->status = 3;
                //     // $task->update_time = time();
                //     // $task->save();
                //     // continue;
                // } else {
                //     if (is_null($task->start_time)) {
                //         $task->start_time = time();
                //     }
                //     $task->status = 1;
                //     $task->update_time = time();
                //     $task->save();
                // }

                //$add_interval_time = (int)$record['add_interval_time'] > 0 ? (int)$record['add_interval_time'] : $add_interval_time;
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


                $wechat = SvAccount::where('device_code', $dtask->device_code)->where('type', 1)->limit(1)->findOrEmpty();
                if ($wechat->isEmpty()) {
                    SvAddWechatRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '设备' . $dtask->device_code . ' 没有获取微信信息',
                        'update_time' => time(),
                    ]);
                    throw new \Exception('设备' . $dtask->device_code . ' 没有绑定微信账号');
                }
                $addRemark = self::_createGreetingMessage($record, $dtask);
                self::_sendChannelAddWechatMessage([
                    'WechatId' => $wechat['account'],
                    'DeviceCode' => $wechat['device_code'],
                    'Phones' => $record['reg_wechat'],
                    'message' =>  $addRemark, //ai生成打招呼消息
                ], $wechat, $record, $dtask);

                array_push($sendWechatIds, [
                    // 'wechatId' => $wechat['account'],
                    // 'deviceCode' => $wechat['device_code'],
                    'friendWechatId' => $record['reg_wechat'],
                    'message' => $addRemark, //ai生成打招呼消息
                    //'taskId' => $request['TaskId'],
                    'recordId' => $record['id'],
                    'channel' => $record['channel'],
                    'isManual' => 0,
                ]);
            }


            if (!empty($sendWechatIds)) {
                $data = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, //加微任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => $dtask->id,
                        'auto_type' => $dtask->auto_type,
                        'deviceId' => $dtask->device_code,
                        'account' => $dtask->account,
                        'account_type' => $dtask->account_type,
                        'start_time' => $dtask->start_time,
                        'end_time' => $dtask->end_time,
                        'time_interval' => ceil(($dtask->end_time - $dtask->start_time) / 60),
                        'send_wechat_ids' => $sendWechatIds,
                        'add_interval_time' => $add_interval_time,
                        'msg' => '加微任务运行'
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $dtask->device_code,
                    'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');
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
                    'time_interval' => ceil(($dtask->end_time - $dtask->start_time) / 60),
                    'msg' => '养号任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');
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
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($setting->content_publish_day ?? 0),
                    'comment_publish_day' => $setting->comment_publish_day ?? 0,
                    'ip_address' => $setting->ip_address ?? [],
                    'is_note_like' => $setting->is_like ?? 0,
                    'msg' => '评论区评论任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );
            self::setLog($data, 'comment');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }



    // 评论区私信任务
    public static function touchCommentToMsgTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            TokenLogService::checkToken($dtask->user_id, '');
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
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($setting->content_publish_day ?? 0),
                    'comment_publish_day' => $setting->comment_publish_day ?? 0,
                    'ip_address' => $setting->ip_address ?? [],
                    'is_note_like' => $setting->is_like ?? 0,
                    'msg' => '评论区私信任务运行'
                ), JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );
            self::setLog($data, 'msg');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $scene = AutomationEnum::SHUT_OFF_OBTAIN;
            self::requestUrl($data, $scene, $setting->user_id, $dtask->id,  $dtask->device_code);
            
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');
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
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($setting->content_publish_day ?? 0),
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
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'messageId' => 0,
            );
            self::setLog($data, 'mark');
            $output->writeln(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($data)
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
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
            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');

            $account = SvDeviceTakeOverTaskAccount::where('id', $dtask->sub_task_id)->findOrEmpty();
            if ($account->isEmpty()) {
                self::setLog('接管账号任务不存在：' . Db::getLastSql(), 'take_over');
                throw new \Exception('接管账号任务不存在');
            }

            $setting = SvDeviceTakeOverTask::where('id', $account->take_over_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('接管任务不存在：' . Db::getLastSql(), 'take_over');
                throw new \Exception('接管任务不存在');
            }

            $device = SvDevice::where('device_code', $dtask->device_code)->findOrEmpty();
            if ($device->isEmpty()) {
                self::setLog('设备不存在' . Db::getLastSql(), 'take_over');
                throw new \Exception('设备不存在');
            }

            $replyType = (int)$setting->task_type === 1 ? (int)$setting->comment_type : (int)$setting->message_type;
            $replyRobotId = (int)$setting->task_type === 1 ? (int)$setting->comment_robot_id : (int)$setting->message_robot_id;
            $replySpeech = (int)$setting->task_type === 1 ? $setting->comment_speech : $setting->message_speech;

            \app\common\model\sv\SvSetting::where('user_id', $dtask->user_id)
                ->where('account', $dtask->account)
                ->update([
                    'open_ai' => 1,
                    'takeover_mode' => 1,
                    'robot_id' => $replyRobotId,
                ]);


            $content =  array(
                'task_id' => $dtask->id,
                'task_type' => $setting->task_type,
                'deviceId' => $dtask->device_code,
                'account' => $dtask->account,
                'account_type' => $dtask->account_type,
                'auto_type' => $dtask->auto_type,
                'start_time' => $dtask->start_time,
                'end_time' => $dtask->end_time,
                'time_interval' => ceil(($dtask->end_time - $dtask->start_time) / 60),
                'comment_type' => $replyType,
                'comment_speech' => $replySpeech,

                'msg' => (int)$setting->task_type === 1 ? '评论任务运行' : '接管任务运行'
            );
            if ($dtask->account_type === 1) {
                $groupStrategy = \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('user_id', $dtask->user_id)->where('persona_id', $dtask->persona_id)->findOrEmpty();
                if ($groupStrategy->isEmpty()) {
                    self::setLog('私域与运营设置不存在：' . Db::getLastSql(), 'take_over');
                    throw new \Exception('私域与运营设置不存在');
                }
                $isAutoGroup = (int)$groupStrategy->is_auto_group;
                if (!AiPersonaOptionService::isEnabledForPersonaId((int)$dtask->persona_id, 'private_operation.options.auto_add_group')) {
                    $isAutoGroup = 0;
                }
                $content['is_auto_group'] = $isAutoGroup;
                $content['sales_wechat'] = $groupStrategy->sales_wechat;
                $content['group_name_template'] = $groupStrategy->group_name_template;
                $content['is_greeting'] = $groupStrategy->is_greeting;
                $content['greeting_text'] = $groupStrategy->greeting_text;
                $content['is_share_chats'] = $groupStrategy->is_share_chats;
            }

            $data = array(
                'type' => DeviceEnum::getTakeOverType($dtask->account_type), // 接管任务启动
                'appType' => $dtask->account_type,
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
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
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }


    /**
     * 同城曝光任务
     */
    public static function sameCityExposureTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "同城曝光任务[{$dtask->device_code}]";

            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');



            $find = \app\common\model\sv\SvCityExposureTask::alias('ps')
                ->field('ps.*')
                ->join('sv_city_exposure_task_account s', 's.city_exposure_id = ps.id')
                ->where('s.id', $dtask->sub_task_id)
                ->where('s.device_code', '=', $dtask->device_code)
                ->where('s.account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('同城曝光任务设置不存在：' . Db::getLastSql(), 'mark');
                throw new \Exception('同城曝光任务设置不存在');
            }

            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::TASK_SAME_CITY_EXPOSURE,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $dtask->sub_task_id,
                    'auto_type' => 1,
                    'radius' => $find->radius,
                    'interval_time' => $find->interval_time,
                    'visit_num' => $find->visit_num,
                    'account_feature' => $find->account_feature,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '同城曝光任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'same_city_exposure');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'same_city_exposure');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 同城曝光任务完成
     */
    public static function sameCityExposureCompletedTask(SvDeviceTask $task, Output $output) {}

    /**
     * 同城截流任务
     */
    public static function sameCityCutoffTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "同城截流任务[{$dtask->device_code}]";

            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');


            $find = \app\common\model\sv\SvCityTouchTask::alias('ps')
                ->field('ps.*')
                ->join('sv_city_touch_task_account s', 's.city_touch_id = ps.id')
                ->where('s.id', $dtask->sub_task_id)
                ->where('s.device_code', '=', $dtask->device_code)
                ->where('s.account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('同城截流任务设置不存在：' . Db::getLastSql(), 'mark');
                throw new \Exception('同城截流任务设置不存在');
            }

            $setting = \app\common\model\aiPersona\AiPersonaAgentConfig::where('persona_id', $find->persona_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('同城截流任务设置不存在：' . Db::getLastSql(), 'mark');
                throw new \Exception('同城截流任务设置不存在');
            }
            $find->comment_speech = $setting->shutoff_comment_speech;
            $find->message_speech = $setting->shutoff_msg_speech;

            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::TASK_SAME_CITY_CUTOFF,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $dtask->sub_task_id,
                    'auto_type' => 1,
                    'task_type' => $find->task_type, //1 评论 2 私信
                    'radius' => $find->radius,
                    'account_feature' => $find->account_feature,
                    'marker_method' => $find->marker_method,
                    'chat_type' => $find->chat_type,
                    'interval_time' => $find->interval_time,
                    'watch_time' => $find->watch_time,
                    'gender' => $find->gender,
                    'old' => $find->old,
                    'region' => $find->region,
                    'city' => $find->city,
                    'send_num' => $find->send_num,
                    'like_num' => $find->like_num,
                    'comment_num' => $find->comment_num,
                    'comment_fans_num' => $find->comment_fans_num,
                    'comment_follow_num' => $find->comment_follow_num,
                    'filter' => $find->filter,
                    'nickname_filter' => $find->nickname_filter,
                    'comment_speech' => $find->comment_speech,
                    'message_speech' => $find->message_speech,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '同城视频截流任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'same_city_cutoff');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'same_city_cutoff');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 同城截流任务完成
     */
    public static function sameCityCutoffCompletedTask(SvDeviceTask $task, Output $output) {}

    /**
     * 团购任务
     */
    public static function groupBuyTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "团购任务[{$dtask->device_code}]";
            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');


            $find = \app\common\model\sv\SvGroupBuyTask::alias('ps')
                ->field('ps.*')
                ->join('sv_group_buy_task_account s', 's.group_buy_id = ps.id')
                ->where('s.id', $dtask->sub_task_id)
                ->where('s.device_code', '=', $dtask->device_code)
                ->where('s.account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('团购任务设置不存在：' . Db::getLastSql(), 'group_buy_task');
                throw new \Exception('团购任务设置不存在');
            }


            $setting = \app\common\model\aiPersona\AiPersonaAgentConfig::where('persona_id', $find->persona_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('团购任务话术设置不存在：' . Db::getLastSql(), 'group_buy_task');
                throw new \Exception('团购任务话术设置不存在');
            }
            $find->comment_speech = $setting->shutoff_comment_speech;
            $find->message_speech = $setting->shutoff_msg_speech;

            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::TASK_GROUP_BUY,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $dtask->sub_task_id,
                    'auto_task' => 1,
                    'task_type' => $find->task_type,
                    'account_feature' => $find->account_feature,
                    'marker_method' => $find->marker_method,
                    'chat_type' => $find->chat_type,
                    //'like_type' => $find->like_type,
                    'group_type' => $find->group_type,
                    'send_num' => $find->send_num,
                    //'radius' => $find->radius,
                    'interval_time' => $find->interval_time,
                    'watch_time' => $find->watch_time,
                    //'content_publish_day' => $find->content_publish_day,
                    'comment_offset' => $find->comment_offset,
                    'gender' => $find->gender,
                    'old' => $find->old,
                    'region' => $find->region,
                    'city' => $find->city,
                    'comment_keyword' => $find->comment_keyword,
                    'filter' => $find->filter,
                    'nickname_filter' => $find->nickname_filter,
                    'comment_speech' => $find->comment_speech,
                    'message_speech' => $find->message_speech,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '团购任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'group_buy_task');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'group_buy_task');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 团购任务完成
     */
    public static function groupBuyCompletedTask(SvDeviceTask $task, Output $output)
    {
        // $scene = AutomationEnum::GROUP_BUY;
        // $payload = [
        //     'userId' => $task->user_id,
        //     'taskId' => $task->sub_task_id,
        //     'time_difference_minutes' => $task->end_time - $task->start_time,
        // ];
        // self::requestUrl($payload, $scene, $task->user_id, $task->sub_task_id,  $task->device_code);
    }


    public static function viralRewriterTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "爆款仿写任务[{$dtask->device_code}]";
            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');


            $find = \app\common\model\sv\SvDeviceViral::alias('ps')
                ->field('ps.*,IF(s.publish_platform > 0, s.publish_platform, s.account_type) as publish_platform,IF(s.publish_media_type > 0, s.publish_media_type, IFNULL(ps.publish_media_type, 1)) as publish_media_type,s.duration as duration,s.publish_day as publish_day,p.tracking_mode,p.tracking_account_config')
                ->join('sv_device_viral_account s', 's.viral_id = ps.id')
                ->join('ai_persona p', 'p.id = ps.persona_id', 'left')
                ->where('ps.id', $dtask->sub_task_id)
                ->where('s.device_code', '=', $dtask->device_code)
                ->where('s.account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('爆款仿写任务设置不存在：' . Db::getLastSql(), 'viral_rewriter_task');
                throw new \Exception('爆款仿写任务设置不存在');
            }


            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::TASK_VIRAL_REWRITER,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $dtask->sub_task_id,
                    'auto_type' => 1,
                    'keywords' => $find->keywords,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'publish_platform' => (int)($find->publish_platform ?? $dtask->account_type),
                    'publish_media_type' => (int)($find->publish_media_type ?? 1),
                    'duration' => \app\common\model\aiPersona\AiPersona::normalizeTrackingDuration($find->duration ?? \app\common\model\aiPersona\AiPersona::TRACKING_DURATION_DEFAULT),
                    'publish_day' => \app\common\model\aiPersona\AiPersona::normalizeTrackingFilterValue($find->publish_day ?? 0),
                    'tracking_mode' => \app\common\model\aiPersona\AiPersona::normalizeTrackingMode($find->tracking_mode ?? \app\common\model\aiPersona\AiPersona::TRACKING_MODE_AUTO),
                    'tracking_account_config' => \app\common\model\aiPersona\AiPersona::normalizeTrackingAccountConfig($find->tracking_account_config ?? []),
                    'custom_date' => $find->custom_date,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'msg' => '爆款仿写任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'viral_rewriter_task');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'viral_rewriter_task');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 团购任务完成
     */
    public static function viralRewriterCompletedTask(SvDeviceTask $task, Output $output)
    {
        // $scene = AutomationEnum::GROUP_BUY;
        // $payload = [
        //     'userId' => $task->user_id,
        //     'taskId' => $task->sub_task_id,
        //     'time_difference_minutes' => $task->end_time - $task->start_time,
        // ];
        // self::requestUrl($payload, $scene, $task->user_id, $task->sub_task_id,  $task->device_code);
    }

    public static function sphThumbTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "视频号点赞任务[{$dtask->device_code}]";
            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');


            $find = \app\common\model\sv\SvDeviceTakeOverTaskAccount::field('*')
                ->where('id', $dtask->sub_task_id)
                ->where('device_code', '=', $dtask->device_code)
                ->where('account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('视频号点赞任务设置不存在：' . Db::getLastSql(), 'thumb_task_task');
                throw new \Exception('视频号点赞任务设置不存在');
            }


            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::SPH_TAKE_THUMB,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'task_id' => $dtask->sub_task_id,
                    'auto_type' => 1,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'comment_type' => 3,
                    'msg' => '视频号点赞任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'thumb_task_task');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'thumb_task_task');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    /**
     * 视频号点赞任务完成
     */
    public static function sphThumbCompletedTask(SvDeviceTask $task, Output $output) {}



    /**
     * 精准获客任务
     */
    public static function preciseCluesTask(SvDeviceTask $dtask, Output $output, callable $callback)
    {
        try {
            self::$logtitle = "精准获客任务[{$dtask->device_code}]";
            TokenLogService::checkToken($dtask->user_id, '');
            self::checkOnline($dtask->device_code, 'ws');


            $find = \app\common\model\sv\SvDevicePreciseClues::alias('ps')
                ->field('ps.*,s.id as precise_clues_account_id,s.clues')
                ->join('sv_device_precise_clues_account s', 's.precise_clues_id = ps.id')
                ->where('ps.id', $dtask->sub_task_id)
                ->where('s.device_code', '=', $dtask->device_code)
                ->where('s.account_type', $dtask->account_type)
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $output->writeln(Db::getLastSql());
                self::setLog('精准获客任务设置不存在：' . Db::getLastSql(), 'precise_clues_task');
                throw new \Exception('精准获客任务设置不存在');
            }

            $mentionLimit = 50;
            $allClues = self::normalizePreciseClues($find->clues);
            $touchedUserIds = \app\common\model\sv\SvDevicePreciseCluesRecord::where('precise_clues_account_id', $find->precise_clues_account_id)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->group('target_user_id')
                ->column('target_user_id');
            $roundNo = max(1, (int)\app\common\model\sv\SvDevicePreciseCluesRecord::where('precise_clues_account_id', $find->precise_clues_account_id)
                ->whereNull('delete_time')
                ->max('round_no') + 1);
            $remainingClues = array_values(array_filter($allClues, function ($item) use ($touchedUserIds) {
                return !in_array($item, $touchedUserIds, true);
            }));
            $currentClues = array_slice($remainingClues, 0, $mentionLimit);

            $payload = array(
                'appType' => $dtask->account_type,
                'messageId' => 0,
                'type' => DeviceEnum::TASK_PRECISE_CLUES,
                'deviceId' => $dtask->device_code,
                'appVersion' => \app\common\enum\DeviceEnum::APP_VERSION,
                'content' => json_encode([
                    'taskId' => $dtask->sub_task_id,
                    'taskAccountId' => $find->precise_clues_account_id,
                    'auto_type' => 1,
                    'account' => $dtask->account,
                    'account_type' => $dtask->account_type,
                    'start_time' => $dtask->start_time,
                    'end_time' => $dtask->end_time,
                    'round_no' => $roundNo,
                    'mention_limit' => $mentionLimit,
                    'wait_seconds' => 60,
                    //'clues' => $currentClues,
                    'all_clues' => $remainingClues,
                    'total_count' => count($allClues),
                    'touched_count' => count(array_intersect($allClues, $touchedUserIds)),
                    'remaining_count' => count($remainingClues),
                    'msg' => '精准获客任务运行',
                ], JSON_UNESCAPED_UNICODE)
            );
            self::setLog($payload, 'precise_clues_task');
            $channel = "device.{$dtask->device_code}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            if (is_callable($callback)) {
                return $callback([
                    'status' => 1,
                    'remark' => '任务执行中',
                ]);
            }
        } catch (\Throwable $th) {
            self::setLog($th->getTraceAsString(), 'precise_clues_task');
            $output->writeln("任务执行失败：" . $th->getMessage());
            if (is_callable($callback)) {
                return $callback([
                    'status' => 3,
                    'remark' => '任务执行失败：' . self::getErrorMsg($th),
                ]);
            }
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    private static function normalizePreciseClues(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $clues = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['target_user_id']
                    ?? $item['targetUserId']
                    ?? $item['douyin_user_id']
                    ?? $item['douyinUserId']
                    ?? $item['douyin_id']
                    ?? $item['douyinId']
                    ?? $item['sec_uid']
                    ?? $item['secUid']
                    ?? $item['uid']
                    ?? $item['user_id']
                    ?? $item['userId']
                    ?? $item['account']
                    ?? $item['id']
                    ?? '';
            }

            $item = trim((string)$item);
            if ($item !== '') {
                $clues[] = $item;
            }
        }

        return array_values(array_unique($clues));
    }

    /**
     * 团购任务完成
     */
    public static function preciseCluesCompletedTask(SvDeviceTask $task) {
        $scene = AutomationEnum::PRECISE_CLUES;
        $payload = [
            'userId' => $task->user_id,
            'taskId' => $task->sub_task_id,
            'time_difference_minutes' => $task->end_time - $task->start_time,
        ];
        self::requestUrl($payload, $scene, $task->user_id, $task->sub_task_id,  $task->device_code);
    }



    private static function _sendChannelAddWechatMessage(array $payload, SvAccount $wechat, array $record, SvDeviceTask $dtask)
    {
        try {
            TokenLogService::checkToken($wechat->user_id, '');
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
                'remark' => $request['Message'],
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
                'exec_task_id' => $dtask->id,
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

        $remark = \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('persona_id', $dtask->persona_id)->value('add_friend_script');
        return $remark ?? '您好！';
    }


    /**
     * @param array|\ArrayAccess $publish
     */
    private static function setRpaPublishStatus(array|\ArrayAccess $publish): void
    {
        try {

            $detail = SvPublishSettingDetail::where('id', $publish['id'])->findOrEmpty();
            if (!$detail->isEmpty()) {

                $account = SvPublishSettingAccount::where('id', $publish['publish_account_id'])->findOrEmpty();
                if (!$account->isEmpty()) {
                    $account->save([
                        'status' => 1,
                        'update_time' => time(),
                    ]);

                    SvPublishSetting::where('id', $detail['publish_id'])->update([
                        'update_time' => time(),
                        'status' => 2,
                    ]);
                    self::setLog('发布账号数据更新成功:' . $publish['publish_account_id'], 'publish');
                }



                TokenLogService::checkToken($detail['user_id'], '');
                $scene = AutomationEnum::SOCIAL_MEDIA_RELEASED;
                $request = $detail->toArray();
                self::requestUrl($request, $scene, $detail['user_id'], $detail['task_id'], $detail['device_code']);
                self::setLog('发布数据状态更新成功:' . $publish['id'], 'publish');

            } else {
                $publish['message'] = '待发布数据丢失:';
                $logPublish = is_object($publish) && method_exists($publish, 'toArray') ? $publish->toArray() : (array)$publish;
                self::setLog($logPublish, 'publish');
            }
        } catch (\Exception $e) {
            self::setLog('_setPublishStatus' . $e, 'error');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    private static function claimRpaPublishDetail(array|\ArrayAccess $publish): bool
    {
        $now = time();
        Db::startTrans();
        try {
            $affected = SvPublishSettingDetail::where('id', $publish['id'])
                ->where('status', 'in', [0, 5])
                ->update([
                    'status' => 3,
                    'exec_time' => $now,
                    'update_time' => $now,
                ]);

            if (!$affected) {
                Db::rollback();
                return false;
            }

            SvPublishSettingAccount::where('id', $publish['publish_account_id'])->update([
                'status' => 1,
                'update_time' => $now,
                'published_count' => Db::raw('published_count+1'),
            ]);

            SvPublishSetting::where('id', $publish['publish_id'])->update([
                'update_time' => $now,
                'status' => 2,
            ]);

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setLog('claimRpaPublishDetail error: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * 发布下发/计费失败时回写明细失败原因。
     * - 未下发且非算力类错误: status=5 可重试
     * - 算力不足或已下发后失败: status=2 永久失败, 保留真实错误文案
     */
    private static function failRpaPublishDetail(
        array|\ArrayAccess $publish,
        string $remark,
        \Throwable $th,
        bool $allowRetry
    ): void {
        $isBilling = (int)$th->getCode() === 4059 || str_contains(self::getErrorMsg($th), '算力不足');
        $permanent = $isBilling || !$allowRetry;
        try {
            $affected = SvPublishSettingDetail::where('id', $publish['id'])
                ->where('status', 'in', [3, 5])
                ->update([
                    'status' => $permanent ? 2 : 5,
                    'remark' => $remark,
                    'update_time' => time(),
                ]);
            // 可重试回滚占用计数; 永久失败保留已尝试占用
            if ($affected && !$permanent) {
                SvPublishSettingAccount::where('id', $publish['publish_account_id'])->update([
                    'update_time' => time(),
                    'published_count' => Db::raw('GREATEST(IFNULL(published_count, 0) - 1, 0)'),
                ]);
            }
        } catch (\Throwable $e) {
            self::setLog('failRpaPublishDetail error: ' . $e->getMessage(), 'error');
        }
    }

    private static function releaseRpaPublishDetail(array|\ArrayAccess $publish, string $remark = '发布下发失败'): void
    {
        try {
            $affected = SvPublishSettingDetail::where('id', $publish['id'])
                ->where('status', 3)
                ->update([
                    'status' => 5,
                    'remark' => $remark !== '' ? $remark : '发布下发失败',
                    'update_time' => time(),
                ]);
            if ($affected) {
                SvPublishSettingAccount::where('id', $publish['publish_account_id'])->update([
                    'update_time' => time(),
                    'published_count' => Db::raw('GREATEST(IFNULL(published_count, 0) - 1, 0)'),
                ]);
            }
        } catch (\Throwable $e) {
            self::setLog('releaseRpaPublishDetail error: ' . $e->getMessage(), 'error');
        }
    }

    private static function checkOnline(string $deviceCode, string $type = 'wx')
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
            //'select'      => env('redis.WS_SELECT', 8),
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
        self::$redisSelect = env('redis.WS_SELECT', 8);
    }


    private static function setLog(array|string $content, string $level = 'info'): void
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('auto')->{$level}(self::$logtitle . "\n" . $content);
    }

    private static function getErrorMsg(\Throwable $e): string
    {
        $error = $e->getMessage();
        if (strpos($error, 'scocket') !== false) {
            $error = 'socket服务器连接失败';
        }
        return $error;
    }

    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string|int $taskId
     * @param string $device_code
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId, string|int $taskId, string $device_code): array
    {
        self::setLog('自动化扣费' . $scene . '----设备号--' . $device_code . '----任务id--' . $taskId);
        return \app\common\service\AutomationBillingService::requestAndCharge(
            $request,
            $scene,
            $userId,
            $taskId,
            $device_code
        );
    }
}
