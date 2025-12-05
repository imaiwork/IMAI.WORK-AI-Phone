<?php

namespace app\api\logic\sv;

use app\common\model\human\HumanVoice;
use app\common\model\sv\SvModules;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use think\facade\Db;
/**
 * SvVideoSettingLogic
 * @desc 视频设置逻辑处理
 */
class SvVideoSettingLogic extends SvBaseLogic
{
    /**
     * @desc 添加视频设置
     * @param array $params
     * @return bool
     */
    public static function addSvVideoSetting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 检查状态值
            $status = isset($params['status']) ? (int)$params['status'] : null;
            if ($status === null || ($status !== 0 && $status !== 1)) {
                self::setError('非法操作：status参数只能为0或1');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'music', 'clip', 'music_type'];
            $decodedData = [];
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
            $params['video_count'] = count($decodedData['anchor'] ?? []);
            // 视频数量检查
            $videoCount = (int)$params['video_count'];

            // 如果status=1，需要进行更多的检查
            if ($status === 1 ) {
                // 检查数据是否足够生成不重复组合
                $voiceCount = count($decodedData['voice'] ?? []);
                $anchorCount = count($decodedData['anchor'] ?? []);
                $copywritingCount = count($decodedData['copywriting'] ?? []);
                $clipCount = count($decodedData['clip'] ?? []);
                $musicCount = count($decodedData['music'] ?? []);
                $musictypeCount = count($decodedData['music_type'] ?? []);

                if($voiceCount == 0){
                    $maxCombinations = max(1, $anchorCount) * max(1, $copywritingCount) * max(1, $clipCount) * max(1, $musicCount) * max(1, $musictypeCount);
                }else{
                    $maxCombinations = max(1, $anchorCount) * max(1, $voiceCount) * max(1, $copywritingCount) * max(1, $clipCount) * max(1, $musicCount)* max(1, $musictypeCount);
                }
                // 最大可能组合数量
                if ($videoCount > $maxCombinations) {
                    self::setError("设置的视频数量({$videoCount})超过了可能的组合数量({$maxCombinations})");
                    return false;
                }
            }
            
