<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvVideoTask;
use app\common\model\auto\AutoDeviceConfig;
use app\common\service\FileService;
use think\facade\Db;

use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\hd\HdPuzzle;
use app\api\logic\sv\ToolsLogic;

/**
 * 设备自动任务-私信接管自动任务
 * Class PublishLogic    
 * @package app\api\logic\auto
 */
class PublishLogic extends ApiLogic
{
    protected static  $VideoPublishTimeMaps = [
        DeviceEnum::ACCOUNT_TYPE_DY => [
            '08:01',
            '13:01',
            '18:01',
        ],
        DeviceEnum::ACCOUNT_TYPE_XHS => [
            '08:08',
            '13:08',
            '18:08',
        ],
        DeviceEnum::ACCOUNT_TYPE_SPH => [
            '08:15',
            '13:15',
            '18:15',
        ],
        DeviceEnum::ACCOUNT_TYPE_KS => [
            '08:22',
            '13:22',
            '18:22',
        ],
    ];

    protected static $execTime = [
        8 => '08:00-08:30',
        13 => '13:00-13:30',
        18 => '18:00-18:30',
    ];

    /**
     * @notes 设置山尖视频发布任务
     * @param array $params 发布参数
     * @return bool
     * @author 系统
     * @date 2026/03/11
     */
    public static function setShanjianPublish($params = [])
    {
        $handler = null;
        try {
            usleep(100000);
            $handler = \think\facade\Cache::store('redis')->handler();
            $handler->select(env('redis.WS_SELECT', 8));
            $PUBLISH_QUEUE_KEY = 'auto_publish_create_'.$params['device_code'];

            // 将任务添加到队列
            $handler->lpush($PUBLISH_QUEUE_KEY, json_encode($params, JSON_UNESCAPED_UNICODE));
            $len = $handler->llen($PUBLISH_QUEUE_KEY);
            if ($len == 0) {
                \think\facade\Log::channel('auto')->write('任务队列长度为0', 'publish');
                return false;
            }

            $RUNNING_KEY = 'auto_publish_create_running_'.$params['device_code'];
            $maxRetries = 3; // 最大重试次数

            $svRunNum = 0;
            // 处理队列中的任务
            while (true) {
                // 获取队列长度，避免无限循环
                $queueLength = $handler->llen($PUBLISH_QUEUE_KEY);
                if ($queueLength == 0) {
                    \think\facade\Log::channel('auto')->write('任务队列处理完成', 'publish');
                    break;
                }

                // 检查是否有任务在运行
                if ($handler->get($RUNNING_KEY) === 1) {
                    \think\facade\Log::channel('auto')->write('当前有任务在运行中，将任务重新放回队列', 'publish');
                    // 将任务重新放回队列
                    sleep(2); // 短暂休眠后继续
                    continue;
                }

                // 使用brpop阻塞获取任务，设置10秒超时
                $taskData = $handler->brpop($PUBLISH_QUEUE_KEY, 10);
                if (empty($taskData)) {
                    \think\facade\Log::channel('auto')->write('队列无任务，退出处理', 'publish');
                    break;
                }

                $task = $taskData[1];
                $params = json_decode($task, true);
                // 检查任务数据是否有效
                if (empty($params)) {
                    \think\facade\Log::channel('auto')->write('任务数据无效，跳过', 'publish');
                    continue;
                }

                if(isset($params['sv_video_id'])){
                    //检查sj中是否还有待生成的视频，如果有sv任务，就移到队列尾，等待sj任务处理完成
                    $sjMedias = ShanjianVideoTask::where('device_code', $params['device_code'])
                        ->field('id, video_setting_id,pic, msg, video_result_url, "sj" as task_type')
                        ->where('auto_type', 1)
                        ->where('status', 1)
                        ->where('is_publish', 0)
                        ->findOrEmpty();
                    if(!$sjMedias->isEmpty()){
                        $handler->lpush($PUBLISH_QUEUE_KEY, $task);
                        //\think\facade\Log::channel('auto')->write('sv视频发布任务移到到队列尾' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
                        $svRunNum++;
                        sleep(5); // 短暂休眠
                        continue;
                    }
                }

                \think\facade\Log::channel('auto')->write('开始处理任务: ' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
                $handler->set($RUNNING_KEY, 1, 60);
                // 执行任务
                $retryCount = 0;
                $success = false;

                while ($retryCount < $maxRetries && !$success) {
                    try {

                        $success = self::runCreateShanjianPublish($params, $handler, $RUNNING_KEY);
                        if ($success) {
                            \think\facade\Log::channel('auto')->write('任务执行成功', 'publish');
                        } else {
                            $retryCount++;
                            \think\facade\Log::channel('auto')->write('任务执行失败，第' . $retryCount . '次重试', 'publish');
                            if ($retryCount < $maxRetries) {
                                sleep(3); // 重试前休眠
                            }
                        }
                    } catch (\Throwable $e) {
                        $retryCount++;
                        \think\facade\Log::channel('auto')->write('任务执行异常，第' . $retryCount . '次重试: ' . $e->__toString(), 'publish');
                        if ($retryCount < $maxRetries) {
                            sleep(3); // 重试前休眠
                        }
                    }
                }

                if (!$success) {
                    \think\facade\Log::channel('auto')->write('任务执行失败，已达到最大重试次数', 'publish');
                    // 可以选择将任务移至失败队列或记录到数据库
                }

                sleep(1); // 任务之间短暂休眠
            }

            return true;
        } catch (\Throwable $th) {
            if ($handler) {
                try {
                    $RUNNING_KEY = 'auto_publish_create_running_'.$params['device_code'];
                    $handler->del($RUNNING_KEY);
                } catch (\Exception $e) {
                    // 忽略删除操作的异常
                }
            }
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'publish');
            return false;
        }
    }

