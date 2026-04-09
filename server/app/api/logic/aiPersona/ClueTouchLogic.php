<?php


namespace app\api\logic\aiPersona;

use think\facade\Db;
use app\api\logic\ApiLogic;
use app\api\logic\sv\ToolsLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;

use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;

/**
 * 客户触达自动任务逻辑
 * Class ClueTouchLogic    
 * @package app\api\logic\aiPersona
 */
class ClueTouchLogic extends ApiLogic
{
    public static function detail($params)
    {
        ini_set('max_execution_time', 0);
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }
            // if ((int)$persona->report_status !== 2) {
            //     self::setError('IP人设分析报告未分析完成');
            //     return false;
            // }
            // if (empty($persona->report_content)) {
            //     self::setError('IP人设分析报告内容为空');
            //     return false;
            // }

            $personaRule = self::getPersonaRule($persona);
            //print_r($personaRule->toArray());die;
            $config = AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$config->isEmpty()) {
                $config->acquire_keywords = empty($config->acquire_keywords) ? $personaRule->clue_acquire_keywords : $config->acquire_keywords;
                $config->intercept_keywords = empty($config->intercept_keywords) ? $personaRule->clue_intercept_keywords : $config->intercept_keywords;
                $config->comment_scripts = empty($config->comment_scripts) ? $personaRule->clue_comment_scripts : $config->comment_scripts;
                $config->dm_scripts = empty($config->dm_scripts) ? $personaRule->clue_dm_scripts : $config->dm_scripts;
                $config->save();
                self::$returnData = $config->toArray();
                return true;
            } else {

                // $payload = array(
                //     'keywords' => $personaRule->clue_content,
                // );

                // $response = \app\common\service\ToolsService::Coze()->clue($payload);
                // // continue;
                // if ((int)$response['code'] !== 10000 || !isset($response['data']['content'])) {
                //     self::setError($response['msg'] ?? '获取线索词失败');
                //     return false;
                // }
                // $result = json_decode($response['data']['content'], true);
                // $output = json_decode($result['output'], true);
                $insertData = [
                    'user_id' => self::$uid,
                    'persona_id' => $params['persona_id'],
                    'acquire_keywords' => $personaRule->clue_acquire_keywords,
                    'intercept_keywords' => $personaRule->clue_intercept_keywords,
                    'comment_scripts' => $personaRule->clue_comment_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'dm_scripts' => $personaRule->clue_dm_scripts ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                    'message_number' => $params['message_number'] ?? 15,
                    'comment_number' => $params['comment_number'] ?? 15,
                    'reply_number' => $params['reply_number'] ?? 0,
                    'content_publish_day' => $params['content_publish_day'] ?? 1,
                    'comment_publish_day' => $params['comment_publish_day'] ?? 1,
                    'exec_date' => date('Y-m-d', time()),
                    'is_first' => 1,
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                ];
                $result = AiPersonaTrafficConfig::create($insertData);
                self::$returnData = $result->toArray();
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
            $find = AiPersonaTrafficConfig::where('user_id', self::$uid)->where('persona_id', $params['persona_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    self::setError('该IP人设获客与截流任务正在执行，不可修改，稍后再试');
                    return false;
                }
                $find->acquire_keywords = $params['acquire_keywords'];
                $find->intercept_keywords = $params['intercept_keywords'];
                $find->comment_scripts = $params['comment_scripts'] ?? \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []);
                $find->dm_scripts = $params['dm_scripts'] ?? \app\common\service\ConfigService::get('touch_clue',  'touch_dm',  []);
                $find->message_number = $params['message_number'] ?? 15;
                $find->comment_number = $params['comment_number'] ?? 15;
                $find->reply_number = $params['reply_number'] ?? 0;
                $find->content_publish_day = $params['content_publish_day'] ?? 1;
                $find->comment_publish_day = $params['comment_publish_day'] ?? 1;
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










    public static function trafficTaskCron(SvDevice $device)
    {
        print_r("\n{$device->device_code}自动化截流任务生成\n");
        try {
            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('IP人设不存在:' . \think\facade\Db::getLastSql());
            }

            $where = [];
            $where[] = ['persona_id', '=', $device->persona_id];
            //$where[] = ['exec_date', '<=', date('Y-m-d', time())];

            $item = AiPersonaTrafficConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->findOrEmpty();
            \think\facade\Log::channel('auto')->write('自动化获客与截流任务生成' . $item->isEmpty() ? \think\facade\Db::getLastSql() :  $item->id, 'touch');
            if ($item->isEmpty()) {
                return true;
            }

            $item->device_code = $device->device_code;
            $item->persona_type = $persona->persona_type;
            self::createTouchTask($item);
            self::createClueTaskCron($item);
            $item->exec_date = date('Y-m-d', strtotime('+1 day'));
            $item->save();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'touch');
            return false;
        }
    }

    private static function createClueTaskCron(AiPersonaTrafficConfig $item)
    {
        print_r("\n{$item->device_code}自动化获客任务生成\n");
        Db::startTrans();
        try {
            $account = \app\common\model\sv\SvAccount::where('device_code',  $item->device_code)->where('type', 1)->where('user_id', $item->user_id)->findOrEmpty();
            if ($account->isEmpty()) {
                \think\facade\Log::channel('auto')->write('请绑定个微，并获取微信账号信息' . $item->device_code . 'clue');
            } else {
                $date = date('Y-m-d', time());
                $times = $item->getClueTimesByType($item->persona_type, 1);
                $keywords = self::splitKeywordsIntoGroups($item->acquire_keywords, count($times));
                foreach ($times as $index => $time) {
                    list($st, $et) = explode('-', $time);
                    $startTime = strtotime($date . ' ' . $st . ':00');
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $et . ':00') - 120;

                    $params = [
                        'name' => '自动化视频号获客任务' . date('YmdHis', time()),
                        'device_codes' => json_encode([$item->device_code], JSON_UNESCAPED_UNICODE),
                        'persona_id' => $item->persona_id,
                        'type' => 1,
                        'keywords' => json_encode($keywords[$index], JSON_UNESCAPED_UNICODE),
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
                        'wechat_reg_type' => 1,
                        'ocr_type' => 1,
                        'exec_time' => json_encode([$time], JSON_UNESCAPED_UNICODE),
                        'user_id' => $item->user_id,
                        'implementation_keywords_number' => count($keywords[$index]),
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
                        'keywords'    => json_encode($keywords[$index], JSON_UNESCAPED_UNICODE),
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
                        'time_config' => json_encode([$time], JSON_UNESCAPED_UNICODE),
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
                }


                $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
                $item->remark = '任务生成成功' . date('Y-m-d H:i:s', time());
                $item->update_time = time();
                $item->save();
            }


            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化获客任务生成' . $item->device_code . ' ' . $th->__toString() . 'clue');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            throw new \Exception($th->getMessage());
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

        // 将关键词均分到各个组
        $index = 0;
        foreach ($keywords as $keyword) {
            $groupIndex = $index % $groupCount;
            $groups[$groupIndex][] = $keyword;
            $index++;
        }

        // 过滤掉空的分组
        return array_values(array_filter($groups, function ($group) {
            return !empty($group);
        }));
    }

    private static function createTouchTask(AiPersonaTrafficConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {

            // $keywords = array_map(function ($item) {
            //     return implode(',', $item['keywords']);
            // }, $item->keywords);
            $accounts = SvAccount::field('id,account,type,nickname,avatar')->where('user_id', $item->user_id)->where('type', 'not in', [1, 5])->where('device_code', $item->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('该设备没有绑定账号' . $item->device_code);
            }

            $deviceTask = [];
            foreach ($accounts as $ak => $account) {
                $times = AiPersonaTrafficConfig::getTimesByType($item->persona_type, $account->type);
                foreach ($times as $scene => $execTimes) {
                    $maxDay =  date('Y-m-d', time());
                    foreach ($execTimes as $execTime => $typeNum) {
                        list($industryType, $sendNum) = explode(',', $typeNum);
                        $typeName = (int)$industryType === 0 ? '自由' : '同城';
                        $date = $maxDay;
                        $times = explode('-', $execTime);

                        $startTime = strtotime($date . ' ' . $times[0] . ':00');
                        $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 120;

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
                            'industry' => json_encode($item->acquire_keywords, JSON_UNESCAPED_UNICODE),
                            'industry_num' => count($item->acquire_keywords),
                            'industry_type' => $industryType,
                            'content' => $scene === 2 ? json_encode($item->dm_scripts, JSON_UNESCAPED_UNICODE) : json_encode($item->comment_scripts, JSON_UNESCAPED_UNICODE),
                            'filter' => json_encode($item->intercept_keywords, JSON_UNESCAPED_UNICODE),
                            'send_num' => $sendNum,
                            'is_like' => 1,
                            'is_follow' => 1,
                            'send_time' => 0,
                            'gender' => '不限',
                            'region' => '不限',
                            'task_start_time' => $startTime,
                            'task_end_time' => $endTime,
                            'content_publish_day' => $item->content_publish_day, //
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

                        array_push($deviceTask, [
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
                    }
                }
            }
            //print_r($deviceTask);die;

            !empty($deviceTask) && (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);

            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->remark = '任务生成成功' . date('Y-m-d H:i:s', time());
            $item->update_time = time();
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write('自动化截流任务生成' . $item->device_code . ' ' . $th->__toString() . 'touch');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->update_time = time();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