            // 开始事务
            Db::startTrans();
            try {
                $params['task_id'] = generate_unique_task_id();
                $params['pic'] =  $params['pic'] ?? '';
                // 添加视频设置
                $setting = SvVideoSetting::create($params);

                // 仅当status=1时才创建视频任务
                if ($status === 1) {
                    $taskData = [];
                    $anchorArr = $decodedData['anchor'] ?? [];
                    $copywritingArr = $decodedData['copywriting'] ?? [];
                    $voiceArr = $decodedData['voice'] ?? [];
                    $clipArr = $decodedData['clip'] ?? [];
                    $musicArr = $decodedData['music'] ?? [];
                    $musicTypeArr = $decodedData['music_type'] ?? [];
                    $anchorCount = count($anchorArr);
                    $copywritingCount = count($copywritingArr);
                    $voiceCount = count($voiceArr);
                    $clipCount = count($clipArr);
                    $musicCount = count($musicArr);
                    
                    for ($i = 0; $i < $videoCount; $i++) {
                        // 形象顺序分配
                        $anchorIndex = $i % max(1, $anchorCount);
                        $anchorItem = $anchorArr[$anchorIndex] ?? [];

                        // 口播文案分配
                        if ($copywritingCount > 0 && $copywritingCount == $anchorCount) {
                            // 顺序一一对应
                            $copywritingIndex = $anchorIndex;
                        } else {
                            // 随机分配
                            $copywritingIndex = $copywritingCount > 0 ? array_rand($copywritingArr) : 0;
                        }
                        $copywritingItem = $copywritingArr[$copywritingIndex] ?? [];

                        // clip分配
                        if ($clipCount > 0) {
                            if ($clipCount == $anchorCount) {
                                $clipIndex = $anchorIndex;
                            } else {
                                $clipIndex = array_rand($clipArr);
                            }
                            $clipItem = $clipArr[$clipIndex] ?? [];
                        } else {
                            $clipItem = [];
                        }

                        // music分配
                        if ($musicCount > 0) {
                            if ($musicCount == $anchorCount) {
                                $musicIndex = $anchorIndex;
                            } else {
                                $musicIndex = array_rand($musicArr);
                            }
                            $musicItem = $musicArr[$musicIndex] ?? [];
                        } else {
                            $musicItem = [];
                        }
                        // music_type分配
                        if ($musictypeCount > 0) {
                            if ($musictypeCount == $anchorCount) {
                                $musictypeIndex = $anchorIndex;
                            }else {
                                $musictypeIndex = array_rand($musicTypeArr);
                            }
                            $musictypeItem = $musicTypeArr[$musictypeIndex] ?? [];
                        }else {
                            $musictypeItem = [];
                        }

                         // voice分配
                         if ($voiceCount > 0) {
                            if ($voiceCount == $anchorCount) {
                                $voiceIndex = $anchorIndex;
                            } else {
                                $voiceIndex = array_rand($voiceArr);
                            }
                            $voiceItem = $voiceArr[$voiceIndex] ?? [];
                        } else {
                            $voiceItem = [];
                        }

                        $model_version = $params['model_version'] ?? 7;

                        $voice_id = $voiceItem['voice_id'] ?? '';
                        $voice_type = $voiceItem['voice_type'] ?? 1;
                        if($voice_type == 0 && $voice_id != ''){
                            $voice_id = HumanVoice::getBuiltInVoice($voice_id,$model_version);
                        }

                        if ($voice_id!= '') {
                            $status = 13;
                        }else{
                            $status = 10;
                        }
                        $anchor_id = $anchorItem['anchor_id'] ?? '';
                        if ($anchor_id != '' && $voice_id!= '') {
                            $status = 13;
                        }elseif($anchor_id != '' && $voice_id == '') {
                            $status = 10;
                        }elseif($anchor_id == '' ) {
                            $status = 0;
                        }
                        if ($i == 0 && $params['pic'] == '') {
                            $params['pic'] = $anchorItem['pic'] ?? '';
                        }
                        $ai_type = $params['ai_type'] ?? 0;
                        $music_url = $musicItem['url'] ?? '';
                        $taskItem = [
                            'user_id' => self::$uid,
                            'video_setting_id' => $setting->id,
                            'name' => $params['name'] . '_' . ($i + 1),
                            'task_id' => generate_unique_task_id(),
                            'type' => $params['type'],
                            'speed' => $params['speed'],
                            'upload_video_url' => $anchorItem['anchor_url'] ?? '',
                            'pic' => $anchorItem['pic'] ?? '',
                            'status' => $status,
                            'gender' => $anchorItem['gender'] ?? 'female',
                            'model_version' => $model_version,
                            'anchor_id' => $anchor_id,
                            'anchor_name' => $anchorItem['name'] ?? '',
                            'voice_id' => $voice_id,
                            'voice_name' => $voiceItem['name'] ?? '',
                            'voice_urls' => $voiceItem['voice_urls'] ?? '',
                            'msg' => $copywritingItem['content'] ?? '',
                            'poi' => $params['poi'] ?? '',
                            'clip_type' => $clipItem['type'] ?? 1,
                            'ai_type' => $ai_type,
                            'automatic_clip' => $params['automatic_clip'] ?? 0,
                            'music_url' => $music_url,
                            'music_type' => $musictypeItem['type'] ?? 1,
                            'audio_type' => 1,
                            'extra' => json_encode([
                                'copywriting' => $copywritingItem,
                                'anchor' => $anchorItem,
                                'voice' => $voiceItem,
                                'combination' => "{$anchorIndex}_{$copywritingIndex}_" . ($voiceCount > 0 ? $voiceIndex : 'null')
                            ], JSON_UNESCAPED_UNICODE),
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                        $taskData[] = $taskItem;
                    }

                    if (!empty($taskData)) {
                        (new SvVideoTask())->saveAll($taskData);
                    }
                }
                SvVideoSetting::update( ['pic' =>$params['pic'],'update_time' => time()],['id' => $setting->id]);

                // 提交事务
                Db::commit();

                self::$returnData = $setting->toArray();
                return true;
            } catch (\Exception $e) {
                // 回滚事务
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
     * @desc 获取视频设置详情
     * @param array $params
     * @return bool
     */
    public static function detailSvVideoSetting(array $params)
    {
        try {
            // 检查视频设置是否存在
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            $jsonFields = ['anchor', 'voice',  'copywriting'];
            foreach ($jsonFields as $field) {
                if (!empty($setting[$field])) {
                    $setting[$field] = json_decode($setting[$field], true);
                } else {
                    $setting[$field] = [];
                }
            }
            // 返回视频设置信息
            self::$returnData = $setting;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新视频设置
     * @param array $params
     * @return bool
     */
    public static function updateSvVideoSetting(array $params)
    {
        try {
            // 检查视频设置是否存在
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            if ($setting['status'] != 0) {
                unset($params['status']);
                SvVideoSetting::where('id', $params['id'])->update($params);
                self::$returnData = SvVideoSetting::find($params['id'])->toArray();
                return true;
            }
            // 检查状态值
            $status = isset($params['status']) ? (int)$params['status'] : null;
            if ($status === null || ($status !== 0 && $status !== 1)) {
                self::setError('非法操作：status参数只能为0或1');
                return false;
            }
            $params['user_id'] = self::$uid;

            // 预处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'music', 'clip', 'music_type'];
            $decodedData = [];
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
            $params['video_count'] = count($decodedData['anchor'] ?? []);
            // 视频数量检查
            $videoCount = (int)$params['video_count'];
            // 如果status=1，需要进行更多的检查
            if ($status === 1 ) {
                // 检查数据是否足够生成不重复组合
                $voiceCount = count($decodedData['voice'] ?? []);
                $anchorCount = count($decodedData['anchor'] ?? []);
                $copywritingCount = count($decodedData['copywriting'] ?? []);
                $clipCount = count($decodedData['clip'] ?? []);
                $musicCount = count($decodedData['music'] ?? []);
                $musictypeCount = count($decodedData['music_type'] ?? []);
                  
                if($voiceCount == 0){

                    $maxCombinations = max(1, $anchorCount) * max(1, $copywritingCount) * max(1, $clipCount) * max(1, $musicCount)* max(1, $musictypeCount);
                }else{
                    $maxCombinations = max(1, $anchorCount) * max(1, $voiceCount) * max(1, $copywritingCount) * max(1, $clipCount) * max(1, $musicCount)* max(1, $musictypeCount);
                }
                // 最大可能组合数量
                if ($videoCount > $maxCombinations) {
                    self::setError("设置的视频数量({$videoCount})超过了可能的组合数量({$maxCombinations})");
                    return false;
                }
            }
            Db::startTrans();
            try {
                // 更新视频设置
                if ($setting['status'] == 0) {
                    SvVideoSetting::where('id', $params['id'])->update($params);
                }
                
                // status=1 时处理视频任务
                if ($status === 1) {
                    $taskData = [];
                    $anchorArr = $decodedData['anchor'] ?? [];
                    $copywritingArr = $decodedData['copywriting'] ?? [];
                    $voiceArr = $decodedData['voice'] ?? [];
                    $clipArr = $decodedData['clip'] ?? [];
                    $musicArr = $decodedData['music'] ?? [];
                    $musicTypeArr = $decodedData['music_type'] ?? [];

                    $anchorCount = count($anchorArr);
                    $copywritingCount = count($copywritingArr);
                    $voiceCount = count($voiceArr);
                    $clipCount = count($clipArr);
                    $musicCount = count($musicArr);

                    for ($i = 0; $i < $videoCount; $i++) {
                        // 形象顺序分配
                        $anchorIndex = $i % max(1, $anchorCount);
                        $anchorItem = $anchorArr[$anchorIndex] ?? [];

                        // 口播文案分配
                        if ($copywritingCount > 0 && $copywritingCount == $anchorCount) {
                            // 顺序一一对应
                            $copywritingIndex = $anchorIndex;
                        } else {
                            // 随机分配
                            $copywritingIndex = $copywritingCount > 0 ? array_rand($copywritingArr) : 0;
                        }
                        $copywritingItem = $copywritingArr[$copywritingIndex] ?? [];

                        // voice分配
                        if ($voiceCount > 0) {
                            if ($voiceCount == $anchorCount) {
                                $voiceIndex = $anchorIndex;
                            } else {
                                $voiceIndex = array_rand($voiceArr);
                            }
                            $voiceItem = $voiceArr[$voiceIndex] ?? [];
                        } else {
                            $voiceItem = [];
                        }

                        if ($clipCount > 0) {
                            if ($clipCount == $anchorCount) {
                                $clipIndex = $anchorIndex;
                            } else {
                                $clipIndex = array_rand($clipArr);
                            }
                            $clipItem = $clipArr[$clipIndex] ?? [];
                        } else {
                            $clipItem = [];
                        }


                        // music分配
                        if ($musicCount > 0) {
                            if ($musicCount == $anchorCount) {
                                $musicIndex = $anchorIndex;
                            } else {
                                $musicIndex = array_rand($musicArr);
                            }
                            $musicItem = $musicArr[$musicIndex] ?? [];
                        } else {
                            $musicItem = [];
                        }
                        // music_type分配
                        if ($musictypeCount > 0) {
                            if ($musictypeCount == $anchorCount) {
                                $musictypeIndex = $anchorIndex;
                            }else {
                                $musictypeIndex = array_rand($musicTypeArr);
                            }
                            $musictypeItem = $musicTypeArr[$musictypeIndex] ?? [];
                        }else {
                            $musictypeItem = [];
                        }
                        $model_version = $params['model_version'] ?? 7;
                        $voice_id = $voiceItem['voice_id'] ?? '';
                        $voice_type = $voiceItem['voice_type'] ?? 1;
                        if($voice_type == 0 && $voice_id != ''){
                            $voice_id = HumanVoice::getBuiltInVoice($voice_id,$model_version);
                        }

                        if ($voice_id!= '') {
                            $status = 13;
                        }else{
                            $status = 10;
                        }
                        $anchor_id = $anchorItem['anchor_id'] ?? '';
                        if ($anchor_id != '' && $voice_id!= '') {
                            $status = 13;
                        }elseif($anchor_id != '' && $voice_id == '') {
                            $status = 10;
                        }elseif($anchor_id == '' ) {
                            $status = 0;
                        }
                        if ($i == 0 && $params['pic'] == '') {
                            $params['pic'] = $anchorItem['pic'] ?? '';
                        }
                        $automatic_clip = $params['automatic_clip'] ?? 0;
                        $ai_type = $params['ai_type'] ?? 0;
                        $music_url = $musicItem['url'] ?? '';
//                        if ($automatic_clip == 1 && !preg_match('#^https?://#i', $music_url)) {
//                            throw new \Exception('背景音乐错误');
//                        }

                        $taskItem = [
                            'user_id' => self::$uid,
                            'video_setting_id' => $setting['id'],
                            'name' => $params['name'] . '_' . ($i + 1),
                            'task_id' => generate_unique_task_id(),
                            'type' => $params['type'],
                            'speed' => $params['speed'],
                            'upload_video_url' => $anchorItem['anchor_url'] ?? '',
                            'pic' => $anchorItem['pic'] ?? '',
                            'status' => $status,
                            'gender' => $anchorItem['gender'] ?? 'female',
                            'model_version' => $model_version,
                            'anchor_id' => $anchor_id,
                            'anchor_name' => $anchorItem['name'] ?? '',
                            'voice_id' => $voice_id,
                            'voice_name' => $voiceItem['name'] ?? '',
                            'voice_urls' => $voiceItem['voice_urls'] ?? '',
                            'msg' => $copywritingItem['content'] ?? '',
                            'poi' => $params['poi'] ?? '',
                            'clip_type' => $clipItem['type'] ?? 1,
                            'ai_type' => $ai_type,
                            'music_type' => $musictypeItem['type'] ?? 1,
                            'automatic_clip' => $params['automatic_clip'] ?? 0,
                            'music_url' => $music_url,
                            'audio_type' => 1,
                            'extra' => json_encode([
                                'copywriting' => $copywritingItem,
                                'anchor' => $anchorItem,
                                'voice' => $voiceItem,
                                'music' => $musicItem,
                                'clip' => $clipItem,
                                'combination' => "{$anchorIndex}_{$copywritingIndex}_" . ($voiceCount > 0 ? $voiceIndex : 'null')
                            ], JSON_UNESCAPED_UNICODE),
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                        $taskData[] = $taskItem;
                    }

                    if (!empty($taskData)) {
                        (new SvVideoTask())->saveAll($taskData);
                    }
                } else if ($status === 0) {
                    // status=0 时删除所有关联的视频任务
                    SvVideoTask::where('video_setting_id', $params['id'])->delete();
                }
            
                // 提交事务
                Db::commit();
                self::$returnData = SvVideoSetting::find($params['id'])->toArray();
                return true;
            } catch (\Exception $e) {
                // 回滚事务
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
     * @desc 删除视频设置
     * @param array $params
     * @return bool
     */
    public static function deleteSvVideoSetting(array $params)
    {
        try {
            // 检查视频设置是否存在
            $setting = SvVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }
            Db::startTrans();
            try {
                SvVideoTask::where('video_setting_id', $params['id'])->select()->delete();
                SvVideoSetting::destroy($params['id']);
                Db::commit();
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

    public static function check(){

        try {
            SvVideoSetting::whereIn('status',[1,2])
                ->where('create_time', '<=', strtotime('-1440 minutes'))
                ->select()->each(function ($item) {

                    if ($item->success_num > 0 ){
                        $update['error_num'] = $item->video_count - $item->success_num;
                        $update['status'] = 5;
                    }else{
                        $update['error_num'] = $item->video_count;
                        $update['status'] = 4;
                    };

                    SvVideoSetting::where('id',$item->id)->update($update);

                });

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }

    }
}