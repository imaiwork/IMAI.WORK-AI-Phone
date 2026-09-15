<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvMediaManualSetting;
use app\common\model\sv\SvMediaManualTask;
use think\facade\Db;

/**
 * SvMediaManualSettingLogic
 * @desc 媒体手动设置逻辑处理
 */
class SvMediaManualSettingLogic extends SvBaseLogic
{
    /**
     * @desc 添加媒体手动设置
     * @param array $params
     * @return bool
     */
    public static function addSvMediaManualSetting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;
            $params['name'] = $params['name'] ?? '扫码发布视频' . date('YmdHi');
            // 预处理JSON字段
            $jsonFields = ['media_url', 'copywriting', 'extra'];
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
            // 媒体数量检查
            $params['media_count'] = count($decodedData['media_url'] ?? []);
             $params['pic'] = $decodedData['media_url'][0]['pic'] ?? '';
             if(!empty($params['pic'])){
               $params['pic'] = $decodedData['media_url'][0]['url'] ?? '';
             }
            Db::startTrans();
            try {
                // 添加媒体手动设置
                $setting = SvMediaManualSetting::create($params);
                
                // 如果有媒体URL，则为每个媒体创建对应的任务记录
                $mediaUrls = $decodedData['media_url'] ?? [];
                $copywritings = $decodedData['copywriting'] ?? [];
                $extras = $decodedData['extra'] ?? [];
                
                if (!empty($mediaUrls)) {
                    $taskData = [];
                    $copywritingCount = count($copywritings);
                    $extraCount = count($extras);
                    
                    foreach ($mediaUrls as $index => $mediaUrl) {
                        // 使用模运算实现循环对应：如果文案数组比视频数组短，则循环使用文案
                        $copywritingIndex = $copywritingCount > 0 ? $index % $copywritingCount : null;
                        $extraIndex = $extraCount > 0 ? $index % $extraCount : null;
                        
                        $copywriting = $copywritingIndex !== null ? ($copywritings[$copywritingIndex] ?? '') : '';
                        $extra = $extraIndex !== null ? ($extras[$extraIndex] ?? []) : [];
                        
                        $media_type = $mediaUrl['type'] == 'video' ? 1 : 2; // 1:视频 2:图片
                        if($media_type == 1){
                           $pic = $mediaUrl['pic'] ?? '';
                        }else{
                           $pic = $mediaUrl['url'] ?? '';
                        }
                        $topic = is_array($copywriting) ? ($copywriting['topic'] ?? []) : [];
                        if(is_array($topic)){
                            $topic = json_encode($topic, JSON_UNESCAPED_UNICODE);
                        }
                        $taskItem = [
                            'user_id' => self::$uid,
                            'manual_setting_id' => $setting->id,
                            'name' => $params['name'] . '_任务_' . ($index + 1),
                            'media_type' => $media_type,
                            'media_url' => $mediaUrl['url'] ?? '',
                            'title' => is_array($copywriting) ? ($copywriting['title'] ?? '') : '',
                            'subtitle' => is_array($copywriting) ? ($copywriting['content'] ?? '') : (is_string($copywriting) ? $copywriting : ''),
                            'topic' => $topic,
                            'status' => 0, // 默认未发布
                            'pic' =>  $pic, 
                            'poi' => is_array($copywriting) ? ($copywriting['poi'] ?? '') : '',
                            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
                            'create_time' => time(),
                            'update_time' => time()
                        ];
                        
                        $taskData[] = $taskItem;
                    }
                    
                    // 批量插入任务
                    if (!empty($taskData)) {
                        (new SvMediaManualTask())->saveAll($taskData);
                    }
                }
                
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
     * @desc 获取媒体手动设置详情
     * @param array $params
     * @return bool
     */
    public static function detailSvMediaManualSetting(array $params)
    {
        try {
            // 检查媒体手动设置是否存在
            $setting = SvMediaManualSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体手动设置不存在');
                return false;
            }

            $data = $setting->toArray();
            // 返回媒体手动设置信息
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新媒体手动设置
     * @param array $params
     * @return bool
     */
    public static function updateSvMediaManualSetting(array $params)
    {
        try {
            // 检查媒体手动设置是否存在
            $setting = SvMediaManualSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体手动设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['media_url', 'copywriting', 'extra'];
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
                } else if (isset($params[$field])) {
                    $decodedData[$field] = [];
                    $params[$field] = json_encode([]);
                }
            }
            // 媒体数量检查
            $params['media_count'] = count($decodedData['media_url'] ?? []);
            
            Db::startTrans();
            try {
                // 更新媒体手动设置
                SvMediaManualSetting::where('id', $params['id'])->update($params);
                
                // 如果有媒体URL，则重新创建对应的任务记录
                if (isset($decodedData['media_url'])) {
                    $mediaUrls = $decodedData['media_url'] ?? [];
                    $copywritings = $decodedData['copywriting'] ?? [];
                    $extras = $decodedData['extra'] ?? [];
                    
                    // 先删除现有的任务记录
                    SvMediaManualTask::where('manual_setting_id', $params['id'])->delete();
                    
                    if (!empty($mediaUrls)) {
                        $taskData = [];
                        $copywritingCount = count($copywritings);
                        $extraCount = count($extras);
                        
                        foreach ($mediaUrls as $index => $mediaUrl) {
                            // 使用模运算实现循环对应：如果文案数组比视频数组短，则循环使用文案
                            $copywritingIndex = $copywritingCount > 0 ? $index % $copywritingCount : null;
                            $extraIndex = $extraCount > 0 ? $index % $extraCount : null;
                            
                            $copywriting = $copywritingIndex !== null ? ($copywritings[$copywritingIndex] ?? '') : '';
                            $extra = $extraIndex !== null ? ($extras[$extraIndex] ?? []) : [];
                            
                            $media_type = is_array($mediaUrl) ? ($mediaUrl['type'] == 'video' ? 1 : 2) : 1; // 1:视频 2:图片
                            $mediaUrlValue = is_array($mediaUrl) ? ($mediaUrl['url'] ?? '') : $mediaUrl;
                            
                            $taskItem = [
                                'user_id' => self::$uid,
                                'manual_setting_id' => $params['id'],
                                'name' => $params['name'] . '_任务_' . ($index + 1),
                                'media_type' => $media_type,
                                'media_url' => $mediaUrlValue,
                                'title' => is_array($copywriting) ? ($copywriting['title'] ?? '') : '',
                                'subtitle' => is_array($copywriting) ? ($copywriting['content'] ?? '') : (is_string($copywriting) ? $copywriting : ''),
                                'topic' => is_array($copywriting) ? ($copywriting['topic'] ?? '') : '',
                                'status' => 0, // 默认未发布
                                'poi' => is_array($copywriting) ? ($copywriting['poi'] ?? '') : '',
                                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
                                'create_time' => time(),
                                'update_time' => time()
                            ];
                            
                            $taskData[] = $taskItem;
                        }
                        
                        // 批量插入任务
                        if (!empty($taskData)) {
                            (new SvMediaManualTask())->saveAll($taskData);
                        }
                    }
                }
                
                Db::commit();
                self::$returnData = SvMediaManualSetting::find($params['id'])->toArray();
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
     * @desc 删除媒体手动设置
     * @param array $params
     * @return bool
     */
    public static function deleteSvMediaManualSetting(array $params)
    {
        try {
            // 检查媒体手动设置是否存在
            $setting = SvMediaManualSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体手动设置不存在');
                return false;
            }

            Db::startTrans();
            try {
                // 先删除关联的媒体手动任务
                $deletedTasks = SvMediaManualTask::where('manual_setting_id', $params['id'])->delete();
                
                // 删除媒体手动设置
                $deletedSetting = SvMediaManualSetting::destroy($params['id']);
                
                if ($deletedSetting) {
                    Db::commit();
                    return true;
                } else {
                    Db::rollback();
                    self::setError('删除媒体手动设置失败');
                    return false;
                }
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
}