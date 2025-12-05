<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
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
            $params['name'] = $params['name'] ?? '混剪创作'.date('YmdHi');
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
            $copywriting =   $decodedData['copywriting'] ?? [];
            $anchor =   $decodedData['anchor'] ?? [];
            $params['status'] = 1;
            $params['video_count'] = count(  $copywriting);
            // 开始事务
            Db::startTrans();
            try {
                $setting = ShanjianVideoSetting::create($params);

                // 如果状态为1，创建对应的视频任务
                 self::createVideoTasks($setting->id, $params,$decodedData);

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
                if (isset($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    }
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
                    self::createVideoTasks($params['id'], $params);
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

    public static function updateName(array $params):bool
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
    private static function createVideoTasks(int $settingId, array $params,$decodedData): void
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
        $materialData =$decodedData['material'] ?? [];
        $clipData = $decodedData['clip'] ?? [];
        $musicData = $decodedData['music'] ?? [];
        if (count($clip_template_id) == 0){
            throw new \Exception("缺少剪辑模版");
        }
        if (count($anchorData) == 0){
            throw new \Exception("形象不能为空");
        }

        foreach ($anchorData as $data){
            if (!array_key_exists('anchor_id', $data) || trim($data['anchor_id']) === '') {
                throw new \Exception("形象不存在");
            }
        }

        if (count($voiceData) == 0){
            throw new \Exception("音色不能为空");
        }
        foreach ($voiceData as $data){
            if (!array_key_exists('voice_id', $data) || trim($data['voice_id']) === '') {
                throw new \Exception("音色还没有生成");
            }
        }
        if (count($copywritingData) == 0){
            throw new \Exception("文案不能为空");
        }
        foreach ($copywritingData as $data){
            if (!array_key_exists('content', $data) || trim($data['content']) === '') {
                throw new \Exception("文案不能为空");
            }
        }

        if (count($materialData) < 3){
            throw new \Exception("素材不能少于三条");
        }
        if (count($characterDesignData) == 0){
            throw new \Exception("人设信息不能为空");
        }

        $copywritingDatanum = count($copywritingData)  * 0.5;
        $materialDatanum = count($materialData);
        $randcopywriting = false;
        if ($materialDatanum > $copywritingDatanum && $materialDatanum > 4){
            $randcopywriting = true;
        }

        for ($i = 0; $i < $videoCount; $i++) {
            $number = random_int(1, 20);
            $music =  config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
            if ( count($musicData) == 0){
                $music_url = $music;
            }else{
                $music_url = $musicData[$i % count($musicData)]['fileUrl'] ?? $music;
            }
            $clip = random_int(0, $clip_template_total);
            if ( count($clipData) == 0){
                $clip_id = $clip_template_id[$clip];
            }else{
                $clip_id =  $clipData[$i % count($clipData)]['clip_template_id'] ??  $clip_template_id[$clip];
            }
            $material = json_encode($materialData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if ($randcopywriting){
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
                $material = json_encode($material, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            }

            $taskItem = [
                'name' => ($params['name'] ?? '视频设置'.date('YmdHi')) . '_' . ($i + 1),
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
                'material' =>$material,
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


    public static function check(){

        try {
            ShanjianVideoSetting::whereIn('status',[1,2])
                ->where('create_time', '<=', strtotime('-1440 minutes'))
                ->select()->each(function ($item) {

                    $item->success_num = ShanjianVideoTask::where('video_setting_id', $item->id)->where('status', 3)->count();
                    if ($item->success_num > 0 ){
                        $update['error_num'] = $item->video_count - $item->success_num;
                        $update['status'] = 3;
                    }else{
                        $update['error_num'] = $item->video_count;
                        $update['status'] = 3;
                    };
                    ShanjianVideoSetting::where('id',$item->id)->update($update);

                });

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

    }
}
