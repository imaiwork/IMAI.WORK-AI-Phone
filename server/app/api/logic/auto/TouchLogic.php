<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceTouchConfig;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 触摸词自动任务逻辑
 * Class TouchLogic    
 * @package app\api\logic\auto
 */
class TouchLogic extends ApiLogic
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

            $accounts = SvAccount::field('id,account,type')->where('user_id', self::$uid)->where('type', 'not in', [1, 5])->where('device_code', $params['device_code'])->select();
            if (count($accounts) === 0) {
                self::setError('该设备没有绑定账号');
                return false;
            }

            $find = AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if(empty($find->comment_screening)){
                    $find->comment_screening = \app\common\service\ConfigService::get('touch_clue', 'comment_screening', []);
                }
                if(empty($find->touch_speech)){
                    $find->touch_speech = \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []);
                }
                self::$returnData = $find->toArray();
            } else {
                $payload = array(
                    'user_id' => self::$uid,
                    'channelVersion' => 3,
                    'keyword' => $config->clue_theme,
                    'targetCount' => 30,
                    'auto' => 30,
                );

                $bool = ToolsLogic::getSearchTerms($payload);
                if ($bool) {
                    $keywords = ToolsLogic::getReturnData();
                    $insertData = [
                        'user_id' => self::$uid,
                        'device_code' => $params['device_code'],
                        'exec_type' => 1, //执行访问1循环执行2ai自动填充3不执行
                        'exec_time' => AutoDeviceTouchConfig::ACCOUNT_EXEC_TIME_MAPS,
                        'exec_date' => date('Y-m-d', time()),
                        'ai_direction' => $config->clue_theme,
                        'clue_theme' => $config->clue_theme,
                        'mode' => 1,
                        'keywords' => [
                            [
                                'title' => $config->clue_theme,
                                'keywords' => $keywords,
                            ]
                        ],
                        'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                        'comment_screening' => \app\common\service\ConfigService::get('touch_clue', 'comment_screening', []),
                        'touch_speech_type' => 1,
                        'touch_speech' => \app\common\service\ConfigService::get('touch_clue',  'touch_speech',  []),
                        'actions' => [
                            'msg_comment_likes' => 0, //0关 1开
                            'msg_follow' => 0, //0关 1开
                            'comment_time' => '', //评论时间，为空不限
                            'areas' => '', //地区，为空不限
                            'gender' => '', //性别0未知1男2女，为空不限
                            'age' => '', //年龄
                            'account_feature' => 1, //0不调光认证号1跳过认证号
                        ],
                    ];
                    $result = AutoDeviceTouchConfig::create($insertData);
                    self::$returnData = $result->toArray();
                } else {
                    self::setError(ToolsLogic::getError());
                    return false;
                }
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
            $find = AutoDeviceTouchConfig::where('user_id', self::$uid)
                ->where('device_code', $params['device_code'])
                ->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    self::setError('触摸词自动生成任务正在执行，不可修改，稍后再试');
                    return false;
                }

                $find->exec_type = $params['exec_type'];
                $find->ai_direction = $params['ai_direction'] ?? $find->ai_direction;
                $find->clue_theme = $params['clue_theme'] ?? $find->clue_theme;
                $find->keywords = $params['keywords'];
                $find->mode = $params['mode'] ?? $find->mode;
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->comment_screening = $params['comment_screening'] ?? $find->comment_screening;
                $find->touch_speech_type = $params['touch_speech_type'] ?? $find->touch_speech_type;
                $find->touch_speech = $params['touch_speech'] ?? $find->touch_speech;
                $find->actions = $params['actions'] ?? $find->actions;
                $find->update_time = time();
                if(is_null($find->exec_date)){
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->save();
            } else {
                self::setError('该设备截流获客任务配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }



    public static function autoTouchTaskCron(int $isFrist = 0, $deviceCode = null)
    {
        print_r("\n{$deviceCode}自动化截流任务生成\n");
        try {
            $where = [];
            if($isFrist === 1){
                $where[] = ['is_first', '=', 0];
                $where[] = ['device_code', '=', $deviceCode];
            }else{
                $where[] = ['exec_date', '<=', date('Y-m-d', time())];
            }
            $items = AutoDeviceTouchConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->select();
            \think\facade\Log::channel('auto')->write('自动化截流任务生成' . $items->isEmpty() ? \think\facade\Db::getLastSql() : $items->count() . '条', 'touch');
            if ($items->isEmpty()) {
                return true;
            }
            $count = $isFrist === 1 ? 1 : 2;
            foreach ($items as $item) {
                for ($i = 0; $i < $count; $i++) {
                    self::createAutoTouchTask($item);
                }
                $item->exec_date = date('Y-m-d', strtotime('+' . $count .' day'));
                $item->is_first = 1;
                $item->save();
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'touch');
            return false;
        }
    }

    private static function createAutoTouchTask(AutoDeviceTouchConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {

            $keywords = array_map(function ($item) {
                return implode(',', $item['keywords']);
            }, $item->keywords);
            $keywords = explode(',', implode(',', $keywords));
            $accounts = SvAccount::field('id,account,type')->where('user_id', $item->user_id)->where('type', 'not in', [1, 5])->where('device_code', $item->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('该设备没有绑定账号');
            }

            $deviceTask = [];
            foreach ($accounts as $ak => $account) {
                $execTimes = AutoDeviceTouchConfig::ACCOUNT_EXEC_TIME_MAPS[$account->type];
                foreach ($execTimes as $scene => $execTime) {
                    $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $item->device_code)
                        ->where('task_type', DeviceEnum::AUTO_TYPE_COMMENT_CLUE)
                        ->where('source', DeviceEnum::TASK_SOURCE_TOUCH)
                        ->where('account', $account->account)
                        ->where('account_type', $account->type)
                        ->where('auto_type', 1)
                        ->where('task_scene', $scene)
                        ->order('day', 'desc')
                        ->limit(1)
                        ->value('day');
                    $date = is_null($maxDay) ? date('Y-m-d', time()) : date('Y-m-d', (strtotime($maxDay) + (24 * 60 * 60)));
                    $times = explode('-', $execTime);

                    $startTime = strtotime($date . ' ' . $times[0] . ':00') > time() ? strtotime($date . ' ' . $times[0] . ':00') : strtotime(date('Y-m-d ' . $times[0] . ':00', strtotime('+1 day')));
                    $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 180;

                    //截流任务
                    $params = [
                        'user_id' => $item->user_id,
                        'task_type' => $scene,
                        'name' => '自动化截流' . DeviceEnum::getTaskSceneDesc($scene) . '任务' . date('mdHis', $startTime),
                        'accounts' => json_encode([[
                            'account' => $account->account,
                            'type' => $account->type,
                        ]], JSON_UNESCAPED_UNICODE),
                        'type' => 3,
                        'industry' => json_encode($keywords, JSON_UNESCAPED_UNICODE),
                        'industry_num' => count($keywords),
                        'content' => json_encode($item->touch_speech, JSON_UNESCAPED_UNICODE),
                        'filter' => is_null($item->comment_screening) ? json_encode(\app\common\service\ConfigService::get('touch_clue', 'comment_screening', []), JSON_UNESCAPED_UNICODE) : json_encode($item->comment_screening, JSON_UNESCAPED_UNICODE),
                        'send_num' => 30,
                        'is_like' => $item->actions['msg_comment_likes'],
                        'is_follow' => $item->actions['msg_follow'],
                        'send_time' => 0,
                        'gender' => $item->actions['gender'] == '' ? '不限' : $item->actions['gender'],
                        'region' => $item->actions['areas'],
                        'task_start_time' => $startTime,
                        'task_end_time' => $endTime,
                        'task_frequency' => 30,
                        'status' => 1,
                        'create_time' => time(),
                        'update_time' => time(),

                    ];

                    $setting = SvLeadScrapingSetting::create($params);
                    //$task = \app\common\model\sv\SvTouchingTask::create($params);
                    $scrapingAccount = SvLeadScrapingSettingAccount::create([
                        'user_id' => $item->user_id,
                        'task_type' => $scene,
                        'scraping_id' => $setting->id,
                        'name' => '自动化截流' . DeviceEnum::getTaskSceneDesc($scene) . '任务' . date('mdHis', $startTime),
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'device_code' => $item->device_code,
                        'status' => 0,
                        'send_start_time' => $startTime,
                        'send_end_time' => $endTime,
                        'count' => 30,
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
                        'auto_type' => 1,
                        'task_name' => '自动化' . DeviceEnum::getTaskSceneDesc($scene) . '任务',
                        'task_scene' => $scene,
                        'time_config' => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'day' => date('Y-m-d', $startTime),
                        'status' => 0,
                        'sub_task_id' => $scrapingAccount->id,
                        'source' => DeviceEnum::TASK_SOURCE_TOUCH,
                        'create_time' => time(),
                    ]);
                }
            }
            //print_r($deviceTask);die;

            !empty($deviceTask) && (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTask);

            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'touch');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
