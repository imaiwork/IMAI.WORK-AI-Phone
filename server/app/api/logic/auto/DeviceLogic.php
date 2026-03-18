<?php


namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoNeedsAnalysis;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDeviceTask;


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

            $report = AutoNeedsAnalysis::where('device_code', $params['device_code'])->where('user_id', self::$uid)->where('step', 2)->order('id', 'desc')->limit(1)->findOrEmpty();
            if ($report->isEmpty()) {
                throw new \Exception('当前设备分析报告不存在，请稍后再试');
            }

            $reportJson = json_decode($report->result, true);
            if (
                isset($reportJson['Operations']['contentType1']) && !empty($reportJson['Operations']['contentType1']) &&
                isset($reportJson['Operations']['contentType2']) && !empty($reportJson['Operations']['contentType2']) &&
                isset($reportJson['Operations']['contentType3']) && !empty($reportJson['Operations']['contentType3']) &&
                isset($reportJson['Operations']['industryType']) && !empty($reportJson['Operations']['industryType'])

            ) {
                $params['contentType3'] = $reportJson['Operations']['contentType3'];
                $params['contentType2'] = $reportJson['Operations']['contentType2'];
                $params['contentType1'] = $reportJson['Operations']['contentType1'];
                $params['industryType'] = $reportJson['Operations']['industryType'];
            } else {
                throw new \Exception('当前设备分析报告数据异常，请稍后再试');
            }
    
            if (isset($params['human_image']) && !empty($params['human_image'])) {
                $humanImageData = $params['human_image'];
                foreach ($humanImageData as $index => $item) {
                    if (!isset($item['anchor_url']) || empty($item['anchor_url'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，anchor_url为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (!isset($item['width']) || empty($item['width'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，width为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (!isset($item['height']) || empty($item['height'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，height为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['chanjing_anchor_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，chanjing_anchor_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['shanjian_anchor_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，shanjian_anchor_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                    if (empty($item['voice_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，voice_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                     if (empty($item['shanjian_voice_id'])) {
                        unset($humanImageData[$index]);
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，human_image数据异常：' . json_encode($params['human_image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index . '，shanjian_voice_id为空';
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        continue;
                    }
                }
                if (count($humanImageData) > 0) {
                  $params['human_image'] = array_values($humanImageData);
                }else{
                    $params['human_image'] = [];
                }
            }
            if (isset($params['clip_material']) && !empty($params['clip_material'])) {
                $clipMaterialData = $params['clip_material'];
                foreach ($clipMaterialData as $index => $item) {
                    if (isset($item['duration']) && $item['duration'] > 59.9) {
                        $errorMsg = '用户id'.$params['user_id'].'，设备号'.$params['device_code'].'自动化新增设备配置，clip_material：' . json_encode($params['clip_material'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '，索引：' . $index;
                        \think\facade\Log::channel('automediaSetting')->write($errorMsg);
                        unset($clipMaterialData[$index]);
                    }
                }
                if (count($clipMaterialData) > 0) {
                    $params['clip_material'] = array_values($clipMaterialData);
                }else{
                    $params['clip_material'] = [];
                }
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
                    "industryType"     => $params["industryType"] ?? "", //行业类型
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $find->save();

                $result                 = $find->toArray();
                $analysis               = !empty($find->analysis) ? json_decode($find->analysis, true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                $result["industryType"] = $analysis["industryType"] ?? ''; //行业类型
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
                    "industryType"     => $params["industryType"] ?? "", //行业类型
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $result                 = AutoDeviceConfig::create($params);
                $result                 = $result->toArray();
                $analysis               = !empty($result['analysis']) ? json_decode($result['analysis'], true) : [];
                $result["contentType1"] = $analysis["contentType1"] ?? '';
                $result["contentType2"] = $analysis["contentType2"] ?? '';
                $result["contentType3"] = $analysis["contentType3"] ?? '';
                $result["industryType"] = $analysis["industryType"] ?? ''; //行业类型
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

                $imageMaterial = $find->image_material;
                if (!empty($imageMaterial)) {
                    if (!is_array($imageMaterial)) {
                        $imageMaterialArray = json_decode($imageMaterial, true) ?: [];
                    } else {
                        $imageMaterialArray = $imageMaterial;
                    }
                    $isOldFormat = false;
                    foreach ($imageMaterialArray as $item) {
                        if (is_string($item)) {
                            $isOldFormat = true;
                            break;
                        }
                    }
                    if ($isOldFormat) {
                        $newImageMaterial = [];
                        foreach ($imageMaterialArray as $url) {
                            $newImageMaterial[] = [
                                'type' => 'image',
                                'cover' => $url,
                                'fileUrl' => $url,
                                'duration' => '2',
                                'status' => '0',
                                'useNumber' => '0',
                            ];
                        }
                        $result['image_material'] = $newImageMaterial;
                    }
                }

                $clipMaterial = $find->clip_material;
                if (!empty($clipMaterial)) {
                    if (!is_array($clipMaterial)) {
                        $clipMaterialArray = json_decode($clipMaterial, true) ?: [];
                    } else {
                        $clipMaterialArray = $clipMaterial;
                    }
                    $imageMaterials = [];
                    $newClipMaterials = [];
                    foreach ($clipMaterialArray as $item) {
                        if (isset($item['type']) && $item['type'] === 'image') {
                            $imageMaterials[] = [
                                'type' => 'image',
                                'cover' => $item['cover'],
                                'fileUrl' => $item['fileUrl'],
                                'duration' => $item['duration'] ?? '2',
                                'status' => $item['status'] ?? '0',
                                'useNumber' => $item['useNumber'] ?? '0',
                            ];
                        } else {
                            $newClipMaterials[] = [
                                'type' => $item['type'],
                                'cover' => $item['cover'],
                                'fileUrl' => $item['fileUrl'],
                                'duration' => $item['duration'] ?? '2',
                                'status' => $item['status'] ?? '0',
                                'useNumber' => $item['useNumber'] ?? '0',
                            ];
                        }
                    }
                    if (!empty($imageMaterials)) {
                        $existingImageMaterial = $result['image_material'] ?? [];
                        if (is_string($existingImageMaterial)) {
                            $existingImageMaterial = json_decode($existingImageMaterial, true) ?: [];
                        }
                        $mergedImageMaterials = array_merge($existingImageMaterial, $imageMaterials);
                        $result['image_material'] = array_values($mergedImageMaterials);
                    }
                    if (!empty($newClipMaterials)) {
                        $result['clip_material'] = array_values($newClipMaterials);
                    }
                }

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


    public static function checkOpt(array $params)
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

            $content = json_decode($payload['content'], true);
            if (empty($content)) {
                throw new \Exception('模型数据生成异常');
            }

            self::$returnData = [
                'is_demo_data' => $content['isDemoData'],
                'data' => $payload,
            ];
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
                $task = self::getAutoPublishImageTask($params, $account);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'publish_platform' => $account->type,
                        'material_id' => $task['id'],
                        'auto_type' => $task['auto_type'],
                        'title' => $task['material_title'],
                        'type' => $task['material_type'],
                        'list' => $task['material_url'],
                        'isLocation' => !empty($task['poi']) ? 1 : 0,
                        'location' => $task['poi'],
                        'isScheduledTime' => true,
                        'scheduledTime' => $task['publish_time'],
                        'taskId' => $task['task_id'],
                        'body' => $task['material_subtitle'],
                        'tag' => $task['material_tag'],
                        'isSend' => 0,
                        'isDemoData' => $task['is_demo_data'],
                    ], JSON_UNESCAPED_UNICODE),

                );
                break;
            case DeviceEnum::AUTO_DEMO_PUBLISH_VIDEO:
                $task = self::getAutoPublishVideoTask($params, $account);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'publish_platform' => $account->type,
                        'material_id' => $task['id'],
                        'auto_type' => $task['auto_type'],
                        'title' => $task['material_title'],
                        'type' => $task['material_type'],
                        'list' => [
                            $task['material_url']
                        ],
                        'isLocation' => !empty($task['poi']) ? 1 : 0,
                        'location' => $task['poi'],
                        'isScheduledTime' => true,
                        'scheduledTime' => $task['publish_time'],
                        'taskId' => $task['task_id'],
                        'body' => $task['material_subtitle'],
                        'tag' => $task['material_tag'],
                        'isSend' => 0,
                        'isDemoData' => $task['is_demo_data'],

                    ], JSON_UNESCAPED_UNICODE),
                    
                );
                break;
            case DeviceEnum::AUTO_DEMO_CLUES:
                $task = self::getAutoCluesTask($params);

                $payload = [
                    'type' => 20,
                    'appType' => DeviceEnum::ACCOUNT_TYPE_SPH,
                    'content' => json_encode([
                        'id' => $task['id'],
                        'task_id' => $task['task_id'],
                        'auto_type' => 0,
                        'platform' => DeviceEnum::getAccountTypeDesc((int)$account->type),
                        'task_type' => 'auto',
                        'device_code' => $params['device_code'],
                        'keywords' => $task['keywords'],
                        'exec_number' => $task['exec_number'] ?? 10000,
                        'is_chat' => $task['chat_type'] ?? 0,
                        'chat_number' => $task['chat_number'] ?? 10,
                        'chat_interval_time' => $task['chat_interval_time'] ?? 10,
                        'add_type' => 0,
                        'remarks' => [],
                        'add_remark_enable' => $task['add_remark_enable'] ?? 0,
                        'add_number' => $task['add_number'] ?? 10,
                        'add_interval_time' => $task['add_interval_time'] ?? 10,
                        'greeting_content' => $task['greeting_content'] ?? '',
                        'status' => 0,
                        'ocr_type' => $task['ocr_type'] ?? 1,
                        'crawl_type' => $task['crawl_type'] ?? 1,
                        'create_time' => time(),
                        'start_time' => $task['start_time'] ?? time(),
                        'end_time' => $task['end_time'] ?? time() + 60 * 5,
                        'time_interval' => ($task['end_time'] - $task['start_time']) / 60,
                        'isSend' => 0,
                        'isDemoData' => $task['is_demo_data'],
                    ], JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                    
                ];
                break;
            case DeviceEnum::AUTO_DEMO_TAKE_OVER:
                //$task = self::getAutoTakeOverTask($params);
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
                        'isDemoData' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_TOUCH_COMMENT:
                $task = self::getAutoTouchTask($params, 1);
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_COMMENT, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task['id'],
                        'auto_type' => 1,
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'startTime' => $task['start_time'],
                        'endTime' => $task['end_time'],
                        'timeInterval' => ($task['end_time'] - $task['start_time']) / 60,
                        'keyword' => $task['industry'],
                        'hasLiked' => $task['is_like'],
                        'hasFollowed' => $task['is_follow'],
                        'commentContents' => $task['content'],
                        'filteredKeywords' => $task['filter'],
                        'commentCount' => $task['send_num'],
                        'dmCount' => $task['send_num'],
                        'noteViewCount' => $task['industry_num'],
                        'industry_type' => $task['industry_type'],
                        'city' => $task['city'],
                        'is_content_author' => $task['is_content_author'],
                        'is_execed_clues' => $task['is_execed_clues'],
                        'is_touch_like' => $task['is_like'],
                        'is_touch_follow' => $task['is_follow'],
                        'content_publish_day' => $task['content_publish_day'] ?? 0,
                        'comment_publish_day' => $task['comment_publish_day'] ?? 0,
                        'ip_address' => $task['ip_address'] ?? [],
                        'is_note_like' => $task['is_like'] ?? 0,
                        'msg' => '评论区私信任务运行',
                        'is_send' => 0,
                        'isDemoData' => $task['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                    
                );
                break;
            case DeviceEnum::AUTO_DEMO_TOUCH_MSG:
                $task = self::getAutoTouchTask($params, 2);
                $payload = array(
                    'type' => DeviceEnum::TASK_COMMENT_TO_MSG, // 评论区评论任务启动
                    'appType' => $account->type,
                    'content' => json_encode(array(
                        'task_id' => $task['id'],
                        'auto_type' => $task['auto_type'],
                        'deviceId' => $params['device_code'],
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'startTime' => $task['start_time'],
                        'endTime' => $task['end_time'],
                        'timeInterval' => ($task['end_time'] - $task['start_time']) / 60,
                        'keyword' => $task['industry'],
                        'hasLiked' => $task['is_like'],
                        'hasFollowed' => $task['is_follow'],
                        'commentContents' => $task['content'],
                        'filteredKeywords' => $task['filter'],
                        'commentCount' => $task['send_num'],
                        'dmCount' => $task['send_num'],
                        'noteViewCount' => $task['industry_num'],
                        'industry_type' => $task['industry_type'],
                        'city' => $task['city'],
                        'is_content_author' => $task['is_content_author'],
                        'is_execed_clues' => $task['is_execed_clues'],
                        'is_touch_like' => $task['is_like'],
                        'is_touch_follow' => $task['is_follow'],
                        'content_publish_day' => $task['content_publish_day'] ?? 0,
                        'comment_publish_day' => $task['comment_publish_day'] ?? 0,
                        'ip_address' => $task['ip_address'] ?? [],
                        'is_note_like' => $task['is_like'] ?? 0,
                        'msg' => '评论区私信任务运行',
                        'is_send' => 0,
                        'isDemoData' => $task['is_demo_data'],
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                    
                );
                break;
            case DeviceEnum::AUTO_DEMO_FRIENDS:
                $sendWechatIds = self::getAutoFriendTask($params);
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
                        'isDemoData' => count($sendWechatIds) > 1 ? 0 : 1,
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
                        'isDemoData' => 0,
                    ), JSON_UNESCAPED_UNICODE),
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'messageId' => 0,
                );
                break;
            case DeviceEnum::AUTO_DEMO_PUBLISH_CIRCLE:

                $task = self::getAutoPublishCircleTask($params, $account);
                $payload = array(
                    'appType' => $account->type,
                    'messageId' => 0,
                    'type' => 5,
                    'deviceId' => $params['device_code'],
                    'appVersion' => '2.7.3',
                    'content' => json_encode([
                        'publish_platform' => 2,
                        'material_id' => $task['material_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'list' => $task['list'],
                        'isLocation' => 0,
                        'location' => '',
                        'isScheduledTime' => true,
                        'scheduledTime' => $task['send_time'] ?? date('Y-m-d H:i:s', time()),
                        'taskId' => $task['material_id'],
                        'body' => $task['body'],
                        'tag' => $task['tag'] ?? '',
                        'comment' => $task['comment'] ?? '',
                        'isSend' => 0,
                        'isDemoData' => $task['is_demo_data'],
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
                        'isDemoData' => 1,

                    ], JSON_UNESCAPED_UNICODE)
                );
                break;
            default:
                throw new \Exception('任务类型不存在');
        }


        return $payload;
    }

    private static function getAutoPublishVideoTask(array $params, SvAccount $account)
    {
        $payload = [
            'publish_platform' => $account->type,
            'id' => 0,
            'auto_type' => 1,
            'material_title' => '视频发布模拟发布标题',
            'material_type' => 1,
            'material_url' => 'https://demo.imai.work/uploads/demo/2.mp4',
            'poi' => 0,
            'publish_time' => date('Y-m-d H:i:s', time()),
            'task_id' => 0,
            'material_subtitle' => '视频发布模拟发布内容',
            'material_tag' => '#视频模拟发布',
            'is_demo_data' => 1,
        ];

        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', 'in', [DeviceEnum::TASK_TYPE_PUBLISH, DeviceEnum::AUTO_TYPE_PUBLISH])
            ->where('account_type', $account->type)
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\sv\SvPublishSettingDetail::alias('ps')
            ->field('ps.*')
            ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
            ->where('s.id', $task->sub_task_id)
            ->where('ps.device_code', '=', $task->device_code)
            ->where('ps.account', $task->account)
            ->where('s.account_type', $task->account_type)
            ->where('ps.material_type', 1)
            ->where('ps.data_type', 0)
            ->order('ps.publish_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $publish->id = 0;
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoPublishImageTask(array $params, SvAccount $account)
    {
        $payload = [
            'publish_platform' => $account->type,
            'id' => 0,
            'auto_type' => 1,
            'material_title' => 'XHS图文发布模拟发布标题',
            'material_type' => 2,
            'material_url' => [
                'https://demo.imai.work/uploads/demo/1.png',
                'https://demo.imai.work/uploads/demo/2.png',
                'https://demo.imai.work/uploads/demo/3.png'
            ],
            'poi' => 0,
            'publish_time' => date('Y-m-d H:i:s', time()),
            'task_id' => 0,
            'material_subtitle' => 'XHS图文发布模拟发布副标题',
            'material_tag' => '',
            'is_demo_data' => 1,
        ];

        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', 'in', [DeviceEnum::TASK_TYPE_PUBLISH, DeviceEnum::AUTO_TYPE_PUBLISH])
            ->where('account_type', $account->type)
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\sv\SvPublishSettingDetail::alias('ps')
            ->field('ps.*')
            ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
            ->where('s.id', $task->sub_task_id)
            ->where('ps.device_code', '=', $task->device_code)
            ->where('ps.account', $task->account)
            ->where('s.account_type', $task->account_type)
            ->where('ps.material_type', 2)
            ->where('ps.data_type', 0)
            ->order('ps.publish_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $publish->material_url = explode(',', $publish->material_url);
        $publish->id = 0;
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoPublishCircleTask(array $params, SvAccount $account)
    {
        $payload = [
            'material_id' => 0,
            'title' => '👍',
            'type' => 1,
            'list' => [
                'https://demo.imai.work/uploads/demo/1.mp4',
            ],
            'taskId' => 0,
            'body' => '👍',
            'tag' => '',
            'comment' => '',
            'is_demo_data' => 1,
        ];

        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', DeviceEnum::TASK_TYPE_WECHAT_CIRCLE)
            ->where('account_type', $account->type)
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
       
        if ($task->isEmpty()) {
            return $payload;
        }

        $publish = \app\common\model\wechat\AiWechatCircleTask::field('*')
            ->where('id', $task->sub_data_id)
            ->where('device_code', '=', $task->device_code)
            ->where('wechat_id', $task->account)
            ->order('send_time desc')
            ->limit(1)
            ->findOrEmpty();
        if ($publish->isEmpty()) {
            return $payload;
        }
        $publish->id = 0;
        $publish->publish_platform = $account->type;
        $publish->material_id = $publish->id;
        $publish->title = $publish->content;
        $publish->list = $publish->attachment_content;
        $publish->body = $publish->content;
        $publish->type = $publish->attachment_type == 1 ? 2 : 1;
        $publish->is_demo_data = 0;
        return $publish->toArray();
    }

    private static function getAutoFriendTask(array $params)
    {
        $payload = [
            [
                'friendWechatId' => 'EIGHTBITttt',
                'message' => 'hello，你好啊',
                'recordId' => 0,
                'isManual' => 0,
            ]
        ];

        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', 'in', [DeviceEnum::TASK_TYPE_CLUES_WECHAT, DeviceEnum::AUTO_TYPE_WECHAT_FRIEND])
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            return $payload;
        }

        $records = \app\common\model\sv\SvAddWechatRecord::alias('r')
            ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
            ->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
            ->where('r.device_code', $params['device_code'])
            ->where('r.status', 'in', [2, 3, 4, 5])
            ->order('r.id', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $remarks = \app\common\service\ConfigService::get('add_remark',  'wechat',  []);
        foreach ($records as $record) {
            $remark = $remarks[array_rand($remarks)] ?? '您好！';
            array_push($payload, [
                'friendWechatId' => $record['reg_wechat'],
                'message' => $remark, //ai生成打招呼消息
                'recordId' => $record['id'],
                'isManual' => 0,
            ]);
        }
        return $payload;
    }

    private static function getAutoTouchTask(array $params, int $task_scene)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'auto_type' => 1,
            'start_time' => time(),
            'end_time' => time() + 60 * 5,
            'industry' => ['AI自动获客'],
            'is_like' => 1, //点赞
            'is_follow' => 1, //评论
            'content' => ['你好'],
            'filter' => array_merge(
                \app\common\service\ConfigService::get('touch_clue',  'comment_screening',  []),
                [',', '.', '?', '!', '，', '。', '！', '？', '多', '少', '钱', '可', '以', '吗']
            ),
            'send_num' => 1,
            'industry_num' => 5,
            'industry_type' => 0,
            'city' => '',
            'is_content_author' => 1, //是否评论内容作者
            'is_execed_clues' => 1, //是否执行过获客任务
            'content_publish_day' => 0, //评论内容发布时间间隔/天
            'comment_publish_day' => 0, //评论发布时间间隔/天
            'ip_address' => [], //IP地址
            'is_demo_data' => 1,
        ];

        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', 'in', [DeviceEnum::TASK_TYPE_TOUCH, DeviceEnum::AUTO_TYPE_COMMENT_CLUE])
            ->where('task_scene', $task_scene)
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            return $payload;
        }

        $account = \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->where('task_type', $task_scene)->findOrEmpty();
        if ($account->isEmpty()) {
            return $payload;
        }

        $setting = \app\common\model\sv\SvLeadScrapingSetting::where('id', $account->scraping_id)->where('task_type', $task_scene)->findOrEmpty();
        if ($setting->isEmpty()) {
            return $payload;
        }
        $setting->id = 0;
        $setting->task_id = $task->id;
        $setting->auto_type = $task->task_type;
        $setting->device_code = $task->device_code;
        $setting->account = $task->account;
        $setting->account_type = $task->type;
        $setting->start_time = $task->start_time;
        $setting->end_time = $task->end_time;
        $setting->industry = !empty($setting->industry) ? json_decode($setting->industry, true) : [];
        $setting->content = !empty($setting->content) ? json_decode($setting->content, true) : [];
        $setting->filter = !empty($setting->filter) ? json_decode($setting->filter, true) : [];
        $setting->is_demo_data = 0;
        return $setting->toArray();
    }

    private static function getAutoCluesTask(array $params)
    {
        $payload = [
            'id' => 0,
            'task_id' => 0,
            'auto_type' => 1,
            'crawl_type' => 1,
            'keywords' => ['AI自动获客'],
            'exec_number' => 1,
            'chat_type' => 0,
            'chat_number' => 10,
            'chat_interval_time' => 10,
            'add_type' => 0,
            'add_remark_enable' => 0,
            'add_number' => 10,
            'add_interval_time' => 10,
            'greeting_content' => '',
            'ocr_type' => 1,
            'crawl_type' => 1,
            'create_time' => time(),
            'start_time' => time(),
            'end_time' => time() + 60 * 5,
            'time_interval' => 5,
            'is_demo_data' => 1,
        ];
        $task = SvDeviceTask::where('device_code', $params['device_code'])
            ->where('task_type', 'in', [DeviceEnum::TASK_TYPE_CLUES, DeviceEnum::AUTO_TYPE_CLUES])
            ->where('user_id', self::$uid)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($task->isEmpty()) {
            return $payload;
        }

        $find = \app\common\model\sv\SvCrawlingTask::alias('ct')
            ->field('ct.*, b.device_code,b.keywords')
            ->join('sv_crawling_task_device_bind b', 'ct.id = b.task_id')
            ->where('ct.id', $task->sub_task_id)
            ->where('b.device_code', $task->device_code)
            ->fetchSql(false)
            ->findOrEmpty();
        if ($find->isEmpty()) {
            return $payload;
        }
        $find->id = 0;
        $find->task_id = $task->id;
        $find->keywords = json_decode($find->keywords, true);
        $find->is_demo_data = 0;
        //print_r($find->toArray());die;
        return $find->toArray();
    }
}
