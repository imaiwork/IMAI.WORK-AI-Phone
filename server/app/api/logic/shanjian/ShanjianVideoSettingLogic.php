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
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
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

            $duration = 0;
            foreach ($copywriting as $key => $value) {
                if (!empty($value['content'])) {
                    $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                }

            }
            if ($duration > 0) {
                $duration = $duration / 3;
            }

            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;
            $params['video_count'] = count($copywriting);
            // 开始事务
            Db::startTrans();
            try {
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
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
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

        $clip_template_id = ShanjianClipTemplate::where('scene', 'virtualman')->column('id');
        $clip_template_total = count($clip_template_id) - 1;
        $videoCount = $params['video_count'] ?? 1;
        $taskData = [];
        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }
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
        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }

        if (count($materialData) < 3) {
            throw new \Exception("素材不能少于三条");
        }
        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        $copywritingDatanum = count($copywritingData) * 0.5;
        $materialDatanum = count($materialData);
        $randcopywriting = false;
        if ($materialDatanum > $copywritingDatanum && $materialDatanum > 4) {
            $randcopywriting = true;
        }

        for ($i = 0; $i < $videoCount; $i++) {
            $number = random_int(1, 20);
            $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
            if (count($musicData) == 0) {
                $music_url = $music;
            } else {
                $music_url = $musicData[$i % count($musicData)]['fileUrl'] ?? $music;
            }
            $clip = random_int(0, $clip_template_total);
            if (count($clipData) == 0) {
                $clip_id = $clip_template_id[$clip];
            } else {
                $clip_id = $clipData[$i % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip];
            }
            $material = json_encode($materialData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($randcopywriting) {
                $numberOfItems = rand(3, 4);
                $randomKeys = array_rand($materialData, $numberOfItems);
                if (is_array($randomKeys)) {
                    // 如果抽取多个元素
                    $material = array_intersect_key($materialData, array_flip($randomKeys));
                } else {
                    // 如果抽取一个元素
                    $material = [$materialData[$randomKeys]];
                }
                $material = array_values($material);
                $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            }

            $taskItem = [
                'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($i + 1),
                'pic' => $anchorData[$i % count($anchorData)]['pic'] ?? '',
                'task_id' => generate_unique_task_id(),
                'status' => 0, // 待处理
                'audio_type' => 1, // 文案驱动
                'user_id' => self::$uid,
                'video_setting_id' => $settingId,
                'anchor_id' => $anchorData[$i % count($anchorData)]['anchor_id'] ?? '',
                'voice_id' => $voiceData[$i % count($voiceData)]['voice_id'] ?? '',
                'card_name' => $characterDesignData[0]['name'] ?? '',
                'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                'title' => $copywritingData[$i % count($copywritingData)]['title'] ?? '',
                'msg' => $copywritingData[$i % count($copywritingData)]['content'] ?? '',
                'material' => $material,
                'music_url' => $music_url,
                'clip_id' => $clip_id,
                'extra' => json_encode([
                    'setting_index' => $i,
                    'create_type' => 'batch'
                ], JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'update_time' => time()
            ];

            $taskData[] = $taskItem;
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
            $maxVideoCount = self::calculateMaxVideoCount($materialCount, $themeVideoCount);
            // 检查请求生成的视频数量是否超过上限
            $requestedVideoCount = $params['video_count'] ?? 0;
            if ($maxVideoCount > 0 && $requestedVideoCount > $maxVideoCount) {
                self::setError("生成的视频数量不能超过上限 {$maxVideoCount} 个");
                return false;
            }
            $anchor = $decodedData['anchor'] ?? [];
            $duration = 0;
            foreach ($anchor as $key => $value) {
                if (!empty($value['duration'])) {
                    $duration = $duration + $value['duration'];
                }
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
        $clip_template_id = ShanjianClipTemplate::where('scene', 'realMan')->column('id');
        $clip_template_total = count($clip_template_id) - 1;
        $videoCount = $params['video_count'] ?? 1;
        $taskData = [];
        
        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $voiceData = $decodedData['voice'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        
        // 数据验证
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }
        if (count($anchorData) == 0) {
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_url', $data) || trim($data['anchor_url']) === '') {
                throw new \Exception("视频不存在");
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }
        
        for ($i = 0; $i < $videoCount; $i++) {
            // 选择音乐
            $number = random_int(1, 20);
            $defaultMusic = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
            $music_url = count($musicData) > 0
                ? ($musicData[$i % count($musicData)]['fileUrl'] ?? $defaultMusic)
                : $defaultMusic;

            // 选择剪辑模板
            $clip = random_int(0, $clip_template_total);
            $clip_id = count($clipData) > 0
                ? ($clipData[$i % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip])
                : $clip_template_id[$clip];

            // 处理素材数据，支持空素材情况
            $selectedMaterial = [];
            $materialCount = count($materialData);
            if ($materialCount > 0) {
                // 动态计算随机选择的素材数量，基于素材总数
                if ($materialCount <= 4) {
                    // 如果素材数量较少，使用所有素材
                    $numberOfItems = $materialCount;
                } else if ($materialCount <= 8) {
                    // 中等数量素材，随机选择3-4个
                    $numberOfItems = rand(3, 4);
                } else {
                    // 较多素材，随机选择4-6个
                    $numberOfItems = rand(4, 6);
                }
                
                // 随机选择素材
                $randomKeys = array_rand($materialData, $numberOfItems);
                
                if (is_array($randomKeys)) {
                    // 如果抽取多个元素
                    $selectedMaterial = array_intersect_key($materialData, array_flip($randomKeys));
                    $selectedMaterial = array_values($selectedMaterial);
                } else {
                    // 如果抽取一个元素
                    $selectedMaterial = [$materialData[$randomKeys]];
                }
            }
            
            // 编码为JSON
            $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $taskItem = [
                'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($i + 1),
                'pic' => $anchorData[$i % count($anchorData)]['pic'] ?? '',
                'task_id' => generate_unique_task_id(),
                'status' => 0, // 待处理
                'audio_type' => 1, // 文案驱动
                'shanjian_type' => $params['shanjian_type'] ?? 1,
                'user_id' => self::$uid,
                'video_setting_id' => $settingId,
                'anchor_id' => $anchorData[$i % count($anchorData)]['anchor_url'] ?? '',
                'voice_id' => '',
                'card_name' => $characterDesignData[0]['name'] ?? '',
                'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                'title' => '',
                'msg' => '',
                'material' => $material,
                'music_url' => $music_url,
                'clip_id' => $clip_id,
                'extra' => json_encode([
                    'setting_index' => $i,
                    'create_type' => 'batch'
                ], JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'update_time' => time()
            ];

            $taskData[] = $taskItem;
        }
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
            $jsonFields = ['copywriting', 'material', 'clip', 'music', 'extra'];
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

            $duration = 0;
            foreach ($copywriting as $key => $value) {
                if (!empty($value['content'])) {
                    $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                }

            }
            if ($duration > 0) {
                $duration = $duration / 3;
            }

            $params['status'] = 1;
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
        // 获取剪辑模板
        $clip_template_id = ShanjianClipTemplate::where('scene', 'oralMixCutting')->column('id');
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
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];

        // 数据验证
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }

        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }
        if (count($materialData) == 0) {
            throw new \Exception("素材不能为空");
        }
        foreach ($copywritingData as $data) {
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }

        // 计算文案数量和素材组数量
        $copywritingCount = count($copywritingData);
        $materialGroupCount = count($materialData); // 素材组数量

        // 随机选择素材组的函数
        $getRandomMaterialGroup = function () use ($materialData) {
            // 从已有的素材组中随机选择一组
            $randomGroupIndex = array_rand($materialData);
            return json_encode($materialData[$randomGroupIndex], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };

        // 生成视频任务
        if ($copywritingCount > $materialGroupCount) {
            // 文案数量 > 素材组数量
            // 将文案分别匹配素材组以此达成请求数量
            for ($i = 0; $i < $videoCount; $i++) {
                $copywritingIndex = $i % $copywritingCount;
                $materialGroupIndex = $i % $materialGroupCount;

                // 从素材组中选择一组
                $selectedMaterial = $materialData[$materialGroupIndex];

                $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], [], $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
            }

        } else {

            // 文案数量 <= 素材组数量
            $maxPossible = $copywritingCount * $materialGroupCount;
            if ($videoCount < $maxPossible) {
                // 随机选择素材组与文案匹配生成对应的视频
                // 确保每个文案都有机会被使用
                $usedCombinations = [];

                for ($i = 0; $i < $videoCount; $i++) {
                    // 随机选择一个文案
                    $copywritingIndex = array_rand($copywritingData);

                    // 随机选择一个素材组
                    $material = $getRandomMaterialGroup();

                    // 生成组合键，避免重复
                    $combinationKey = $copywritingIndex . '-' . $material;

                    // 如果该组合已使用，重新选择
                    $attempts = 0;
                    while (in_array($combinationKey, $usedCombinations) && $attempts < 10) {
                        $copywritingIndex = array_rand($copywritingData);
                        $material = $getRandomMaterialGroup();
                        $combinationKey = $copywritingIndex . '-' . $material;
                        $attempts++;
                    }

                    $usedCombinations[] = $combinationKey;
                    $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], [], $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                }
            } else {
                // 循环使用文案和素材组生成视频
                for ($i = 0; $i < $videoCount; $i++) {
                    $copywritingIndex = $i % $copywritingCount;
                    $material = $getRandomMaterialGroup();
                    $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], [], $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                }
            }
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }

    /**
     * 创建任务项
     */
    private static function createTaskItem(int $index, array $params, array $copywritingItem, array $characterDesignData, string $material, array $musicData, array $clipData, array $clip_template_id, int $clip_template_total, int $settingId): array
    {
        $number = random_int(1, 20);
        $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
        $voice = $params['voice'] ?? false;
        // 选择音乐
        if (count($musicData) == 0) {
            $music_url = $music;
        } else {
            $music_url = $musicData[$index % count($musicData)]['fileUrl'] ?? $music;
        }

        // 选择剪辑模板
        $clip = random_int(0, $clip_template_total);
        if (count($clipData) == 0) {
            $clip_id = $clip_template_id[$clip];
        } else {
            $clip_id = $clipData[$index % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip];
        }
        $material = json_decode($material, true);
        $pic = "";
        foreach ($material as $key => &$value) {
            if (isset($value['cover'])) {
                $pic = $value['cover'];
                unset($material[$key]['cover']);
            }
            if (isset($value['type']) && $value['type'] == 'video') {
                $extra = $params['extra'] ?? false;
                $value['soundSwitch'] = $extra === "true" ? true : false;
            }
        }

        $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $shanjian_type = $params['shanjian_type'] ?? 3;
        return [
            'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($index + 1),
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
            'extra' => json_encode([
                'setting_index' => $index,
                'create_type' => 'batch'
            ], JSON_UNESCAPED_UNICODE),
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
            $jsonFields = ['copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
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
            foreach ($copywritingData as $data) {
                // 检查标题是否存在且为非空数组
                if (!isset($data['title']) || !is_array($data['title']) || empty($data['title'])) {
                    throw new \Exception("标题必须填写");
                }
            }

            $params['status'] = 1;
            $params['video_count'] =  $params['video_count'] ?? 1;
            // 开始事务
            Db::startTrans();
            try {
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
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        // 数据验证
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }

        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }
        if (count($materialData) == 0) {
            throw new \Exception("素材不能为空");
        }
        foreach ($copywritingData as &$data) {;
            if (isset($data['title']) && is_array($data['title']) ) {
                $data['title'] = implode('\n', $data['title']);
            }
        }

        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        // 计算文案数量和素材组数量
        $copywritingCount = count($copywritingData);
        $materialGroupCount = count($materialData); // 素材组数量
        // 随机选择素材组的函数
        $getRandomMaterialGroup = function () use ($materialData) {
            // 从已有的素材组中随机选择一组
            $randomGroupIndex = array_rand($materialData);
            return json_encode($materialData[$randomGroupIndex], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };
        // 生成视频任务
        if ($copywritingCount > $materialGroupCount) {
            // 文案数量 > 素材组数量
            // 将文案分别匹配素材组以此达成请求数量
            for ($i = 0; $i < $videoCount; $i++) {
                $copywritingIndex = $i % $copywritingCount;
                $materialGroupIndex = $i % $materialGroupCount;
                // 从素材组中选择一组
                $selectedMaterial = $materialData[$materialGroupIndex];
                $material = json_encode($selectedMaterial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], $characterDesignData, $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
            }

        } else {

            // 文案数量 <= 素材组数量
            $maxPossible = $copywritingCount * $materialGroupCount;
            if ($videoCount < $maxPossible) {
                // 随机选择素材组与文案匹配生成对应的视频
                // 确保每个文案都有机会被使用
                $usedCombinations = [];

                for ($i = 0; $i < $videoCount; $i++) {
                    // 随机选择一个文案
                    $copywritingIndex = array_rand($copywritingData);

                    // 随机选择一个素材组
                    $material = $getRandomMaterialGroup();

                    // 生成组合键，避免重复
                    $combinationKey = $copywritingIndex . '-' . $material;

                    // 如果该组合已使用，重新选择
                    $attempts = 0;
                    while (in_array($combinationKey, $usedCombinations) && $attempts < 10) {
                        $copywritingIndex = array_rand($copywritingData);
                        $material = $getRandomMaterialGroup();
                        $combinationKey = $copywritingIndex . '-' . $material;
                        $attempts++;
                    }

                    $usedCombinations[] = $combinationKey;
                    $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], $characterDesignData, $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
                }
            } else {
                // 循环使用文案和素材组生成视频
                for ($i = 0; $i < $videoCount; $i++) {
                    $copywritingIndex = $i % $copywritingCount;
                    $material = $getRandomMaterialGroup();
                    $taskData[] = self::createTaskItem($i, $params, $copywritingData[$copywritingIndex], $characterDesignData, $material, $musicData, $clipData, $clip_template_id, $clip_template_total, $settingId);
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
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
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

            $duration = 0;
            foreach ($copywriting as $key => $value) {
                if (!empty($value['content'])) {
                    $duration = $duration + mb_strlen($value['content'], 'UTF-8');
                }

            }
            if ($duration > 0) {
                $duration = $duration / 3;
            }

            $anchor = $decodedData['anchor'] ?? [];
            $params['status'] = 1;
            $params['video_count'] = count($copywriting);
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

        $clip_template_id = ShanjianClipTemplate::where('scene', 'newsMixCutting')->column('id');
        $clip_template_total = count($clip_template_id) - 1;
        $videoCount = $params['video_count'] ?? 1;
        $taskData = [];
        // 解析JSON数据
        $anchorData = $decodedData['anchor'] ?? [];
        $copywritingData = $decodedData['copywriting'] ?? [];
        $characterDesignData = $decodedData['character_design'] ?? [];
        $materialData = $decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        if (count($clip_template_id) == 0) {
            throw new \Exception("缺少剪辑模版");
        }
        if (count($anchorData) == 0) {
            throw new \Exception("视频不能为空");
        }

        foreach ($anchorData as $data) {
            if (!array_key_exists('anchor_url', $data) || trim($data['anchor_url']) === '') {
                throw new \Exception("视频不存在");
            }
        }

        if (count($copywritingData) == 0) {
            throw new \Exception("文案不能为空");
        }
        foreach ($copywritingData as $data) {
            if (!array_key_exists('title', $data) || trim($data['title']) === '') {
                throw new \Exception("标题不能为空");
            }
        }
        if (count($materialData) < 2) {
            throw new \Exception("素材不能少于二条");
        }
        if (count($characterDesignData) == 0) {
            throw new \Exception("人设信息不能为空");
        }

        $copywritingDatanum = count($copywritingData) * 0.5;
        $materialDatanum = count($materialData);
        $randcopywriting = false;
        if ($materialDatanum > $copywritingDatanum && $materialDatanum > 4) {
            $randcopywriting = true;
        }

        for ($i = 0; $i < $videoCount; $i++) {
            $number = random_int(1, 20);
            $music = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
            if (count($musicData) == 0) {
                $music_url = $music;
            } else {
                $music_url = $musicData[$i % count($musicData)]['fileUrl'] ?? $music;
            }
            $clip = random_int(0, $clip_template_total);
            if (count($clipData) == 0) {
                $clip_id = $clip_template_id[$clip];
            } else {
                $clip_id = $clipData[$i % count($clipData)]['clip_template_id'] ?? $clip_template_id[$clip];
            }
            $material = json_encode($materialData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($randcopywriting) {
                $numberOfItems = rand(3, 4);
                $randomKeys = array_rand($materialData, $numberOfItems);
                if (is_array($randomKeys)) {
                    // 如果抽取多个元素
                    $material = array_intersect_key($materialData, array_flip($randomKeys));
                } else {
                    // 如果抽取一个元素
                    $material = [$materialData[$randomKeys]];
                }
                $material = array_values($material);
                $material = json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            }

            $taskItem = [
                'name' => ($params['name'] ?? '视频设置' . date('YmdHi')) . '_' . ($i + 1),
                'pic' => $anchorData[$i % count($anchorData)]['pic'] ?? '',
                'task_id' => generate_unique_task_id(),
                'status' => 0, // 待处理
                'audio_type' => 1, // 文案驱动
                'user_id' => self::$uid,
                'shanjian_type' => 4,
                'video_setting_id' => $settingId,
                'anchor_id' => $anchorData[$i % count($anchorData)]['anchor_id'] ?? '',
                'card_name' => $characterDesignData[0]['name'] ?? '',
                'card_introduced' => $characterDesignData[0]['introduced'] ?? '',
                'title' => $copywritingData[$i % count($copywritingData)]['title'] ?? '',
                'msg' => $copywritingData[$i % count($copywritingData)]['content'] ?? '',
                'material' => $material,
                'music_url' => $music_url,
                'clip_id' => $clip_id,
                'extra' => json_encode([
                    'setting_index' => $i,
                    'create_type' => 'batch'
                ], JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
                'update_time' => time()
            ];

            $taskData[] = $taskItem;
        }
        if (!empty($taskData)) {
            (new ShanjianVideoTask())->saveAll($taskData);
        }
    }
}
