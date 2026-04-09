<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\wechat\AiWechatCircleTask;

use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;

use app\common\model\sv\SvVideoTask;

use app\common\model\sv\SvDevice;
use app\common\model\aiPersona\AiPersona;
use think\facade\Db;
use app\common\service\FileService;
use app\api\logic\sv\ToolsLogic;

/**
 * � 发布任务逻辑
 * Class PublishLogic    
 * @package app\api\logic\aiPersona
 */
class PublishLogic extends ApiLogic
{
    public static function getTimesByType(int $personaType)
    {
        $maps = [
            1 => [
                1 => [
                    '17:00-17:30' => '17:03,1', //发布时间，表示用同一个生成的视频
                ],
                2 => [ //微信朋友圈
                    '07:30-08:00' => '07:35,0',
                    '17:00-17:30' => '17:20,1',
                ],
                3 => [
                    '08:00-08:30' => '08:03,0',
                ],
                4 => [
                    '08:00-08:30' => '08:15,0',
                ],
                5 => [
                    '08:00-08:30' => '08:25,0',
                ],
            ],
            2 => [
                1 => [
                    '09:00-09:30' => '09:03,0',
                ],
                2 => [
                    '18:00-18:15' => '18:05,1',
                ],
                3 => [
                    '09:00-09:30' => '09:15,0',
                ],
                4 => [
                    '09:00-09:30' => '09:25,0',
                ],
            ],
            3 => [
                2 => [
                    '13:15-13:30' => '13:20,0',
                ],
                3 => [
                    '09:00-09:30' => '09:10,0',
                    '16:45-17:00' => '16:46,1',
                ],
                4 => [
                    '09:00-09:30' => '09:25,0',
                    '16:45-17:00' => '16:54,1',
                ],
            ],
        ];

        return $maps[$personaType] ?? [];
    }

    protected static $videoTypes = [
        1 => [1, 3],
        2 => [3, 4],
        3 => [1, 3, 4],
    ];

