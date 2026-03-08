<?php

namespace app\api\logic\storyboard;

use app\api\logic\ApiLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\storyboard\StoryboardVideoSetting;
use app\common\model\storyboard\StoryboardVideoTask;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Log;

/**
 * StoryboardVideoTaskLogic
 * 分镜视频任务逻辑处理
 */
class StoryboardVideoTaskLogic extends ApiLogic
{
    const STORYBOARD_VIDEO_CREATE = 'storyboard_video_create';
    const STORYBOARD_VIDEO_STATUS = 'storyboard_video_status';

    public static function status($data)
    {
        $taskId = $data['task_id'] ?? '';
        if (!$taskId) {
            message('参数错误');
        }
        $scene = self::STORYBOARD_VIDEO_STATUS;
        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    public static function checkStatus()
    {

        $tasks = StoryboardVideoTask::where('status', '=', 0)->where('create_time', '<', time() - 2400)->select()->toArray();
        Log::channel('storyboard')->write('超过40分钟无回调的任务' . json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $result   = [];
        $response = \app\common\service\ToolsService::storyboard();
        foreach ($tasks as $task) {
            if (!empty($task['extra']['video_id'])) {
                $result = $response->status(['task_id' => $task['extra']['video_id']]);
            }
            Log::channel('storyboard')->write('超过40分钟无回调的任务处理' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 超过40分钟无回调的任务处理
            if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                if (isset($result['data']['videos'])) {
                    $video_result_url = FileService::downloadFileBySource($result['data']['videos'][0]['url'], 'video');
                    $urldata          = [
                        'old' => '没有',
                        'new' => $video_result_url
                    ];
                    Log::channel('storyboard')->write('定时任务查询获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $scene  = self::STORYBOARD_VIDEO_CREATE;
                    $unit   = ModelConfig::where('scene', '=', $scene)->value('score', 0);
                    $update = [
                        'video_result_url' => $video_result_url,
                        'video_token'      => (int)$unit,
                        'status'           => 3,
                        'update_time'      => time()
                    ];
                    StoryboardVideoTask::where('id', $task['id'])->update($update);
                    $setting = StoryboardVideoSetting::where('id', $task['video_setting_id'])->findOrEmpty();
                    if (!$setting->isEmpty()) {
                        $setting->inc('success_num')->save();
                    }
                    continue;
                } else {
                    $errorUpdate = [
                        'status'      => 2,
                        'remark'      => $result['data']['message'] ?? '请求超时',
                        'update_time' => time()
                    ];
                }
            } else {
                $errorUpdate = [
                    'status'      => 2,
                    'remark'      => '请求超时',
                    'update_time' => time()
                ];
            }

            //失败返还算力
            $userId = $task['user_id'];
            $taskId = $task['task_id'];

            $typeID = AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO;

            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }
            StoryboardVideoTask::where('id', $task['id'])->update($errorUpdate);
            $setting = StoryboardVideoSetting::where('id', $task['video_setting_id'])->findOrEmpty();
            if (!$setting->isEmpty()) {
                $setting->inc('error_num')->save();
            }

        }

        return true;
    }

    /**
     * 删除分镜视频任务
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {

            if (is_string($id)) {
                $task = StoryboardVideoTask::where('id', $id)
                                           ->where('user_id', self::$uid)
                                           ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                                           ->find();

                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                StoryboardVideoTask::where('id', $id)->select()->delete();
            } else {
                $task = StoryboardVideoTask::whereIn('id', $id)->where(['user_id' => self::$uid])
                                           ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                                           ->column('id');
                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                StoryboardVideoTask::whereIn('id', $id)->select()->delete();
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取分镜视频任务详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $task = StoryboardVideoTask::where('id', $id)
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
            self::$returnData = $taskData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}
