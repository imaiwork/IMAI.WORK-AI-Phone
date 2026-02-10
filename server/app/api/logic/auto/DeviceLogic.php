<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoNeedsAnalysis;
use app\common\model\sv\SvAccount;


/**
 * 设备自动任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\auto
 */
class DeviceLogic extends ApiLogic
{
    public static function add($params)
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;

            $report = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where('step', 2)->order('id', 'desc')->limit(1)->findOrEmpty();
            if ($report->isEmpty()) {
                throw new \Exception('当前设备分析报告不存在，请稍后再试');
            }

            $reportJson = json_decode($report->result, true);
            if(
                isset($reportJson['Operations']['contentType1']) && !empty($reportJson['Operations']['contentType1']) &&
                isset($reportJson['Operations']['contentType2']) && !empty($reportJson['Operations']['contentType2']) &&
                isset($reportJson['Operations']['contentType3']) && !empty($reportJson['Operations']['contentType3'])

            ){
                $params['contentType3'] = $reportJson['Operations']['contentType3'];
                $params['contentType2'] = $reportJson['Operations']['contentType2'];
                $params['contentType1'] = $reportJson['Operations']['contentType1'];
            }else{
                throw new \Exception('当前设备分析报告数据异常，请稍后再试');
            }


            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status === DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    throw new \Exception('当前设备自动任务正在执行中，请稍后再试');
                }
                $find->status          = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->human_image     = $params['human_image'];
                $find->clip_material   = $params['clip_material'];
                $find->image_material  = $params['image_material'];
                $find->clue_theme      = $params['contentType3'] ?? '';
                $find->video_theme     = $params['contentType2'] ?? '';
                $find->text_theme      = $params['contentType1'] ?? '';
                $find->update_time     = time();
                $find->analysis        = json_encode([
                    "contentType1"     => $params['contentType1'] ?? "", //内容类型1
                    "contentType2"     => $params["contentType2"] ?? "", //内容类型2
                    "contentType3"     => $params["contentType3"] ?? "", //内容类型3
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $find->save();

                $result                 = $find->toArray();
                $analysis               = !empty($find->analysis) ? json_decode($find->analysis, true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                self::$returnData       = $result;
            } else {
                $params['create_time']     = time();
                $params['update_time']     = time();
                $params['clue_theme']      = $params['contentType3'] ?? '';
                $params['video_theme']     = $params['contentType2'] ?? '';
                $params['text_theme']      = $params['contentType1'] ?? '';
                $params['analysis']        = json_encode([
                    "contentType1"     => $params['contentType1'] ?? "", //内容类型1
                    "contentType2"     => $params["contentType2"] ?? "", //内容类型2
                    "contentType3"     => $params["contentType3"] ?? "", //内容类型3
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $result                 = AutoDeviceConfig::create($params);
                $result                 = $result->toArray();
                $analysis               = !empty($result['analysis']) ? json_decode($result['analysis'], true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                self::$returnData       = $result;
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail($params)
    {
        try {
            $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                $result                       = $find->toArray();
                $analysis                     = !empty($find->analysis) ? json_decode($find->analysis, true) : [];
                $result["contentType1"]       = $analysis["contentType1"] ?? '';
                $result["contentType2"]       = $analysis["contentType2"] ?? '';
                $result["contentType3"]       = $analysis["contentType3"] ?? '';
                self::$returnData             = $result;
                self::$returnData['is_empty'] = 0;
            } else {
                self::$returnData = [
                    'device_code' => $params['device_code'],
                    'human_image' => [],
                    'clip_material' => [],
                    'image_material' => [],
                    'clue_theme' => '',
                    'video_theme' => '',
                    'text_theme' => '',
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
                    'is_empty' => 1,
                ];
                self::$returnData["contentType1"]    = '';
                self::$returnData["contentType2"]    = '';
                self::$returnData["contentType3"]    = '';
            }
            list($setting, $task_status, $is_config) = self::getAutoConfigStatus($find);
            self::$returnData['auto_setting'] = $setting;
            self::$returnData['task_status'] = $task_status;
            self::$returnData['is_config'] = $is_config;
            self::$returnData['accounts'] = SvAccount::field('id,account,type')->where('type', '<>', 1)->where('user_id', self::$uid)->where('device_code', $params['device_code'])->select();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }





    public static function opt($params)
    {
        try {
            $account = SvAccount::where('user_id', self::$uid)->where('device_code', $params['device_code'])->where('type', $params['account_type'])->findOrEmpty();
            if ($account->isEmpty()) {
                throw new \Exception('账号不存在');
            }

            \think\facade\Cache::store('redis')->handler()->select(env('redis.WS_SELECT', 8));
            $status = \think\facade\Cache::store('redis')->handler()->get("xhs:device:{$params['device_code']}:status");
            if (unserialize($status) !== 'online') {
                throw new \Exception('设备未上线');
            }

            $payload = self::getPayload($params, $account);
            if (empty($payload)) {
                throw new \Exception('模型数据生成异常');
            }
            $channel = "device.{$params['device_code']}.message";
            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => json_encode($payload)
            ]);
            self::$returnData = $payload;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function getPayload(array $params, SvAccount $account): array
    {
        $payload = [];
        switch ((int)$params['source']) {
            case DeviceEnum::AUTO_DEMO_PUBLISH_IMAGE:
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'publish_platform' => $account->type,
                        'material_id' => 0,
                        'auto_type' => 1,
                        'title' => 'XHS图文发布模拟发布标题',
                        'type' => 2,
                        'list' => [
                            'https://demo.imai.work/uploads/demo/1.png',
                            'https://demo.imai.work/uploads/demo/2.png',
                            'https://demo.imai.work/uploads/demo/3.png'
                        ],
                        'isLocation' => 0,
                        'location' => '',
                        'isScheduledTime' => true,
                        'scheduledTime' => date('Y-m-d H:i:s', time()),
                        'taskId' => 0,
                        'body' => '图文发布模拟发布内容',
                        'tag' => '#图文模拟发布',
                        'isSend' => 0,

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_DEMO_PUBLISH_VIDEO:
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'publish_platform' => $account->type,
                        'material_id' => 0,
                        'auto_type' => 1,
                        'title' => '视频发布模拟发布标题',
                        'type' => 1,
                        'list' => [
                            'https://demo.imai.work/uploads/demo/1.mp4',
                        ],
                        'isLocation' => 0,
                        'location' => '',
                        'isScheduledTime' => true,
                        'scheduledTime' => date('Y-m-d H:i:s', time()),
                        'taskId' => 0,
                        'body' => '视频发布模拟发布内容',
                        'tag' => '#视频模拟发布',
                        'isSend' => 0,

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_DEMO_CLUES:
                $keywords = [
                    'AI自动获客'
                ];
                $payload = [
                    'type' => 20,
                    'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                    'content' => json_encode([
                        'id' => 0,
                        'task_id' => 0,
                        'auto_type' => 0,
                        'platform' => DeviceEnum::getAccountTypeDesc((int)$account->type),
                        'task_type' => 'auto',
                        'device_code' => $params['device_code'],
                        'keywords' => $keywords,
                        'exec_number' => 1,
                        'is_chat' => 0,
                        'chat_number' => 10,
                        'chat_interval_time' => 10,
                        'add_type' => 0,
                        'remarks' => [],
                        'add_remark_enable' => 0,
                        'add_number' => 10,
                        'add_interval_time' => 10,
                        'greeting_content' => '',
                        'status' => 0,
                        'ocr_type' => 1,
                        'crawl_type' => 1,
                        'create_time' => time(),
                        'start_time' => time(),
                        'end_time' => time() + 60 * 5,
                        'time_interval' => 5,
                        'isSend' => 0,
                    ], JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                ];
                break;
            case DeviceEnum::AUTO_DEMO_TAKE_OVER:
                $payload = array(
                    'type' => DeviceEnum::getTakeOverType($account->type), // 接管任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'content' => '自动私信模拟发送内容',
                        'auto_type' => 1,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 5,
                        'time_interval' => 5,
                        'msg' => '接管任务运行',
                        'isSend' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_TOUCH_COMMENT:
                $keyword = ['AI自动获客'];
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_COMMENT, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'startTime' => time(),
                        'endTime' => time() + 60 * 5,
                        'timeInterval' => 5,
                        'keyword' => $keyword,
                        'hasLiked' => 1,
                        'hasFollowed' => 1,
                        'commentContents' => ['oi'],
                        'filteredKeywords' => array_merge(
                            \app\common\service\ConfigService::get('touch_clue',  'comment_screening',  []),
                            [',', '.', '?', '!', '，', '。', '！', '？', '多', '少', '钱', '可', '以', '吗']
                        ),
                        'commentCount' => 1,
                        'dmCount' => 1,
                        'noteViewCount' => 5,
                        'industry_type' => 0,
                        'city' => '',
                        'is_content_author' => 1,
                        'is_execed_clues' => 1,
                        'is_touch_like' => $setting->is_touch_like ?? 0,
                        'is_touch_follow' => $setting->is_touch_follow ?? 0,
                        'content_publish_day' => $setting->content_publish_day ?? 0,
                        'comment_publish_day' => $setting->comment_publish_day ?? 0,
                        'ip_address' => $setting->ip_address ?? [],
                        'msg' => '评论区私信任务运行',
                        'is_send' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_TOUCH_MSG:
                $keyword = ['AI自动获客'];
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_MSG, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'startTime' => time(),
                        'endTime' => time() + 60 * 5,
                        'timeInterval' => 5,
                        'keyword' => $keyword,
                        'hasLiked' => 1,
                        'hasFollowed' => 1,
                        'commentContents' => ['oi'],
                        'filteredKeywords' => array_merge(
                            \app\common\service\ConfigService::get('touch_clue',  'comment_screening',  []),
                            [',', '.', '?', '!', '，', '。', '！', '？', '多', '少', '钱', '可', '以', '吗']
                        ),
                        'commentCount' => 1,
                        'dmCount' => 1,
                        'noteViewCount' => 5,
                        'industry_type' => 0,
                        'city' => '',
                        'is_content_author' => 1,
                        'is_execed_clues' => 1,
                        'is_touch_like' => $setting->is_touch_like ?? 0,
                        'is_touch_follow' => $setting->is_touch_follow ?? 0,
                        'content_publish_day' => $setting->content_publish_day ?? 0,
                        'comment_publish_day' => $setting->comment_publish_day ?? 0,
                        'ip_address' => $setting->ip_address ?? [],
                        'msg' => '评论区私信任务运行',
                        'is_send' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_FRIENDS:
                $sendWechatIds = [
                    [
                        'friendWechatId' => 'EIGHTBITttt',
                        'message' => 'hello，你好啊',
                        'recordId' => 0,
                        'isManual' => 0,
                    ]
                ];
                $payload = array(
                    'type' => DeviceEnum::RPA_ADD_WECHAT, // 接管任务启动
                    'appType' => 0,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 5,
                        'time_interval' => 5,
                        'send_wechat_ids' => $sendWechatIds,
                        'add_interval_time' => 10,
                        'msg' => '加微任务运行',
                        'isSend' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_ACTIVE:
                $payload = array(
                    'type' => DeviceEnum::getMaintainAccountType($account->type), // 养号任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => 0,
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 5,
                        'time_interval' => 5,
                        'msg' => '养号任务运行',
                        'isSend' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_PUBLISH_CIRCLE:
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'material_id' => 0,
                        'title' => '👍',
                        'type' => 1,
                        'list' => [
                            'https://demo.imai.work/uploads/demo/1.mp4',
                        ],
                        'isLocation' => 0,
                        'location' => '',
                        'isScheduledTime' => true,
                        'scheduledTime' => date('Y-m-d H:i:s', time()),
                        'taskId' => 0,
                        'body' => '👍',
                        'tag' => '',
                        'comment' => '',
                        'isSend' => 0,
                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            case DeviceEnum::AUTO_DEMO_WECHAT_CIRCLE_THUMB_COMMENT:
                $payload = array(
                    'appType' => 1,
                    'messageId' => 0,
                    'type' => DeviceEnum::WECHAT_CIRCLE_LIKE_COMMENT,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.4.0',
                    'content' => json_encode([
                        'taskId' => 0,
                        "hasLiked" => 1, //点赞
                        "hasComment" => 1, //评论
                        "planCoverage" => 2, //当天   1、3天内   2、7天内
                        "interactionConut" => 3,  //互动数量
                        "timeInterval" => 3,  //互动间隔/分钟
                        "commentType" => 2,  //AI识别并评论   1、不评论   2、固定评论
                        "commentContent" =>  '👍', //固定评论内容
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'start_time' => time(),
                        'end_time' => time() + 60 * 5,
                        'time_interval' => 5,

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            default:
                throw new \Exception('任务类型不存在');
        }


        return $payload;
    }
    
}
