<?php

namespace app\api\logic\auto;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\DeviceEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\auto\AutoDeviceConfig;
use app\common\model\auto\AutoDeviceSetting;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use app\common\model\user\User;
use think\facade\Db;
use Exception;

/**
 * 自动设备设置逻辑类
 * Class AutoDeviceSettingLogic
 * @package app\api\logic\auto
 */
class AutoDeviceSettingLogic extends ApiLogic
{
    const COPYWRITING_CREATE = 'copywritingCreate'; //文案创作
    const NEWS_MIXCUT_TITLE = 'newsMixcutTitle'; //新闻标题
    const COMBINED_PICTURE_TITLE = 'combinedPictureTitle'; //组合图片标题
    /**
     * 新增自动设备设置
     * @param array $params 请求参数
     * @return bool
     */
    public static function addAutoDeviceSetting(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['status'] = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;

            $find = AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                if ($find->status === DeviceEnum::AUTO_CONFIG_STATUS_RUNNING) {
                    throw new \Exception('当前设备自动任务正在执行中，请稍后再试');
                }
                $find->status = DeviceEnum::AUTO_CONFIG_STATUS_WAIT;
                $find->human_image = $params['human_image'];
                $find->clip_material = $params['clip_material'];
                $find->image_material = $params['image_material'];
                $find->video_theme = $params['video_theme'];
                $find->text_theme = $params['text_theme'];
                if(is_null($find->exec_date)){
                    $find->exec_date = date('Y-m-d', strtotime('+1 day'));
                }
                $find->update_time = time();
                $find->save();
                self::$returnData = $find->toArray();
            } else {
                $params['create_time'] = time();
                $params['update_time'] = time();
                $params['execution_day'] = date('Y-m-d', time());
                $result = AutoDeviceSetting::create($params);
                self::$returnData = $result->toArray();
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 更新自动设备设置
     * @param array $params 请求参数
     * @return bool
     */
    public static function updateAutoDeviceSetting(array $params): bool
    {
        try {
            if (!isset($params['id']) || empty($params['id'])) {
                self::setError('参数错误，缺少自动设备设置ID');
                return false;
            }

            $setting = AutoDeviceSetting::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('自动设备设置不存在或无权限访问');
                return false;
            }

            $updateData = [];
            if (isset($params['type'])) {
                $updateData['type'] = $params['type'];
            }
            if (isset($params['device_config_id'])) {
                $updateData['device_config_id'] = $params['device_config_id'];
            }
            if (isset($params['device_code'])) {
                $updateData['device_code'] = $params['device_code'];
            }
            if (isset($params['human_image'])) {
                $updateData['human_image'] = $params['human_image'];
            }
            if (isset($params['clip_material'])) {
                $updateData['clip_material'] = $params['clip_material'];
            }
            if (isset($params['image_material'])) {
                $updateData['image_material'] = $params['image_material'];
            }
            if (isset($params['video_theme'])) {
                $updateData['video_theme'] = $params['video_theme'];
            }
            if (isset($params['text_theme'])) {
                $updateData['text_theme'] = $params['text_theme'];
            }
            if (isset($params['status'])) {
                $updateData['status'] = $params['status'];
            }
            if (isset($params['execution_day'])) {
                $updateData['execution_day'] = $params['execution_day'];
            }
            if (isset($params['remark'])) {
                $updateData['remark'] = $params['remark'];
            }

            if (empty($updateData)) {
                self::setError('没有需要更新的数据');
                return false;
            }

            $setting->save($updateData);
            self::$returnData = $setting->refresh()->toArray();
            return true;
        } catch (Exception $e) {
            self::setError('更新自动设备设置异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除自动设备设置
     * @param int $id 自动设备设置ID
     * @return bool
     */
    public static function deleteAutoDeviceSetting(int $id): bool
    {
        try {
            $setting = AutoDeviceSetting::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('自动设备设置不存在或无权限访问');
                return false;
            }

            if (!$setting->delete()) {
                self::setError('删除自动设备设置失败');
                return false;
            }

            return true;
        } catch (Exception $e) {
            self::setError('删除自动设备设置异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取自动设备设置详情
     * @param int $id 自动设备设置ID
     * @return bool
     */
    public static function getAutoDeviceSettingDetail($params): bool
    {
        try {
            $find = AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $params['device_code'])->findOrEmpty();
            if (!$find->isEmpty()) {
                self::$returnData = $find->toArray();
            } else {
                self::$returnData = [
                    'device_code' => $params['device_code'],
                    'human_image' => [],
                    'clip_material' => [],
                    'image_material' => [],
                    'clue_theme' => '',
                    'video_theme' => '',
                    'text_theme' => '',
                    'status' => DeviceEnum::AUTO_CONFIG_STATUS_WAIT
                ];
            }
            return true;
        } catch (Exception $e) {
            self::setError('获取社媒发布平台详情异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取自动设备设置列表
     * @return bool
     */
    public static function autoDeviceSetting(): bool
    {
        try {
            $settings = AutoDeviceSetting::where('user_id', self::$uid)
                ->select();

            if ($settings->isEmpty()) {
                self::setError('没有找到自动设备设置记录');
                return false;
            }

            self::$returnData = $settings->toArray();
            return true;
        } catch (Exception $e) {
            self::setError('获取自动设备设置列表异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 处理human_image数据并插入到相关表
     * @return bool
     */
    public static function processHumanImageData(string $deviceCode)
    {
        try {
            $day = date('Y-m-d');
            // 获取自动设备设置记录
            $tasks = AutoDeviceSetting::whereIn('status', [0, 2, 3])
                ->where('device_code', $deviceCode)
                ->where('execution_day', '<=', $day)
                ->limit(5)
                ->select();
            if ($tasks->isEmpty()) {
                return true;
            }
            $allResults = [];
            $currentTime = time();
            // 遍历处理每个任务
            foreach ($tasks as $task) {
                $task->status = 1;
                $execution_day = date('Y-m-d', strtotime($task->execution_day) + 2 * 86400);
                $task->execution_day = $execution_day;
                $task->save();
                Db::startTrans();
                try {

                    // 获取并验证human_image数据
                    $newhumanImage = $humanImage = $task->human_image;
                    $videoTheme = $task->video_theme;
                    $textTheme = $task->text_theme;
                    array_splice($newhumanImage, 0, 1);
                    $task->human_image = $newhumanImage;
                    // 定义4种shanjiang_type类型
                    $shanjiangTypes = [1, 2, 3, 5, 4, 6];
                  //  $shanjiangTypes = [1,2,3,4];
                    $currentResults = [];
                    $copywritingBreak = false;
                    // 为每种类型创建对应的记录
                    foreach ($shanjiangTypes as $type) {
                        $uniqueId = generate_unique_task_id();

                        if (in_array($type, [1,2,5])) {
                             $firstHumanImage = $humanImage[0] ?? null;
                            if (empty($firstHumanImage)) {
                                continue; // 跳过没有元素的任务
                            }
                        }
                        // 根据类型获取对应的文案结果
                        if (in_array($type, [1, 2, 3, 5]) && !$copywritingBreak) {
                            $firstVideoTheme = $videoTheme;
                            if (empty($firstVideoTheme)) {
                                continue; // 跳过没有元素的任务
                            }
                            $shanjian = [
                                'keywords' => $firstVideoTheme
                            ];
                            $copywritingResult[0] = self::copywriting($shanjian, $task->user_id, 1);
                            if (!$copywritingResult[0]) {
                                throw new \Exception("视频文案生成失败");
                                continue;
                            }
                            $copywritingBreak = true;
                        } elseif ($type == 4) {
                            $firstVideoTheme = $videoTheme ?? null;
                            if (empty($firstVideoTheme)) {
                                continue; // 跳过没有元素的任务
                            }
                            $shanjian = [
                                'keywords' => $firstVideoTheme
                            ];
                            $copywriting = self::copywriting($shanjian, $task->user_id, 2);
                            if (!$copywriting) {
                                throw new \Exception("新闻体文案生成失败");
                                continue;
                            }
                        }elseif ($type == 6) {
                            $firstTextTheme = $textTheme;
                            if (empty($firstTextTheme)) {
                                continue; // 跳过没有元素的任务
                            }
                        }

                        // 根据类型设置名称前缀
                        $typeName = '';
                        switch ($type) {
                            case 1:
                                $typeName = '数字人口播';
                                break;
                            case 2:
                                $typeName = '真人口播';
                                break;
                            case 3:
                                $typeName = '素材';
                                break;
                            case 4:
                                $typeName = '新闻体';
                                break;
                            case 5:
                                $typeName = '数字人口播';
                                break;
                            case 6:
                                $typeName = '拼图';
                                break;
                            default:
                                $typeName = '未知类型';
                        }

                        if (in_array($type, [1, 2, 3, 4])) {
                            $shanjianVideoSettingData = [
                                'auto_type' => 1,
                                'device_code' => $task->device_code,
                                'user_id' => $task->user_id,
                                'name' => '自动生成任务-' . $typeName . '-' . date('YmdHi'),
                                'pic' => $firstHumanImage['pic'] ?? '',
                                'task_id' => $uniqueId,
                                'status' => 1, // 1待处理
                                'video_count' => 1,
                                'anchor' => json_encode([$firstHumanImage], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'shanjian_type' => $type, // 设置类型
                                'create_time' => $currentTime,
                                'update_time' => $currentTime
                            ];

                            switch ($type) {
                                case 1:
                                case 2:
                                    $material = json_encode($task->clip_material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $anchor_id =  [
                                        [
                                            "anchor_id" => $firstHumanImage['shanjian_anchor_id'] ?? '',
                                            "pic" =>  $firstHumanImage['pic'] ?? '',
                                            "anchor_url" =>  $firstHumanImage['anchor_url'] ?? '',
                                            "name" => $firstHumanImage['name'] ?? '',
                                        ]
                                    ];
                                    $pic = $firstHumanImage['pic'] ?? '';
                                    $shanjianVideoSettingData['anchor'] = json_encode($anchor_id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                    $shanjianVideoSettingData['material'] = $material;

                                    $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $voice =  [
                                        [
                                            "voice_id" => $firstHumanImage['shanjian_voice_id'] ?? '',
                                        ]
                                    ];
                                    $shanjianVideoSettingData['voice'] = json_encode($voice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                    $scene = $type == 1 ? 'virtualman' : 'realMan';

                                    $isvideo = true;
                                    // 数字人口播和真人口播共享相同的处理逻辑
                                    //   $material = json_encode([$firstHumanImage], JSON_UNESCAPED_UNICODE);
                                    break;
                                case 3:
                                    $material = $task->clip_material;
                                    $isvideo = false;

                                    foreach ($material as $key => &$value) {
                                        if (isset($value['cover'])) {
                                            $pic = $value['cover'];
                                            unset($material[$key]['cover']);
                                        }
                                        if (isset($value['type']) && $value['type'] == 'video') {
                                            $value['soundSwitch'] = true;
                                            $isvideo = true;
                                        }
                                    }
                                    if (!$isvideo) {
                                        break; // 跳过没有视频的任务
                                    }

                                    $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    // 素材类型需要特殊处理
                                    $scene = 'oralMixCutting';
                                    $shanjianVideoSettingData['material'] = $material;
                                    $shanjianVideoSettingData['voice'] =  $firstHumanImage['shanjian_voice_id'] ?? '';

                                    break;
                                case 4:
                                    $material = $task->clip_material;
                                    foreach ($material as $key => &$value) {
                                        if (isset($value['cover'])) {
                                            $pic = $value['cover'];
                                            unset($material[$key]['cover']);
                                        }
                                        if (isset($value['type']) && $value['type'] == 'video') {
                                            $value['soundSwitch'] = true;
                                            $isvideo = true;
                                        }
                                    }
                                   
                                    // 使用预先生成的文案
                                    $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                                    $copywritingResult4[0]['title'] = $copywriting['content']['0'];
                                    $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult4, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $shanjianVideoSettingData['voice'] =  $firstHumanImage['shanjian_voice_id'] ?? '';
                                    $shanjianVideoSettingData['material'] = $material;
                                    // 新闻体类型需要特殊处理
                                    $scene = 'newsMixCutting';

                                    break;
                                default:
                                    $material = ''; // 默认空字符串
                                    break;
                            }
                            if (!$isvideo) {
                                continue; // 跳过没有视频的任务
                            }

                            $shanjian_voice_id = $firstHumanImage['shanjian_voice_id'] ?? '';
                            if (!$shanjian_voice_id  && in_array($type,[1,3])) {
                                continue;
                            }
                            $clip_template_id = ShanjianClipTemplate::where('scene', $scene)->column('id');
                            $clip_template_total = count($clip_template_id) - 1;
                            $clip = random_int(0, $clip_template_total);
                            $clip_id =  $clip_template_id[$clip];
                            $shanjianVideoSettingData['pic'] = $pic;
                            // 1. 插入到山涧视频设置表
                            $shanjianVideoSetting = ShanjianVideoSetting::create($shanjianVideoSettingData);
                            $number = random_int(1, 20);
                            $music_url = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
                            $taskTitle = $copywritingResult[0]['title'] ?? '';
                            $taskMsg = $copywritingResult[0]['content'] ?? '';
                            if ($type == 4) {
                                $title = json_decode($copywritingResult4[0]['title'], true);
                                $taskTitle = implode('\n', $title);
                                $taskMsg = '';
                            }

                            $shanjianVideoTaskData = [
                                'shanjian_type' => $type, // 设置类型
                                'device_code' => $task->device_code,
                                'name' => '自动生成任务-' . $typeName . '-' . date('YmdHi'),
                                'pic' =>  $pic,
                                'task_id' => generate_unique_task_id(),
                                'status' => 0, // 待处理
                                'audio_type' => 1, // 文案驱动
                                'auto_type' => 1,
                                'user_id' => $task->user_id,
                                'video_setting_id' => $shanjianVideoSetting->id,
                                'anchor_id' => $firstHumanImage['shanjian_anchor_id'] ?? '',
                                'voice_id' => $firstHumanImage['shanjian_voice_id'] ?? '',
                                'card_name' => '',
                                'card_introduced' =>  '',
                                'title' =>  $taskTitle,
                                'msg' =>  $taskMsg,
                                'material' => $material,
                                'music_url' => $music_url,
                                'clip_id' => $clip_id,
                                'extra' => json_encode([
                                    'setting_index' => 1,
                                    'create_type' => 'batch'
                                ], JSON_UNESCAPED_UNICODE),
                                'create_time' => time(),
                                'update_time' => time()
                            ];

                            if ($type == 2) {
                                $shanjianVideoTaskData['anchor_id'] = $firstHumanImage['anchor_url'];
                            }
                            $shanjianVideoTask = ShanjianVideoTask::create($shanjianVideoTaskData);

                            // 保存当前类型的结果
                            $currentResults[] = [
                                'type' => $type,
                                'type_name' => $typeName,
                                'shanjian_video_setting_id' => $shanjianVideoSetting->id,
                                'shanjian_video_task_id' => $shanjianVideoTask->id
                            ];
                        } elseif ($type == 5) {
                            // 3. 插入到SV视频设置表
                            $svVideoSettingData = [
                                'user_id' => $task->user_id,
                                'name' => '自动生成-' . $typeName . '-' . date('YmdHi'),
                                'pic' => $firstHumanImage['pic'] ?? '',
                                'task_id' => $uniqueId,
                                'automatic_clip' => 0,
                                'model_version' => $firstHumanImage['model_version'] ?? 7,
                                'status' => 1, // 1待处理
                                'ai_type' => 0,
                                'video_count' => 1,
                                'device_code' => $task->device_code,
                                'auto_type' => 1,
                                'anchor' => json_encode($firstHumanImage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'copywriting' => json_encode($copywritingResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'create_time' => time(),
                                'update_time' => time()
                            ];
                            $svVideoSetting = SvVideoSetting::create($svVideoSettingData);
                            $anchor_id = $firstHumanImage['anchor_id'] ?? '';
                            if (empty($anchor_id)) {
                                $status = 0;
                            } else {
                                $status = 10;
                            }
                            // 4. 插入到SV视频任务表
                            $svVideoTaskData = [
                                'user_id' => $task->user_id,
                                'video_setting_id' => $svVideoSetting->id,
                                'name' =>  '自动生成任务-' . $typeName . '-' . date('YmdHi'),
                                'task_id' => generate_unique_task_id(),
                                'type' => 3, // 3小红书
                                'device_code' => $task->device_code,
                                'speed' => 1,
                                'auto_type' => 1,
                                'width' => $firstHumanImage['width'] ?? 720,
                                'height' => $firstHumanImage['height'] ?? 1280,
                                'pic' => $firstHumanImage['pic'] ?? '',
                                'automatic_clip' => 1,
                                'status' => $status, // 0待处理
                                'gender' => $firstHumanImage['gender'] ?? 'male',
                                'model_version' => $firstHumanImage['model_version'] ?? 7,
                                'upload_video_url' => $firstHumanImage['anchor_url'] ?? '',
                                'anchor_name' => $firstHumanImage['name'] ?? '',
                                'anchor_id' => $firstHumanImage['chanjing_anchor_id'] ?? '',
                                'msg' => $copywritingResult[0]['content'] ?? '',
                                'audio_type' => 1, // 1文案驱动
                                'extra' => json_encode([
                                    'combination' => '0_0_0',
                                    'anchor' => $firstHumanImage
                                ], JSON_UNESCAPED_UNICODE),
                                'create_time' => $currentTime,
                                'update_time' => $currentTime
                            ];
                            SvVideoTask::create($svVideoTaskData);

                        } elseif ($type == 6) {
                            $puzzleDate['keywords'] =  $firstTextTheme;
                            // 根据素材数量智能选择合适的数量
                            $materialCount = count($task->image_material);
                            $imagedMaterial = $task->image_material;
                            if ($materialCount < 4) {
                                $hdparams = [
                                    'user_id' => $task->user_id,
                                    'name' => '自动生成任务-' . $typeName . '-' . date('YmdHi'),
                                    'task_id' => $uniqueId,
                                    'copywriting' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'result_count' => 0,
                                    'task_count' => 1,
                                    'device_code' => $task->device_code,
                                    'error_num' => 1,
                                    'auto_type' => 1,
                                    'material' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'status' => 5, // 1待处理
                                    'create_time' => $currentTime,
                                    'update_time' => $currentTime
                                ];
                                $puzzleSetting = HdPuzzleSetting::create($hdparams);

                                $puzzletask[] = [
                                    'user_id' => $task->user_id,
                                    'puzzle_setting_id' => $puzzleSetting->id,
                                    'name' =>  '自动生成任务-' . $typeName  . '生成失败-' .  1,
                                    'task_id' => generate_unique_task_id(),
                                    'auto_type' => 1,
                                    'device_code' => $task->device_code,
                                    'status' => 2,
                                    'remark' => '图片素材只有' . $materialCount . '张，不足4张',
                                    'type' => 2,
                                    'title' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'material' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'create_time' => time(),
                                    'update_time' => time()
                                ];
                                (new HdPuzzle())->saveAll($puzzletask);
                                continue;
                            }

                            $sliceNum = $task_count = $result_count = $xhnum = $puzzleDate['number'] = 0;
                            if ($materialCount > 13) {
                                $xhnum = floor($materialCount / 9);
                                $materialCount = $materialCount - $xhnum * 9;
                                $puzzleDate['number'] = $xhnum * 5;
                                $result_count = 20 * $xhnum;
                                $task_count = 5 * $xhnum;
                                $sliceNum = $xhnum * 9;
                            }
                            if ($materialCount > 8) {
                                $puzzleDate['number'] += 5;
                                $result_count +=  20;
                                $task_count += 5;
                                $sliceNum += 9;
                            } elseif ($materialCount > 5) {
                                $puzzleDate['number'] += 4;
                                $result_count += 16;
                                $task_count += 4;
                                $sliceNum += 6;
                            } elseif ($materialCount > 3) {
                                // 使用全部素材，并从原始数组中清空
                                $puzzleDate['number'] += $materialCount == 5 ? 3 : 2;
                                $result_count += $materialCount == 5 ? 12 : 8;
                                $task_count += $materialCount == 5 ? 3 : 2;
                                $sliceNum += $materialCount == 5 ? 5 : 4;
                            }
                            $hdpuzzleResults = self::copywriting($puzzleDate, $task->user_id, 3);
                            if (!$hdpuzzleResults) {
                                continue;
                            }
                            $hdCopywriting = [];
                            foreach ($hdpuzzleResults['content'] as  $hd) {
                                $hdCopywriting[]['title'] = $hd;
                            }
                            // 获取总素材数组（所有可用素材）
                            $selectedMaterial = $task->image_material;
                            array_splice($imagedMaterial, 0, $sliceNum);
                            // dd($imagedMaterial);
                            $task->image_material = $imagedMaterial;
                            // typeMap定义：type对应的素材数量
                            $typeMap = [
                                5 => 9,
                                4 => 6,
                                3 => 5,
                                2 => 4,
                                1 => 3
                            ];

                            // 创建主任务设置
                            $hdparams = [
                                'user_id' => $task->user_id,
                                'name' => '自动生成任务-' . $typeName . '-' . date('YmdHi'),
                                'task_id' => $uniqueId,
                                'copywriting' => json_encode($hdCopywriting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'result_count' => $result_count,
                                'task_count' => $task_count,
                                'device_code' => $task->device_code,
                                'auto_type' => 1,
                                'material' => json_encode(array_slice($selectedMaterial, 0, $sliceNum), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'status' => 1, // 1待处理
                                'create_time' => $currentTime,
                                'update_time' => $currentTime
                            ];
                            $puzzleSetting = HdPuzzleSetting::create($hdparams);

                            $puzzletask = [];
                            $currentType = self::getTemplateType(count($selectedMaterial));
                            $cycleMaterial =  array_slice($selectedMaterial, 0, $typeMap[$currentType]);
                            // 外层循环：处理每个文案
                            foreach ($hdCopywriting as $copywritingIndex => $hd) {
                                // 获取初始currentType（基于剩余素材数量）
                                if ($currentType < 1) {
                                    array_splice($selectedMaterial, 0, 9);
                                    $currentType = self::getTemplateType(count($selectedMaterial));
                                    $cycleMaterial = array_slice($selectedMaterial, 0, $typeMap[$currentType]);
                                }
                                $currentMaterial = array_slice($cycleMaterial, 0, $typeMap[$currentType]);
                                // 创建子任务
                                $puzzletask[] = [
                                    'user_id' => $task->user_id,
                                    'puzzle_setting_id' => $puzzleSetting->id,
                                    'name' =>  '自动生成任务-' . $typeName . '-' . (count($puzzletask) + 1),
                                    'task_id' => generate_unique_task_id(),
                                    'auto_type' => 1,
                                    'device_code' => $task->device_code,
                                    'status' => 0,
                                    'type' => $currentType,
                                    'title' => json_encode($hd['title'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'material' => json_encode($currentMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    'create_time' => time(),
                                    'update_time' => time()
                                ];

                                // type递减
                                $currentType--;
                            }
                            if (!empty($puzzletask)) {
                                (new HdPuzzle())->saveAll($puzzletask);
                            }
                        }
                    }

                    // 更新字段
                    $task->status = 2;
                    // 保存修改后的素材数组到任务对象
                    $task->save();
                    // // 收集当前任务的处理结果
                
                    // 提交事务
                    Db::commit();
                } catch (Exception $e) {
                  //  var_dump($e->__tostring());
                    \think\facade\Log::channel('auto')->info('自动化失败' . $e->__toString());
                    // 回滚事务
                    Db::rollback();
                    $task->status = 3;
                    $task->save();
                    continue;
                }
            }

            // 设置返回数据
            self::$returnData = [];

            return true;
        } catch (Exception $e) {
            \think\facade\Log::channel('auto')->info('自动化失败' . $e->__toString());
            self::setError('处理human_image数据异常：' . $e->getMessage());
            return false;
        }
    }

    private static function getTemplateType($materialCount)
    {
        // 优先选择9张图，然后是6张图，5张图，4张图，3张图，2张图
        if ($materialCount >= 9) {
            return 5; // 9张图
        } elseif ($materialCount >= 6) {
            return 4; // 6张图
        } elseif ($materialCount >= 5) {
            return 3; // 5张图
        } elseif ($materialCount >= 4) {
            return 2; // 4张图
        } else {
            return 1; // 2-3张图
        }
    }

    private static function copywriting(array $data, int $userId, $type)
    {
        try {

            switch ($type) {
                case 1:
                    $scene = self::COPYWRITING_CREATE;
                    $channelVersion = 5;
                    $number = $data['number'] ?? 500;

                    break;
                case 2:
                    $scene = self::NEWS_MIXCUT_TITLE;
                    $channelVersion = 7;
                    $number = $data['number'] ?? 1;
                    break;
                case 3:
                    $scene = self::COMBINED_PICTURE_TITLE;
                    $channelVersion = 8;
                    $number = $data['number'] ?? 1;
                    break;
                default:
                    throw new \Exception('参数错误');
            }
            $keywords = $data['keywords'] ?? '';
            if (empty($keywords) || empty($number)) {
                throw new \Exception('参数错误');
            }

            $taskId = generate_unique_task_id();
            $request = [
                'keywords' => $keywords,
                'number' => $number,
                'channelVersion' => $channelVersion,
            ];
            $result = self::requestUrl($request, $scene, $userId, $taskId);
            if (!empty($result) && isset($result['content'])) {
                return $result;
            } else {
                throw new \Exception('生成失败');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {

            [$tokenScene, $tokenCode] = match ($scene) {
                self::COPYWRITING_CREATE => ['shanjian_copywriting_create', AccountLogEnum::TOKENS_DEC_COZE_TEXT],
                self::NEWS_MIXCUT_TITLE => ['news_mixcut_title', AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_TITLE],
                self::COMBINED_PICTURE_TITLE => ['combined_picture_title', AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE_TITLE],
            }; //计费
            $unit = TokenLogService::checkToken($userId, $tokenScene); // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now'] = time();
            switch ($scene) {
                case self::COPYWRITING_CREATE:
                    $response = \app\common\service\ToolsService::Shanjian()->text($request);
                    break;
                case self::NEWS_MIXCUT_TITLE:
                    $response = \app\common\service\ToolsService::Coze()->newsmixcuttitle($request);
                    break;
                case self::COMBINED_PICTURE_TITLE:
                    $response = \app\common\service\ToolsService::Coze()->title($request);
                    break;

                default:
            } //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $points = $unit;
                if ($points > 0) {
                    $break = true;
                    $extra = [];
                    switch ($scene) {
                        case self::COPYWRITING_CREATE:
                            $break = false;
                            $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::NEWS_MIXCUT_TITLE:
                            $break = false;
                            $extra = ['生成文案条数' => 1, '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::COMBINED_PICTURE_TITLE:
                            $break = false;
                            $extra = ['生成标题条数' => 1, '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        default:
                    }
                    if ($break) {
                        return $response['data'] ?? [];
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
                return $response['data'] ?? [];
            }
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
