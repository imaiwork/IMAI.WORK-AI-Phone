<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvSetting;

use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTakeOverTask;
use app\common\model\sv\SvDeviceTakeOverTaskAccount;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\sv\SvDeviceTaskExistenceService;

use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 私域接管逻辑
 * Class TakeOverLogic    
 * @package app\api\logic\aiPersona
 */
class TakeOverLogic extends BasePersonaLogic
{


    public static function autoTakeOverTaskCron(SvDevice $device, $taskScenes = null)
    {
        print_r("\n{$device->device_code}自动化私信接管任务生成\n");
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        try {
            $taskScenes = is_array($taskScenes) ? array_map('intval', $taskScenes) : null;

            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception( $device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql());
                \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql(), 'create');
                return $result;
            }

            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'customer_service')) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.customer_service=0，跳过智能客服任务', 'create');
                return $result;
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];

            $item = AiPersonaAgentConfig::where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write($device->device_code . '自动化私信接管任务生成' . ($item->isEmpty() ? \think\facade\Db::getLastSql() :  $item->id), 'create');
            if ($item->isEmpty()) {
                return $result;
            }


            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            $item->persona = $persona;

            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createAutoTakeOverTask($item));
            }
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createAutoTakeOverCommentTask($item));
            }
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createSphThumbTask($item));
            }

            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'create');
            return $result;
        }
    }

    /**
     * 创建评论点赞任务账号
     *
     * @param AiPersonaAgentConfig $item
     * @return array|null
     */
    private static function getTakeOverReplyConfig(AiPersonaAgentConfig $item, int $accountType, string $scene): ?array
    {
        $scene = $scene === 'comment' ? 'comment' : 'dm';
        $sceneText = $scene === 'comment' ? '评论' : '私信';
        $config = self::getPlatformTakeOverSceneConfig($item, $accountType, $scene);
        if ($config === null) {
            \think\facade\Log::channel('auto')->write($item->device_code . ' account_type=' . $accountType . ' ' . $sceneText . '配置不存在，跳过接管任务', 'create');
            return null;
        }

        $commentOnlyLike = $scene === 'comment' && (int)($config['comment_only_like'] ?? 0) === 1;
        if ($commentOnlyLike) {
            return [
                'type' => 3,
                'agent_id' => 0,
                'speech' => [],
            ];
        }

        $type = (int)($config['type'] ?? 1);
        $agentId = (int)($config['agent_id'] ?? 0);
        $speech = self::normalizeTakeOverSpeech($config['speech'] ?? []);

        $valid = ($type === 1 && $agentId > 0) || ($type === 2 && !empty($speech));

        if (!$valid) {
            \think\facade\Log::channel('auto')->write($item->device_code . ' account_type=' . $accountType . ' ' . $sceneText . '配置无效，跳过接管任务', 'create');
            return null;
        }

        // 绑定保留,但当前空间不可见/已删除的智能体不再下发任务(切回原团队后自动恢复)
        if ($type === 1 && $agentId > 0) {
            $usability = \app\common\service\aiPersona\AgentConfigService::resolveAgentUsability(
                (int)$item->user_id,
                $agentId
            );
            if (!$usability['usable']) {
                \think\facade\Log::channel('auto')->write(
                    $item->device_code . ' account_type=' . $accountType . ' ' . $sceneText
                    . '智能体不可用 status=' . ($usability['status'] ?? '')
                    . ' agent_id=' . $agentId . '，跳过接管任务',
                    'create'
                );
                return null;
            }
        }

        if ($type === 1) {
            $speech = [];
        }

        if ($type === 2) {
            $agentId = 0;
        }

        if ($type === 3) {
            $agentId = 0;
            $speech = [];
        }

        return [
            'type' => $type,
            'agent_id' => $agentId,
            'speech' => $speech,
        ];
    }

    private static function getPlatformTakeOverSceneConfig(AiPersonaAgentConfig $item, int $accountType, string $scene): ?array
    {
        $rawConfig = $item->getData('platform_agent_config');
        if (self::isEmptyPlatformTakeOverConfig($rawConfig)) {
            $rawConfig = self::buildLegacyPlatformTakeOverConfig($item);
        } elseif (is_string($rawConfig)) {
            $decoded = json_decode($rawConfig, true);
            $rawConfig = is_array($decoded) ? $decoded : null;
        }
        if (!is_array($rawConfig)) {
            return null;
        }

        $platformConfig = $rawConfig[(string)$accountType] ?? $rawConfig[$accountType] ?? null;
        if (!is_array($platformConfig)) {
            return null;
        }

        $sceneConfig = $platformConfig[$scene] ?? null;
        return is_array($sceneConfig) ? $sceneConfig : null;
    }

    private static function isEmptyPlatformTakeOverConfig(mixed $rawConfig): bool
    {
        if ($rawConfig === null) {
            return true;
        }

        if (is_string($rawConfig)) {
            $rawConfig = trim($rawConfig);
            if ($rawConfig === '') {
                return true;
            }

            $decoded = json_decode($rawConfig, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            return $decoded === null || $decoded === [];
        }

        if (is_array($rawConfig)) {
            return $rawConfig === [];
        }

        return false;
    }

    private static function buildLegacyPlatformTakeOverConfig(AiPersonaAgentConfig $item): array
    {
        $wechatDmConfig = self::buildLegacyTakeOverReplyItem(
            (int)$item->getData('wechat_chat_type'),
            (int)$item->getData('wechat_chat_agent_id'),
            $item->getData('wechat_chat_speech')
        );
        $socialDmConfig = self::buildLegacyTakeOverReplyItem(
            (int)$item->getData('dm_type'),
            (int)$item->getData('dm_agent_id'),
            $item->getData('dm_speech')
        );
        $commentConfig = self::buildLegacyTakeOverReplyItem(
            (int)$item->getData('comment_type'),
            (int)$item->getData('comment_agent_id'),
            $item->getData('comment_speech'),
            true
        );

        $config = [];
        foreach (AiPersonaAgentConfig::PLATFORM_ACCOUNT_TYPES as $platformType) {
            $config[$platformType] = [
                'dm' => $platformType === AiPersonaAgentConfig::WECHAT_PLATFORM_TYPE ? $wechatDmConfig : $socialDmConfig,
                'comment' => $commentConfig,
            ];
        }

        return $config;
    }

    private static function buildLegacyTakeOverReplyItem(int $type, int $agentId, mixed $speech, bool $withCommentOnlyLike = false): array
    {
        $type = (int)$type;
        $commentOnlyLike = $withCommentOnlyLike && $type === 3;
        if (!in_array($type, [1, 2], true)) {
            $type = 1;
        }

        $item = [
            'type' => $type,
            'speech' => self::normalizeTakeOverSpeech($speech),
            'agent_id' => (int)$agentId,
        ];

        if ($withCommentOnlyLike) {
            $item['comment_only_like'] = $commentOnlyLike ? 1 : 0;
        }

        return $item;
    }

    private static function normalizeTakeOverSpeech(mixed $speech): array
    {
        if (is_string($speech)) {
            $decoded = json_decode($speech, true);
            $speech = is_array($decoded) ? $decoded : [$speech];
        }

        if (!is_array($speech)) {
            return [];
        }

        $result = [];
        foreach ($speech as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return $result;
    }

    private static function createSphThumbTask(AiPersonaAgentConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();

        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 15);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '视频号点赞任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());

            foreach ($schedules as $key => $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = $schedule->platform;
                if (empty($platforms) || !is_array($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $schedule->start_time . '-' . $schedule->end_time;
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE,
                        (int)($platform['account_type'] ?? 0),
                        (string)$item->device_code,
                        '视频号点赞任务'
                    )) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '视频号点赞任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '视频号点赞任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $task_name = "自动化视频号点赞任务" . date('mdHis', time());
                    $task = SvDeviceTakeOverTask::create([
                        'user_id' => $item->user_id,
                        'task_name' => $task_name,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'accounts' => json_encode($account->toArray(), JSON_UNESCAPED_UNICODE),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'comment_type' => 3,
                        'comment_speech' => $item->comment_speech,
                        'comment_robot_id' => $item->comment_agent_id,
                        'status' => 0,
                        'task_frep' => 0,
                        'create_time' => time(),
                    ]);

                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => $item->user_id,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'robot_id' => (int)$account->type === 1 ? $item->wechat_chat_agent_id : $item->dm_agent_id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_SPH_THUMB,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $task_name,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_COMMENT_LIKE,
                        'source' => DeviceEnum::TASK_SOURCE_SPH_THUMB,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }
            
            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化视频号点赞任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }

    private static function createAutoTakeOverCommentTask(AiPersonaAgentConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 11);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '评论接管任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());

            foreach ($schedules as $key => $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = $schedule->platform;
                if (empty($platforms) || !is_array($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $schedule->start_time . '-' . $schedule->end_time;

                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER,
                        (int)($platform['account_type'] ?? 0),
                        (string)$item->device_code,
                        '评论接管任务'
                    )) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '评论接管任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    $replyConfig = self::getTakeOverReplyConfig($item, (int)$account->type, 'comment');
                    if ($replyConfig === null) {
                        continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '评论接管任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $task_name = "自动化评论接管任务" . date('mdHis', time());

                    $task = SvDeviceTakeOverTask::create([
                        'user_id' => $item->user_id,
                        'task_name' => $task_name,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'accounts' => json_encode($account->toArray(), JSON_UNESCAPED_UNICODE),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'comment_type' => $replyConfig['type'],
                        'comment_speech' => $replyConfig['speech'],
                        'comment_robot_id' => $replyConfig['agent_id'],
                        'task_type' => 1,
                        'status' => 0,
                        'task_frep' => 0,
                        'create_time' => time(),
                    ]);

                    
                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => $item->user_id,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'robot_id' => $replyConfig['agent_id'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_TAKE_OVER,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $task_name,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_COMMENT_TAKE_OVER,
                        'source' => DeviceEnum::TASK_SOURCE_TAKEOVER,
                        'create_time' => time(),
                    ]);
                    $result['created']++;

                }
            }
            
            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化评论接管任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }

    private static function createAutoTakeOverTask(AiPersonaAgentConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 6);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '私信接管任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());

            foreach ($schedules as $key => $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = $schedule->platform;
                if (empty($platforms) || !is_array($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $schedule->start_time . '-' . $schedule->end_time;
                
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER,
                        (int)($platform['account_type'] ?? 0),
                        (string)$item->device_code,
                        '私信接管任务'
                    )) {
                        continue;
                    }
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '私信接管任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    $replyConfig = self::getTakeOverReplyConfig($item, (int)$account->type, 'dm');
                    if ($replyConfig === null) {
                        continue;
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '私信接管任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $task_name = "自动化私信接管任务" . date('mdHis', time());

                    $task = SvDeviceTakeOverTask::create([
                        'user_id' => $item->user_id,
                        'task_name' => $task_name,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'accounts' => json_encode($account->toArray(), JSON_UNESCAPED_UNICODE),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'message_type' => $replyConfig['type'],
                        'message_speech' => $replyConfig['speech'],
                        'message_robot_id' => $replyConfig['agent_id'],
                        'task_type' => 2,
                        'status' => 0,
                        'task_frep' => 0,
                        'create_time' => time(),
                    ]);

                    
                    $row = SvDeviceTakeOverTaskAccount::create([
                        'take_over_id' => $task->id,
                        'user_id' => $item->user_id,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'robot_id' => $replyConfig['agent_id'],
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'status' => 0,
                    ]);
                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_TAKE_OVER,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $task_name,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER,
                        'source' => DeviceEnum::TASK_SOURCE_TAKEOVER,
                        'create_time' => time(),
                    ]);
                    $result['created']++;

                }
            }
            
            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化私信接管任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }

    private static function shouldCreateScene(?array $taskScenes, int $scene): bool
    {
        return $taskScenes === null || in_array($scene, $taskScenes, true);
    }
}
