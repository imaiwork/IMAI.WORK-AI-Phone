<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevice;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceClueConfig;
use think\facade\Db;

use app\api\logic\sv\ToolsLogic;

/**
 * 线索词自动任务逻辑
 * Class ClueLogic    
 * @package app\api\logic\auto
 */
class ClueLogic extends ApiLogic
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

            $find = AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
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
                        'clue_theme' => $config->clue_theme,
                        'exec_time' => '01:00-06:00',
                        'exec_date' => date('Y-m-d', time()),
                        'keywords' => [
                            [
                                'title' => $config->clue_theme,
                                'keywords' => $keywords,
                            ]
                        ],
                        'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    ];
                    $result = AutoDeviceClueConfig::create($insertData);
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
            $find = AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status == DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    self::setError('获客自动生成任务正在执行，不可修改，稍后再试');
                    return false;
                }

                $find->exec_type = $params['exec_type'];
                $find->clue_theme = $params['clue_theme'];
                $find->keywords = $params['keywords'];
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->update_time = time();
                if(is_null($find->exec_date)){
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->save();
            } else {
                self::setError('该设备线索词自动任务配置不存在');
                return false;
            }
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }



    public static function autoClueTaskCron(int $isFrist = 0, $deviceCode = null)
    {
        print_r("\n{$deviceCode}自动化获客任务生成\n");
        try {
            $where = [];
            if($isFrist === 1){
                $where[] = ['is_first', '=', 0];
                $where[] = ['device_code', '=', $deviceCode];
            }else{
                $where[] = ['exec_date', '<=', date('Y-m-d', time())];
            }
            $items = AutoDeviceClueConfig::where('status', '<>', DeviceEnum::AUTO_CONFIG_STATUS_RUNNING)->where($where)->select();
            \think\facade\Log::channel('auto')->write('自动化获客任务生成' . $items->isEmpty() ? \think\facade\Db::getLastSql() : $items->count() . '条', 'clue');
            if ($items->isEmpty()) {
                return true;
            }
            $count = $isFrist === 1 ? 1 : 2;
            foreach ($items as $item) {
                for($i = 0; $i < $count; $i++){
                    self::createAutoClueTask($item);
                }
                $item->exec_date = date('Y-m-d', strtotime('+' . $count .' day'));
                $item->is_first = 1;
                $item->save();
            }
            return true;
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'clue');
            return false;
        }
    }


    private static function createAutoClueTask(AutoDeviceClueConfig $item)
    {
        $item->status = DeviceEnum::AUTO_CONFIG_STATUS_RUNNING;
        $item->save();
        Db::startTrans();
        try {
            $keywords = array_map(function ($item) {
                return implode(',', $item['keywords']);
            }, $item->keywords);
            $keywords = explode(',', implode(',', $keywords));

            $wechat_device_code = \app\common\model\sv\SvDevice::where('device_code', $item->device_code)->where('user_id', $item->user_id)->value('wechat_device_code');
            if ($wechat_device_code === null) {
                throw new \Exception('请绑定个微，并获取微信账号信息');
            }
            $wechat = \app\common\model\wechat\AiWechat::where('device_code', $wechat_device_code)->where('user_id', $item->user_id)->findOrEmpty();
            if ($wechat->isEmpty()) {
                throw new \Exception('请绑定个微，并获取微信账号信息');
            }
            $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $item->device_code)
                ->where('task_type', DeviceEnum::AUTO_TYPE_CLUES)
                ->where('source', DeviceEnum::TASK_SOURCE_CLUES)
                ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE)
                ->where('auto_type', 1)
                ->order('day', 'desc')
                ->limit(1)
                ->value('day');
            $date = is_null($maxDay) ? date('Y-m-d', time()) : date('Y-m-d', (strtotime($maxDay) + (24 * 60 * 60)));
            $times = explode('-', $item->exec_time);

            $startTime = strtotime($date . ' ' . $times[0] . ':00') > time() ? strtotime($date . ' ' . $times[0] . ':00') : strtotime(date('Y-m-d ' . $times[0] . ':00', strtotime('+1 day')));
            $endTime =  strtotime(date('Y-m-d', $startTime) . ' ' . $times[1] . ':00') - 180;

            $params = [
                'name' => '自动化视频号获客任务' . date('mdHis', $startTime),
                'device_codes' => json_encode([$item->device_code], JSON_UNESCAPED_UNICODE),
                'type' => 1,
                'keywords' => json_encode($keywords, JSON_UNESCAPED_UNICODE),
                'chat_type' => 0,
                'chat_number' => 0,
                'chat_interval_time' => 0,
                'add_type' => 0,
                'remark' => '',
                'add_number' => 0,
                'add_interval_time' => 0,
                'greeting_content' => '',
                'crawl_type' => 1,
                'source' => 2, //1手动创建2自动化任务创建
                'private_message_prompt' => '',
                'add_friends_prompt' => '',
                'wechat_id' => $wechat->wechat_id,
                'wechat_reg_type' => 1,
                'ocr_type' => 1,
                'exec_time' => json_encode([$item->exec_time], JSON_UNESCAPED_UNICODE),
                'user_id' => $item->user_id,
                'implementation_keywords_number' => count($keywords),
                'status' => 0,
                'exec_add_count' => 0,
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
                'keywords'    => json_encode($keywords, JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'update_time' => time(),
                'status'      => 1,
                'auto_type'   => 1,
            ];
            $bind = \app\common\model\sv\SvCrawlingTaskDeviceBind::create($bindData);

            $account = \app\common\model\sv\SvAccount::where('device_code',  $item->device_code)->where('type', 1)->where('user_id', $item->user_id)->findOrEmpty();
            $account =  $account->isEmpty() ? '' : $account->account;
            $deviceTask = [
                'user_id' => $item->user_id,
                'device_code' => $item->device_code,
                'task_type' => DeviceEnum::AUTO_TYPE_CLUES,
                'account' => $account,
                'account_type' => 1,
                'auto_type' => 1,
                'task_name' => '自动化' . DeviceEnum::getTaskSceneDesc(DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE) . '任务',
                'task_scene' => DeviceEnum::AUTO_TASK_SCENE_SPH_CLUE,
                'time_config' => json_encode([$item->exec_time], JSON_UNESCAPED_UNICODE),
                'start_time' => $task->start_time,
                'end_time' => $task->end_time,
                'day' => date('Y-m-d', $task->start_time),
                'status' => 0,
                'sub_task_id' => $task->id,
                'source' => DeviceEnum::TASK_SOURCE_CLUES,
                'create_time' => time(),
            ];
            \app\common\model\sv\SvDeviceTask::create($deviceTask);
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FINISHED;
            $item->save();
            Db::commit();
        } catch (\Throwable $th) {
            \think\facade\Log::channel('auto')->write($th->__toString(), 'clue');
            Db::rollback();
            $item->status = DeviceEnum::AUTO_CONFIG_STATUS_FAILED;
            $item->remark = $th->getMessage();
            $item->save();
            throw new \Exception($th->getMessage());
        }
    }
}
