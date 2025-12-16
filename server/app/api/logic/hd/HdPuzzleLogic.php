<?php

namespace app\api\logic\hd;

use app\api\logic\ApiLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

/**
 * 拼图任务逻辑
 */
class HdPuzzleLogic extends ApiLogic
{
    /**
     * 新增拼图任务
     * @param array $params
     * @return bool
     */
    public static function addPuzzle(array $params)
    {
        try {
            $setting = HdPuzzleSetting::where('id', $params['puzzle_setting_id'])->where('user_id', self::$uid)->find();
            if (!$setting) {
                self::setError('拼图设置不存在');
                return false;
            }

            $status = isset($params['status']) ? (int)$params['status'] : 0;
            if (!in_array($status, [0,1,2], true)) {
                self::setError('状态值非法');
                return false;
            }

            foreach (['title', 'material', 'extra'] as $field) {
                if (!empty($params[$field])) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                } else {
                    $params[$field] = json_encode([], JSON_UNESCAPED_UNICODE);
                }
            }

            $params['user_id'] = self::$uid;
            $params['task_id'] = $params['task_id'] ?? generate_unique_task_id();

            $puzzle = HdPuzzle::create($params);
            self::$returnData = $puzzle->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 拼图详情
     * @param array $params
     * @return bool
     */
    public static function detailPuzzle(array $params)
    {
        try {
            $puzzle = HdPuzzle::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$puzzle) {
                self::setError('拼图不存在');
                return false;
            }

            foreach (['title', 'material', 'extra'] as $field) {
                $puzzle[$field] = !empty($puzzle[$field]) ? json_decode($puzzle[$field], true) : [];
            }

            self::$returnData = $puzzle;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 更新拼图
     * @param array $params
     * @return bool
     */
    public static function updatePuzzle(array $params)
    {
        try {
            $puzzle = HdPuzzle::where('id', $params['id'])->where('user_id', self::$uid)->find();
            if (!$puzzle) {
                self::setError('拼图不存在');
                return false;
            }

            if (isset($params['status']) && !in_array((int)$params['status'], [0,1,2], true)) {
                self::setError('状态值非法');
                return false;
            }

            foreach (['title', 'material', 'extra'] as $field) {
                if (array_key_exists($field, $params)) {
                    if (is_array($params[$field])) {
                        $params[$field] = json_encode($params[$field], JSON_UNESCAPED_UNICODE);
                    } else {
                        $decoded = json_decode($params[$field], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            self::setError("字段 {$field} 的JSON格式无效");
                            return false;
                        }
                    }
                }
            }

            $puzzle->save($params);
            self::$returnData = $puzzle->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除拼图
     * @param array $params
     * @return bool
     */
    public static function deletePuzzle(array $params)
    {
        try {
            $puzzle = HdPuzzle::where('id', $params['id'])->where('user_id', self::$uid)->find();
            if (!$puzzle) {
                self::setError('拼图不存在');
                return false;
            }
            $puzzle->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function taskCron($taskId = '')
    {
        // 获取待处理的任务，限制5条
        $tasks = HdPuzzle::where(function ($q) use ($taskId) {
            // 第一组条件
            $q->where('status', 0);

            if (!empty($taskId)) {
                $q->where('task_id', $taskId);
            }
        })
            ->where('tries', '<', 10)
            ->order('tries DESC, id ASC')
            ->limit(5)
            ->select();

        if ($tasks->isEmpty()) {
            return;
        }

        try {
            foreach ($tasks as $task) {
                $puzzleSetting = HdPuzzleSetting::where('id', $task->puzzle_setting_id)->whereIn('status', [1, 2])->findOrEmpty();
                if ($puzzleSetting->isEmpty()) {
                    throw new \Exception('关联的设置不存在');
                }
                try {

                    $task->tries++;
                    $input['images'] = $task['material'];
                    $input['titles'] = $task['title'];
                    $type = $task['type'];
                    switch ($type) {
                        case 1:
                            $input['channelVersion'] = 9;
                            break;
                        case 2:
                            $input['channelVersion'] = 10;
                            break;
                        case 3:
                            $input['channelVersion'] = 11;
                            break;
                        case 4:
                            $input['channelVersion'] = 12;
                            break;
                        case 5:
                            $input['channelVersion'] = 13;
                            break;
                    }
                    $response = \app\common\service\ToolsService::Coze()->puzzle($input);
                    $puzzle_url = [];
                    Log::channel('puzzle')->write('拼图结果' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    
                    if (isset($response['code']) && $response['code'] == 10000) {
                        
                        $image_count = $response['data']['image_count'] ?? 0;
                        $unit = ModelConfig::where('scene', 'combined_picture')->value('score', 0);
                        $points = $unit * $image_count;
                        $result = $response['data']['result'] ?? [];
                        $resultnum = count($result);
                        if ($points > 0 && $resultnum > 0) {
                            foreach ($result as $v) {
                                $puzzle_url[] = FileService::downloadFileBySource($v, 'image');
                            }
                            $task->puzzle_url = json_encode($puzzle_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $extra = ['扣费项目' => '小红书图片自动合成', '算力单价' => $unit, '实际消耗算力' => $points];
                            $task->img_token = $points;
                            $taskId = $task->task_id;
                            //token扣除
                            User::userTokensChange($task->user_id, $points);
                            //记录日志
                            AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_COMBINED_PICTURE, $points, $taskId, $extra);
                            $task->status = 1;
                            $puzzleSetting->status = 2;
                            $puzzleSetting->success_puzzle_count += $image_count;
                            $puzzleSetting->success_num += 1;
                        } elseif ($resultnum < 1) {
                            $task->status = 2;
                            $puzzleSetting->error_num += 1;
                            $puzzleSetting->status = 2;
                        }


                    }
                } catch (\Exception $e) {
                    Log::channel('puzzle')->write('拼图是失败：' . $task->task_id.'，原因：'. $e->getMessage());
                    $task->tries = $task->tries + 1;
                    if ($task->tries == 5) {
                        $task->status = 2;
                        $puzzleSetting->error_num += 1;
                    }
                    $task->remark = $e->getMessage();
                }
                $task->save();
                $all = $puzzleSetting->success_num + $puzzleSetting->error_num;
                if ($all >= $puzzleSetting->task_count) {
                    if ($puzzleSetting->success_num == 0) {
                        $puzzleSetting->status = 5;
                    } elseif ($puzzleSetting->error_num > 0) {
                        $puzzleSetting->status = 4;
                    } else {
                        $puzzleSetting->status = 3;
                    }
                }
                $puzzleSetting->save();

            }
        } catch (\Exception $e) {

        }

        return;
    }
}

