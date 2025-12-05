<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use think\facade\Db;
use think\facade\Log;
use app\common\service\FileService;

/**
 * ShanjianVideoTaskLogic
 * 闪剪视频任务逻辑处理
 */
class ShanjianVideoTaskLogic extends ApiLogic
{

    const COPYWRITING_CREATE = 'copywritingCreate'; //文案创作
    const SHANJIAN_VIDEO = 'shanjianVideo';

    /**
     * 更新闪剪视频任务
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $task = ShanjianVideoTask::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->find();

            if (!$task) {
                self::setError('视频任务不存在');
                return false;
            }

            $data = [];

            // 只更新允许修改的字段
            $allowFields = ['name', 'title', 'msg', 'card_name', 'card_introduced', 'material', 'music_url', 'clip_id'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    if ($field === 'material' || $field === 'extra') {
                        $data[$field] = json_encode($params[$field]);
                    } else {
                        $data[$field] = $params[$field];
                    }
                }
            }

            if (!empty($data)) {
                $data['update_time'] = time();
                $task->save($data);
            }

            self::$returnData = $task->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除闪剪视频任务
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {

            if (is_string($id)) {
                $task = ShanjianVideoTask::where('id', $id)
                    ->where('user_id', self::$uid)
                    ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                    ->find();

                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                ShanjianVideoTask::where('id', $id)->select()->delete();
            } else {
                $task = ShanjianVideoTask::whereIn('id', $id)->where(['user_id' => self::$uid])
                    ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                    ->column('id');
                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                ShanjianVideoTask::whereIn('id', $id)->select()->delete();
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取闪剪视频任务详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $task = ShanjianVideoTask::where('id', $id)
                ->where('user_id', self::$uid)
                ->find();

            if (!$task) {
                self::setError('视频任务不存在');
                return false;
            }

            $taskData = $task->toArray();

            // 处理JSON字段
            if (!empty($taskData['material'])) {
                $taskData['material'] = json_decode($taskData['material'], true);
            } else {
                $taskData['material'] = [];
            }

            if (!empty($taskData['extra'])) {
                $taskData['extra'] = json_decode($taskData['extra'], true);
            } else {
                $taskData['extra'] = [];
            }

            // 处理文件URL
            //            $taskData['pic'] = trim($taskData['pic']) ? FileService::getFileUrl($taskData['pic']) : "";
            //            $taskData['music_url'] = trim($taskData['music_url']) ? FileService::getFileUrl($taskData['music_url']) : "";
            //            $taskData['video_result_url'] = trim($taskData['video_result_url']) ? FileService::getFileUrl($taskData['video_result_url']) : "";

            self::$returnData = $taskData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * 从回调更新闪剪视频任务
     * @param array $data
     * @return bool
     */
    public static function notify(array $data): bool
    {
        if (!isset($data['task_id']) || empty($data['task_id'])) {
            self::setError('缺少任务ID');
            return false;
        }

        // 先初步查找任务，减少不必要的事务锁定
        $task = ShanjianVideoTask::where('task_id', $data['task_id'])->where('status', 1)->find();
        if (!$task) {
            // 任务不存在或状态已变更，直接返回成功，避免回调方重试
            Log::channel('shanjian')->info('Notify: 任务不存在或状态已变更，task_id: ' . $data['task_id']);
            return true;
        }

        Db::startTrans();
        try {
            // 在事务中锁定任务行，防止并发修改
            $task = ShanjianVideoTask::where('task_id', $data['task_id'])->lock(true)->find();

            // 再次检查任务状态，确保在锁定期间没有被其他进程处理
            if (!$task || $task->status != 1) {
                Db::commit(); // 任务已被处理，提交空事务并返回
                Log::channel('shanjian')->info('Notify: 任务在锁定后发现已被处理，task_id: ' . $data['task_id']);
                return true;
            }

            if (isset($data['status'])) {
                $ShanjianVideoSetting = ShanjianVideoSetting::where('id', $task->video_setting_id)->whereIn('status', [1, 2])->findOrEmpty();
                if ($ShanjianVideoSetting->isEmpty()) {
                    throw new \Exception('关联的视频设置不存在');
                }
                $num = $ShanjianVideoSetting->video_count - $ShanjianVideoSetting->success_num - $ShanjianVideoSetting->error_num;
                $status = $num <= 1 ? 3 : 2;

                switch ($data['status']) {
                    case 'failed':
                        $task->status = 2;
                        $task->remark = $data['errorMessage'] ?? '处理失败';
                        $ShanjianVideoSetting->error_num += 1;
                        $ShanjianVideoSetting->status = $status;
                        $ShanjianVideoSetting->save();
                        break;
                    case 'succeed':
                        $task->status = 3;
                        if (isset($data['result']['videoUrl'])) {
                            $count = UserTokensLog::where('user_id', $task->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN)->where('action', 2)->where('task_id', $task->task_id)->count();
                            if ($count == 0) {
                                $duration = $data['result']['duration'];
                                $video_result_url = FileService::downloadFileBySource($data['result']['videoUrl'], 'video');
                                Log::channel('shanjian')->write('获取视频链接' . json_encode(['old' => $data['result']['videoUrl'] ?? '没有', 'new' => $video_result_url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                                $task->video_result_url = $video_result_url;
                                $unit = TokenLogService::checkToken($task->user_id, 'human_video_shanjian');
                                $points = round($duration * $unit, 2);
                                $extra = ['扣费项目' => '口播混剪视频生成：视频时长' . $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                $task->video_token = $points;
                                User::userTokensChange($task->user_id, $points);
                                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN, $points, $task->task_id, $extra);
                            }
                        }
                        $ShanjianVideoSetting->success_num += 1;
                        $ShanjianVideoSetting->status = $status;
                        $ShanjianVideoSetting->save();
                        break;
                }
            }

            $task->update_time = time();
            $task->save();

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            Log::channel('shanjian')->error('Notify 处理失败, task_id: ' . $data['task_id'] . ', Error: ' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function copywriting(array $data)
    {
        $keywords = $data['keywords'] ?? '';
        $number = $data['number'] ?? '';
        if (empty($keywords)  || empty($number)) {
            message('参数错误');
        }

        $taskId = generate_unique_task_id();
        $request = [
            'keywords' => $keywords,
            'number' => $number,
            'channelVersion' => 5,
        ];
        $scene = self::COPYWRITING_CREATE;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);
        if (!empty($result) && isset($result['content'])) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $response =  \app\common\service\ToolsService::Shanjian();
        [$tokenScene, $tokenCode] = match ($scene) {
            self::COPYWRITING_CREATE => ['shanjian_copywriting_create', AccountLogEnum::TOKENS_DEC_COZE_TEXT],
            self::SHANJIAN_VIDEO => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {
            case self::COPYWRITING_CREATE:

                $response =  $response->text($request);
                break;
            case self::SHANJIAN_VIDEO:
                $response = $response->virtualmanBroadcast($request);
                break;
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;

            if ($points > 0) {

                $extra = [];
                $break = false;
                switch ($scene) {
                    case self::COPYWRITING_CREATE:
                        $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    case self::SHANJIAN_VIDEO:
                        $break = true;
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
    }


    /**
     * 处理视频合成任务
     * @param string $taskId 任务ID
     * @return void
     */
    public static function compositeVideoCron(string $taskId = '')
    {
        try {

            // 获取待处理的任务，限制5条
            $tasks =  ShanjianVideoTask::where(function ($q) use ($taskId) {
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

            foreach ($tasks as $task) {
                try {
                    // 更新状态为视频合成中
                    $scene = self::SHANJIAN_VIDEO;
                    $response = self::requestUrl([
                        'styleId'      => $task->clip_id,
                        'virtualmanId'      => $task->anchor_id,
                        'title'       => $task->title,
                        'content'       => $task->msg,
                        'speakerId'      => $task->voice_id,
                        'materials'      => $task->material,
                        'introduceCard'      => [
                            'name' => $task->card_name,
                            'description' => $task->card_introduced,
                        ],
                        'packRules' => [
                            "backgroundMusic" => [
                                "audioSwitch" => true,
                                "audioUrl" => $task->music_url,
                                "volume" => 0.5
                            ],
                        ],
                        'processRules' => [
                            "watermarkShow" => false,
                        ]
                    ], $scene, $task->user_id, $task->task_id);
                    Log::channel('shanjian')->write('合成视频' . json_encode($response));

                    if (!isset($response['data']['taskId']) || empty($response['data']['taskId'])) {
                        $task->tries = $task->tries + 1;
                        if ($task->tries == 10) {
                            $task->remark = '视频合成10次失败';
                            if (isset($response['message']) && !empty($response['message'])) {
                                $task->remark = $response['message'];
                            }
                            $task->status = 2;
                        }
                        $task->save();
                        return;
                    }
                    if (isset($response['token']) && $response['token'] > 0) {
                        $task->video_token = $response['token'];
                    }

                    if (isset($response['code']) && $response['code'] == 'Succeed' && $response['code'] != 10000) {
                        // 更新视频结果
                        $task->result_id = $response['data']['taskId'];
                        $task->status = 1;
                        if (!$task->save()) {
                            throw new \Exception("更新视频结果失败");
                        }
                    } elseif (isset($response['code']) && $response['code'] != 10000) {
                        $task->tries = $task->tries + 1;
                        if ($task->tries == 10) {
                            $task->status = 2;
                            $task->remark = '视频合成10次失败';
                        }
                        $task->save();
                        return;
                    }
                } catch (\Exception $e) {
                    $task->tries = $task->tries + 1;
                    if ($task->tries == 10) {
                        $task->status = 2;
                    }
                    $task->remark = $e->getMessage();
                    $task->save();
                }
            }
        } catch (\Exception $e) {
            Log::channel('shanjian')->info('批量处理视频任务失败' . $e->getMessage());
        }
    }


    public static function check()
    {
        try {
            $tasks = ShanjianVideoTask::where('status', 1)
                ->where('create_time', '<=', strtotime('-5 minutes'))
                ->select();

            foreach ($tasks as $task) {
                // 1. 先进行外部API调用，避免在事务中持有锁过长时间
                $params = ['taskId' => $task->result_id, 'task_id' => $task->task_id];
                $response = \app\common\service\ToolsService::Shanjian()->status($params);

                // 如果API调用失败，或任务仍在处理中，则跳过当前任务，等待下次检查
                if (!isset($response['code']) || $response['code'] != 10000 || 
                    (isset($response['data']['status']) && $response['data']['status'] == 'processing')
                ) {
                    continue; 
                }

                Db::startTrans();
                try {
                    // 在事务中锁定任务行，防止与notify等其他进程并发修改
                    $item = ShanjianVideoTask::where('id', $task->id)->lock(true)->find();

                    // 再次检查任务状态，确保在锁定期间没有被处理
                    if (!$item || $item->status != 1) {
                        Db::commit(); // 任务已被处理，提交空事务并继续下一个
                        continue;
                    }

                    $ShanjianVideoSetting = ShanjianVideoSetting::where('id', $item->video_setting_id)->whereIn('status', [1, 2])->findOrEmpty();
                    if ($ShanjianVideoSetting->isEmpty()) {
                        $item->status = 2;
                        $item->remark = '关联的视频设置不存在，任务超时';
                        $item->save();
                        Db::commit();
                        continue;
                    }

                    $num = $ShanjianVideoSetting->video_count - $ShanjianVideoSetting->success_num - $ShanjianVideoSetting->error_num;
                    $status = $num <= 1 ? 3 : 2; // 如果是最后一个任务，则设置总状态为3
                    
                    if (isset($response['data']['status'])) {
                        $data = $response['data'];
                        switch ($data['status']) {
                            case 'failed':
                                $item->status = 2;
                                $item->remark = $data['errorMessage'] ?? '处理失败';
                                $ShanjianVideoSetting->error_num += 1;
                                $ShanjianVideoSetting->status = $status;
                                $ShanjianVideoSetting->save();
                                break;
                            case 'succeed':
                                $item->status = 3;
                                if (isset($data['result']['videoUrl'])) {
                                    $count = UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN)->where('action', 2)->where('task_id', $item->task_id)->count();
                                    if ($count == 0) {
                                        $duration = $data['result']['duration'];
                                        $video_result_url = FileService::downloadFileBySource($data['result']['videoUrl'], 'video');
                                        Log::channel('shanjian')->write('获取视频链接' . json_encode(['old' => $data['result']['videoUrl'] ?? '没有', 'new' => $video_result_url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                        
                                        $item->video_result_url = $video_result_url;
                                        $unit = TokenLogService::checkToken($item->user_id, 'human_video_shanjian');
                                        $points = round($duration * $unit, 2);
                                        $extra = ['扣费项目' => '口播混剪视频生成：视频时长' . $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                        $item->video_token = $points;
                                        User::userTokensChange($item->user_id, $points);
                                        AccountLogLogic::recordUserTokensLog(true, $item->user_id, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN, $points, $item->task_id, $extra);
                                    }
                                }
                                $ShanjianVideoSetting->success_num += 1;
                                $ShanjianVideoSetting->status = $status;
                                $ShanjianVideoSetting->save();
                                break;
                        }
                        $item->update_time = time();
                        $item->save();
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    Log::channel('shanjian')->error('Check 方法处理任务失败, task_id: ' . $task->task_id . ', Error: ' . $e->getMessage());
                    // 标记任务为失败，避免无限重试
                    $task->status = 2;
                    $task->remark = 'Check方法异常：' . $e->getMessage();
                    $task->save();
                }
            }
            return true;
        } catch (\Exception | \think\db\exception\DataNotFoundException | \think\db\exception\ModelNotFoundException $e) { // Added specific DB exceptions
            self::setError($e->getMessage());
            Log::channel('shanjian')->error('Check 方法整体异常: ' . $e->getMessage());
            return false;
        }
    }
}
