<?php

namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\auto\AutoDeviceWechatCircleConfig;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoNeedsAnalysis;
use app\common\model\sv\SvAccount;
use app\common\model\wechat\AiWechatCircleTaskConfig;
use app\common\model\wechat\AiWechatCircleTask;
use app\api\logic\device\TaskLogic;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use Exception;

/**
 * 微信朋友圈自动化配置逻辑类
 * Class AutoDeviceWechatCircleConfigLogic
 * @package app\api\logic\auto
 */
class AutoDeviceWechatCircleConfigLogic extends ApiLogic
{
    const COZE_COPYWRITING = 'cozeCopywriting';

    /**
     * 新增微信朋友圈自动化配置
     * @param array $params 请求参数
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
            $params['create_time'] = time();
            $params['update_time'] = time();

            $result = AutoDeviceWechatCircleConfig::create($params);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 更新微信朋友圈自动化配置
     * @param array $params 请求参数
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            if (!isset($params['id']) || empty($params['id'])) {
                self::setError('参数错误，缺少配置ID');
                return false;
            }

            $config = AutoDeviceWechatCircleConfig::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$config) {
                self::setError('配置不存在或无权限访问');
                return false;
            }

            if ($config->status === DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                self::setError('当前任务正在执行中，请稍后再试');
                return false;
            }

            $updateData = [
                'update_time' => time(),
                'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT,
            ];

            if (isset($params['device_config_id'])) {
                $updateData['device_config_id'] = $params['device_config_id'];
            }
            if (isset($params['device_code'])) {
                $updateData['device_code'] = $params['device_code'];
            }
            if (isset($params['video_material'])) {
                $updateData['video_material'] = $params['video_material'];
            }
            if (isset($params['image_material'])) {
                $updateData['image_material'] = $params['image_material'];
            }
            if (isset($params['industry_type'])) {
                $updateData['industry_type'] = $params['industry_type'];
            }
            if (isset($params['is_ai'])) {
                $updateData['is_ai'] = $params['is_ai'];
            }
            if (isset($params['exec_time'])) {
                $updateData['exec_time'] = $params['exec_time'];
            }
            if (isset($params['remark'])) {
                $updateData['remark'] = $params['remark'];
            }

            $updateData['exec_date'] =  date('Y-m-d');;


            $config->save($updateData);
            self::$returnData = $config->refresh()->toArray();
            return true;
        } catch (Exception $e) {
            self::setError('更新配置异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除微信朋友圈自动化配置
     * @param int $id 配置ID
     * @return bool
     */
    public static function delete(int $id): bool
    {
        try {
            $config = AutoDeviceWechatCircleConfig::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$config) {
                self::setError('配置不存在或无权限访问');
                return false;
            }

            if (!$config->delete()) {
                self::setError('删除配置失败');
                return false;
            }

            return true;
        } catch (Exception $e) {
            self::setError('删除配置异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取微信朋友圈自动化配置详情
     * @param array $params 请求参数
     * @return bool
     */
    public static function detail(array $params): bool
    {
        try {
            $config = AutoDeviceWechatCircleConfig::where('user_id', self::$uid)
                ->where('device_code', $params['device_code'])
                ->findOrEmpty();

            $account = SvAccount::where('user_id', self::$uid)
                ->where('type', 1)
                ->where('device_code', $params['device_code'])
                ->findOrEmpty()->toArray();

            if (!$config->isEmpty()) {
                $result = $config->toArray();

                $videoMaterial = $result['video_material'] ?? [];
                $imageMaterial = $result['image_material'] ?? [];
                $allMaterials = array_merge($videoMaterial, $imageMaterial);
                if (!empty($allMaterials)) {
                    shuffle($allMaterials);
                    $result['example'] = $allMaterials[0];
                } else {
                    $result['example'] = [];
                }
            } else {
                $find = AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();

                if ($find->isEmpty()) {
                    self::setError('设备自动化配置不存在');
                    return false;
                }
                $analysis = json_decode($find->analysis, true);
                $params['industry_type'] = $analysis['industryType'];
                $params['device_config_id'] = $find->id ?? 0;
                $params['video_material'] = $find->clip_material ?? [];
                $params['image_material'] = $find->image_material ?? [];
                $params['user_id'] = self::$uid;
                $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $params['exec_date'] = date('Y-m-d');
                $params['create_time'] = time();
                $params['update_time'] = time();

                $result = AutoDeviceWechatCircleConfig::create($params);
                $result = $result->toArray();
                $videoMaterial = $result['video_material'] ?? [];
                $imageMaterial = $result['image_material'] ?? [];
                $allMaterials = array_merge($videoMaterial, $imageMaterial);
                if (!empty($allMaterials)) {
                    shuffle($allMaterials);
                    $result['example'] = $allMaterials[0];
                } else {
                    $result['example'] = [];
                }
            }
            $result['copywrighting'] = '天雪场人少，雪质超好！❄️ 老板我亲自上阵测试了一把，这速度简直爽翻！';
            $result['accountInfo'] = $account;
            self::$returnData = $result;
            return true;
        } catch (Exception $e) {
            self::setError('获取配置详情异常：' . $e->getMessage());
            return false;
        }
    }
    /**
     * 处理微信朋友圈自动化配置数据
     * @param string $deviceCode 设备编码
     * @return bool
     */
    public static function processCircleData(string $deviceCode): bool
    {
        $day = date('Y-m-d');
        $tasks = AutoDeviceWechatCircleConfig::whereIn('status', [0, 2, 3])
            ->where('device_code', $deviceCode)
            ->where('exec_date', '<=', $day)
            ->limit(5)
            ->select();

        if ($tasks->isEmpty()) {
            return true;
        }

        foreach ($tasks as $task) {
            try {

                $task->status = 1;
                $task->update_time = time();
                $task->exec_date = date('Y-m-d', strtotime($day . ' +1 day'));
                $task->save();

                $videoMaterial = $task->video_material ?: [];
                $imageMaterial = $task->image_material ?: [];

                if (empty($videoMaterial) && empty($imageMaterial)) {
                    $task->status = 3;
                    $task->remark = '视频素材和图片素材都为空';
                    $task->save();
                    continue;
                }
                if ($task->is_ai == 1) {
                    $report = AutoNeedsAnalysis::where('device_code', $task->device_code)->where('user_id', $task->user_id)->where('step', 2)
                        ->order('id', 'desc')->limit(1)->findOrEmpty()->toArray();
                    if (empty($report)) {

                        $task->remark = '任务运营分析数据为空';
                        $task->status = 3;
                        $task->save();
                        continue;
                    }
                    $analysis = $report['contents'] ?? [];
                    $analysisAnalysisForm = [];
                    if (empty($analysis)) {
                        $task->remark = '任务运营分析对话数据为空';
                        $task->status = 3;
                        $task->save();
                        continue;
                    } else {
                        $analysisAnalysisForm = json_decode($analysis, true);
                        $analysisAnalysisForm = $analysisAnalysisForm['Analysis_Form'];
                    }
                    $analysisFormResult = false;
                    foreach ($analysisAnalysisForm as $key => $item) {
                        if ($key == 'contents') {
                            continue;
                        }
                        if (empty($item)) {
                            $task->remark = '未分析到设备需求,字段' . $key . '为空';
                            $task->status = 3;
                            $task->save();
                            $analysisFormResult = true;
                            break;
                        }
                    }
                    if ($analysisFormResult) {
                        continue;
                    }
                    $ipStyle = $analysisAnalysisForm['ipStyle'] ?? '';
                    $ipTalent = $analysisAnalysisForm['ipTalent'] ?? '';
                    $brandStory = $analysisAnalysisForm['brandStory'] ?? '';
                    $accountStage = $analysisAnalysisForm['accountStage'] ?? '';
                    $targetCustomers = $analysisAnalysisForm['targetCustomers'] ?? '';
                    $basicInformation = $analysisAnalysisForm['basicInformation'] ?? '';
                    $contentPreferences = $analysisAnalysisForm['contentPreferences'] ?? '';
                    $productServiceFeatures = $analysisAnalysisForm['productServiceFeatures'] ?? '';
                    $brandAchievementsPositioning = $analysisAnalysisForm['brandAchievementsPositioning'] ?? '';
                    $coze['sn'] = 3;
                    $coze['number'] = 1;
                    $coze['length'] = 80;
                    $coze['keywords'] = "我的IP是" . $ipTalent . "，语气像" . $ipStyle . "。品牌信息：" . $basicInformation .
                        "，目标客户：" . $targetCustomers . "。产品特点：" . $productServiceFeatures . "，品牌故事：" . $brandStory .
                        "。内容偏好：" . $contentPreferences . "，品牌成就与定位：" . $brandAchievementsPositioning .
                        "。账号阶段：" . $accountStage . "。";
                    $maxRetries = 3;
                    $retryCount = 0;
                    $contentmsg = '';
                    while (empty($contentmsg) && $retryCount < $maxRetries) {
                        $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $task->user_id, 4);
                        $contentmsg = $copywritingResult['content']['0'] ?? '';
                        $retryCount++;
                    }

                    $auto_type = 1;
                    $clipMaterialArray = $task->video_material ?: [];
                    shuffle($clipMaterialArray);
                    $randomLength = rand(2, 3);
                    $selectedMaterials = array_slice($clipMaterialArray, 0, $randomLength);

                    $imageMaterialArray = $task->image_material ?: [];
                    shuffle($imageMaterialArray);
                    $randomLength = rand(3, 4);
                    $selectedImageMaterials = array_slice($imageMaterialArray, 0, $randomLength);

                    $mergedMaterials = array_merge($selectedMaterials, $selectedImageMaterials);
                    $isvideo = false;
                    $materialDuration = 0;
                    foreach ($mergedMaterials as $key => &$value) {
                        if (isset($value['cover'])) {
                            $pic = $value['cover'];
                            unset($mergedMaterials[$key]['cover']);
                        }
                        if (isset($value['duration'])) {
                            $nowDuration = $value['duration'];
                        } else {
                            $nowDuration = 2;
                        }
                        $materialDuration += $nowDuration;
                        if ($materialDuration > 290 || $nowDuration > 59) {
                            unset($mergedMaterials[$key]);
                            $materialDuration -= $nowDuration;
                        }
                        if (isset($value['type']) && $value['type'] == 'video') {
                            $value['soundSwitch'] = true;
                            $isvideo = true;
                        }
                    }
                    if (!$isvideo) {
                        continue; // 跳过没有视频的任务
                    }
                    $uniqueId = generate_unique_task_id();

                    $shanjianVideoSettingData = [
                        'auto_type' => 1,
                        'device_code' => $task->device_code,
                        'user_id' => $task->user_id,
                        'name' => '自动生成朋友圈任务-素材混剪-' . date('YmdHi'),
                        'task_id' => $uniqueId,
                        'status' => 1, // 1待处理
                        'video_count' => 1,
                        'anchor' => json_encode([""], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'shanjian_type' => 3, // 设置类型
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                    $material = json_encode(array_values($mergedMaterials), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    // 素材类型需要特殊处理
                    $scene = 'oralMixCutting';
                    $shanjianVideoSettingData['material'] = $material;
                    $voiceIds = ShanjianAnchor::where('user_id', $task->user_id)->where('status', 6)->column('voice_id');
                    if (!empty($voiceIds)) {
                        $randomKey = array_rand($voiceIds);
                        $randomVoice = $voiceIds[$randomKey]; // 这就是随机选中的 ID
                    } else {
                        $task->remark = '该用户没有可用的音色';
                        $task->status = 3;
                        $task->save();
                        continue;
                    }
                    $shanjianVideoSettingData['voice'] = $randomVoice;
                    if (!$isvideo) {
                        continue; // 跳过没有视频的任务
                    }

                    $clip_template_id = ShanjianClipTemplate::where('scene', $scene)->where('auto_type', $auto_type)->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    $clip = random_int(0, $clip_template_total);
                    $clip_id =  $clip_template_id[$clip];
                    $shanjianVideoSettingData['pic'] = $pic;
                    $shanjianVideoSettingData['wechat_type'] = 1;
                    // 1. 插入到山涧视频设置表
                    $shanjianVideoSetting = ShanjianVideoSetting::create($shanjianVideoSettingData);
                    $number = random_int(1, 20);
                    $music_url = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
                    $shanjianVideoTaskData = [
                        'shanjian_type' => 3, // 设置类型
                        'device_code' => $task->device_code,
                        'name' => '自动生成朋友圈任务-素材混剪-' . date('YmdHi'),
                        'pic' =>  $pic,
                        'task_id' => generate_unique_task_id(),
                        'status' => 0, // 待处理
                        'audio_type' => 1, // 文案驱动
                        'auto_type' => 1,
                        'wechat_type' => 1,
                        'user_id' => $task->user_id,
                        'video_setting_id' => $shanjianVideoSetting->id,
                        'anchor_id' =>  '',
                        'voice_id' => $randomVoice,
                        'card_name' => '',
                        'card_introduced' =>  '',
                        'title' =>  "",
                        'msg' =>  $contentmsg,
                        'extra' => json_encode([
                            'industry_type' => $task->industry_type,
                            'exec_time' => $task->exec_time,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'material' => $material,
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'create_time' => time(),
                        'update_time' => time()
                    ];


                    $shanjianVideoTask = ShanjianVideoTask::create($shanjianVideoTaskData);
                    $task->status = 2;
                    $task->save();
                    continue;
                }
                $publishType = null;
                $selectedMaterial = [];

                if (!empty($videoMaterial) && !empty($imageMaterial)) {
                    $publishType = rand(0, 1) ? 'video' : 'image';
                } elseif (!empty($videoMaterial)) {
                    $publishType = 'video';
                } else {
                    $publishType = 'image';
                }

                if ($publishType === 'video') {
                    shuffle($videoMaterial);
                    $selectedMaterial = array_slice($videoMaterial, 0, 1);
                    $attachmentType = 2;
                } else {
                    shuffle($imageMaterial);
                    $count = rand(1, min(6, count($imageMaterial)));
                    $selectedMaterial = array_slice($imageMaterial, 0, $count);
                    $attachmentType = 1;
                }
                $selectedMaterial = array_column($selectedMaterial, 'fileUrl');

                $wechatIds = [];
                $accounts = SvAccount::where('device_code', $task->device_code)
                    ->where('type', 1)
                    ->where('user_id', $task->user_id)
                    ->select();

                foreach ($accounts as $account) {
                    $wechatIds[] = $account->account;
                }

                if (empty($wechatIds)) {
                    $task->status = 3;
                    $task->remark = '该设备没有绑定微信账号';
                    $task->save();
                    continue;
                }
                $coze['sn'] = 0;
                $coze['number'] = 1;
                $coze['length'] = 120;
                $coze['keywords'] = $task->industry_type;
                $content = '';
                $maxRetries = 3;
                $retryCount = 0;
                while (empty($content) && $retryCount < $maxRetries) {
                    $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $task->user_id, 4);
                    $content = $copywritingResult['content']['0'] ?? '';
                    $retryCount++;
                }

                $execTime = $task->exec_time ?: '["08:30-09:00"]';
                if (is_string($execTime)) {
                    $execTime = json_decode($execTime, true) ?: ['08:30-09:00'];
                }

                $sendTimeDelay = 3;
                if (!empty($execTime) && is_array($execTime)) {
                    $firstTimeRange = $execTime[0] ?? '08:30-09:00';
                    $timeParts = explode('-', $firstTimeRange);
                    if (!empty($timeParts[0])) {
                        $baseTime = trim($timeParts[0]);
                    }
                }

                $sendTimeStr = date('Y-m-d') . ' ' . ($baseTime ?? '08:30') . ':00';
                $sendTimestamp = strtotime($sendTimeStr) + ($sendTimeDelay * 60);

                $allTaskInstall = [];

                foreach ($wechatIds as $wechatId) {
                    $account = SvAccount::where('account', $wechatId)
                        ->where('type', 1)
                        ->where('user_id', $task->user_id)
                        ->limit(1)
                        ->findOrEmpty();

                    if ($account->isEmpty()) {
                        continue;
                    }

                    $taskConfig = AiWechatCircleTaskConfig::create([
                        'user_id' => $task->user_id,
                        'task_name' => '自动化朋友圈发布任务',
                        'content' => $content,
                        'attachment_type' => $attachmentType,
                        'attachment_content' => $selectedMaterial,
                        'wechat_ids' => [$wechatId],
                        'auto_type' => 1,
                        'status' => 1,
                        'date' => date('Y-m-d'),
                        'time_config' => $firstTimeRange,
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);

                    $circleTask = AiWechatCircleTask::create([
                        'user_id' => $task->user_id,
                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                        'task_config_id' => $taskConfig->id,
                        'device_code' => $account->device_code,
                        'wechat_id' => $wechatId,
                        'task_id' => time() . rand(100, 999),
                        'task_type' => 1,
                        'auto_type' => 1,
                        'content' => $content,
                        'attachment_type' => $attachmentType,
                        'attachment_content' => $selectedMaterial,
                        'send_time' => date('Y-m-d H:i:s', $sendTimestamp),
                        'date' => date('Y-m-d H:i:s', time()),
                        'send_status' => 0,
                        'create_time' => time()
                    ]);

                    $allTaskInstall[] = [
                        'user_id' => $task->user_id,
                        'device_code' => $account->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_WECHAT_CIRCLE,
                        'account' => $account->account,
                        'account_type' => $account->type,
                        'task_name' => '自动化朋友圈发布任务' . date('YmdHi'),
                        'status' => 0,
                        'auto_type' => 1,
                        'day' => date('Y-m-d', $sendTimestamp),
                        'time_config' => json_encode($execTime, JSON_UNESCAPED_UNICODE),
                        'start_time' => $sendTimestamp,
                        'end_time' => $sendTimestamp + 1800,
                        'sub_task_id' => $taskConfig->id,
                        'sub_data_id' => $circleTask->id,
                        'source' => DeviceEnum::TASK_SOURCE_WECHAT_CIRCLE_PUBLISH,
                        'create_time' => time(),
                    ];

                    TaskLogic::updateWechatRpaTaskTime($account->device_code, $sendTimestamp);
                }

                if (!empty($allTaskInstall)) {
                    TaskLogic::add($allTaskInstall);
                    $task->status = 2;
                    $task->remark = '';
                } else {
                    $task->status = 3;
                    $task->remark = '未能创建任何任务';
                }
                $task->save();
                return true;
            } catch (Exception $e) {
                \think\facade\Log::channel('wechatCircle')->info('自动化朋友圈失败' . $e->__toString());
                $task->status = 3;
                $task->remark = mb_substr($e->getMessage(), 0, 100);
                $task->save();
                return false;
            }
        }
        return true;
    }
}
