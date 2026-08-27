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
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\model\aiPersona\AiPersonaWechatInteractionConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\sv\CircleInteractionActionService;
use app\common\service\sv\SvDeviceTaskExistenceService;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 私域互动管家逻辑
 * Class InteractiveLogic    
 * @package app\api\logic\aiPersona
 */
class InteractiveLogic extends BasePersonaLogic
{
    public static function detail(array $params)
    {
        ini_set('max_execution_time', 0);
        try {
            $config = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($config->isEmpty()) {
                self::setError('设备自动化配置不存在');
                return false;
            }

            $personaRule = self::getPersonaRule($config);
            $isAutoGroup = self::getAutoGroupStatus($config);
            $agentConfig = self::ensureAgentConfig((int)$params['persona_id'], (int)self::$uid);

            if ($personaRule->is_wechat_updated === 1) {
                $agentConfig->moments_speech = $personaRule->wechat_comment_speech ?? [];
                $agentConfig->update_time = time();
                $agentConfig->save();
            }

            $find = AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $find->add_friend_script = $personaRule->is_wechat_updated === 1 ? implode("\n", self::normalizeAddFriendScript($personaRule->wechat_add_friend_script)) : $find->add_friend_script;
                $find->is_auto_group = $isAutoGroup;
                // 旧记录未初始化关键词时懒补默认词（NULL=未初始化，[]=用户已清空，不回灌）
                if (is_null($find->getData('group_trigger_keywords'))) {
                    $find->group_trigger_keywords = AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords();
                }
                $find->save();
                $find->clue_count = self::getClues();
                self::$returnData = self::mergeMomentsIntoInteractionResponse($find->toArray(), $agentConfig);
            } else {
                $insertData = [
                    'user_id' => self::$uid,
                    'persona_id' => $params['persona_id'],
                    'add_friend_enabled' => $params['add_friend_enabled'] ?? 1,
                    'add_friend_source' => $params['add_friend_source'] ?? 1,
                    'add_friend_script' => implode("\n", self::normalizeAddFriendScript($personaRule->wechat_add_friend_script ?? [])),
                    'number' => $params['number'] ?? 15,
                    'is_auto_group' => $isAutoGroup,
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'exec_time' =>  [],
                    'exec_date' => $params['exec_date'] ?? date('Y-m-d', time()),
                    'group_trigger_mode' => AiPersonaWechatInteractionConfig::GROUP_TRIGGER_MODE_AI,
                    'group_trigger_keywords' => AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords(),
                ];
                $interaction = AiPersonaWechatInteractionConfig::create($insertData);
                $interaction->clue_count = self::getClues();
                self::$returnData = self::mergeMomentsIntoInteractionResponse($interaction->toArray(), $agentConfig);
            }
            $personaRule->is_wechat_updated = 0;
            $personaRule->save();
            return true;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('device')->write($th->__toString());
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function update(array $params)
    {
        try {
            if ($params['is_greeting'] == 1 && empty($params['greeting_text'])) {
                self::setError('请输入欢迎语');
                return false;
            }
            $groupTriggerMode = (int)($params['group_trigger_mode'] ?? AiPersonaWechatInteractionConfig::GROUP_TRIGGER_MODE_AI);
            $flags = CircleInteractionActionService::normalizeFlags($params['is_like'] ?? 0, $params['is_comment'] ?? 0);
            if ($flags['is_like'] === 0 && $flags['is_comment'] === 0) {
                self::setError('请至少开启点赞或评论其中一项');
                return false;
            }
            $find = AiPersonaWechatInteractionConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('该设备点赞评论任务任务配置不存在');
                return false;
            }

            $rawGroupTriggerKeywords = array_key_exists('group_trigger_keywords', $params)
                ? $params['group_trigger_keywords']
                : ($find->group_trigger_keywords ?: AiPersonaWechatInteractionConfig::getDefaultGroupTriggerKeywords());
            $groupTriggerKeywords = AiPersonaWechatInteractionConfig::normalizeGroupTriggerKeywords($rawGroupTriggerKeywords);
            if ($groupTriggerMode === AiPersonaWechatInteractionConfig::GROUP_TRIGGER_MODE_KEYWORD && empty($groupTriggerKeywords)) {
                self::setError('请添加自定义触发关键词');
                return false;
            }

            $agentConfig = self::ensureAgentConfig((int)$params['persona_id'], (int)self::$uid);
            $momentsAction = array_key_exists('moments_action', $params)
                ? CircleInteractionActionService::normalizeMomentsAction($params['moments_action'])
                : CircleInteractionActionService::flagsToMomentsAction($flags['is_like'], $flags['is_comment']);
            if ($momentsAction === CircleInteractionActionService::ACTION_NONE) {
                self::setError('请至少开启点赞或评论其中一项');
                return false;
            }
            $momentsType = (int)($params['moments_type'] ?? $params['comment_method'] ?? $agentConfig->moments_type ?: 1);
            $momentsType = $momentsType === 2 ? 2 : 1;
            $momentsSpeech = array_key_exists('moments_speech', $params)
                ? $params['moments_speech']
                : ($params['comment_speech'] ?? $agentConfig->moments_speech);
            $momentsSpeech = CircleInteractionActionService::normalizeMomentsSpeech($momentsSpeech);
            if ($momentsType === 2 && empty($momentsSpeech)) {
                $momentsSpeech = CircleInteractionActionService::normalizeMomentsSpeech(
                    \app\common\service\ConfigService::get('wechat_circle', 'comment_speech', [])
                );
            }

            $agentConfig->moments_enabled = (int)($params['moments_enabled'] ?? 1);
            $agentConfig->moments_action = $momentsAction;
            $agentConfig->moments_type = $momentsType;
            if (array_key_exists('moments_agent_id', $params)) {
                $agentConfig->moments_agent_id = (int)$params['moments_agent_id'];
            }
            $agentConfig->moments_speech = $momentsSpeech;
            $agentConfig->update_time = time();
            $agentConfig->save();

            $find->add_friend_enabled = $params['add_friend_enabled'] ?? 1;
            $find->add_friend_source = $params['add_friend_source'] ?? 1;
            $find->add_friend_script = $params['add_friend_script'] ?? '';
            $find->number = $params['number'] ?? 15;
            $find->sales_wechat = $params['sales_wechat'] ?? [];
            $find->group_name_template = $params['group_name_template'] ?? '';
            $find->is_auto_group = self::getAutoGroupStatusByPersonaId((int)$params['persona_id']);
            $find->is_greeting = $params['is_greeting'] ?? 0;
            $find->greeting_text = $params['greeting_text'] ?? '';
            $find->is_share_chats = $params['is_share_chats'] ?? ($find->is_share_chats ?? 1);
            $find->group_trigger_mode = $groupTriggerMode;
            $find->group_trigger_keywords = $groupTriggerKeywords;
            $find->update_time = time();
            if (is_null($find->exec_date)) {
                $find->exec_date = date('Y-m-d', strtotime('+1 day'));
            }
            $find->save();

            CircleInteractionActionService::syncPendingTaskActionByPersona(
                (int)$params['persona_id'],
                $momentsAction
            );

            $find->clue_count = self::getClues();
            self::$returnData = self::mergeMomentsIntoInteractionResponse($find->toArray(), $agentConfig);
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function ensureAgentConfig(int $personaId, int $userId): AiPersonaAgentConfig
    {
        $agentConfig = CircleInteractionActionService::loadPersonaMomentsConfig($personaId, $userId);
        if ($agentConfig !== null) {
            return $agentConfig;
        }

        return AiPersonaAgentConfig::create(AiPersonaAgentConfig::getDefaultConfigData($userId, $personaId));
    }

    /**
     * @param array<string,mixed> $interactionData
     * @return array<string,mixed>
     */
    private static function mergeMomentsIntoInteractionResponse(array $interactionData, AiPersonaAgentConfig $agentConfig): array
    {
        $flags = CircleInteractionActionService::actionToFlags(
            CircleInteractionActionService::normalizeMomentsAction($agentConfig->moments_action)
        );
        $interactionData['is_like'] = $flags['is_like'];
        $interactionData['is_comment'] = $flags['is_comment'];
        $interactionData['comment_method'] = (int)$agentConfig->moments_type === 2 ? 2 : 1;
        $interactionData['comment_speech'] = CircleInteractionActionService::normalizeMomentsSpeech($agentConfig->moments_speech);
        $interactionData['moments_enabled'] = (int)$agentConfig->moments_enabled;
        $interactionData['moments_action'] = CircleInteractionActionService::normalizeMomentsAction($agentConfig->moments_action);
        $interactionData['moments_type'] = (int)$agentConfig->moments_type === 2 ? 2 : 1;
        $interactionData['moments_agent_id'] = (int)$agentConfig->moments_agent_id;
        $interactionData['moments_speech'] = $interactionData['comment_speech'];
        return $interactionData;
    }

    private static function getClues()
    {
        $count = SvAddWechatRecord::where('user_id', self::$uid)->where('status', 4)->group('reg_wechat')->count();
        return $count;
    }

    private static function getAutoGroupStatus(AiPersona $persona): int
    {
        $options = AiPersonaOptionService::getOptionsByPersona($persona);
        return AiPersonaOptionService::isEnabled($options, 'private_operation.options.auto_add_group') ? 1 : 0;
    }

    private static function getAutoGroupStatusByPersonaId(int $personaId): int
    {
        $persona = AiPersona::where('user_id', self::$uid)->where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return 1;
        }

        return self::getAutoGroupStatus($persona);
    }

    private static function normalizeAddFriendScript(mixed $scripts): array
    {
        if (is_array($scripts)) {
            return array_values(array_filter(array_map('strval', $scripts), 'strlen'));
        }

        $script = trim((string)$scripts);
        return $script === '' ? [] : [$script];
    }

    private static function normalizeSchedulePlatforms(mixed $platforms): array
    {
        return is_array($platforms) ? array_values($platforms) : [];
    }

    public static function autoInteractiveTaskCron(SvDevice $device, $taskScenes = null)
    {
        print_r("\n{$device->device_code}自动化互动管家任务生成\n");
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        try {
            $taskScenes = is_array($taskScenes) ? array_map('intval', $taskScenes) : null;
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql(), 'create');
                return $result;
            }


            $options = AiPersonaOptionService::getOptionsByPersona($persona);
            if (!AiPersonaOptionService::isEnabled($options, 'private_operation.status')) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.private_operation.status=0，跳过私域运营任务', 'create');
                return $result;
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            $item = AiPersonaWechatInteractionConfig::where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write($device->device_code . '自动化互动管家任务生成' . ($item->isEmpty() ? \think\facade\Db::getLastSql() :  $item->id), 'create');
            if ($item->isEmpty()) {
                return $result;
            }

            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            $item->persona = $persona;
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT)) {
                if (AiPersonaOptionService::isEnabled($options, 'private_operation.options.circle_config')) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createAutoLikeReplyTask($item));
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.private_operation.options.circle_config=0，跳过朋友圈点赞评论任务', 'create');
                }
            }
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_FRIENDS)) {
                if (AiPersonaOptionService::isEnabled($options, 'private_operation.options.add_friend')) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createAutoAddWechatTask($item));
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.private_operation.options.add_friend=0，跳过自动加好友任务', 'create');
                }
            }
            $item->exec_date = date('Y-m-d', strtotime('+1 day'));
            $item->is_first = 0;
            $item->save();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'create');
            return $result;
        }
    }

    private static function createAutoLikeReplyTask(AiPersonaWechatInteractionConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        $agentConfig = CircleInteractionActionService::loadPersonaMomentsConfig((int)$item->persona_id, (int)$item->user_id);
        if ($agentConfig === null) {
            \think\facade\Log::channel('auto')->write(
                $item->device_code . '朋友圈智能体配置不存在，跳过点赞评论任务生成',
                'create'
            );
            return $result;
        }

        $snapshot = CircleInteractionActionService::buildMomentsTaskSnapshot($agentConfig, (int)$item->user_id);
        if (!$snapshot['ok']) {
            \think\facade\Log::channel('auto')->write(
                $item->device_code . ($snapshot['skip_reason'] ?? '朋友圈配置无效，跳过任务生成'),
                'create'
            );
            return $result;
        }

        $action = (int)$snapshot['action'];
        $robotId = (int)$snapshot['robot_id'];
        $commentType = (int)$snapshot['comment_type'];
        $comment = (string)$snapshot['comment'];

        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $schedules = self::getAutoSchedule($item->persona, 8);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '朋友圈点赞评论任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());

            foreach ($schedules as $key => $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = self::normalizeSchedulePlatforms($schedule->platform);
                if (empty($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $schedule->start_time . '-' . $schedule->end_time;
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT,
                        (int)($platform['account_type'] ?? 0),
                        (string)$item->device_code,
                        '朋友圈点赞评论任务'
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
                            '朋友圈点赞评论任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '朋友圈点赞评论任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $task = SvDeviceCircleLikeReply::create([
                        'user_id' => $item->user_id,
                        'auto_type' => 1,
                        'persona_id' => $item->persona_id,
                        'task_name' => '自动化朋友圈点赞评论任务' . date('YmdHis', time()),
                        'accounts' => $account->toArray(),
                        'time_config' => $execTime,
                        'action' => $action,
                        'number' => $item->number,
                        'interval' => 2,
                        'range' => 0,
                        'robot_id' => $robotId,
                        'auto_reply_config_id' => $item->id,
                        'comment_type' => $commentType,
                        'comment' => $comment,
                        'task_frep' => 0,
                        'create_time' => time(),
                    ]);
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

                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化朋友圈点赞评论任务' . date('YmdHis', time()),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $task->id,
                        'sub_data_id' => $row->id,
                        'persona_id' => $item->persona_id,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_THUMB_COMMENT,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }

            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->remark = '任务执行成功' . date('Y-m-d H:i:s', time());
            $item->update_time = time();
            $item->save();

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('点赞评论任务生成失败：' . $item->device_code . "  \n. " . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->save();
            return $result;
        }
    }



    private static function createAutoAddWechatTask(AiPersonaWechatInteractionConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 9);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '加好友任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());

            foreach ($schedules as $key => $schedule) {
                $st = strtotime($date . ' ' . $schedule->start_time . ':00');
                $et = strtotime($date . ' ' . $schedule->end_time . ':00');

                $platforms = self::normalizeSchedulePlatforms($schedule->platform);
                if (empty($platforms)) {
                    continue;
                }
                $interval = ($et - $st) / count($platforms);
                $sort = array_column($platforms, 'order');
                array_multisort($sort, SORT_ASC, $platforms);
                $execTime = $schedule->start_time . '-' . $schedule->end_time;
                
                foreach ($platforms as $index => $platform) {
                    if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                        DeviceEnum::AUTO_TASK_SCENE_FRIENDS,
                        (int)($platform['account_type'] ?? 0),
                        (string)$item->device_code,
                        '加好友任务'
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
                            '加好友任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                    }

                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_FRIENDS,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '加好友任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $deviceTask = [
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_WECHAT_FRIEND,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'persona_id' => $item->persona_id,
                        'auto_type' => 1,
                        'task_name' => '自动化加微任务' . date('YmdHis', time()),
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => 0,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_FRIENDS,
                        'source' => DeviceEnum::TASK_SOURCE_FRIENDS,
                        'create_time' => time(),
                    ];
                    \app\common\model\sv\SvDeviceTask::create($deviceTask);
                    $result['created']++;
                }
            }

            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化加微任务生成失败：' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->result = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
        }
    }

    private static function shouldCreateScene(?array $taskScenes, int $scene): bool
    {
        return $taskScenes === null || in_array($scene, $taskScenes, true);
    }
}
