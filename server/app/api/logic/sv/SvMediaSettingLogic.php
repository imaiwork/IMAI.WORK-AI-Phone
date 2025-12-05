<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvModules;
use app\common\model\sv\SvMediaSetting;
use app\common\model\sv\SvVideoTask;
use think\facade\Db;
/**
 * SvMediaSettingLogic
 * @desc 媒体设置逻辑处理
 */
class SvMediaSettingLogic extends SvBaseLogic
{
    /**
     * @desc 添加媒体设置
     * @param array $params
     * @return bool
     */
    public static function addSvMediaSetting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 预处理JSON字段
            $jsonFields = ['media_url', 'title', 'subtitle'];
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
            // 视频数量检查
            $params['media_count'] = count($decodedData['media_url']);
            try {
                // 添加媒体设置
                $setting = SvMediaSetting::create($params);

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
     * @desc 获取媒体设置详情
     * @param array $params
     * @return bool
     */
    public static function detailSvMediaSetting(array $params)
    {
        try {
            // 检查媒体设置是否存在
            $setting = SvMediaSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体设置不存在');
                return false;
            }

        
            $data = $setting->toArray();
            
            // 转换6个特定字段为数组
            $jsonFields = ['media_url', 'title', 'subtitle'];
            foreach ($jsonFields as $field) {
                if (!empty($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                } else {
                    $data[$field] = [];
                }
            }
            // 返回媒体设置信息
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新媒体设置
     * @param array $params
     * @return bool
     */
    public static function updateSvMediaSetting(array $params)
    {
        try {
            // 检查媒体设置是否存在
            $setting = SvMediaSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体设置不存在');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['media_url', 'title', 'subtitle'];
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
            // 视频数量检查
            $params['media_count'] = count($decodedData['media_url']);
            try {
                // 更新媒体设置
                SvMediaSetting::where('id', $params['id'])->update($params);
                // 提交事务
                self::$returnData = SvMediaSetting::find($params['id'])->toArray();
                return true;
            } catch (\Exception $e) {
                self::setError($e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除媒体设置
     * @param array $params
     * @return bool
     */
    public static function deleteSvMediaSetting(array $params)
    {
        try {
            // 检查媒体设置是否存在
            $setting = SvMediaSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$setting) {
                self::setError('媒体设置不存在');
                return false;
            }

            Db::startTrans();
            try {
                // 删除关联的视频任务
                SvVideoTask::where('video_setting_id', $params['id'])->delete();
                
                // 删除媒体设置
                SvMediaSetting::destroy($params['id']);
                
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


    /**
     * @desc 已有作品添加媒体设置
     * @param array $params
     * @return bool
     */
    public static function addExistSvMediaSetting(array $params)
    {
        try {
            $params['user_id'] = self::$uid;

            // 检查状态值
            $status = isset($params['status']) ? (int)$params['status'] : null;
            if ($status === null || ($status !== 0 && $status !== 1)) {
                self::setError('非法操作：status参数只能为0或1');
                return false;
            }

            // 检查状态值
            $setting_type = isset($params['setting_type']) ? (int)$params['setting_type'] : null;
            if ($setting_type === null || ($setting_type == 1)) {
                self::setError('非法操作：非已有作品新增');
                return false;
            }

            // 预处理JSON字段
            $jsonFields = ['extra', 'title', 'subtitle', 'topic'];
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

            // 视频数量检查
            $videoCount = (int)$params['video_count'];
            if ($videoCount <= 0) {
                self::setError('视频数量必须大于0');
                return false;
            }

            // 如果status=1，需要进行更多的检查
            if ($status === 1) {
                // 检查数据是否足够生成不重复组合
                $extraCount = count($decodedData['extra'] ?? []);
                $titleCount = count($decodedData['title'] ?? []);
                $subtitleCount = count($decodedData['subtitle'] ?? []);
                $topicCount = count($decodedData['topic'] ?? []);

                // 最大可能组合数量
                $maxCombinations = max(1, $extraCount)  * max(1, $titleCount) *
                    max(1, $subtitleCount)  * max(1, $topicCount);
                if ($videoCount > $maxCombinations) {
                    self::setError("设置的视频数量({$videoCount})超过了可能的组合数量({$maxCombinations})");
                    return false;
                }
            }

            // 开始事务
            Db::startTrans();
            try {
                $params['task_id'] = generate_unique_task_id();
                $params['status'] = 3;
                $params['success_num'] = $videoCount;
                // 添加媒体设置
                $setting = SvMediaSetting::create($params);

                // 仅当status=1时才创建视频任务
                if ($status === 1) {
                    // 生成不重复的组合
                    $combinations = [];
                    $taskData = [];

                    // 准备索引范围
                    $extraIndices = range(0, max(0, count($decodedData['extra']) - 1));
                    $titleIndices = range(0, max(0, count($decodedData['title']) - 1));
                    $subtitleIndices = range(0, max(0, count($decodedData['subtitle']) - 1));
                    $topicIndices = range(0, max(0, count($decodedData['topic']) - 1));

                    // 如果某个数组为空，确保至少有一个元素（索引0）
                    if (empty($extraIndices)) $extraIndices = [0];
                    if (empty($titleIndices)) $titleIndices = [0];
                    if (empty($subtitleIndices)) $subtitleIndices = [0];
                    if (empty($topicIndices)) $topicIndices = [0];

                    // 创建不重复的组合
                    $count = 0;
                    $attempts = 0;
                    while ($count < $videoCount) {
                        $attempts++;

                        // 随机选择每个元素的索引
                        $extraIndex = $extraIndices[array_rand($extraIndices)];
                        $titleIndex = $titleIndices[array_rand($titleIndices)];
                        $subtitleIndex = $subtitleIndices[array_rand($subtitleIndices)];
                        $topicIndex = $topicIndices[array_rand($topicIndices)];

                        // 创建组合键
                        $combinationKey = "{$extraIndex}_{$titleIndex}_{$subtitleIndex}_{$topicIndex}";

                        // 如果该组合已存在，重新选择
                        if (isset($combinations[$combinationKey])) {
                            continue;
                        }

                        // 记录已使用的组合
                        $combinations[$combinationKey] = true;

                        // 获取对应的数据
                        $extraItem = !empty($decodedData['extra']) ? $decodedData['extra'][$extraIndex] : [];
                        $titleItem = !empty($decodedData['title']) ? $decodedData['title'][$titleIndex] : [];
                        $subtitleItem = !empty($decodedData['subtitle']) ? $decodedData['subtitle'][$subtitleIndex] : [];
                        $topicItem = !empty($decodedData['topic']) ? $decodedData['topic'][$topicIndex] : [];
                        $model_version = isset($extraItem['model_version']) ? $extraItem['model_version'] : 4;
                        // 构建视频任务数据
                        $taskItem = [
                            'user_id' => self::$uid,
                            'video_setting_id' => $setting->id,
                            'name' => $params['name'] . '_' . ($count + 1),
                            'task_id' => generate_unique_task_id(),
                            'type' => $params['type'],
                            'speed' => $params['speed'],
                            'video_result_url' => isset($extraItem['video_result_url']) ? $extraItem['video_result_url'] : '',
                            'pic' => $params['pic'] ?? "1",
                            'status' => 6, // 待处理状态
                            'title' => isset($titleItem['content']) ? $titleItem['content'] : '',
                            'subtitle' => isset($subtitleItem['content']) ? $subtitleItem['content'] : '',
                            'gender' => isset($extraItem['gender']) ? $extraItem['gender'] : 'female',
                            'model_version' => $model_version,

                            'topic' => isset($topicItem['topic']) ? $topicItem['topic'] : '',
                            'poi' => $params['poi'] ?? '',
                            'audio_type' => 1, // 默认文案驱动
                            'extra' => json_encode([
                                'combination' => $combinationKey // 记录组合信息，方便追踪
                            ], JSON_UNESCAPED_UNICODE),
                            'create_time' => time(),
                            'update_time' => time()
                        ];

                        $taskData[] = $taskItem;
                        $count++;
                    }

                    // 批量插入视频任务
                    if (!empty($taskData)) {
                        (new SvVideoTask())->saveAll($taskData);
                    }
                }

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


    public static function retry(array $params){

        Db::startTrans();

        try {
            $tasks = SvVideoTask::where('video_setting_id', $params['id'])->whereIn('status',[2,5])->where('user_id', self::$uid)
                ->select()->toArray();
            if (!$tasks) {
                self::setError('视频不存在');
                return false;
            };
            $setting = SvMediaSetting::where('id', $params['id'])->field('id,error_num,status')->find();
            if (!$setting) {
                self::setError('任务不存在');
                return false;
            };
            $num = 0;
            foreach ($tasks as $task){
                if ($task['status'] == 2){
                    $update['status'] = 0;
                }else{
                    $update['status'] = 3;
                }
                $update['tries'] = 0;
                SvVideoTask::where('id',$task['id'])->update($update);
                $num++;
            }
            $set['error_num'] = $setting['error_num'] - $num;
            $set['status'] = 2;
            $set['id'] = $setting['id'];
            $setting->update($set);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }


    }
}