<?php

namespace app\api\logic\storyboard;

use app\api\controller\VideoInfoController;
use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\WechatLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\storyboard\StoryboardVideoSetting;
use app\common\model\storyboard\StoryboardVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

/**
 * StoryboardVideoTaskLogic
 * 分镜视频任务逻辑处理
 */
class StoryboardVideoTaskLogic extends ApiLogic
{
    const STORYBOARD_VIDEO_CREATE = 'storyboard_video_create';
    const STORYBOARD_VIDEO_STATUS = 'storyboard_video_status';

    public static function notify(array $data)
    {
        if (empty($data['task_id'])) {
            self::setError('缺少任务ID');
            return false;
        }
        // 先初步查找任务，减少不必要的事务锁定
        $task = StoryboardVideoTask::where('task_id', $data['task_id'])->where('status', 'in', [0, 1])->find();
        if (!$task) {
            // 任务不存在
            Log::channel('storyboard')->info('Notify: 任务不存在，task_id: ' . $data['task_id']);
            return true;
        }
        Db::startTrans();
        try {
            if (isset($data['state'])) {
                $StoryboardVideoSetting = StoryboardVideoSetting::where('id', $task->video_setting_id)->findOrEmpty();
                if ($StoryboardVideoSetting->isEmpty()) {
                    throw new \Exception('关联的视频设置不存在');
                }
                $num = $StoryboardVideoSetting->video_count - $StoryboardVideoSetting->success_num - $StoryboardVideoSetting->error_num;
                $typeID = AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO;
                $scene  = 'storyboard_video_create';
                switch ($data['state']) {
                    case 'error':
                        $status = '生成失败';
                        if ($num == 1 && $StoryboardVideoSetting->error_num > 0) {
                            $StoryboardVideoSetting->status = 4;
                        }
                        $task->status = 2;
                        $task->remark = $data['message'] ?? '处理失败';
                        if (str_contains($task->remark, 'containing photorealistic people')) {
                            $task->remark = '目前不支持上传包含真人的图像';
                        }
                        if (str_contains($task->remark, 'system error')) {
                            $task->remark = '系统错误生成失败，请重新生成';
                        }
                        if (str_contains($task->remark, 'third-party') || str_contains($task->remark, 'content policies')) {
                            $task->remark = '此内容违反第三方肖像权、内容相似性的防护规定，请重新生成';
                        }
                        if (str_contains($task->remark, 'please try again') || str_contains($task->remark, 'task timeout') || str_contains($task->remark, 'You already have')) {
                            $task->remark = '任务超时，请稍后再试';
                        }
                        $StoryboardVideoSetting->error_num += 1;
                        $StoryboardVideoSetting->save();
                        $userId = $task->user_id;
                        $taskId = $task->task_id;
                        $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                        }
                        break;
                    case 'succeeded':
                        $status = '生成成功';
                        $task->status = 3;
                        if (isset($data['data']['videos'])) {
                            $video_result_url = FileService::downloadFileBySource($data['data']['videos'][0]['url'], 'video');
                            $old              = '没有';
                            $urldata          = [
                                'old' => $old,
                                'new' => $video_result_url
                            ];
                            Log::channel('storyboard')->write('获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $task->video_result_url = $video_result_url;
                        }
                        if ($num == 1 && $StoryboardVideoSetting->error_num > 0) {
                            $StoryboardVideoSetting->status = 4;
                        }
                        if ($num == 1 && $StoryboardVideoSetting->error_num < 1) {
                            $StoryboardVideoSetting->status = 3;
                        }
                        $StoryboardVideoSetting->success_num += 1;
                        $StoryboardVideoSetting->save();
                        $unit              = ModelConfig::where('scene', $scene)->value('score', 0);
                        $points            = $unit;
                        $task->video_token = $points;
                        //生成缩略图
                        if ($task->width == '16') {
                            $width  = 960;
                            $height = 540;
                        } else {
                            $width  = 540;
                            $height = 960;
                        }
                        $videos          = [
                            'video_url' => FileService::getFileUrl($video_result_url),
                            'time'      => 1.0,
                            'options'   => [
                                'width'   => $width,
                                'height'  => $height,
                                'quality' => 2
                            ]
                        ];
                        $thumbnailResult = (new VideoInfoController())->videoThumbnail($videos);
                        if ($thumbnailResult['result']) {
                            $task->pic = $thumbnailResult['url'];
                        }
                        break;
                }

                $mnpMessage = [
                    'openid'   => UserAuth::where('user_id', $task->user_id)->order('id', 'desc')->value('openid'),
                    'scene_id' => 402,
                    'name'     => $task->name,
                    'time'     => date('Y-m-d H:i:s', time()),
                    'status'   => $status
                ];
                WechatLogic::sendMnpMessage($mnpMessage);
            }

            $task->update_time = time();
            $task->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::channel('storyboard')->error('Notify 处理失败, task_id: ' . $data['task_id'] . ', Error: ' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function videoTask(array $data)
    {
        $name = $data['name'] ?? '';
        //step 1
        $theme   = $data['theme'] ?? '';
        $content = $data['content'] ?? '';
        $gender  = $data['gender'] ?? '';
        //step 2
        $image_urls = $data['image_urls'] ?? [];
        //step 3
        $frequency    = $data['frequency'] ?? '';       //镜头切换频率
        $aspect_ratio = $data['aspect_ratio'] ?? '16:9';//输出比例
        $duration     = $data['duration'] ?? 10;        //输出时长
        $style        = $data['style'] ?? '';           //视频风格
        $number       = $data['number'] ?? 1;           //生成视频数量

        $keywords = '视频类型：【' . $theme . '】
        视频细节：【' . $content . '】
        人物性别：【' . $gender . '】
        视频风格：【' . $style . '】
        镜头切换频率：【' . $frequency . '】
        输出比例：【' . $aspect_ratio . '】
        输出时长：【' . $duration . 's】';
        if (empty($name) || empty($number) || empty($theme) || empty($content) || empty($gender) || empty($style)) {
            message('参数错误');
        }

        $taskId  = generate_unique_task_id();
        $request = [
            'prompt'       => $keywords,
            'aspect_ratio' => $aspect_ratio,
            'duration'     => $duration,
            'image_urls'   => $image_urls
        ];
        $scene   = self::STORYBOARD_VIDEO_CREATE;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);

        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

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


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::storyboard();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::STORYBOARD_VIDEO_CREATE  => ['storyboard_video_create', AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO],
            };                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          //计费
            $unit               = TokenLogService::checkToken($userId, $tokenScene);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now']     = time();

            switch ($scene) {
                case self::COPYWRITING_CREATE:
                    $response = $response->text($request);
                    break;
                case self::STORYBOARD_VIDEO_CREATE:
                    $response = $response->create($request);
                    break;
                default:
            }

            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $duration = $response['data']['data']['duration'] ?? 0;
                $points   = $unit * $request['duration'];
                if ($points > 0) {
                    $extra = [];
                    switch ($scene) {
                        case self::COPYWRITING_CREATE:
                            $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::STORYBOARD_VIDEO_CREATE:
                            $extra = ['扣费项目' => '一句话生成视频', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        default:
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
                return $response ?? [];
            }
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
