<?php


namespace app\api\logic\aiPersona;

use think\facade\Db;
use app\api\logic\ApiLogic;
use app\api\logic\sv\ToolsLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;

use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\aiPersona\AiPersonaAgentConfig;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\aiPersona\SphClueKeywordService;
use app\common\service\sv\SvDeviceTaskExistenceService;

/**
 * 客户触达自动任务逻辑
 * Class ClueTouchLogic    
 * @package app\api\logic\aiPersona
 */
class ClueTouchLogic extends BasePersonaLogic
{
    public static function detail(array $params)
    {
        ini_set('max_execution_time', 0);
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            $personaRule = self::getPersonaRule($persona);
            //print_r($personaRule->toArray());die;
            $config = AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$config->isEmpty()) {
                \think\facade\Log::channel('auto')->write('更新 -- 客户触达自动任务配置' . json_encode($config->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'clue');
                \think\facade\Log::channel('auto')->write('更新 -- 客户触达自动任务配置'. json_encode($personaRule->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'clue');
                $config->clue_keywords = $personaRule->is_clue_updated == 1 || empty($config->clue_keywords) ? $personaRule->clue_keywords : $config->clue_keywords;
                $config->acquire_keywords = $personaRule->is_clue_updated == 1 || empty($config->acquire_keywords) ? $personaRule->clue_acquire_keywords : $config->acquire_keywords;
                $config->intercept_keywords = $personaRule->is_clue_updated == 1 || empty($config->intercept_keywords) ? $personaRule->clue_intercept_keywords : $config->intercept_keywords;
                $config->comment_scripts = $personaRule->is_clue_updated == 1 || empty($config->comment_scripts) ? $personaRule->clue_comment_scripts : $config->comment_scripts;
                $config->dm_scripts = $personaRule->is_clue_updated == 1 || empty($config->dm_scripts) ? $personaRule->clue_dm_scripts : $config->dm_scripts;
                $group_buy_config = $config->group_buy_config ?? [];

                if (isset($group_buy_config['group_buy_method'])) {
                    $group_buy_config['group_buy_method'] = 2;
                }

                $config->group_buy_config = $group_buy_config;
                $config->content_publish_day = AiPersonaTrafficConfig::normalizeContentPublishDay($config->content_publish_day);
                $config->update_time = time();
                $config->save();

                $personaRule->is_clue_updated = 0;
                $personaRule->save();
                self::$returnData = $config->toArray();
                return true;
            } else {
                \think\facade\Log::channel('auto')->write('创建 -- 客户触达自动任务配置' . json_encode($personaRule->toArray(), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), 'clue');
                
                $insertData = [
                    'user_id' => self::$uid,
                    'persona_id' => $params['persona_id'],
                    'clue_keywords' => $personaRule->clue_keywords,
                    'acquire_keywords' => $personaRule->clue_acquire_keywords,
                    'intercept_keywords' => $personaRule->clue_intercept_keywords,
                    'comment_scripts' => $personaRule->clue_comment_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'dm_scripts' => $personaRule->clue_dm_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'message_number' => $params['message_number'] ?? 15,
                    'comment_number' => $params['comment_number'] ?? 15,
                    'reply_number' => $params['reply_number'] ?? 0,
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($params['content_publish_day'] ?? 0),
                    'comment_publish_day' => $params['comment_publish_day'] ?? 0,
                    'intercept_max_number' => $params['intercept_max_number'] ?? 10,
                    'intercept_keyword_used_type' => $params['intercept_keyword_used_type'] ?? 2,
                    'clue_max_number' => $params['clue_max_number'] ?? 10,
                    'clue_keyword_used_type' => $params['clue_keyword_used_type'] ?? 2,
                    'group_buy_config' => ['group_buy_method' => 2],
                    'same_city_config' => [],
                    'video_cutoff_number' => $params['video_cutoff_number'] ?? 30,
                    'city_cutoff_number' => $params['city_cutoff_number'] ?? 30,
                    'group_cutoff_number' => $params['group_cutoff_number'] ?? 30,
                    'is_first' => 1,
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'create_time' => time(),
                ];
                $result = AiPersonaTrafficConfig::create($insertData);
                self::$returnData = $result->toArray();
            }
            $personaRule->is_clue_updated = 0;
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
            $find = AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                // if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                //     self::setError('该IP人设获客与截流任务正在执行，不可修改，稍后再试');
                //     return false;
                // }
                $find->clue_keywords = $params['clue_keywords'] ?? [];
                $find->acquire_keywords = $params['acquire_keywords'] ?? [];
                $find->intercept_keywords = $params['intercept_keywords'] ?? [];
                $find->comment_scripts = $params['comment_scripts'] ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []);
                $find->dm_scripts = $params['dm_scripts'] ?? \app\common\service\ConfigService::get('touch_clue',  'touch_dm',  []);
                $find->message_number = $params['message_number'] ?? 15;
                $find->comment_number = $params['comment_number'] ?? 15;
                $find->reply_number = $params['reply_number'] ?? 0;
                $find->content_publish_day = AiPersonaTrafficConfig::normalizeContentPublishDay($params['content_publish_day'] ?? 0);
                $find->comment_publish_day = $params['comment_publish_day'] ?? 0;
                $find->intercept_max_number = $params['intercept_max_number'] ?? 10;
                $find->intercept_keyword_used_type = $params['intercept_keyword_used_type'] ?? 2;
                $find->clue_max_number = $params['clue_max_number'] ?? 10;
                $find->clue_keyword_used_type = $params['clue_keyword_used_type'] ?? 2;
                $find->group_buy_config = $params['group_buy_config'] ?? [];
                $find->same_city_config = $params['same_city_config'] ?? [];
                $find->video_cutoff_number = $params['video_cutoff_number'] ?? 30;
                $find->city_cutoff_number = $params['city_cutoff_number'] ?? 30;
                $find->group_cutoff_number = $params['group_cutoff_number'] ?? 30;

                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->update_time = time();

                if (is_null($find->exec_date)) {
                    $find->exec_date = date('Y-m-d', time());
                }
                $find->save();
            } else {
                self::setError('该IP人设获客与截流配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }










    public static function trafficTaskCron(SvDevice $device, $taskScenes = null)
    {
        print_r("\n{$device->device_code}自动化截流任务生成\n");
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        try {
            $taskScenes = is_array($taskScenes) ? array_map('intval', $taskScenes) : null;
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                //throw new \Exception( $device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql());
                \think\facade\Log::channel('auto')->write($device->device_code . 'IP人设不存在:' . \think\facade\Db::getLastSql(), 'create');
                return $result;
            }

            $options = AiPersonaOptionService::getOptionsByPersona($persona);
            if (!AiPersonaOptionService::isEnabled($options, 'auto_clues.status')) {
                \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.status=0，跳过获客截流任务', 'create');
                return $result;
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            //$where[] = ['exec_date', '<=', date('Y-m-d', time())];

            $item = AiPersonaTrafficConfig::where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write($device->device_code . '自动化获客与截流任务生成' . ($item->isEmpty() ? \think\facade\Db::getLastSql() :  $item->id), 'create');
            if ($item->isEmpty()) {
                return $result;
            }

            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            $item->persona = $persona;
            if (self::shouldCreateAnyScene($taskScenes, [
                DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG,
                DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE,
                DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT,
            ])) {
                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.video_shutoff')) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createTouchTask($item, $taskScenes));
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.video_shutoff=0，跳过截流任务', 'create');
                }
            }
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE)) {
                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.sph_clues')) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createClueTaskCron($item));
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.sph_clues=0，跳过视频号获客任务', 'create');
                }
            }
            if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.city_clues')) {
                if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE)) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createSameCityExposureTaskCron($item)); // 同城曝光
                }
                if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF)) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createSameCityCutoffTaskCron($item)); // 同城截流
                }
            } else {
                \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.city_clues=0，跳过同城曝光和同城截流任务', 'create');
            }
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY)) {
                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.group_clues')) {
                    $result = SvDeviceTaskExistenceService::mergeSlotResult($result, self::createGroupBuyTaskCron($item)); // 团购截流
                } else {
                    \think\facade\Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.group_clues=0，跳过团购截流任务', 'create');
                }
            }


            $item->exec_date = date('Y-m-d', strtotime('+1 day'));
            $item->save();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'create');
            return $result;
        }
    }

    private static function createSameCityExposureTaskCron(AiPersonaTrafficConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        \think\facade\Log::channel('auto')->write($item->device_code . '同城曝光任务生成', 'create');
        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 12);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '同城曝光任务');
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
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '同城曝光任务'
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
                        DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '同城曝光任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $taskName = '自动化同城曝光任务' . date('YmdHis', time());
                    //团购截流任务
                    $params = [
                        'user_id' => $item->user_id,
                        'persona_id' => $item->persona_id,
                        'task_type' => 3,
                        'name' => $taskName,
                        'accounts' => json_encode([[
                            'account' => $account->account,
                            'type' => $account->type,
                        ]], JSON_UNESCAPED_UNICODE),
                        'status' => 1,
                        'account_feature' => 1,
                        'radius' => 5,
                        'visit_num' => 100,
                        'interval_time' => 10,
                        'task_start_time' => $startTime,
                        'task_end_time' => $endTime,
                        'task_frequency' => 1,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'task_date' => [
                            date('Y-m-d', $startTime)
                        ],
                        'persona_ids' => $item->persona_id,
                        'create_time' => time(),

                    ];
                    $task = \app\common\model\sv\SvCityExposureTask::create($params);

                    $accountItem = \app\common\model\sv\SvCityExposureTaskAccount::create([
                        'user_id' => $item->user_id,
                        'task_type' => 3,
                        'city_exposure_id' => $task->id,
                        'status' => 0,
                        'name' => $taskName,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'send_start_time' => $startTime,
                        'send_end_time' => $endTime,
                        'count' => 50,
                        'published_count' => 0,
                        'persona_id' => $item->persona_id,
                        'create_time' => time(),
                    ]);

                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_SAME_CITY_EXPOSURE,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $taskName,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'persona_id' => $item->persona_id,
                        'status' => 0,
                        'sub_task_id' => $accountItem->id,
                        'source' => DeviceEnum::TASK_SOURCE_SAME_CITY_EXPOSURE,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }


            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化同城曝光任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }



    private static function createSameCityCutoffTaskCron(AiPersonaTrafficConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        \think\facade\Log::channel('auto')->write($item->device_code . '同城截流任务生成', 'create');
        Db::startTrans();
        try {
            $schedules = self::getAutoSchedule($item->persona, 13);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '同城截流任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());
            $cityConfig = is_array($item->same_city_config) ? $item->same_city_config : [];
            $cityConfig = self::ensureInteractiveAction($cityConfig);

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
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '同城截流任务'
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
                        DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '同城截流任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $taskName = '自动化同城视频截流任务' . date('YmdHis', time());
                    //团购截流任务
                    $params = [
                        'user_id' => $item->user_id,
                        'task_type' => in_array(3, $cityConfig['interactive_action']) ? 1 : 2,
                        'name' => $taskName,
                        'accounts' => json_encode([[
                            'account' => $account->account,
                            'type' => $account->type,
                        ]], JSON_UNESCAPED_UNICODE),
                        'marker_method' => $cityConfig['interactive_action'],
                        'chat_type' => 1,
                        'radius' => $cityConfig['range'] ?? 5,
                        'interval_time' => $cityConfig['touch_interval'] ?? 10,
                        'watch_time' => $cityConfig['view_video_time'] ?? 10,
                        'gender' => self::getGender($cityConfig['gender'] ?? 0),
                        'old' => json_encode($cityConfig['age_range'] ?? [
                            'min' => 18,
                            'max' => 30,
                        ], JSON_UNESCAPED_UNICODE),
                        'region' => '不限',
                        'city' => '不限',
                        'send_num' => $cityConfig['exec_number'] ?? 50,
                        'like_num' => $cityConfig['filter_video_thumb_num'] ?? 10,
                        'comment_num' => $cityConfig['filter_video_comment_num'] ?? 10,
                        'comment_fans_num' => json_encode($cityConfig['filter_comment_fans'] ?? [
                            'min' => 0,
                            'max' => 10000,
                        ], JSON_UNESCAPED_UNICODE),
                        'comment_follow_num' => json_encode($cityConfig['filter_comment_follow'] ?? [
                            'min' => 0,
                            'max' => 100000,
                        ], JSON_UNESCAPED_UNICODE),
                        'filter' => $item->intercept_keywords,
                        'nickname_filter' => $cityConfig['filter_nickname'] ?? [],
                        'task_start_time' => $startTime,
                        'task_end_time' => $endTime,
                        'task_frequency' => 0,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'task_date' => [$date],
                        'persona_id' => $item->persona_id,
                        'status' => 1,
                        'task_exec_type' => 0,
                        'minutes' => 0,
                        'create_time' => time(),

                    ];
                    $task = \app\common\model\sv\SvCityTouchTask::create($params);

                    $accountItem = \app\common\model\sv\SvCityTouchTaskAccount::create([
                        'user_id' => $item->user_id,
                        'task_type' => $params['task_type'],
                        'city_touch_id' => $task->id,
                        'status' => 0,
                        'name' => $taskName,
                        'account' => $account->account,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'account_type' => $account->type,
                        'device_code' => $item->device_code,
                        'send_start_time' => $startTime,
                        'send_end_time' => $endTime,
                        'count' => $cityConfig['exec_number'] ?? 50,
                        'published_count' => 0,
                        'persona_id' => $item->persona_id,
                        'create_time' => time(),
                    ]);

                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_SAME_CITY_CUTOFF,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $taskName,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'persona_id' => $item->persona_id,
                        'status' => 0,
                        'sub_task_id' => $accountItem->id,
                        'source' => DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化同城截流任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }



    private static function createGroupBuyTaskCron(AiPersonaTrafficConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        \think\facade\Log::channel('auto')->write($item->device_code . '团购截流任务生成', 'create');
        Db::startTrans();
        try {
            $schedules = self::getAutoSchedule($item->persona, 14);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '团购截流任务');
                Db::commit();
                return $result;
            }
            $date = date('Y-m-d', time());
            $groupConfig = is_array($item->group_buy_config) ? $item->group_buy_config : [];
            $groupConfig = self::ensureInteractiveAction($groupConfig);
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
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '团购截流任务'
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
                        DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY,
                        (int)$account->type,
                        $startTime,
                        $endTime,
                        '团购截流任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    $taskName = '自动化团购截流任务' . date('YmdHis', time());
                    //团购截流任务
                    $params = [
                        'user_id' => $item->user_id,
                        'task_type' => in_array(3, $groupConfig['interactive_action']) ? 1 : 2,
                        'group_buy_type' => $groupConfig['group_buy_method'] ?? 2,
                        'name' => $taskName,
                        'status' => 1,
                        'accounts' => json_encode([[
                            'account' => $account->account,
                            'type' => $account->type,
                        ]], JSON_UNESCAPED_UNICODE),
                        'account_feature' => 1,
                        'marker_method' => $groupConfig['interactive_action'],
                        'chat_type' => 1,
                        'like_type' => 1,
                        'group_type' => $groupConfig['group_buy_keyword'] ?? '',
                        'send_num' => $groupConfig['exec_number'] ?? 50,
                        'radius' => $groupConfig['radius'] ?? 5,
                        'interval_time' => $groupConfig['touch_interval'] ?? 60,
                        'watch_time' => $groupConfig['view_video_time'] ?? 60,
                        'content_publish_day' => $groupConfig['group_publish_day'] ?? 1,
                        'comment_offset' => $groupConfig['group_num_comment'] ?? 0,
                        'gender' => self::getGender($groupConfig['gender'] ?? 0),
                        'old' => json_encode($groupConfig['age_range'] ?? [
                            'min' => 10,
                            'max' => 60,
                        ], JSON_UNESCAPED_UNICODE),
                        'region' => '不限',
                        'city' => '不限',
                        'comment_keyword' => $groupConfig['comment_keywords'] ?? [],
                        'filter' => $groupConfig['comment_keywords'] ?? [],
                        'nickname_filter' => $groupConfig['filter_nickname'] ?? [],
                        'task_start_time' => $startTime,
                        'task_end_time' => $endTime,
                        'task_frequency' => 1,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'task_date' => [$date],
                        'persona_id' => $item->persona_id,
                        'task_exec_type' => 0,
                        'minutes' => 0,
                        'create_time' => time(),

                    ];
                    $task = \app\common\model\sv\SvGroupBuyTask::create($params);

                    $accountItem = \app\common\model\sv\SvGroupBuyTaskAccount::create([
                        'user_id' => $item->user_id,
                        'task_type' => $groupConfig['group_buy_method'] ?? 1,
                        'group_buy_id' => $task->id,
                        'status' => 0,
                        'name' => $taskName,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'device_code' => $item->device_code,
                        'send_start_time' => $startTime,
                        'send_end_time' => $endTime,
                        'count' => $groupConfig['exec_number'] ?? 50,
                        'published_count' => 0,
                        'persona_id' => $item->persona_id,
                        'create_time' => time(),
                    ]);

                    \app\common\model\sv\SvDeviceTask::create([
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_GROUP_BUY,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => $taskName,
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'persona_id' => $item->persona_id,
                        'status' => 0,
                        'sub_task_id' => $accountItem->id,
                        'source' => DeviceEnum::TASK_SOURCE_GROUP_BUY,
                        'create_time' => time(),
                    ]);
                    $result['created']++;
                }
            }

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化团购截流任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }




    private static function createClueTaskCron(AiPersonaTrafficConfig $item): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        print_r("\n{$item->device_code}自动化获客任务生成\n");
        Db::startTrans();
        try {

            $schedules = self::getAutoSchedule($item->persona, 4);
            if ($schedules->isEmpty()) {
                SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, '视频号获客任务');
                Db::commit();
                return $result;
            }

            $date = date('Y-m-d', time());
            $persona = $item->persona instanceof AiPersona
                ? $item->persona
                : AiPersona::where('id', $item->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在');
            }

            $takenKeywords = [];
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
                    $startTime = $st + $index * $interval;
                    $endTime = $startTime + $interval;
                    $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                    if ($account->isEmpty()) {
                        SvDeviceTaskExistenceService::bumpMissingAccount(
                            $result,
                            (string)$item->device_code,
                            (int)$platform['account_type'],
                            '视频号获客任务'
                        );
                        continue;
                    }
                    $time = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                    if ($endTime < time()) {
                        \think\facade\Log::channel('auto')->write($item->device_code . '该账号类型[' . $account->type . ']时间[' . $time . ']已过期', 'create');
                        //continue;
                    }

                    $taskStartTime = $startTime;
                    $taskEndTime = $endTime;
                    if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                        (int)$item->user_id,
                        (string)$item->device_code,
                        (int)$item->persona_id,
                        DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE,
                        (int)$account->type,
                        $taskStartTime,
                        $taskEndTime,
                        '视频号获客任务'
                    )) {
                        $result['skipped_existing']++;
                        continue;
                    }

                    try {
                        $keyword = SphClueKeywordService::takeKeyword($item, $persona, $takenKeywords);
                    } catch (\Throwable $keywordEx) {
                        \think\facade\Log::channel('auto')->write(
                            $item->device_code . '视频号获客取词失败: ' . $keywordEx->getMessage(),
                            'create'
                        );
                        continue;
                    }

                    $takenKeywords[] = $keyword;
                    $slotKeywords = [$keyword];
                    $params = [
                        'name' => '自动化视频号获客任务' . date('YmdHis', time()),
                        'device_codes' => json_encode([$item->device_code], JSON_UNESCAPED_UNICODE),
                        'persona_id' => $item->persona_id,
                        'type' => 1,
                        'keywords' => json_encode($slotKeywords, JSON_UNESCAPED_UNICODE),
                        'chat_type' => 0,
                        'chat_number' => 0,
                        'chat_interval_time' => 0,
                        'add_type' => 1,
                        'remark' => '',
                        'add_number' => 0,
                        'add_interval_time' => 10,
                        'greeting_content' => '',
                        'crawl_type' => 1,
                        'source' => 2, //1手动创建2自动化任务创建
                        'private_message_prompt' => '',
                        'add_friends_prompt' => '',
                        'wechat_id' => $account->account,
                        'wechat_reg_type' => 0,
                        'ocr_type' => 1,
                        'exec_time' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'user_id' => $item->user_id,
                        'implementation_keywords_number' => count($slotKeywords),
                        'status' => 0,
                        'exec_add_count' => 15,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'auto_type' => 1,
                        'remarks' => json_encode(\app\common\service\ConfigService::get('add_remark', 'wechat', []), JSON_UNESCAPED_UNICODE),
                    ];
                    $task = \app\common\model\sv\SvCrawlingTask::create($params);
                    $bindData = [
                        'user_id'     => $item->user_id,
                        'task_id'     => $task->id,
                        'device_code' => $item->device_code,
                        'persona_id' => $item->persona_id,
                        'keywords'    => json_encode($slotKeywords, JSON_UNESCAPED_UNICODE),
                        'create_time' => time(),
                        'update_time' => time(),
                        'status'      => 1,
                        'auto_type'   => 1,
                    ];
                    $bind = \app\common\model\sv\SvCrawlingTaskDeviceBind::create($bindData);

                    $deviceTask = [
                        'user_id' => $item->user_id,
                        'device_code' => $item->device_code,
                        'task_type' => DeviceEnum::AUTO_TYPE_CLUES,
                        'account' => $account->account,
                        'account_type' => 1,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'task_name' => '自动化' . DeviceEnum::getTaskSceneDesc(DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE) . '任务' . date('YmdHis', time()),
                        'task_scene' => DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $task->start_time,
                        'end_time' => $task->end_time,
                        'day' => date('Y-m-d', $task->start_time),
                        'status' => 0,
                        'sub_task_id' => $task->id,
                        'persona_id' => $item->persona_id,
                        'source' => DeviceEnum::TASK_SOURCE_CLUES,
                        'create_time' => time(),
                    ];
                    \app\common\model\sv\SvDeviceTask::create($deviceTask);
                    $result['created']++;
                }
            }


            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化获客任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            // 回滚后恢复词语库内存态，避免把补库写回的词继续留在当前对象上
            $freshConfig = AiPersonaTrafficConfig::where('id', $item->id)->findOrEmpty();
            if (!$freshConfig->isEmpty()) {
                $item->clue_keywords = $freshConfig->clue_keywords;
            }
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }


    /**
     * @notes 将关键词数组均分到多个子数组中
     * @param array $keywords 关键词数组
     * @param int $groupCount 分组数量
     * @return array 二维数组，包含均分后的关键词组
     * @author 系统
     * @date 2026/03/12
     */
    private static function splitKeywordsIntoGroups(array $keywords, int $groupCount = 4): array
    {
        // 如果关键词为空或不是数组，返回空数组
        if (empty($keywords) || !is_array($keywords)) {
            return [];
        }

        // 确保分组数量至少为1
        $groupCount = max(1, $groupCount);

        // 初始化分组数组
        $groups = array_fill(0, $groupCount, []);

        // 计算关键词数量
        $keywordCount = count($keywords);

        // 将关键词分配到各个组，当关键词数量小于分组数量时重复使用
        for ($i = 0; $i < $groupCount; $i++) {
            // 计算当前分组应该使用的关键词索引（循环使用）
            $keywordIndex = $i % $keywordCount;
            $groups[$i][] = $keywords[$keywordIndex];
        }

        // 分配剩余的关键词
        $index = $groupCount;
        while ($index < $keywordCount) {
            $groupIndex = $index % $groupCount;
            $keywordIndex = $index % $keywordCount;
            $groups[$groupIndex][] = $keywords[$keywordIndex];
            $index++;
        }

        return $groups;
    }

    /**
     * @notes 关键词轮询：优先使用昨日未执行过的关键词
     * @param array $keywords 关键词数组
     * @param int $userId 用户ID
     * @param string $deviceCode 设备编码
     * @param string $modelClass 模型类全名
     * @param string $field 记录字段名
     * @param int|null $taskType 任务类型（可选）
     * @return array 轮询后的关键词数组
     * @author 系统
     * @date 2026/07/08
     */
    private static function rotateKeywords(
        array $keywords,
        int $userId,
        string $deviceCode,
        string $modelClass,
        string $field,
        ?int $taskType = null
    ): array {
        if (empty($keywords)) {
            return $keywords;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayStart = strtotime($yesterday . ' 00:00:00');
        $yesterdayEnd = strtotime($yesterday . ' 23:59:59');

        $model = new $modelClass();
        $query = $model->where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->whereBetween('create_time', [$yesterdayStart, $yesterdayEnd])
            ->whereIn($field, $keywords);

        if ($taskType !== null) {
            $query->where('task_type', $taskType);
        }

        $executedKeywords = $query->group($field)->column($field);

        if (empty($executedKeywords)) {
            return $keywords;
        }

        $executedKeywordMap = array_flip(array_map('strval', $executedKeywords));
        $unusedKeywords = [];
        $usedKeywords = [];
        foreach ($keywords as $keyword) {
            if (isset($executedKeywordMap[(string)$keyword])) {
                $usedKeywords[] = $keyword;
            } else {
                $unusedKeywords[] = $keyword;
            }
        }

        if (empty($unusedKeywords)) {
            shuffle($keywords);
            return $keywords;
        }

        return array_merge($unusedKeywords, $usedKeywords);
    }

    /**
     * @notes 生成截流任务-私信
     * @param AiPersonaTrafficConfig $item
     * @author 系统
     * @date 2026/03/12
     */
    private static function createTouchTask(AiPersonaTrafficConfig $item, ?array $taskScenes = null): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        \think\facade\Log::channel('auto')->write($item->device_code . '截流任务生成', 'create');
        Db::startTrans();
        try {
            if (empty($item->acquire_keywords)) {
                \think\facade\Log::channel('auto')->write('该设备截流行业词为空' . $item->device_code, 'create');
                Db::commit();
                return $result;
            }

            $agentConfig = \app\common\model\aiPersona\AiPersonaAgentConfig::where([
                'persona_id'  => $item->persona_id,
                'user_id'     => $item->user_id,
                'delete_time' => null
            ])->findOrEmpty();
            if ($agentConfig->isEmpty()) {
                \think\facade\Log::channel('auto')->write('该设备没有绑定智能体' . $item->device_code, 'create');
                Db::commit();
                return $result;
            }

            $schedules = self::getAutoSchedule($item->persona, 2);
            ///截流私信
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult(
                    $result,
                    self::createTouchSceneTask($schedules, $item, $agentConfig, DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG, '自由', 0)
                );
            }
            //截流留痕获客
            $schedules = self::getAutoSchedule($item->persona, 3);
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult(
                    $result,
                    self::createTouchSceneTask($schedules, $item, $agentConfig, DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE, '自由', 0)
                );
            }

            $schedules = self::getAutoSchedule($item->persona, 1);
            if (self::shouldCreateScene($taskScenes, DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT)) {
                $result = SvDeviceTaskExistenceService::mergeSlotResult(
                    $result,
                    self::createTouchSceneTask($schedules, $item, $agentConfig, DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT, '自由', 0)
                );
            }



            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->remark = '任务生成成功' . date('Y-m-d H:i:s', time());
            $item->update_time = time();
            $item->save();
            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化截流任务生成' . $item->device_code . ' ' . $th->__toString(), 'create');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            return $result;
            //throw new \Exception($th->getMessage());
        }
    }

    private static function createTouchSceneTask(
        \think\Collection $schedules,
        AiPersonaTrafficConfig $item,
        AiPersonaAgentConfig $agentConfig,
        int $scene,
        string $typeName,
        int $industryType
    ): array {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        $date = date('Y-m-d', time());
        $taskLabel = '截流' . DeviceEnum::getTaskSceneDesc($scene) . '任务';
        if ($schedules->isEmpty()) {
            SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$item->device_code, $taskLabel);
            return $result;
        }
        $acquireKeywords = is_array($item->acquire_keywords) ? $item->acquire_keywords : [];
        $acquireKeywords = self::rotateKeywords(
            $acquireKeywords,
            $item->user_id,
            $item->device_code,
            \app\common\model\sv\SvLeadScrapingRecord::class,
            'industry_keyword',
            $scene
        );
        foreach ($schedules as $schedule) {
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
                $startTime = $st + $index * $interval;
                $endTime = $startTime + $interval;
                $account =  SvAccount::field('id,account,type,nickname,avatar')->where('type', $platform['account_type'])->where('user_id', $item->user_id)->where('device_code', $item->device_code)->findOrEmpty();
                if ($account->isEmpty()) {
                    SvDeviceTaskExistenceService::bumpMissingAccount(
                        $result,
                        (string)$item->device_code,
                        (int)$platform['account_type'],
                        $taskLabel
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
                    $scene,
                    (int)$account->type,
                    $startTime,
                    $endTime,
                    $taskLabel
                )) {
                    $result['skipped_existing']++;
                    continue;
                }

                //截流任务
                $params = [
                    'user_id' => $item->user_id,
                    'persona_id' => $item->persona_id,
                    'task_type' => $scene,
                    'name' => '自动化截流' . DeviceEnum::getTaskSceneDesc($scene) . $typeName . '任务' . date('YmdHis', time()),
                    'accounts' => json_encode([[
                        'account' => $account->account,
                        'type' => $account->type,
                    ]], JSON_UNESCAPED_UNICODE),
                    'industry' => json_encode($acquireKeywords, JSON_UNESCAPED_UNICODE),
                    'industry_num' => count($acquireKeywords),
                    'industry_type' => $industryType,
                    'content' => $scene === 2 ? json_encode($agentConfig->shutoff_msg_speech, JSON_UNESCAPED_UNICODE) : json_encode($agentConfig->shutoff_comment_speech, JSON_UNESCAPED_UNICODE),
                    'filter' => json_encode($item->intercept_keywords, JSON_UNESCAPED_UNICODE),
                    'send_num' => count($acquireKeywords) * $item->intercept_max_number,
                    'is_like' => 1,
                    'is_follow' => 1,
                    'send_time' => 0,
                    'gender' => '不限',
                    'region' => '不限',
                    'task_start_time' => $startTime,
                    'task_end_time' => $endTime,
                    'content_publish_day' => AiPersonaTrafficConfig::normalizeContentPublishDay($item->content_publish_day), //
                    'comment_publish_day' => $item->comment_publish_day, //
                    'task_frequency' => 30,
                    'status' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                    'ip_address' => [],
                    'city' => [],
                    'marker_method' => [1, 2, 3, 4, 5]
                ];
                $setting = SvLeadScrapingSetting::create($params);
                //$task = \app\common\model\sv\SvTouchingTask::create($params);
                $scrapingAccount = SvLeadScrapingSettingAccount::create([
                    'user_id' => $item->user_id,
                    'persona_id' => $item->persona_id,
                    'task_type' => $scene,
                    'scraping_id' => $setting->id,
                    'name' => '自动化截流' . DeviceEnum::getTaskSceneDesc($scene) . $typeName . '任务' . date('YmdHis', time()),
                    'account' => $account->account,
                    'account_type' => $account->type,
                    'nickname' => $account->nickname,
                    'avatar' => $account->avatar,
                    'device_code' => $item->device_code,
                    'status' => 0,
                    'send_start_time' => $startTime,
                    'send_end_time' => $endTime,
                    'count' => $account->type == DeviceEnum::ACCOUNT_TYPE_XHS ? 10 : 30,
                    'published_count' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                \app\common\model\sv\SvDeviceTask::create([
                    'user_id' => $item->user_id,
                    'device_code' => $item->device_code,
                    'task_type' => DeviceEnum::AUTO_TYPE_COMMENT_CLUE,
                    'account' => $account->account,
                    'account_type' => $account->type,
                    'nickname' => $account->nickname,
                    'avatar' => $account->avatar,
                    'auto_type' => 1,
                    'task_name' => '自动化' . DeviceEnum::getTaskSceneDesc($scene) . $typeName . '任务' . date('YmdHis', time()),
                    'task_scene' => $scene,
                    'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day' => date('Y-m-d', $startTime),
                    'persona_id' => $item->persona_id,
                    'status' => 0,
                    'sub_task_id' => $scrapingAccount->id,
                    'source' => DeviceEnum::TASK_SOURCE_TOUCH,
                    'create_time' => time(),
                ]);
                $result['created']++;
            }
        }

        return $result;
    }

    private static function shouldCreateScene(?array $taskScenes, int $scene): bool
    {
        return $taskScenes === null || in_array($scene, $taskScenes, true);
    }

    private static function shouldCreateAnyScene(?array $taskScenes, array $scenes): bool
    {
        return $taskScenes === null || !empty(array_intersect($taskScenes, $scenes));
    }



    private static function ensureInteractiveAction(array $config, array $default = [1, 2, 3]): array
    {
        if (!isset($config['interactive_action'])) {
            $config['interactive_action'] = $default;
            return $config;
        }

        $actions = array_map('intval', (array)$config['interactive_action']);
        foreach ([1, 2] as $required) {
            if (!in_array($required, $actions, true)) {
                $actions[] = $required;
            }
        }
        $config['interactive_action'] = array_values(array_unique($actions));
        return $config;
    }

    private static function getGender(int $gender)
    {
        $maps = [
            0 => '不限',
            1 => '男',
            2 => '女',
        ];
        if (!isset($maps[$gender])) {
            return '不限';
        }
        return $maps[$gender] ?? '不限';
    }
}