    private static function runCreateShanjianPublish($params, $handler, $RUNNING_KEY)
    {

        Db::startTrans();
        try {
            $device = SvDevice::where('auto_type', 1)
                ->when(isset($params['device_code']) && !empty($params['device_code']), function ($query) use ($params) {
                    $query->where('device_code', $params['device_code']);
                })
                ->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('没有自动化发布设备');
            }

            $autoConfig = AutoDeviceConfig::where('device_code', $device->device_code)->findOrEmpty();
            if ($autoConfig->isEmpty()) {
                throw new \Exception('设备' . $device->device_code . '没有自动发布配置');
            }
            $accounts = SvAccount::field('id, account, type, nickname, avatar')
                //->where('type', '<>', 3)
                ->where('device_code', $device->device_code)->select();
            if ($accounts->isEmpty()) {
                throw new \Exception('设备' . $device->device_code . '没有绑定账号');
            }

            $medias = [];
            if (isset($params['sj_video_id'])) {
                $sjMedias = ShanjianVideoTask::where('device_code', $device->device_code)
                    ->field('id, video_setting_id,pic, msg, video_result_url, "sj" as task_type')
                    ->where('auto_type', 1)
                    ->where('wechat_type', 0)
                    ->where('user_id', $device->user_id)
                    ->where('status', 3)
                    ->where('is_publish', 0)
                    ->when(isset($params['sj_video_id']) && !empty($params['sj_video_id']), function ($query) use ($params) {
                        $query->where('id', $params['sj_video_id']);
                    })
                    ->where('id', 'not in', function ($query) use ($device) {
                        $query->name('sv_publish_setting_detail')
                            ->where('device_code', $device->device_code)
                            ->where('task_type', 99)
                            ->where('material_type', 1)
                            ->where('auto_type', 1)
                            ->field('material_id');
                    })
                    ->order('id', 'asc')
                    ->select();
                $medias = $sjMedias->toArray();
            }

            if (isset($params['sv_video_id'])) {
                $svMedias = SvVideoTask::where('device_code', $device->device_code)
                    ->field('id, video_setting_id,pic, msg, video_result_url, clip_result_url, automatic_clip, "sv" as task_type')
                    ->where('auto_type', 1)
                    ->where('user_id', $device->user_id)
                    ->where('automatic_clip', 1)
                    ->where('clip_status', 3)
                    ->where('status', 6)
                    ->where('is_publish', 0)
                    ->when(isset($params['sv_video_id']) && !empty($params['sv_video_id']), function ($query) use ($params) {
                        $query->where('id', $params['sv_video_id']);
                    })
                    ->order('id', 'asc')
                    ->select();
                $medias = $svMedias->toArray();
            }

            if (empty($medias)) {
                throw new \Exception('设备' . $device->device_code . '暂时没有可以视频素材生成');
            }
            $deviceTaskInsert = [];
            foreach ($accounts as $account) {
                $exec_times = self::$VideoPublishTimeMaps[$account->type];

                $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $device->device_code)
                    ->where('task_type', DeviceEnum::AUTO_TYPE_PUBLISH)
                    ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('day', 'desc')
                    ->limit(1)
                    ->fetchSql(false)
                    ->value('day');
                if(!is_null($maxDay)){
                    if(strtotime($maxDay) < time()){
                        $maxDay = date('Y-m-d', time());
                    }
                }
                
                $lastPublishTime =  \app\common\model\sv\SvPublishSettingDetail::where('device_code', $device->device_code)
                    ->where('user_id', $device->user_id)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('id', 'desc')
                    ->limit(1)
                    ->fetchSql(false)
                    ->value('publish_time');
                $publishTimes = self::getPublishTimes($maxDay, $exec_times, count($medias), $lastPublishTime);
                foreach ($medias as $mk => $media) {
                    if ($media['task_type'] == 'sj') {
                        ShanjianVideoTask::where('id', $media['id'])->update([
                            'is_publish' => 2, //正在使用中
                            'update_time' => time(),
                        ]);
                    }

                    if ($media['task_type'] == 'sv') {
                        SvVideoTask::where('id', $media['id'])->update([
                            'is_publish' => 2, //正在使用中
                            'update_time' => time(),
                        ]);
                    }

                    $publishTime = $publishTimes[$mk];

                    $task_id = generate_unique_task_id();
                    $response = [
                        'code' => 10000,
                    ];
                    $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                        'keywords' => $media['msg'] != '' ? $media['msg'] : $autoConfig->video_theme,
                        'task_id' => $task_id,
                        'source' => 'shanjian2',
                        'user_id' => $device->user_id,
                    ]);