    public static function materialPersonaPublishCron(SvDevice $device)
    {
        try {
            $devices = SvDevice::alias('d')
                ->field('d.device_code,d.auto_type,d.status,d.user_id,ap.persona_type,d.persona_id,ap.publish_mode')
                ->join('ai_persona ap', 'ap.id = d.persona_id')
                ->where('d.auto_type', 1)
                ->where('d.persona_id', '>', 0)
                ->where('ap.publish_mode', 2)
                ->where('d.device_code', $device->device_code)
                //->where('d.device_code', '0c0d339d1e5c60679d')
                ->select();
            //print_r($devices->toArray());die;
            foreach ($devices as $device) {
                $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
                if ($persona->isEmpty()) {
                    throw new \Exception('设备' . $device->device_code . '没有绑定角色');
                }
                //print_r($persona->toArray());die;
                $rule = null;
                if ($persona->persona_type == 1) {
                    $rule = $persona->individual;
                } elseif ($persona->persona_type == 2) {
                    $rule = $persona->enterprise;
                } elseif ($persona->persona_type == 3) {
                    $rule = $persona->local;
                }
                //sv视频
                $materials = Material::where('material_type', 1)
                    ->where('user_id', $device->user_id)
                    ->where('persona_id', $device->persona_id)
                    ->where('use_status', 1)
                    ->where('publish_mode', 2)
                    ->select();
                $videos = [];
                foreach ($materials as $item) {
                    $rediskey = 'material_' . $item['id'] . '_device_' . $device->device_code;
                    $device_bind_num = \think\facade\Cache::store('redis')->get($rediskey);
                    if (empty($device_bind_num)) {
                        $device_bind_num = 0;
                    }
                    if ($device_bind_num > 2) {
                        continue;
                    }
                    array_push($videos, $item);
                }

                $index = array_rand($videos, 2);
                $videos = array_map(function ($key) use ($videos) {
                    return $videos[$key];
                }, $index);
                self::runCreatePublishByMaterial($videos, $device, $rule);
            }

            return true;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'publish');
            return false;
        }
    }

    private static function runCreatePublishByMaterial($medias, SvDevice $device, $rule)
    {

        try {

            $accounts = self::getAccountsByDevice($device);
            if (empty($accounts)) {
                throw new \Exception('设备' . $device->device_code . '没有绑定账号');
            }

            $exec_times = self::getExecTime(self::getTimesByType($device->persona_type));
            $maxDay = date('Y-m-d', time());
            foreach ($medias as $mk => $media) {
                $times = $exec_times[$mk];
                foreach ($times as $timeConfig => $publishTimes) {
                    foreach ($publishTimes as $accountType => $publishTime) {

                        $account = $accounts[$accountType] ?? [];
                        if (empty($account)) {
                            continue;
                        }

                        $publishTime = strtotime($maxDay . ' ' . $publishTime);
                        $response = [
                            'code' => 10000,
                        ];
                        $status = 0;
                        $remark = '';
                        $task_id = generate_unique_task_id();
                        $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                            'keywords' => $rule->getClueContent(),
                            'task_id' => $task_id,
                            'source' => 'shanjian2',
                            'user_id' => $device->user_id,
                        ]);

                        if ((int)$response['code'] === 10000) {
                            list($st, $et) = explode('-', $timeConfig);
                            $title = $response['data']['title'] ?? '';
                            $content = $response['data']['content'] ?? '';
                            $material_url = $media->file_url != '' ? FileService::getFileUrl($media->file_url) : '';

                            usleep(500000);
                            try {
                                if ($accountType === 2) {
                                    $exist = AiWechatCircleTask::where('user_id', $device->user_id)
                                        ->where('device_code', $device->device_code)
                                        ->where('auto_type', 1)
                                        ->where('task_type',  1)
                                        ->where('wechat_id', $account['account'])
                                        ->where('persona_id', $device->persona_id)
                                        ->where('send_time', date('Y-m-d H:i:s', $publishTime))
                                        ->findOrEmpty();
                                    if (!$exist->isEmpty()) {
                                        continue;
                                    }
                                    $taskConfig = AiWechatCircleTaskConfig::create([
                                        'user_id' => $device->user_id,
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'content' => $content,
                                        'attachment_type' => 2,
                                        'attachment_content' => [$material_url],
                                        'wechat_ids' => [$account['account']],
                                        'auto_type' => 1,
                                        'status' => $status === 2 ? 3 : 1,
                                        'date' => date('Y-m-d'),
                                        'persona_id' => $device->persona_id,
                                        'time_config' => $timeConfig,
                                        'create_time' => time(),
                                        'update_time' => time(),
                                    ]);

                                    $circleTask = AiWechatCircleTask::create([
                                        'user_id' => $device->user_id,
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'task_config_id' => $taskConfig->id,
                                        'device_code' => $device->device_code,
                                        'wechat_id' => $account['account'],
                                        'task_id' => time() . rand(100, 999),
                                        'task_type' => 1,
                                        'auto_type' => 1,
                                        'content' => $content,
                                        'attachment_type' => 2,
                                        'attachment_content' => [$material_url],
                                        'send_time' => date('Y-m-d H:i:s', $publishTime),
                                        'send_status' => $status === 2 ? 3 : 0,
                                        'persona_id' => $device->persona_id,
                                        'create_time' => time()
                                    ]);
                                    \app\common\model\sv\SvDeviceTask::create([
                                        'user_id' => $device->user_id,
                                        'device_code' => $device->device_code,
                                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                                        'account' => $account['account'],
                                        'account_type' => 1,
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'auto_type' => 1,
                                        'day' => date('Y-m-d', $publishTime),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'start_time' => strtotime(date('Y-m-d ' . $st . ':00', $publishTime)),
                                        'end_time' => strtotime(date('Y-m-d ' . $et . ':00', $publishTime)) - 120,
                                        'sub_task_id' => $taskConfig->id,
                                        'sub_data_id' => $circleTask->id,
                                        'status' => $status === 2 ? 3 : 0,
                                        'remark' => $remark,
                                        'persona_id' => $device->persona_id,
                                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
                                        'create_time' => time(),
                                    ]);
                                } else {
                                    $exist = SvPublishSettingDetail::where('user_id', $device->user_id)
                                        ->where('device_code', $device->device_code)
                                        ->where('auto_type', 1)
                                        ->where('account_type', $account['type'])
                                        ->where('task_type',  99)
                                        ->where('persona_id', $device->persona_id)
                                        ->where('publish_time', date('Y-m-d H:i:s', $publishTime))
                                        ->findOrEmpty();
                                    if (!$exist->isEmpty()) {
                                        continue;
                                    }

                                    $setting = SvPublishSetting::create([
                                        'user_id' => $device->user_id,
                                        'task_type' => 99,
                                        'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                                        'auto_type' => 1,
                                        'video_setting_id' => 0,
                                        'matrix_media_setting_id' => 0,
                                        'video_ids' => json_encode([$media->id], JSON_UNESCAPED_UNICODE),
                                        'scene' => 2,
                                        'type' => 0,
                                        'media_type' => 1,
                                        'publish_start' => date('Y-m-d', $publishTime),
                                        'publish_end' => date('Y-m-d', $publishTime),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'data_type' => 0,
                                        'date_type' => 1,
                                        'publish_frep' => 1,
                                        'persona_id' => $device->persona_id,
                                        'status' => 3,
                                        'create_time' => time()
                                    ]);

                                    $paccount =  SvPublishSettingAccount::create([
                                        'publish_id' => $setting->id,
                                        'task_type' => 99,
                                        'user_id' => $device->user_id,
                                        'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
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
                                        'persona_id' => $device->persona_id,
                                        'data_type' => 0,
                                        'create_time' => time()
                                    ]);

                                    $detail = SvPublishSettingDetail::create([
                                        'publish_id' => $setting->id,
                                        'publish_account_id' => $paccount->id,
                                        'task_type' => 99,
                                        'video_task_id' => $media->id,
                                        'video_setting_id' => $media->video_setting_id,
                                        'user_id' => $device->user_id,
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'auto_type' => 1,
                                        'device_code' => $device->device_code,
                                        'matrix_media_setting_id' => 0,
                                        'material_id' => $media->id,
                                        'material_url' => $material_url,
                                        'material_title' => $title,
                                        'material_subtitle' => $content,
                                        'material_type' => 1,
                                        'material_tag' => '',
                                        'pic' => FileService::getFileUrl($media->thumbnail_url),
                                        'poi' => '',
                                        'data_type' => 0,
                                        'task_id' => $task_id,
                                        'sub_task_id' => time() . ($mk + 100),
                                        'scene' => 2,
                                        'platform' => $account['type'],
                                        'status' => $status,
                                        'remark' => $remark,
                                        'persona_id' => $device->persona_id,
                                        'publish_time' => date('Y-m-d H:i:s', $publishTime),
                                        'create_time' => time()
                                    ]);
                                    //$detail->refresh();

                                    \app\common\model\sv\SvDeviceTask::create([
                                        'user_id' => $device->user_id,
                                        'device_code' => $device->device_code,
                                        'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'auto_type' => 1,
                                        'task_name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'start_time' => strtotime(date('Y-m-d ' . $st . ':00', $publishTime)),
                                        'end_time' => strtotime(date('Y-m-d ' . $et . ':00', $publishTime)) - 120,
                                        'day' => date('Y-m-d', $publishTime),
                                        'status' => $status === 2 ? 3 : 0,
                                        'remark' => $remark,
                                        'sub_task_id' => $paccount->id,
                                        'sub_data_id' => $detail->id,
                                        'persona_id' => $device->persona_id,
                                        'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                                        'create_time' => time(),
                                    ]);
                                }
                            } catch (\Throwable $th) {
                                \think\facade\Log::channel('auto')->write('24小时视频发布任务异常：' . $th->__toString(), 'publish');
                                continue;
                            }
                        } else {

                            \think\facade\Log::channel('auto')->write('24小时视频文案异常：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
                        }
                    }
                }

                $rediskey = 'material_' . $media['id'] . '_device_' . $device->device_code;
                \think\facade\Cache::store('redis')->inc($rediskey);
                MaterialUseLog::create([
                    'material_id' => $media['id'],
                    'user_id' => $device->user_id,
                    'persona_id' => $device->persona_id,
                    'publish_mode' => 2,
                    'use_scene' => 2,
                    'use_status' => 1,
                    'task_id' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }


            //$handler->del($RUNNING_KEY);
            return true;
        } catch (\Throwable $th) {

            //$handler->del($RUNNING_KEY);
            \think\facade\Log::channel('auto')->write('根据素材生成24h视频发布任务异常：' . $th->__toString(), 'publish');
            return false;
        }
    }






    public static function shanjianPersonaPublishCron()
    {
        try {
            $devices = SvDevice::alias('d')
                ->field('d.device_code,d.auto_type,d.status,d.user_id,ap.persona_type,d.persona_id,ap.publish_mode')
                ->join('ai_persona ap', 'ap.id = d.persona_id')
                ->where('d.auto_type', 1)
                ->where('d.persona_id', '>', 0)
                ->where('ap.publish_mode', 1)
                //->where('d.device_code', '69a01a50f61050b7a1')
                ->limit(10)
                ->select();
            //print_r($devices->toArray());die;
            $maxDay = date('Y-m-d', time());
            foreach ($devices as $device) {
                $videos = ShanjianVideoTask::field('id,device_code, video_setting_id,pic, msg, video_result_url, status,persona_id, remark')
                    ->where('auto_type', 1)
                    ->where('wechat_type', 0)
                    ->where('status', 'in', [2, 3])
                    ->where('device_code', $device->device_code)
                    ->where('user_id', $device->user_id)
                    ->where('is_publish', 0)
                    ->where('persona_id', $device->persona_id)
                    ->where('shanjian_type', 'in', self::$videoTypes[$device->persona_type])
                    ->where('create_time', 'between', [strtotime($maxDay . ' 00:00:00'), strtotime($maxDay . ' 23:59:59')])
                    ->limit(2)
                    ->select();
                //print_r($videos->toArray());die;
                if (!$videos->isEmpty() && count($videos) == 2) {
                    // $param = [
                    //     'device_code' => $query->device_code,
                    //     'sj_video_id' => $query->id
                    // ];
                    self::runCreateShanjianPublish($videos, $device);
                }
            }

            return true;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('auto')->write('任务处理异常: ' . $th->__toString(), 'publish');
            return false;
        }
    }



    private static function runCreateShanjianPublish($medias, SvDevice $device)
    {


        try {

            $accounts = self::getAccountsByDevice($device);
            if (empty($accounts)) {
                throw new \Exception('设备' . $device->device_code . '没有绑定账号');
            }

            $exec_times = self::getExecTime(self::getTimesByType($device->persona_type));
            //print_r($exec_times);die;
            $maxDay = date('Y-m-d', time());
            foreach ($medias as $mk => $media) {
                $times = $exec_times[$mk];
                foreach ($times as $timeConfig => $publishTimes) {
                    foreach ($publishTimes as $accountType => $publishTime) {

                        $account = $accounts[$accountType] ?? [];
                        if (empty($account)) {
                            continue;
                            //throw new \Exception('账号' . $accountType . '不存在');
                        }

                        $publishTime = strtotime($maxDay . ' ' . $publishTime);
                        $response = [
                            'code' => 10000,
                        ];
                        $status = 0;
                        $remark = '';
                        $task_id = generate_unique_task_id();
                        if ((int)$media->status === 2) {
                            $status = 2;
                            $remark = '视频生成失败';
                        } elseif ((int)$media->status === 3) {
                            $status = 0;
                            $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                                'keywords' => $media->msg != '' ? $media->msg : '',
                                'task_id' => $task_id,
                                'source' => 'shanjian2',
                                'user_id' => $device->user_id,
                            ]);
                        }

                        if ((int)$response['code'] === 10000) {
                            list($st, $et) = explode('-', $timeConfig);
                            $title = $response['data']['title'] ?? '';
                            $content = $response['data']['content'] ?? '';
                            $material_url = $media->video_result_url != '' ? FileService::getFileUrl($media->video_result_url) : '';
                            usleep(500000);
                            try {
                                if ($accountType === 2) {
                                    $exist = AiWechatCircleTask::where('user_id', $device->user_id)
                                        ->where('device_code', $device->device_code)
                                        ->where('auto_type', 1)
                                        ->where('task_type',  1)
                                        ->where('wechat_id', $account['account'])
                                        ->where('persona_id', $device->persona_id)
                                        ->where('send_time', date('Y-m-d H:i:s', $publishTime))
                                        ->findOrEmpty();
                                    if (!$exist->isEmpty()) {
                                        continue;
                                    }
                                    $taskConfig = AiWechatCircleTaskConfig::create([
                                        'user_id' => $device->user_id,
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'content' => $content,
                                        'attachment_type' => 2,
                                        'attachment_content' => [$material_url],
                                        'wechat_ids' => [$account['account']],
                                        'auto_type' => 1,
                                        'status' => $status === 2 ? 3 : 1,
                                        'date' => date('Y-m-d'),
                                        'persona_id' => $device->persona_id,
                                        'time_config' => $timeConfig,
                                        'create_time' => time(),
                                        'update_time' => time(),
                                    ]);

                                    $circleTask = AiWechatCircleTask::create([
                                        'user_id' => $device->user_id,
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'task_config_id' => $taskConfig->id,
                                        'device_code' => $device->device_code,
                                        'wechat_id' => $account['account'],
                                        'task_id' => time() . rand(100, 999),
                                        'task_type' => 1,
                                        'auto_type' => 1,
                                        'content' => $content,
                                        'attachment_type' => 2,
                                        'attachment_content' => [$material_url],
                                        'send_time' => date('Y-m-d H:i:s', $publishTime),
                                        'send_status' => $status === 2 ? 3 : 0,
                                        'persona_id' => $device->persona_id,
                                        'create_time' => time()
                                    ]);
                                    \app\common\model\sv\SvDeviceTask::create([
                                        'user_id' => $device->user_id,
                                        'device_code' => $device->device_code,
                                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                                        'account' => $account['account'],
                                        'account_type' => 1,
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi', time()),
                                        'auto_type' => 1,
                                        'day' => date('Y-m-d', $publishTime),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'start_time' => strtotime(date('Y-m-d ' . $st . ':00', $publishTime)),
                                        'end_time' => strtotime(date('Y-m-d ' . $et . ':00', $publishTime)) - 120,
                                        'sub_task_id' => $taskConfig->id,
                                        'sub_data_id' => $circleTask->id,
                                        'status' => $status === 2 ? 3 : 0,
                                        'remark' => $remark,
                                        'persona_id' => $device->persona_id,
                                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
                                        'create_time' => time(),
                                    ]);
                                } else {
                                    $exist = SvPublishSettingDetail::where('user_id', $device->user_id)
                                        ->where('device_code', $device->device_code)
                                        ->where('auto_type', 1)
                                        ->where('account_type', $account['type'])
                                        ->where('task_type',  99)
                                        ->where('persona_id', $device->persona_id)
                                        ->where('publish_time', date('Y-m-d H:i:s', $publishTime))
                                        ->findOrEmpty();
                                    if (!$exist->isEmpty()) {
                                        continue;
                                    }
                                    
                                    $setting = SvPublishSetting::create([
                                        'user_id' => $device->user_id,
                                        'task_type' => 99,
                                        'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'accounts' => json_encode([$account], JSON_UNESCAPED_UNICODE),
                                        'auto_type' => 1,
                                        'video_setting_id' => 0,
                                        'matrix_media_setting_id' => 0,
                                        'video_ids' => json_encode([$media->id], JSON_UNESCAPED_UNICODE),
                                        'scene' => 1,
                                        'type' => 0,
                                        'media_type' => 1,
                                        'publish_start' => date('Y-m-d', $publishTime),
                                        'publish_end' => date('Y-m-d', $publishTime),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'data_type' => 0,
                                        'date_type' => 1,
                                        'publish_frep' => 1,
                                        'persona_id' => $device->persona_id,
                                        'status' => 3,
                                        'create_time' => time()
                                    ]);

                                    $paccount =  SvPublishSettingAccount::create([
                                        'publish_id' => $setting->id,
                                        'task_type' => 99,
                                        'user_id' => $device->user_id,
                                        'name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'auto_type' => 1,
                                        'device_code' => $device->device_code,
                                        'media_type' => 1,
                                        'video_setting_id' => 0,
                                        'video_ids' => json_encode([$media['id']], JSON_UNESCAPED_UNICODE),
                                        'matrix_media_setting_id' => 0,
                                        'scene' => 1,
                                        'status' => 2,
                                        'task_status' => 2,
                                        'publish_start' => date('Y-m-d', $publishTime),
                                        'publish_end' => date('Y-m-d', $publishTime),
                                        'next_publish_time' => date('Y-m-d H:i:s', $publishTime),
                                        'count' => 1,
                                        'published_count' => 0,
                                        'persona_id' => $device->persona_id,
                                        'data_type' => 0,
                                        'create_time' => time()
                                    ]);


                                    $detail = SvPublishSettingDetail::create([
                                        'publish_id' => $setting->id,
                                        'publish_account_id' => $paccount->id,
                                        'task_type' => 99,
                                        'video_task_id' => $media->id,
                                        'video_setting_id' => $media->video_setting_id,
                                        'user_id' => $device->user_id,
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'auto_type' => 1,
                                        'device_code' => $device->device_code,
                                        'matrix_media_setting_id' => 0,
                                        'material_id' => $media->id,
                                        'material_url' => $material_url,
                                        'material_title' => $title,
                                        'material_subtitle' => $content,
                                        'material_type' => 1,
                                        'material_tag' => '',
                                        'pic' => FileService::getFileUrl($media->pic),
                                        'poi' => '',
                                        'data_type' => 0,
                                        'task_id' => $task_id,
                                        'sub_task_id' => time() . ($mk + 100),
                                        'scene' => 1,
                                        'platform' => $account['type'],
                                        'status' => $status,
                                        'remark' => $remark,
                                        'persona_id' => $device->persona_id,
                                        'publish_time' => date('Y-m-d H:i:s', $publishTime),
                                        'create_time' => time()
                                    ]);
                                    //$detail->refresh();


                                    \app\common\model\sv\SvDeviceTask::create([
                                        'user_id' => $device->user_id,
                                        'device_code' => $device->device_code,
                                        'task_type' => DeviceEnum::AUTO_TYPE_PUBLISH,
                                        'account' => $account['account'],
                                        'account_type' => $account['type'],
                                        'nickname' => $account['nickname'],
                                        'avatar' => $account['avatar'],
                                        'auto_type' => 1,
                                        'task_name' => '自动化视频发布任务' . date('YmdHsi', time()),
                                        'time_config' => json_encode([$timeConfig], JSON_UNESCAPED_UNICODE),
                                        'start_time' => strtotime(date('Y-m-d ' . $st . ':00', $publishTime)),
                                        'end_time' => strtotime(date('Y-m-d ' . $et . ':00', $publishTime)) - 120,
                                        'day' => date('Y-m-d', $publishTime),
                                        'status' => $status === 2 ? 3 : 0,
                                        'remark' => $remark,
                                        'sub_task_id' => $paccount->id,
                                        'sub_data_id' => $detail->id,
                                        'persona_id' => $device->persona_id,
                                        'source' => DeviceEnum::TASK_SOURCE_PUBLISH,
                                        'create_time' => time(),
                                    ]);
                                }
                                ShanjianVideoTask::where('id', $media->id)->update([
                                    'is_publish' => 1,
                                    'update_time' => time(),
                                ]);
                            } catch (\Throwable $th) {
                                \think\facade\Log::channel('auto')->write('24小时视频发布任务异常：' . $th->__toString(), 'publish');
                                continue;
                            }
                        } else {
                            //文案生成异常时状态重置
                            ShanjianVideoTask::where('id', $media->id)->update([
                                'is_publish' => 0, //正在使用中
                                'update_time' => time(),
                            ]);

                            \think\facade\Log::channel('auto')->write('24小时视频文案异常：' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'publish');
                        }
                    }
                }
            }


            //$handler->del($RUNNING_KEY);
            return true;
        } catch (\Throwable $th) {
            //$handler->del($RUNNING_KEY);
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

    private static function getExecTime(array $times)
    {
        $res = [];
        foreach ($times as $key => $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                // 解析 "时间,标志"
                list($time, $flag) = explode(',', $value);
                $res[$flag][$slot][$key] = $time;
            }
        }
        return $res;
    }

    private static function getAccountsByDevice(SvDevice $device)
    {

        $accounts = SvAccount::field('id, account, type, nickname, avatar')
            ->where('device_code', $device->device_code)->where('user_id', $device->user_id)->select()->toArray();

        $wechat = SvAccount::field('id, account, type, nickname, avatar')
            ->where('device_code', $device->device_code)->where('user_id', $device->user_id)->where('type', 1)->findOrEmpty();
        if (!$wechat->isEmpty()) {
            $wechat->type = 2;
            array_push($accounts, $wechat->toArray());
        }
        $return = [];
        foreach ($accounts as $account) {
            $return[$account['type']] = $account;
        }
        return $return;
    }
}
