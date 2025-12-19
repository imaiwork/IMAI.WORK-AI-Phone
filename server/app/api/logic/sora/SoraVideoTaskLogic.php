<?php

namespace app\api\logic\sora;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

/**
 * SoraVideoTaskLogic
 * sora视频任务逻辑处理
 */
class SoraVideoTaskLogic extends ApiLogic
{
    const SORA_VIDEO_CREATE = 'sora_video_create';
    const SORA_PRO_VIDEO_CREATE = 'sora_pro_video_create';
    const COPYWRITING_CREATE = 'copywriting_create';
    const SORA_VIDEO_STATUS = 'sora_video_status';

    public static function notify(array $data)
    {
        if (empty($data['task_id'])) {
            self::setError('缺少任务ID');
            return false;
        }
        // 先初步查找任务，减少不必要的事务锁定
        $task = SoraVideoTask::where('task_id', $data['task_id'])->where('status', 'in', [0, 1])->find();
        if (!$task) {
            // 任务不存在
            Log::channel('sora')->info('Notify: 任务不存在，task_id: ' . $data['task_id']);
            return true;
        }
        Db::startTrans();
        try {
            if (isset($data['state'])) {
                $SoraVideoSetting = SoraVideoSetting::where('id', $task->video_setting_id)->findOrEmpty();
                if ($SoraVideoSetting->isEmpty()) {
                    throw new \Exception('关联的视频设置不存在');
                }
                $num = $SoraVideoSetting->video_count - $SoraVideoSetting->success_num - $SoraVideoSetting->error_num;
                if ($data['model_version'] == 2){
                    $typeID = AccountLogEnum::TOKENS_DEC_SORA_PRO_VIDEO;
                    $scene = 'sora_pro_video_create';
                }else{
                    $typeID = AccountLogEnum::TOKENS_DEC_SORA_VIDEO;
                    $scene = 'sora_video_create';
                }

                $remark = '一句话生成视频';
                switch ($data['state']) {
                    case 'error':
                        if ($num == 1 && $SoraVideoSetting->error_num > 0){
                            $SoraVideoSetting->status = 4;
                        }
                        $task->status = 2;
                        $task->remark = $data['message'] ?? '处理失败';
                        if (str_contains($task->remark,'containing photorealistic people')){
                            $task->remark = '目前不支持上传包含真人的图像';
                        }
                        if (str_contains($task->remark,'system error')){
                            $task->remark = '系统错误生成失败，请重新生成';
                        }
                        if (str_contains($task->remark,'third-party')){
                            $task->remark = '此内容违反第三方肖像权、内容相似性的防护规定，请重新生成';
                        }
                        $SoraVideoSetting->error_num += 1;
                        $SoraVideoSetting->save();
                        $userId = $task->user_id;
                        $taskId = $task->task_id;
                        $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                        }
                        break;
                    case 'succeeded':
                        $task->status = 3;
                        if (isset($data['data']['videos'])) {
                            $video_result_url = FileService::downloadFileBySource($data['data']['videos'][0]['url'], 'video');
                            $old = '没有';
                            $urldata = [
                                'old' => $old,
                                'new' => $video_result_url
                            ];
                            Log::channel('sora')->write('获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $task->video_result_url = $video_result_url;
                        }
                        if ($num == 1 && $SoraVideoSetting->error_num > 0){
                            $SoraVideoSetting->status = 4;
                        }
                        if ($num == 1 && $SoraVideoSetting->error_num < 1){
                            $SoraVideoSetting->status = 3;
                        }
                        $SoraVideoSetting->success_num += 1;
                        $SoraVideoSetting->save();
                        $unit = ModelConfig::where('scene', $scene)->value('score', 0);
                        $points = $unit;
                        $task->video_token = $points;
                        break;
                }
            }

            $task->update_time = time();
            $task->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::channel('sora')->error('Notify 处理失败, task_id: ' . $data['task_id'] . ', Error: ' . $e->getMessage());
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
        $scene   = self::SORA_VIDEO_CREATE;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);

        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    public static function status($data){
        $taskId = $data['task_id'] ?? '';
        if (!$taskId){
            message('参数错误');
        }
        $scene   = self::SORA_VIDEO_STATUS;
        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    public static function checkStatus(){

        $tasks = SoraVideoTask::where('status', '=',0)->where('create_time', '<', time() - 2400)->select()->toArray();
        Log::channel('sora')->write('超过40分钟无回调的任务' . json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $result = [];
        $response = \app\common\service\ToolsService::sora();
        foreach ($tasks as $task){
            if (!empty($task['extra']['video_id'])){
                $result = $response->status(['task_id' => $task['extra']['video_id']]);
            }
            Log::channel('sora')->write('超过40分钟无回调的任务处理' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 超过两小时无回调的任务处理
            if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                if (isset($result['data']['videos'])){
                    $video_result_url = FileService::downloadFileBySource($result['data']['videos'][0]['url'], 'video');
                    $urldata = [
                        'old' => '没有',
                        'new' => $video_result_url
                    ];
                    Log::channel('sora')->write('定时任务查询获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $scene = $task['model_version'] == 2? self::SORA_PRO_VIDEO_CREATE : self::SORA_VIDEO_CREATE;
                    $unit = ModelConfig::where('scene', '=', $scene )->value('score', 0);
                    $update = [
                        'video_result_url' => $video_result_url,
                        'video_token' => (int)$unit,
                        'status' => 3,
                        'update_time' => time()
                    ];
                    SoraVideoTask::where('id', $task['id'])->update($update);
                    $setting = SoraVideoSetting::where('id', $task['video_setting_id'])->findOrEmpty();
                    if (!$setting->isEmpty()) {
                        $setting->inc('success_num')->save();
                    }
                    continue;
                }else{
                    $errorUpdate = [
                        'status' => 2,
                        'remark' => $result['data']['message'] ?? '请求超时',
                        'update_time' => time()
                    ];
                }
            }else{
                $errorUpdate = [
                    'status' => 2,
                    'remark' => '请求超时',
                    'update_time' => time()
                ];
            }

            //失败返还算力
            $userId = $task['user_id'];
            $taskId = $task['task_id'];
            if ($task['model_version'] == 2){
                $typeID = AccountLogEnum::TOKENS_DEC_SORA_PRO_VIDEO;
            }else{
                $typeID = AccountLogEnum::TOKENS_DEC_SORA_VIDEO;
            }
            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }
            SoraVideoTask::where('id', $task['id'])->update($errorUpdate);
            $setting = SoraVideoSetting::where('id', $task['video_setting_id'])->findOrEmpty();
            if (!$setting->isEmpty()) {
                $setting->inc('error_num')->save();
            }

        }

        return true;
    }
    

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::sora();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::COPYWRITING_CREATE => ['sora_copywriting_create', AccountLogEnum::TOKENS_DEC_SORA_COPYWRITING],
                self::SORA_VIDEO_CREATE  => ['sora_video_create', AccountLogEnum::TOKENS_DEC_SORA_VIDEO],
            };                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        //计费
            $unit               = TokenLogService::checkToken($userId, $tokenScene);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    // 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now']     = time();

            switch ($scene) {
                case self::COPYWRITING_CREATE:
                    $response = $response->text($request);
                    break;
                case self::SORA_VIDEO_CREATE:
                    $response = $response->create($request);
                    break;
                default:
            }

            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $duration = $response['data']['data']['duration'] ?? 0;
                $points   = $unit*$request['duration'];
                if ($points > 0) {
                    $extra = [];
                    switch ($scene) {
                        case self::COPYWRITING_CREATE:
                            $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SORA_VIDEO_CREATE:
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
     * 删除sora视频任务
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {

            if (is_string($id)) {
                $task = SoraVideoTask::where('id', $id)
                                         ->where('user_id', self::$uid)
                                         ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                                         ->find();

                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                SoraVideoTask::where('id', $id)->select()->delete();
            } else {
                $task = SoraVideoTask::whereIn('id', $id)->where(['user_id' => self::$uid])
                                         ->whereIn('status', [2, 3]) // 只能删除失败或成功的任务
                                         ->column('id');
                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                SoraVideoTask::whereIn('id', $id)->select()->delete();
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取sora视频任务详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $task = SoraVideoTask::where('id', $id)
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

}