                    if ((int)$response['code'] === 10000) {
                        $setting = SvPublishSetting::create([
                            'user_id' => $device->user_id,
                            'task_type' => 99,
                            'name' => '自动化视频发布任务' . date('YmdHsi', $publishTime),
                            'accounts' => json_encode([$account->toArray()], JSON_UNESCAPED_UNICODE),
                            'auto_type' => 1,
                            'video_setting_id' => 0,
                            'matrix_media_setting_id' => 0,
                            'video_ids' => json_encode([$media['id']], JSON_UNESCAPED_UNICODE),
                            'scene' => 2,
                            'type' => 0,
                            'media_type' => 1,
                            'publish_start' => date('Y-m-d', $publishTime),
                            'publish_end' => date('Y-m-d', $publishTime),
                            'time_config' => json_encode([self::$execTime[(int)date('H', $publishTime)]], JSON_UNESCAPED_UNICODE),
                            'data_type' => 0,
                            'date_type' => 1,
                            'publish_frep' => 1,
                            'status' => 3,
                            'create_time' => time()
                        ]);

                        $paccount =  SvPublishSettingAccount::create([
                            'publish_id' => $setting->id,
                            'task_type' => 99,
                            'user_id' => $device->user_id,
                            'name' => '自动化视频发布任务' . date('YmdHsi', $publishTime),
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'nickname' => $account->nickname,
                            'avatar' => $account->avatar,
                            'auto_type' => 1,
                            'device_code' => $device->device_code,
                            'media_type' => 1,
                            'video_setting_id' => 0,
                            'video_ids' => json_encode([$media['id']], JSON_UNESCAPED_UNICODE),
                            'matrix_media_setting_id' => 0,
                            'scene' => 2,
                            'status' => 2,
                            'task_status' => 2,
                            'publish_start' => date('Y-m-d', $publishTime),
                            'publish_end' => date('Y-m-d', $publishTime),
                            'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                            'count' => 1,
                            'published_count' => 0,
                            'data_type' => 0,
                            'create_time' => time()
                        ]);

                        $title = $response['data']['title'] ?? '';
                        $content = $response['data']['content'] ?? '';
                        $material_url = (isset($media['automatic_clip']) && $media['automatic_clip'] == 1) ? $media['clip_result_url'] : $media['video_result_url'];
                        $detail = SvPublishSettingDetail::create([
                            'publish_id' => $setting->id,
                            'publish_account_id' => $paccount->id,
                            'task_type' => 99,
                            'video_task_id' => $media['id'],
                            'video_setting_id' => $media['video_setting_id'],
                            'user_id' => $device->user_id,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'auto_type' => 1,
                            'device_code' => $device->device_code,
                            'matrix_media_setting_id' => 0,
                            'material_id' => $media['id'],
                            'material_url' => FileService::getFileUrl($material_url),
                            'material_title' => $title,
                            'material_subtitle' => $content,
                            'material_type' => 1,
                            'material_tag' => '',
                            'pic' => FileService::getFileUrl($media['pic']),
                            'poi' => '',
                            'data_type' => 0,
                            'task_id' => $task_id,
                            'sub_task_id' => time() . ($mk + 100),
                            'scene' => 2,
                            'platform' => $account->type,
                            'status' => 0,
                            'publish_time' => date('Y-m-d H:i:s', $publishTime),
                            'create_time' => time()
                        ]);
                        $detail->refresh();

                        $time = self::$execTime[(int)date('H', $publishTime)];
                        $tmpTime = explode('-', $time);

                        \app\common\model\sv\SvDeviceTask::create([
                            'user_id' => $device->user_id,
                            'device_code' => $device->device_code,
                            'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'auto_type' => 1,
                            'task_name' => '自动化视频发布任务',
                            'time_config' => json_encode([$time], JSON_UNESCAPED_UNICODE),
                            'start_time' => strtotime(date('Y-m-d ' . $tmpTime[0] . ':00', $publishTime)),
                            'end_time' => strtotime(date('Y-m-d ' . $tmpTime[1] . ':00', $publishTime)) - 180,
                            'day' => date('Y-m-d', $publishTime),
                            'status' => 0,
                            'sub_task_id' => $paccount->id,
                            'sub_data_id' => $detail->id,
                            'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                            'create_time' => time(),
                        ]);

                        if ($media['task_type'] == 'sj') {
                            ShanjianVideoTask::where('id', $media['id'])->update([
                                'is_publish' => 1,
                                'update_time' => time(),
                            ]);
                        }

                        if ($media['task_type'] == 'sv') {
                            SvVideoTask::where('id', $media['id'])->update([
                                'is_publish' => 1,
                                'update_time' => time(),
                            ]);
                        }
                    } else {
                        //文案生成异常时状态重置
                        if ($media['task_type'] == 'sj') {
                            ShanjianVideoTask::where('id', $media['id'])->update([
                                'is_publish' => 0, //正在使用中
                                'update_time' => time(),
                            ]);
                        }

                        if ($media['task_type'] == 'sv') {
                            SvVideoTask::where('id', $media['id'])->update([
                                'is_publish' => 0, //正在使用中
                                'update_time' => time(),
                            ]);
                        }

                        \think\facade\Log::channel('auto')->write('24小时视频文案异常：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
                    }
                }
            }
            Db::commit();
            $handler->del($RUNNING_KEY);
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            $handler->del($RUNNING_KEY);
            \think\facade\Log::channel('auto')->write('24小时视频发布任务异常：' . $th->__toString(), 'publish');
            return false;
        }
    }

    private static function getPublishTimes(string|null $maxDay, array $times, int $mediaCount, string|null $lastPublishTime)
    {
        $publishTimes = [];
        for ($i = 0; $i < 10; $i++) {
            $date = is_null($maxDay) ? date('Y-m-d', time() + ($i * (24 * 60 * 60))) : $maxDay;
            for ($j = 0; $j < count($times); $j++) {
                $exec_time = $times[$j];
                $publishTime = strtotime($date . ' ' . $exec_time) > time() ? strtotime($date . ' ' . $exec_time) : strtotime(date($date . ' ' . $exec_time, strtotime('+1 day')));
                if (in_array(date('Y-m-d H:i:s', $publishTime), $publishTimes)) {
                    $publishTime = $publishTime +  ($i * (24 * 60 * 60));
                }
                array_push($publishTimes, date('Y-m-d H:i:s', $publishTime));
            }
        }
        sort($publishTimes);
        $publishTimes = array_map(function ($item) use ($lastPublishTime) {
            if (is_null($lastPublishTime)) {
                return strtotime($item);
            }
            return strtotime($item) > strtotime($lastPublishTime) ? strtotime($item) : 0;
        }, $publishTimes);
        $publishTimes = array_values(array_filter($publishTimes));
        return $publishTimes;
    }


    public static  function setPuzzlePublish(array $params = [])
    {
        Db::startTrans();
        try {
            $devices = SvDevice::where('auto_type', 1)
                ->when(isset($params['device_code']) && !empty($params['device_code']), function ($query) use ($params) {
                    $query->where('device_code', $params['device_code']);
                })->select();
            if ($devices->isEmpty()) {
                throw new \Exception('没有自动化发布设备');
            }

            $accounts = SvAccount::field('id, user_id, account, type, device_code,nickname,avatar')
                ->where('type', 3)
                ->where('device_code', '=', $params['device_code'])
                ->where('device_code', 'in', function ($query) {
                    $query->name('sv_device')->where('auto_type', 1)->field('device_code');
                })->select();
            //print_r($accounts->toArray());die;
            foreach ($accounts as $account) {
                $autoConfig = AutoDeviceConfig::where('device_code', $account->device_code)->order('id', 'desc')->limit(1)->findOrEmpty();
                if ($autoConfig->isEmpty()) {
                    throw new \Exception('设备' . $account->device_code . '没有自动发布配置');
                }
                $puzzles = HdPuzzle::where('device_code', $account->device_code)
                    ->field('id, puzzle_setting_id,puzzle_url')
                    ->where('user_id', $account->user_id)
                    ->where('auto_type', 1)
                    ->where('status', 1)
                    ->where('is_publish', 0)
                    ->order('id', 'asc')
                    ->select();
                if ($puzzles->isEmpty()) {
                    throw new \Exception('设备' . $account->device_code . '暂时没有可以拼图素材生成');
                }

                $maxDay =  \app\common\model\sv\SvDeviceTask::where('device_code', $account->device_code)
                    ->where('task_type', DeviceEnum::AUTO_TYPE_PUBLISH)
                    ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('day', 'desc')
                    ->limit(1)
                    ->value('day');
                $lastPublishTime =  \app\common\model\sv\SvPublishSettingDetail::where('device_code', $account->device_code)
                    ->where('user_id', $account->user_id)
                    ->where('auto_type', 1)
                    ->where('account', $account->account)
                    ->where('account_type', $account->type)
                    ->order('id', 'desc')
                    ->limit(1)
                    ->fetchSql(false)
                    ->value('publish_time');
                $times = self::$VideoPublishTimeMaps[$account->type];
                $medias = self::getPuzzleMedias($puzzles);
                $settingIds = array_values(array_unique(array_column($puzzles->toArray(), 'puzzle_setting_id')));
                $deviceTaskInsert = [];
                $publishTimes = self::getPublishTimes($maxDay, $times, count($medias), $lastPublishTime);

                foreach ($medias as $mk => $media) {
                    $publishTime = $publishTimes[$mk];
                    //$maxDay = date('Y-m-d', $publishTime);
                    //print_r(date('Y-m-d H:i:s', $publishTime) . PHP_EOL);
                    $setting = SvPublishSetting::create([
                        'user_id' => $account->user_id,
                        'task_type' => 99,
                        'name' => '自动化拼图发布任务' . date('YmdHsi', $publishTime),
                        'accounts' => json_encode([$account->toArray()], JSON_UNESCAPED_UNICODE),
                        'auto_type' => 1,
                        'video_setting_id' => 0,
                        'matrix_media_setting_id' => 0,
                        'video_ids' => json_encode([$settingIds], JSON_UNESCAPED_UNICODE),
                        'scene' => 2,
                        'type' => 0,
                        'media_type' => 2,
                        'publish_start' => date('Y-m-d', $publishTime),
                        'publish_end' => date('Y-m-d', $publishTime),
                        'time_config' => json_encode([self::$execTime[(int)date('H', $publishTime)]], JSON_UNESCAPED_UNICODE),
                        'data_type' => 0,
                        'date_type' => 1,
                        'publish_frep' => 1,
                        'status' => 3,
                        'create_time' => time()
                    ]);

                    $paccount =  SvPublishSettingAccount::create([
                        'publish_id' => $setting->id,
                        'task_type' => 99,
                        'user_id' => $account->user_id,
                        'name' => '自动化拼图发布任务' . date('YmdHsi', $publishTime),
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'nickname' => $account->nickname,
                        'avatar' => $account->avatar,
                        'auto_type' => 1,
                        'device_code' => $account->device_code,
                        'media_type' => 2,
                        'video_setting_id' => 0,
                        'video_ids' => json_encode([$settingIds], JSON_UNESCAPED_UNICODE),
                        'matrix_media_setting_id' => 0,
                        'scene' => 2,
                        'status' => 2,
                        'task_status' => 2,
                        'publish_start' => date('Y-m-d', $publishTime),
                        'publish_end' => date('Y-m-d', $publishTime),
                        'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                        'count' => 1,
                        'published_count' => 0,
                        'data_type' => 0,
                        'create_time' => time()
                    ]);

                    $task_id = generate_unique_task_id();
                    $response = [
                        'code' => 10000,
                    ];
                    $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                        'keywords' => $autoConfig->text_theme,
                        'task_id' => $task_id,
                        'source' => 'puzzle',
                        'user_id' => $account->user_id,
                    ]);

                    if ((int)$response['code'] === 10000) {
                        $title = $response['data']['title'] ?? '';
                        $content = $response['data']['content'] ?? '';
                        $detail = SvPublishSettingDetail::create([
                            'publish_id' => $setting->id,
                            'publish_account_id' => $paccount->id,
                            'task_type' => 99,
                            'video_task_id' => 0,
                            'video_setting_id' => 0,
                            'user_id' => $autoConfig->user_id,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'auto_type' => 1,
                            'device_code' => $autoConfig->device_code,
                            'matrix_media_setting_id' => 0,
                            'material_id' => 0,
                            'material_url' => implode(',', count($media) > 9 ? array_values(array_intersect_key($media, array_flip(array_rand($media, 9)))) : $media),
                            'material_title' => $title,
                            'material_subtitle' => $content,
                            'material_type' => 2,
                            'material_tag' => '',
                            'pic' => isset($media[0]) ? $media[0] : '',
                            'poi' => '',
                            'data_type' => 0,
                            'task_id' => $task_id,
                            'sub_task_id' => time() . ($mk + 100),
                            'scene' => 2,
                            'platform' => $account->type,
                            'status' => 0,
                            'publish_time' => date('Y-m-d H:i:s', $publishTime),
                            'create_time' => time()
                        ]);

                        $_time = self::$execTime[(int)date('H', $publishTime)];
                        $tmpTime = explode('-', $_time);
                        array_push($deviceTaskInsert, [
                            'user_id' => $account->user_id,
                            'device_code' => $account->device_code,
                            'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                            'account' => $account->account,
                            'account_type' => $account->type,
                            'auto_type' => 1,
                            'task_name' => '自动化拼图发布任务',
                            'time_config' => json_encode([$_time], JSON_UNESCAPED_UNICODE),
                            'start_time' => strtotime(date('Y-m-d ' . $tmpTime[0] . ':00', $publishTime)),
                            'end_time' => strtotime(date('Y-m-d ' . $tmpTime[1] . ':00', $publishTime)) - 180,
                            'day' => date('Y-m-d', $publishTime),
                            'status' => 0,
                            'sub_task_id' => $paccount->id,
                            'sub_data_id' => $detail->id,
                            'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                            'create_time' => time(),
                        ]);
                    }
                }

                if (!empty($deviceTaskInsert)) {
                    (new \app\common\model\sv\SvDeviceTask())->saveAll($deviceTaskInsert);
                    HdPuzzle::where('id', 'in', array_column($puzzles->toArray(), 'id'))->update([
                        'is_publish' => 1,
                        'update_time' => time(),
                    ]);
                }
            }
            Db::commit();
        } catch (\Throwable $th) {
            Db::rollback();
            \think\facade\Log::channel('auto')->write($th->__toString(), 'publish');
            return false;
        }
    }

    private static function getPuzzleMedias($puzzles)
    {

        $imgArr = array();
        foreach ($puzzles as $puzzle) {
            $imgArr = array_merge($imgArr, json_decode($puzzle->puzzle_url, true));
        }
        $totalImages = count($imgArr);
        $groupCount = 6;
        $imagesPerGroup = floor($totalImages / $groupCount);
        $remainingImages = $totalImages % $groupCount;
        // 创建6个空组
        $medias = array_fill(0, $groupCount, array());

        $index = 0;
        // 先平均分配图片
        for ($i = 0; $i < $groupCount; $i++) {
            $currentGroupSize = $imagesPerGroup + ($i < $remainingImages ? 1 : 0);
            for ($j = 0; $j < $currentGroupSize; $j++) {
                if ($index < $totalImages) {
                    $medias[$i][] = FileService::getFileUrl($imgArr[$index]);
                    $index++;
                }
            }
        }
        return $medias;
    }
}
