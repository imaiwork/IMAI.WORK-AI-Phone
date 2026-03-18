<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\model\ModelConfig;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use think\facade\Db;

/**
 * 闪剪视频设置逻辑处理
 * Class ShanjianVideoSettingLogic
 * @package app\api\logic\shanjian
 */
class ShanjianVideoSettingLogic extends ApiLogic
{
    /**
     * 添加闪剪视频设置
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            // 验证audio和copywriting不能同时存在
            $audio = $decodedData['audio'] ?? [];
            $copywriting = $decodedData['copywriting'] ?? [];

            if (!empty($audio) && !empty($copywriting)) {
                self::setError("audio参数和copywriting参数不能同时存在，只能使用其中一个");
                return false;
            }

            // 根据使用的参数类型计算时长
            $duration = 0;
            if (!empty($audio)) {
                // 当使用audio参数时，计算audio内容的时长
                foreach ($audio as $key => $value) {
                    $duration += 30;
                }
            } else {
                // 当使用copywriting参数时，计算文案内容的时长
                foreach ($copywriting as $key => $value) {
                    if (!empty($value['content'])) {
                        $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                    }
                }
                if ($duration > 0) {
                    $duration = $duration / 3;
                }
            }
            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $video_count = $extra['video_count'] ?? 0;
            if($video_count <= 0){
                self::setError("视频数量必须大于0，当前值为：$video_count");
                return false;
            }
            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;

            // 根据使用的参数类型计算视频数量
            if (!empty($audio)) {
                $params['video_count'] = count($audio) *  $video_count;
            } else {
                $params['video_count'] = count($copywriting) * $video_count;
            }
            // 开始事务
            Db::startTrans();
            try {
                $duration = $duration * $video_count;
                $unit = TokenLogService::checkToken(self::$uid, 'human_video_shanjian', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasks($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新闪剪视频设置
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $setting = ShanjianVideoSetting::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'title', 'character_design', 'material', 'clip', 'music', 'extra'];

            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            $params['update_time'] = time();

            // 开始事务
            Db::startTrans();
            try {
                $setting->save($params);

                // 如果状态变为1，重新创建视频任务
                if (isset($params['status']) && $params['status'] == 1) {
                    // 删除旧的视频任务
                    ShanjianVideoTask::where('video_setting_id', $params['id'])->delete();
                    // 创建新的视频任务
                    self::createVideoTasks($params['id'], $params, $decodedData);
                } elseif (isset($params['status']) && $params['status'] == 0) {
                    // 如果状态变为0，删除所有关联的视频任务
                    ShanjianVideoTask::where('video_setting_id', $params['id'])->delete();
                }

                Db::commit();
                self::$returnData = $setting->refresh()->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateName(array $params): bool
    {
        try {
            $find = ShanjianVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($find->isEmpty()) {
                self::setError('视频设置不存在');
                return false;
            }
            $find->name = $params['name'];
            $find->update_time = time();
            $find->save();
            self::$returnData = $find->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 获取闪剪视频设置详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $setting = ShanjianVideoSetting::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            $settingData = $setting->toArray();

            // 处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($settingData[$field])) {
                    $settingData[$field] = json_decode($settingData[$field], true);
                } else {
                    $settingData[$field] = [];
                }
            }

            self::$returnData = $settingData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除闪剪视频设置
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {
            if (is_string($id)) {
                ShanjianVideoSetting::destroy(['id' => $id]);
            } else {
                ShanjianVideoSetting::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 创建视频任务
     * @param int $settingId
     * @param array $params
     * @return void
     */
    private static function createVideoTasks(int $settingId, array $params, $decodedData): void
    {
        $taskData = [];
        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];

        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_id', $data) || trim($data['anchor_id']) === '') {
                throw new \Exception("形象不存在");
            }
        }

        if (count($voiceData) == 0) {
            throw new \Exception("音色不能为空");
        }
        foreach ($voiceData as $data) {
            if (!array_key_exists('voice_id', $data) || trim($data['voice_id']) === '') {
                throw new \Exception("音色还没有生成");
            }
        }

        // 验证文案或音频数据
        if (empty($audioData) && empty($copywritingData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证copywriting数据
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        // 解析控制参数：1=顺序，0=随机
        $controlParams = $extraData ?? [];
        $humanMode = $controlParams['human'] ?? 1; // human控制anchorData
        $musicMode = $controlParams['music'] ?? 1; // music控制music_url
        $clipMode = $controlParams['clip'] ?? 1; // clip控制clip_id
        $materialMode = $controlParams['material'] ?? 1; // material控制material选择
        $aimusicMode = $controlParams['ai_music'] ?? false; 

        $copywritingDatanum = count($copywritingData) * 0.5;
        $materialDatanum = count($materialData);
        $randcopywriting = false;
        if ($materialDatanum > $copywritingDatanum && $materialDatanum > 4) {
            $randcopywriting = true;
        }

        // 确定每个文案/音频要生成的视频数量
        $videoCountPerItem = $extraData['video_count'] ?? 5; // 每个文案/音频生成的视频数量

        // 确定主要数据源（文案或音频）
        $primaryDataSource = !empty($audioData) ? $audioData : $copywritingData;
        $primaryDataCount = count($primaryDataSource);

        $globalTaskIndex = 0; // 全局任务索引

        // 遍历每个文案/音频，为每个生成指定数量的视频
        for ($dataIndex = 0; $dataIndex < $primaryDataCount; $dataIndex++) {
            for ($videoIndex = 0; $videoIndex < $videoCountPerItem; $videoIndex++) {

                // 获取当前数据项
                $currentDataItem = $primaryDataSource[$dataIndex];

                // 人设选择：1=顺序，0=随机
                $selectedAnchor = null;
                $selectedVoice = null;

                if ($humanMode == 1) {
                    // 顺序选择：形象按顺序循环使用
                    $anchorIndex = $globalTaskIndex % count($anchorData);
                    $voiceIndex = $globalTaskIndex % count($voiceData);
                    $selectedAnchor = $anchorData[$anchorIndex];
                    $selectedVoice = $voiceData[$voiceIndex];
                } else {
                    // 随机选择：文案和形象随机匹配
                    $randomAnchorIndex = random_int(0, count($anchorData) - 1);
                    $randomVoiceIndex = random_int(0, count($voiceData) - 1);
                    $selectedAnchor = $anchorData[$randomAnchorIndex];
                    $selectedVoice = $voiceData[$randomVoiceIndex];
                }

                $number = random_int(1, 20);
                $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                // 音乐选择：1=顺序，0=随机
                if (count($musicData) == 0) {
                    if ($aimusicMode) {
                         $music_url = $music;
                    } else {
                        $music_url = '';
                    }
                } else {
                    if ($musicMode == 1) {
                        // 顺序选择
                        $music_url = $musicData[$globalTaskIndex % count($musicData)] ?? $music;
                    } else {
                        // 随机选择
                        $randomIndex = random_int(0, count($musicData) - 1);
                        $music_url = $musicData[$randomIndex] ?? $music;
                    }
                }

                // 剪辑模板选择：1=顺序，0=随机
                if (count($clipData) == 0) {
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'virtualman')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    if ($clipMode == 1) {
                        // 顺序选择
                        $clip = $globalTaskIndex % ($clip_template_total + 1);
                        $clip_id = $clip_template_id[$clip];
                    } else {
                        // 随机选择
                        $clip = random_int(0, $clip_template_total);
                        $clip_id = $clip_template_id[$clip];
                    }
                } else {
                    if ($clipMode == 1) {
                        // 顺序选择
                        $clip_id = $clipData[$globalTaskIndex % count($clipData)]['clip_template_id'] ?? '';
                    } else {
                        // 随机选择
                        $randomIndex = random_int(0, count($clipData) - 1);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? '';
                    }
                }

                // 素材选择：1=顺序，0=随机
                if ($materialMode == 1) {
                    // 顺序选择：使用全部素材
                    $material = json_encode($materialData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // 随机选择：根据randcopywriting逻辑随机选择素材
                    if ($randcopywriting) {
                        $numberOfItems = rand(3, 4);
                        $randomKeys = array_rand($materialData, $numberOfItems);
                        if (is_array($randomKeys)) {
                            // 如果抽取多个元素
                            $selectedMaterial = array_intersect_key($materialData, array_flip($randomKeys));
                        } else {
                            // 如果抽取一个元素
                            $selectedMaterial = [$materialData[$randomKeys]];
                        }
                        $material = json_encode(array_values($selectedMaterial), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    } else {
                        // 简单随机选择一个素材
                        $randomIndex = random_int(0, count($materialData) - 1);
                        $selectedMaterial = [$materialData[$randomIndex]];
                        $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }

                // 确定任务内容（文案或音频）
                $taskContent = [];
                $audioType = 1; // 默认文案驱动

                if (!empty($audioData)) {
                    // 音频驱动模式
                    $audioItem = $audioData[$dataIndex % count($audioData)] ?? null;
                    if ($audioItem) {
                        // 支持音频数据格式：字符串或对象
                        if (is_string($audioItem)) {
                            $taskContent['audio_url'] = trim($audioItem, " `'\"`");
                        } elseif (is_array($audioItem) && isset($audioItem['url'])) {
                            $taskContent['audio_url'] = $audioItem['url'];
                        } else {
                            $taskContent['audio_url'] = '';
                        }
                        $audioType = 2; // 音频驱动
                    }
                }

                if (!empty($copywritingData)) {
                    // 文案数据
                    $copywritingItem = $copywritingData[$dataIndex % count($copywritingData)] ?? null;
                    if ($copywritingItem) {
                        $taskContent['title'] = $copywritingItem['title'] ?? '';
                        $taskContent['content'] = $copywritingItem['content'] ?? '';
                        if ($audioType != 2) {
                            $taskContent['audio_url'] = ''; // 文案模式下不设置audio_url
                        }
                    }
                }

                $extra = [
                    'setting_index' => $globalTaskIndex,
                    'create_type' => 'batch',
                    'data_source_index' => $dataIndex,
                    'video_index_in_source' => $videoIndex
                ];
                $mergedArray = array_merge($extra, $extraData);
//                $taskname = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($globalTaskIndex + 1);
                $taskname = $taskContent['title'] ?? date('YmdHis'). '数字人口播混剪';
                $taskname = mb_substr( $taskname, 0, 120, 'UTF-8');
                $taskItem = [
                    'name' => $taskname,
                    'pic' => $selectedAnchor['pic'] ?? '',
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => $audioType,
                    'user_id' => self::$uid,
                    'video_setting_id' => $settingId,
                    'anchor_id' => $selectedAnchor['anchor_id'] ?? '',
                    'voice_id' => $selectedVoice['voice_id'] ?? '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $taskContent['title'] ?? '',
                    'msg' => $taskContent['content'] ?? '',
                    'audio_url' => $taskContent['audio_url'] ?? '',
                    'material' => $material,
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time()
                ];

                $taskData[] = $taskItem;
                $globalTaskIndex++;
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }


    public static function check()
    {

        try {
            ShanjianVideoSetting::whereIn('status', [1, 2])
                ->where('create_time', '<=', strtotime('-1440 minutes'))
                ->select()->each(function ($item) {

                    $item->success_num = ShanjianVideoTask::where('video_setting_id', $item->id)->where('status', 3)->count();
                    if ($item->success_num > 0) {
                        $update['error_num'] = $item->video_count - $item->success_num;
                        $update['status'] = 3;
                    } else {
                        $update['error_num'] = $item->video_count;
                        $update['status'] = 3;
                    };
                    ShanjianVideoSetting::where('id', $item->id)->update($update);
                });
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 计算组合数 C(n,k)
     * @param int $n 总数
     * @param int $k 选取数量
     * @return int 组合数结果
     */
    private static function combination(int $n, int $k): int
    {
        if ($k > $n || $k < 0) return 0;
        if ($k == 0 || $k == $n) return 1;
        $k = min($k, $n - $k); // 取较小值以减少计算
        $result = 1;
        for ($i = 1; $i <= $k; $i++) {
            $result = $result * ($n - $k + $i) / $i;
        }
        return (int)$result;
    }

    /**
     * 计算视频生成数量上限
     * @param int $materialCount 素材数量
     * @param int $themeVideoCount 主题视频数量
     * @return int 视频生成数量上限
     */
    private static function calculateMaxVideoCount(int $materialCount, int $themeVideoCount = 1): int
    {
        if ($materialCount <= 0 || $themeVideoCount <= 0) return 0;

        // 计算所有可能的素材组合数
        $combinationCount = 0;

        // 单素材组合: C(n,1)
        $combinationCount += self::combination($materialCount, 1);

        // 双素材组合: C(n,2)
        if ($materialCount >= 2) {
            $combinationCount += self::combination($materialCount, 2);
        }

        // 三素材组合: C(n,3)
        if ($materialCount >= 3) {
            $combinationCount += self::combination($materialCount, 3);
        }

        // 全素材组合: C(n,n)
        $combinationCount += self::combination($materialCount, $materialCount);

        // 计算最终上限：组合数 * 主题视频数量
        return $combinationCount * $themeVideoCount;
    }

    /**
     * 类型2视频设置添加
     * @param array $params 请求参数
     * @return bool
     */
    public static function addType2(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $copywriting = $decodedData['copywriting'] ?? [];
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;
            $themeVideoCount = !empty($decodedData['anchor']) && is_array($decodedData['anchor']) ? count($decodedData['anchor']) : 0;

            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $themeVideoCount;
            if ($params['video_count'] == 0) {
                self::setError("形象数量不能为0或者生成的视频数量不能为0");
                return false;
            }
            $anchor = $decodedData['anchor'] ?? [];
            if (count($anchor) == 0) {
                self::setError("形象不能为空");
                return false;
            }
            $duration = 0;
            if (!empty($anchor) && is_array($anchor)) {
                // 计算单个anchor的平均时长
                $totalDuration = 0;
                $anchorCount = 0;
                foreach ($anchor as $key => $value) {
                    if (!empty($value['duration'])) {
                        $totalDuration += $value['duration'];
                        $anchorCount++;
                    }
                }
                // 计算平均时长，然后乘以video_count
                $singleAnchorDuration = $anchorCount > 0 ? ($totalDuration / $anchorCount) : 0;
                $duration = $singleAnchorDuration * $video_count;
            }

            $params['status'] = 1;
            // 开始事务
            Db::startTrans();
            try {
                TokenLogService::checkToken(self::$uid, 'shanjian_realman_broadcast', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType2($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType2(int $settingId, array $params, $decodedData): void
    {

        $taskData = [];

        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];

        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_url', $data) || trim($data['anchor_url']) === '') {
                throw new \Exception("视频不存在");
            }
        }

        // 模式控制参数：1=按顺序，0=随机
        $controlParams = $extraData ?? [];
        $humanMode = $controlParams['human'] ?? 1; // 控制anchorData选择模式
        $musicMode = $controlParams['music'] ?? 1; // 控制music_url选择模式
        $clipMode = $controlParams['clip'] ?? 1; // 控制clip_id选择模式
        $video_count = $controlParams['video_count'] ?? 1; // 每个素材生成的视频数量
        $aimusicMode = $controlParams['aimusic'] ?? false; // 控制是否使用默认音乐

        // 获取基础资源数量
        $anchorCount = count($anchorData);
        $musicCount = count($musicData);
        $materialCount = count($materialData);
        $clipCount = count($clipData);

        // 外层循环：遍历每个素材
        foreach ($anchorData as $anchorIndex => $currentAnchor) {
            // 内层循环：每个素材生成$video_count个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if ($musicCount > 0) {
                    if ($musicMode == 1) {
                        // 按顺序选择音乐，基于素材索引*视频数量+视频索引循环
                        $musicIndex = $videoIndex % $musicCount;
                        // 检查当前索引的音乐是否有值
                        if (isset($musicData[$musicIndex]) && !empty($musicData[$musicIndex])) {
                            $music_url = $musicData[$musicIndex];
                            $musicStrategy = 'sequential';
                        } else {
                            // 当前索引不存在，从初始开始循环选择
                            $music_url = $musicData[0] ?? $defaultMusic;
                            $musicStrategy = 'sequential_restart_from_beginning';
                        }
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 没有music数据时，每个视频使用不同的随机音乐
                     if ($aimusicMode) {
                         $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 按顺序选择剪辑模板，当$video_count > $anchorCount时使用不同模板
                        $clipIndex = ($anchorIndex * $video_count + $videoIndex) % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 没有clip数据时，从数据库获取模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'realMan')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // 编码为JSON
                $material = json_encode($materialData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // 计算当前视频的全局索引
                $globalIndex = $anchorIndex * $video_count + $videoIndex;

                $extra = [
                    'setting_index' => $globalIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop',
                    'control_mode' => [
                        'human' => $humanMode,
                        'music' => $musicMode,
                        'clip' => $clipMode
                    ],
                    'selection_info' => [
                        'anchor_index' => $anchorIndex,
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'video_count_per_material' => $video_count,
                        'total_videos' => $materialCount * $video_count,
                        'material_index' => $anchorIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];
                $mergedArray = array_merge($extra, $extraData);
               // $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_形象' . ($anchorIndex + 1) . '_视频' . ($videoIndex + 1);
                // 生成视频名称时包含素材和视频信息
                $videoName = date('YmdHis'). '真人口播混剪';
                $modeSuffix = '';
                if ($humanMode == 0) $modeSuffix .= 'H';
                if ($musicMode == 0) $modeSuffix .= 'M';
                if ($clipMode == 0) $modeSuffix .= 'C';

                if (!empty($modeSuffix)) {
                    $videoName .= '_' . $modeSuffix;
                }
                $videoName = mb_substr( $videoName, 0, 120, 'UTF-8');
                $taskItem = [
                    'name' => $videoName,
                    'pic' => $currentAnchor['pic'] ?? '',
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => 1, // 文案驱动
                    'shanjian_type' => $params['shanjian_type'] ?? 1,
                    'user_id' => self::$uid,
                    'video_setting_id' => $settingId,
                    'anchor_id' => $currentAnchor['anchor_url'] ?? '',
                    'voice_id' => '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => '',
                    'msg' => '',
                    'material' => $material,
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time()
                ];

                $taskData[] = $taskItem;
            } // 内层循环结束

        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    public static function addType3(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');

            // 预处理JSON字段
            $jsonFields = ['copywriting', 'material', 'clip', 'music', 'extra', 'audio'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            // 验证audio和copywriting不能同时存在
            $audio = $decodedData['audio'] ?? [];
            $copywriting = $decodedData['copywriting'] ?? [];

            if (!empty($audio) && !empty($copywriting)) {
                self::setError("audio参数和copywriting参数不能同时存在，只能使用其中一个");
                return false;
            }

            // 根据使用的参数类型计算时长
            $duration = 0;
            if (!empty($audio)) {
                // 当使用audio参数时，计算audio内容的时长
                foreach ($audio as $key => $value) {
                    $duration += 30;
                }
            } else {
                // 当使用copywriting参数时，计算文案内容的时长
                foreach ($copywriting as $key => $value) {
                    if (!empty($value['content'])) {
                        $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                    }
                }
                if ($duration > 0) {
                    $duration = $duration / 3;
                }
            }
            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $params['status'] = 1;
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;

            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $materialCount;
            if ($params['video_count'] == 0) {
                self::setError("素材数量不能为0或者生成的视频数量不能为0");
                return false;
            }

            // 开始事务
            Db::startTrans();
            try {
                $unit = TokenLogService::checkToken(self::$uid, 'shanjian_broadcast_mixcut', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType3($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType3(int $settingId, array $params, $decodedData): void
    {


        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        $aimusicMode = $extraData['aimusic'] ?? false; // 控制是否使用默认音乐



        $voice=  $params['voice']??'';
        if (empty($voice)) {
            throw new \Exception("语音不能为空");
        }
        // 验证素材数据
        if (count($materialData) == 0) {
            throw new \Exception("素材不能为空");
        }

        // 获取模式控制参数
        $musicMode = $extraData['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $extraData['clip'] ?? 1; // 1=顺序，0=随机
        $contentMode = $extraData['content_mode'] ?? 1; // 1=顺序，0=随机
        $video_count = $extraData['video_count'] ?? 1; // 每个素材生成的视频数量

        // 计算资源数量
        $materialCount = count($materialData);
        $copywritingCount = count($copywritingData);
        $audioCount = count($audioData);
        $clipCount = count($clipData);

        // 验证文案或音频数据（其中一个必须存在）
        if (empty($audioData) && empty($copywritingData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证copywriting数据
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }
        // 外层循环：遍历每个素材
        foreach ($materialData as $materialIndex => $currentMaterial) {
            // 内层循环：每个素材生成 $video_count 个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // 计算当前视频的全局索引
                $globalIndex = $materialIndex * $video_count + $videoIndex;

                // ===== 素材选择逻辑 =====
                // 当前素材就是选中的素材
                $selectedMaterial = $currentMaterial;
                $pic = "";
                foreach ($selectedMaterial as $key => &$value) {
                    if (isset($value['cover'])) {
                        $pic = $value['cover'];
                        unset($selectedMaterial[$key]['cover']);
                    }
                    if (isset($value['type']) && $value['type'] == 'video') {
                        $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                        $value['soundSwitch'] = $soundSwitch === "true" ? true : false;
                    }
                }
                $materialJson = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 顺序选择剪辑模板
                        $clipIndex = $globalIndex % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 获取剪辑模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'oralMixCutting')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;
                    // 数据验证
                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    // 从数据库获取模板
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if (count($musicData) > 0) {
                    if ($musicMode == 1) {
                        // 顺序选择音乐
                        $musicIndex = $globalIndex % count($musicData);
                        $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                        $musicStrategy = 'sequential';
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 控制是否使用默认音乐
                    if ($aimusicMode) {
                        $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }

                // ===== 内容选择逻辑（文案或音频）=====
                $taskItem = [];

                if (!empty($copywritingData)) {
                    // 使用文案
                    $copywritingItem = null;

                    if ($contentMode == 1) {
                        // 顺序选择文案
                        $copywritingIndex = $globalIndex % $copywritingCount;
                        $copywritingItem = $copywritingData[$copywritingIndex];
                        $contentStrategy = 'sequential_copywriting';
                    } else {
                        // 随机选择文案
                        $copywritingIndex = array_rand($copywritingData);
                        $copywritingItem = $copywritingData[$copywritingIndex];
                        $contentStrategy = 'random_copywriting';
                    }
                    $taskItem = [
                        'pic' => $pic,
                        'task_id' => generate_unique_task_id(),
                        'status' => 0, // 待处理
                        'audio_type' => 1, // 文案驱动
                        'shanjian_type' => 3,
                        'user_id' => self::$uid,
                        'video_setting_id' => $settingId,
                        'anchor_id' => '',
                        'voice_id' => $voice ?? '',
                        'card_name' => $characterDesignData[0]['name'] ?? '',
                        'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                        'title' => $copywritingItem['title'] ?? '',
                        'msg' => $copywritingItem['content'] ?? '',
                        'material' => $materialJson,
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'audio_url' => '', // 文案模式下不包含音频地址
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                } elseif (!empty($audioData)) {
                    // 使用音频
                    $audioItem = null;

                    if ($contentMode == 1) {
                        // 顺序选择音频
                        $audioIndex = $globalIndex % $audioCount;
                        $audioItem = $audioData[$audioIndex];
                        $contentStrategy = 'sequential_audio';
                    } else {
                        // 随机选择音频
                        $audioIndex = array_rand($audioData);
                        $audioItem = $audioData[$audioIndex];
                        $contentStrategy = 'random_audio';
                    }

                    $taskItem = [
                        'pic' => $pic,
                        'task_id' => generate_unique_task_id(),
                        'status' => 0, // 待处理
                        'audio_type' => 2, // 音频驱动
                        'shanjian_type' => 3,
                        'user_id' => self::$uid,
                        'video_setting_id' => $settingId,
                        'anchor_id' => '',
                        'voice_id' => $voice ?? '',
                        'card_name' => '',
                        'card_introduced' => '',
                        'msg' =>  '',
                        'material' => $materialJson,
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'audio_url' => $audioItem,
                        'create_time' => time(),
                        'update_time' => time()
                    ];
                }

                // 添加额外的调试信息
                $extra = [
                    'setting_index' => $globalIndex,
                    'material_index' => $materialIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop_type3',
                    'content_mode' => $contentMode,
                    'video_count_per_material' => $video_count,
                    'total_videos' => $materialCount * $video_count,
                    'selection_info' => [
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                        'content_strategy' => $contentStrategy
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'material_index' => $materialIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];

                // 合并额外的调试信息到任务项
                if (isset($taskItem['extra'])) {
                    $existingExtra = json_decode($taskItem['extra'], true);
                    $mergedExtra = array_merge($extra, $existingExtra);
                    $taskItem['extra'] = json_encode($mergedExtra, JSON_UNESCAPED_UNICODE);
                }
                // 生成视频名称
             //   $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
//                $modeSuffix = $contentMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
//                $videoName .= '_' . $modeSuffix;

                $taskItem['name'] = $taskItem['title'] ??  date('YmdHis') . "素材混剪";
                $taskItem['name'] = mb_substr( $taskItem['name'], 0, 120, 'UTF-8');
                $taskData[] = $taskItem;
            } // 内层循环结束
        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 创建任务项（基于音频）
     */
    private static function createTaskItemWithAudio(
        int $index,
        array $params,
        $audioItem,
        string $material,
        array $musicData,
        array $clipData,
        array $clip_template_id,
        int $clip_template_total,
        int $settingId,
        array $selectionContext = []
    ): array {

        // ===== 数据选择逻辑 =====
        $musicMode = $selectionContext['music_mode'] ?? 1; // 1=顺序，0=随机
        $clipMode = $selectionContext['clip_mode'] ?? 1; // 1=顺序，0=随机
        $globalIndex = $selectionContext['global_index'] ?? $index;
        $materialIndex = $selectionContext['material_index'] ?? 0;
        $videoIndex = $selectionContext['video_index'] ?? 0;
        $video_count = $selectionContext['video_count'] ?? 1;
        $materialCount = $selectionContext['material_count'] ?? 1;
        $clipCount = count($clipData);
        $musicCount = count($musicData);

        // ===== 音乐选择逻辑 =====
        $number = random_int(1, 20);
        $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

        if ($musicCount > 0) {
            if ($musicMode == 1) {
                // 顺序选择音乐
                $musicIndex = $globalIndex % $musicCount;
                $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                $musicStrategy = 'sequential';
            } else {
                // 随机选择音乐
                $randomIndex = array_rand($musicData);
                $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                $musicStrategy = 'random';
            }
        } else {
            // 没有music数据时使用默认音乐
            $music_url = $defaultMusic;
            $musicStrategy = 'default_random';
        }

        // ===== 剪辑模板选择逻辑 =====
        if ($clipCount > 0) {
            if ($clipMode == 1) {
                // 顺序选择剪辑模板
                $clipIndex = $globalIndex % $clipCount;
                $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $clipIndex;
                $clipStrategy = 'sequential';
            } else {
                // 随机选择剪辑模板
                $randomIndex = array_rand($clipData);
                $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $randomIndex;
                $clipStrategy = 'random';
            }
        } else {
            // 从数据库获取模板
            $clip = random_int(0, $clip_template_total);
            $clip_id = $clip_template_id[$clip];
            $clipTemplateIndex = $clip;
            $clipStrategy = 'default_random';
        }

        // ===== 素材处理逻辑 =====
        $material = json_decode($material, true);
        $pic = "";
        $extra = $params['extra'] ?? [];
        $decodedData['extra'] = json_decode($extra, true);

        foreach ($material as $key => &$value) {
            if (isset($value['cover'])) {
                $pic = $value['cover'];
                unset($material[$key]['cover']);
            }
            if (isset($value['type']) && $value['type'] == 'video') {
                $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                $value['soundSwitch'] = $soundSwitch === "true" ? true : false;
            }
        }
        $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ===== 基础参数处理 =====
        $voice = $params['voice'] ?? false;
        $shanjian_type = $params['shanjian_type'] ?? 3;

        // ===== 调试信息构建 =====
        $extra = [
            'setting_index' => $globalIndex,
            'material_index' => $materialIndex,
            'video_index' => $videoIndex,
            'create_type' => 'batch_with_context_audio',
            'video_count_per_material' => $video_count,
            'total_videos' => $materialCount * $video_count,
            'selection_info' => [
                'clip_template_index' => $clipTemplateIndex,
                'clip_strategy' => $clipStrategy,
                'music_strategy' => $musicStrategy,
                'content_strategy' => 'audio'
            ],
            'loop_info' => [
                'material_count' => $materialCount,
                'material_index' => $materialIndex,
                'video_index_in_material' => $videoIndex,
                'global_video_index' => $globalIndex
            ]
        ];

        $mergedArray = array_merge($extra, $decodedData['extra']);

        // ===== 视频名称生成 =====
        $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
        $modeSuffix = $musicMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
        $videoName .= '_' . $modeSuffix;

        return [
            'name' => $videoName,
            'pic' => $pic,
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 2, // 音频驱动
            'shanjian_type' => $shanjian_type,
            'user_id' => self::$uid,
            'video_setting_id' => $settingId,
            'anchor_id' => '',
            'voice_id' => $voice ?? '',
            'card_name' => '',
            'card_introduced' => '',
            'title' =>  '',
            'msg' =>  '',
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'audio_url' => $audioItem,
            'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    /**
     * 创建任务项（基于文案）
     */
    private static function createTaskItem(
        int $index,
        array $params,
        array $copywritingItem,
        array $characterDesignData,
        string $material,
        array $musicData,
        array $clipData,
        array $clip_template_id,
        int $clip_template_total,
        int $settingId,
        array $selectionContext = []
    ): array {

        // ===== 数据选择逻辑 =====
        $musicMode = $selectionContext['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $selectionContext['clip'] ?? 1; // 1=顺序，0=随机
        $globalIndex = $selectionContext['global_index'] ?? $index;
        $materialIndex = $selectionContext['material_index'] ?? 0;
        $videoIndex = $selectionContext['video_index'] ?? 0;
        $video_count = $selectionContext['video_count'] ?? 1;
        $materialCount = $selectionContext['material_count'] ?? 1;
        $clipCount = count($clipData);
        $musicCount = count($musicData);

        // ===== 音乐选择逻辑 =====
        $number = random_int(1, 20);
        $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

        if ($musicCount > 0) {
            if ($musicMode == 1) {
                // 顺序选择音乐
                $musicIndex = $globalIndex % $musicCount;
                $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                $musicStrategy = 'sequential';
            } else {
                // 随机选择音乐
                $randomIndex = array_rand($musicData);
                $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                $musicStrategy = 'random';
            }
        } else {
            // 没有music数据时使用默认音乐
            $music_url = $defaultMusic;
            $musicStrategy = 'default_random';
        }

        // ===== 剪辑模板选择逻辑 =====
        if ($clipCount > 0) {
            if ($clipMode == 1) {
                // 顺序选择剪辑模板
                $clipIndex = $globalIndex % $clipCount;
                $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $clipIndex;
                $clipStrategy = 'sequential';
            } else {
                // 随机选择剪辑模板
                $randomIndex = array_rand($clipData);
                $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                $clipTemplateIndex = $randomIndex;
                $clipStrategy = 'random';
            }
        } else {
            // 从数据库获取模板
            $clip = random_int(0, $clip_template_total);
            $clip_id = $clip_template_id[$clip];
            $clipTemplateIndex = $clip;
            $clipStrategy = 'default_random';
        }

        // ===== 素材处理逻辑 =====
        $material = json_decode($material, true);
        $pic = "";
        $extra = $params['extra'] ?? [];
        $decodedData['extra'] = json_decode($extra, true);

        foreach ($material as $key => &$value) {
            if (isset($value['cover'])) {
                $pic = $value['cover'];
                unset($material[$key]['cover']);
            }
            if (isset($value['type']) && $value['type'] == 'video') {
                $soundSwitch =  $decodedData['extra']['soundSwitch'] ?? false;
                $value['soundSwitch'] = $soundSwitch === "true" ? true : false;
            }
        }
        $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ===== 基础参数处理 =====
        $voice = $params['voice'] ?? false;
        $shanjian_type = $params['shanjian_type'] ?? 3;

        // ===== 调试信息构建 =====
        $extra = [
            'setting_index' => $globalIndex,
            'material_index' => $materialIndex,
            'video_index' => $videoIndex,
            'create_type' => 'batch_with_context',
            'video_count_per_material' => $video_count,
            'total_videos' => $materialCount * $video_count,
            'selection_info' => [
                'clip_template_index' => $clipTemplateIndex,
                'clip_strategy' => $clipStrategy,
                'music_strategy' => $musicStrategy,
                'content_strategy' => 'copywriting'
            ],
            'loop_info' => [
                'material_count' => $materialCount,
                'material_index' => $materialIndex,
                'video_index_in_material' => $videoIndex,
                'global_video_index' => $globalIndex
            ]
        ];

        $mergedArray = array_merge($extra, $decodedData['extra']);

        // ===== 视频名称生成 =====
        $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
        $modeSuffix = $musicMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
        $videoName .= '_' . $modeSuffix;

        return [
            'name' => $videoName,
            'pic' => $pic,
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 1, // 文案驱动
            'shanjian_type' => $shanjian_type,
            'user_id' => self::$uid,
            'video_setting_id' => $settingId,
            'anchor_id' => '',
            'voice_id' => $voice ?? '',
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => $copywritingItem['title'] ?? '',
            'msg' => $copywritingItem['content'] ?? '',
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'audio_url' => '', // 文案模式下不包含音频地址
            'extra' => json_encode($mergedArray, JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    public static function addType4(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');

            // 预处理JSON字段
            $jsonFields = ['copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }

            $copywritingData = $decodedData['copywriting'] ?? [];
            $audioData = $decodedData['audio'] ?? [];

            // 文案和音频不能同时为空
            if (empty($copywritingData)) {
                throw new \Exception('请提供文案内容');
            }
            if (!empty($audioData)) {
                throw new \Exception('新闻体不能传音频内容');
            }

            // 验证文案数据
            foreach ($copywritingData as $data) {
                // 检查标题是否存在且为非空数组
                if (!isset($data['title']) || !is_array($data['title']) || empty($data['title'])) {
                    throw new \Exception("标题必须填写");
                }
            }
            $extra = $decodedData['extra'] ?? [];
            $volume = $extra['volume'] ?? 0.3;
            if ($volume < 0 || $volume > 1) {
                self::setError("声音值必须在 0 到 1 之间，当前值为：$volume");
                return false;
            }
            $params['status'] = 1;
            $materialCount = !empty($decodedData['material']) && is_array($decodedData['material']) ? count($decodedData['material']) : 0;

            $video_count = $extra['video_count'] ?? 0;
            $params['video_count'] = $video_count * $materialCount;
            if ($params['video_count'] == 0) {
                self::setError("素材数量不能为0或者生成的视频数量不能为0");
                return false;
            }
            // 开始事务
            Db::startTrans();
            try {
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType41($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType4(int $settingId, array $params, $decodedData): void
    {
        // 获取剪辑模板
        $clip_template_id = ShanjianClipTemplate::where('scene', 'newsMixCutting')->column('id');
        $clip_template_total = count($clip_template_id) - 1;

        // 设置视频数量上下限：1-50
        $videoCount = $params['video_count'] ?? 1;
        if ($videoCount > 50) {
            throw new \Exception("视频数量不能超过50");
        }
        if ($videoCount < 1) {
            throw new \Exception("视频数量不能小于1");
        }

        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $audioData = $decodedData['audio'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        // 数据验证
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }

        // 文案和音频不能同时为空
        if (empty($copywritingData) && empty($audioData)) {
            throw new \Exception("文案和音频不能同时为空");
        }

        // 验证文案数据
        if (!empty($copywritingData)) {
            foreach ($copywritingData as $data) {
                if (!isset($data['title']) || !is_array($data['title']) || empty($data['title'])) {
                    throw new \Exception("标题必须填写");
                }
            }
        }
        // 验证音频数据
        if (!empty($audioData)) {
            foreach ($audioData as $data) {
                if (!array_key_exists('url', $data) || trim($data['url']) === '') {
                    throw new \Exception("音频地址不能为空");
                }
                if (!array_key_exists('duration', $data) || floatval($data['duration']) <= 0) {
                    throw new \Exception("音频时长必须大于0");
                }
            }
        }

        if (count($materialData) == 0) {
            throw new \Exception("素材不能为空");
        }
        foreach ($copywritingData as &$data) {;
            if (isset($data['title']) && is_array($data['title'])) {
                $data['title'] = implode('\n', $data['title']);
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        // 计算文案数量和素材组数量
        $copywritingCount = !empty($copywritingData) ? count($copywritingData) : 0;
        $audioCount = !empty($audioData) ? count($audioData) : 0;
        $materialGroupCount = count($materialData); // 素材组数量

        // 根据参数类型计算视频数量
        $effectiveCount = !empty($audioData) ? $audioCount : $copywritingCount;
        // 随机选择素材组的函数
        $getRandomMaterialGroup = function () use ($materialData) {
            // 从已有的素材组中随机选择一组
            $randomGroupIndex = array_rand($materialData);
            return json_encode($materialData[$randomGroupIndex], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };
        // 生成视频任务
        if ($effectiveCount > $materialGroupCount) {
            // 有效数据数量 > 素材组数量
            // 将有效数据分别匹配素材组以此达成请求数量
            for ($i = 0; $i < $videoCount; $i++) {
                $effectiveIndex = $i % $effectiveCount;
                $materialGroupIndex = $i % $materialGroupCount;

                // 根据数据类型获取对应的数据项
                if ($audioData) {
                    $effectiveData = $audioData[$effectiveIndex];
                    $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $materialData[$materialGroupIndex], $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                } else {
                    $effectiveData = $copywritingData[$effectiveIndex];
                    $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $materialData[$materialGroupIndex], $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                }

                $taskData[] = $taskItem;
            }
        } else {

            // 有效数据数量 <= 素材组数量
            $maxPossible = $effectiveCount * $materialGroupCount;
            if ($videoCount < $maxPossible) {
                // 随机选择素材组与有效数据匹配生成对应的视频
                // 确保每个有效数据都有机会被使用
                $usedCombinations = [];

                for ($i = 0; $i < $videoCount; $i++) {
                    // 根据数据类型随机选择有效数据
                    if ($audioData) {
                        $effectiveIndex = array_rand($audioData);
                        $effectiveData = $audioData[$effectiveIndex];
                    } else {
                        $effectiveIndex = array_rand($copywritingData);
                        $effectiveData = $copywritingData[$effectiveIndex];
                    }

                    // 随机选择一个素材组
                    $selectedMaterial = $materialData[array_rand($materialData)];
                    $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    // 生成组合键，避免重复
                    $combinationKey = $effectiveIndex . '-' . $material;

                    // 如果该组合已使用，重新选择
                    $attempts = 0;
                    while (in_array($combinationKey, $usedCombinations) && $attempts < 10) {
                        if ($audioData) {
                            $effectiveIndex = array_rand($audioData);
                            $effectiveData = $audioData[$effectiveIndex];
                        } else {
                            $effectiveIndex = array_rand($copywritingData);
                            $effectiveData = $copywritingData[$effectiveIndex];
                        }
                        $selectedMaterial = $materialData[array_rand($materialData)];
                        $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $combinationKey = $effectiveIndex . '-' . $material;
                        $attempts++;
                    }

                    $usedCombinations[] = $combinationKey;

                    // 根据数据类型创建任务项
                    if ($audioData) {
                        $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    } else {
                        $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    }

                    $taskData[] = $taskItem;
                }
            } else {
                // 循环使用有效数据和素材组生成视频
                for ($i = 0; $i < $videoCount; $i++) {
                    $effectiveIndex = $i % $effectiveCount;
                    $selectedMaterial = $materialData[array_rand($materialData)];

                    // 根据数据类型获取对应的数据项
                    if ($audioData) {
                        $effectiveData = $audioData[$effectiveIndex];
                        $taskItem = self::createTaskItemWithAudioType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    } else {
                        $effectiveData = $copywritingData[$effectiveIndex];
                        $taskItem = self::createTaskItemWithCopywritingType4($i, $params, $effectiveData, $characterDesignData, $selectedMaterial, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                    }

                    $taskData[] = $taskItem;
                }
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    public static function addType41(array $params): bool
    {
        try {
            $params['user_id'] = self::$uid;
            $params['task_id'] = generate_unique_task_id();
            $params['create_time'] = time();
            $params['update_time'] = time();
            $params['name'] = $params['name'] ?? '混剪创作' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'audio', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($params[$field])) {
                    // 如果已经是数组，则直接使用
                    if (is_array($params[$field])) {
                        $decodedData[$field] = $params[$field];
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        // 尝试解析JSON字符串
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedData[$field] = $decoded;
                        } else {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            $copywriting = $decodedData['copywriting'] ?? [];
            $audioData = $decodedData['audio'] ?? [];

            // 验证文案和音频不能同时为空
            if (empty($copywriting) && empty($audioData)) {
                self::setError("文案和音频至少需要提供一种");
                return false;
            }

            // 验证音频数据（如果提供）
            if (!empty($audioData)) {
                foreach ($audioData as $audioItem) {
                    if (empty($audioItem['url'])) {
                        self::setError("音频URL不能为空");
                        return false;
                    }
                    if (empty($audioItem['duration']) || $audioItem['duration'] <= 0) {
                        self::setError("音频时长必须大于0");
                        return false;
                    }
                }
            }

            // 计算token消耗时长
            $duration = 0;
            foreach ($copywriting as $key => $value) {
                if (!empty($value['content'])) {
                    $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                }
            }
            if ($duration > 0) {
                $duration = $duration / 3;
            }

            // 如果有音频数据，根据音频时长计算
            if (!empty($audioData)) {
                $audioDuration = 0;
                foreach ($audioData as $audioItem) {
                    if (is_string($audioItem)) {
                        // 字符串格式：支持双反引号嵌套格式，默认时长30秒
                        $audioDuration += 30;
                    } elseif (is_array($audioItem) && isset($audioItem['duration'])) {
                        // 对象格式
                        $audioDuration += $audioItem['duration'];
                    }
                }
                $duration += $audioDuration;
            }

            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;

            // 动态计算视频数量：优先使用音频数量，否则使用文案数量
            $copywritingCount = !empty($copywriting) ? count($copywriting) : 0;
            $audioCount = !empty($audioData) ? count($audioData) : 0;
            $params['video_count'] = max($copywritingCount, $audioCount);
            // 开始事务
            Db::startTrans();
            try {
                $unit = TokenLogService::checkToken(self::$uid, 'human_video_shanjian', $duration);
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                self::createVideoTasksType4($setting->id, $params, $decodedData);

                Db::commit();
                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                Db::rollback();
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function createVideoTasksType41(int $settingId, array $params, $decodedData): void
    {
        $taskData = [];
        // 解析JSON数据
        $copywritingData = $decodedData['copywriting'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        $extraData = $decodedData['extra'] ?? [];
        // 验证素材数据
        if (count($materialData) == 0) {
            throw new \Exception("素材不能为空");
        }

        // 验证文案和音频不能同时为空
        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }

        // 验证文案数据（如果提供）
        if (count($copywritingData) > 0) {
            foreach ($copywritingData as &$data) {
                if (!array_key_exists('title', $data) || count($data['title']) == 0) {
                    throw new \Exception("标题不能为空");
                }
                if (isset($data['title']) && is_array($data['title']) ) {
                    $data['title'] = implode('\n', $data['title']);
                }
            }
        }

        // 获取模式控制参数
        $musicMode = $extraData['music'] ?? 1; // 1=顺序，0=随机
        $clipMode = $extraData['clip'] ?? 1; // 1=顺序，0=随机
        $contentMode = $extraData['content_mode'] ?? 1; // 1=顺序，0=随机
        $video_count = $extraData['video_count'] ?? 1; // 每个素材生成的视频数量
        $aimusicMode = $extraData['aimusic'] ?? false; // 控制是否使用默认音乐

        // 计算资源数量
        $materialCount = count($materialData);
        $copywritingCount = count($copywritingData);
        $clipCount = count($clipData);

        // 外层循环：遍历每个素材
        foreach ($materialData as $materialIndex => $currentMaterial) {
            // 内层循环：每个素材生成 $video_count 个视频
            for ($videoIndex = 0; $videoIndex < $video_count; $videoIndex++) {

                // 计算当前视频的全局索引
                $globalIndex = $materialIndex * $video_count + $videoIndex;

                // ===== 素材选择逻辑 =====
                // 当前素材就是选中的素材
                $selectedMaterial = $currentMaterial;
                $pic = "";
                foreach ($selectedMaterial as $key => &$value) {
                    if (isset($value['cover'])) {
                        $pic = $value['cover'];
                        unset($selectedMaterial[$key]['cover']);
                    }
                }
                $materialJson = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // ===== 剪辑模板选择逻辑 =====
                if ($clipCount > 0) {
                    if ($clipMode == 1) {
                        // 顺序选择剪辑模板
                        $clipIndex = $globalIndex % $clipCount;
                        $clip_id = $clipData[$clipIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $clipIndex;
                        $clipStrategy = 'sequential';
                    } else {
                        // 随机选择剪辑模板
                        $randomIndex = array_rand($clipData);
                        $clip_id = $clipData[$randomIndex]['clip_template_id'] ?? $clipData[0]['clip_template_id'];
                        $clipTemplateIndex = $randomIndex;
                        $clipStrategy = 'random';
                    }
                } else {
                    // 获取剪辑模板
                    $clip_template_id = ShanjianClipTemplate::where('scene', 'newsMixCutting')->column('id');
                    $clip_template_total = count($clip_template_id) - 1;

                    if (count($clip_template_id) == 0) {
                        throw new \Exception("缺少剪辑模版");
                    }
                    // 使用数据库默认剪辑模板
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $clipTemplateIndex = $clip;
                    $clipStrategy = 'default_random';
                }

                // ===== 音乐选择逻辑 =====
                $number = random_int(1, 20);
                $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';

                if (count($musicData) > 0) {
                    if ($musicMode == 1) {
                        // 顺序选择音乐
                        $musicIndex = $globalIndex % count($musicData);
                        $music_url = $musicData[$musicIndex] ?? $defaultMusic;
                        $musicStrategy = 'sequential';
                    } else {
                        // 随机选择音乐
                        $randomIndex = array_rand($musicData);
                        $music_url = $musicData[$randomIndex] ?? $defaultMusic;
                        $musicStrategy = 'random';
                    }
                } else {
                    // 控制是否使用默认音乐
                    if ($aimusicMode) {
                        $music_url = $defaultMusic;
                    } else {
                        $music_url = '';
                    }
                    $musicStrategy = 'default_random';
                }
            
                // ===== 内容选择逻辑（文案或音频）=====
                $taskItem = [];
                // 使用文案
                $copywritingItem = null;

                    // 顺序选择文案
                $copywritingIndex = $globalIndex % $copywritingCount;
                $copywritingItem = $copywritingData[$copywritingIndex];
                $contentStrategy = 'sequential_copywriting';
                $taskItem = [
                    'pic' => $pic,
                    'task_id' => generate_unique_task_id(),
                    'status' => 0, // 待处理
                    'audio_type' => 1, // 文案驱动
                    'shanjian_type' => 4,
                    'user_id' => self::$uid,
                    'video_setting_id' => $settingId,
                    'anchor_id' => '',
                    'voice_id' => $params['voice'] ?? '',
                    'card_name' => $characterDesignData[0]['name'] ?? '',
                    'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                    'title' => $copywritingItem['title'] ?? '',
                    'msg' => $copywritingItem['content'] ?? '',
                    'material' => $materialJson,
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'audio_url' => '', // 文案模式下不包含音频地址
                    'create_time' => time(),
                    'update_time' => time()
                ];


                // 添加额外的调试信息
                $extra = [
                    'setting_index' => $globalIndex,
                    'material_index' => $materialIndex,
                    'video_index' => $videoIndex,
                    'create_type' => 'nested_loop_type41',
                    'video_count_per_material' => $video_count,
                    'total_videos' => $materialCount * $video_count,
                    'selection_info' => [
                        'clip_template_index' => $clipTemplateIndex,
                        'clip_strategy' => $clipStrategy,
                        'music_strategy' => $musicStrategy,
                        'content_strategy' => $contentStrategy ?? 'unknown'
                    ],
                    'loop_info' => [
                        'material_count' => $materialCount,
                        'material_index' => $materialIndex,
                        'video_index_in_material' => $videoIndex,
                        'global_video_index' => $globalIndex
                    ]
                ];

                // 合并额外的调试信息到任务项
                if (isset($taskItem['extra'])) {
                    $existingExtra = json_decode($taskItem['extra'], true);
                    $mergedExtra = array_merge($extra, $existingExtra);
                    $taskItem['extra'] = json_encode($mergedExtra, JSON_UNESCAPED_UNICODE);
                } else {
                    $taskItem['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE);
                }

                // 生成视频名称
//                $videoName = ($params['name'] ?? '视频设置' . date('YmdHi')) . '_素材' . ($materialIndex + 1) . '_视频' . ($videoIndex + 1);
//                $modeSuffix = $contentMode == 1 ? 'S' : 'R'; // S=顺序，R=随机
//                $videoName .= '_' . $modeSuffix;
                $taskItem['name'] = $taskItem['title'] ??  date('YmdHis') . '新闻体';
                $taskItem['name'] = mb_substr( $taskItem['name'], 0, 120, 'UTF-8');
                $taskData[] = $taskItem;
            } // 内层循环结束
        } // 外层循环结束
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 为Type4创建音频驱动的任务项
     * @param int $index 任务索引
     * @param array $params 请求参数
     * @param array|string $audioItem 音频数据（支持字符串格式和对象格式）
     * @param array $characterDesignData 人设数据
     * @param array $selectedMaterial 素材数据
     * @param array $musicData 音乐数据
     * @param array $clipData 剪辑数据
     * @param array $clip_template_id 剪辑模板ID数组
     * @param int $clip_template_total 剪辑模板总数
     * @param int $settingId 设置ID
     * @return array 任务项数据
     */
    private static function createTaskItemWithAudioType4(int $index, array $params, $audioItem, array $characterDesignData, array $selectedMaterial, array $musicData, array $clipData, array $clip_template_id, int $clip_template_total, int $settingId): array
    {
        $number = random_int(1, 20);
        $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
        $music_url = !empty($musicData) ? $musicData[$index % count($musicData)]['fileUrl'] ?? $music : $music;

        $clip = random_int(0, $clip_template_total);
        $clip_id = !empty($clipData) ? $clipData[$index % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip] : $clip_template_id[$clip];

        $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 处理音频数据，支持字符串格式（含双反引号嵌套）和对象格式
        $audioUrl = '';
        $audioFormat = 'unknown';
        if (is_string($audioItem)) {
            // 字符串格式：支持双反引号嵌套格式
            $audioUrl = trim($audioItem, " `'\"`");
            $audioFormat = 'string';
        } elseif (is_array($audioItem) && isset($audioItem['url'])) {
            // 对象格式
            $audioUrl = $audioItem['url'];
            $audioFormat = 'object';
        }

        return [
            'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($index + 1),
            'pic' => '', // Type4可能没有anchor pic
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 2, // 音频驱动
            'user_id' => self::$uid,
            'shanjian_type' => 4,
            'video_setting_id' => $settingId,
            'anchor_id' => '', // Type4可能没有anchor_id
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => '', // 音频模式下可能没有title
            'msg' => '', // 音频模式下可能没有content
            'audio_url' => $audioUrl, // 音频URL
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'extra' => json_encode([
                'setting_index' => $index,
                'create_type' => 'batch',
                'audio_format' => $audioFormat
            ], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }

    /**
     * 为Type4创建文案驱动的任务项
     * @param int $index 任务索引
     * @param array $params 请求参数
     * @param array $copywritingItem 文案数据
     * @param array $characterDesignData 人设数据
     * @param array $selectedMaterial 素材数据
     * @param array $musicData 音乐数据
     * @param array $clipData 剪辑数据
     * @param array $clip_template_id 剪辑模板ID数组
     * @param int $clip_template_total 剪辑模板总数
     * @param int $settingId 设置ID
     * @return array 任务项数据
     */
    private static function createTaskItemWithCopywritingType4(int $index, array $params, array $copywritingItem, array $characterDesignData, array $selectedMaterial, array $musicData, array $clipData, array $clip_template_id, int $clip_template_total, int $settingId): array
    {
        $number = random_int(1, 20);
        $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
        $music_url = !empty($musicData) ? $musicData[$index % count($musicData)]['fileUrl'] ?? $music : $music;

        $clip = random_int(0, $clip_template_total);
        $clip_id = !empty($clipData) ? $clipData[$index % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip] : $clip_template_id[$clip];

        $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($index + 1),
            'pic' => '', // Type4可能没有anchor pic
            'task_id' => generate_unique_task_id(),
            'status' => 0, // 待处理
            'audio_type' => 1, // 文案驱动
            'user_id' => self::$uid,
            'shanjian_type' => 4,
            'video_setting_id' => $settingId,
            'anchor_id' => '', // Type4可能没有anchor_id
            'card_name' => $characterDesignData[0]['name'] ?? '',
            'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
            'title' => $copywritingItem['title'] ?? '',
            'msg' => $copywritingItem['content'] ?? '',
            'audio_url' => '', // 文案模式下不设置audio_url
            'material' => $material,
            'music_url' => $music_url,
            'clip_id' => $clip_id,
            'extra' => json_encode([
                'setting_index' => $index,
                'create_type' => 'batch'
            ], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
            'update_time' => time()
        ];
    }
}
