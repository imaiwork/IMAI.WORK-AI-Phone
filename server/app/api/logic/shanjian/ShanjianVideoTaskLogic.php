<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
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
    const SHANJIAN_REALMAN_BROADCAST = 'shanjianRealmanBroadcast';
    const SHANJIAN_BROADCAST_MIXCUT = 'shanjianBroadcastMixcut';
    const SHANJIAN_NEWS_MIXCUT = 'shanjianNewsMixcut';

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
     * 更新视频设置表的状态和计数
     * @param int $videoSettingId 视频设置ID
     * @param bool $isSuccess 是否成功
     * @return void
     */
    private static function updateVideoSettingStatus(int $videoSettingId, bool $isSuccess): void
    {
        try {
            $videoSetting = ShanjianVideoSetting::find($videoSettingId);
            if (!$videoSetting) {
                return;
            }

            // 更新计数
            if ($isSuccess) {
                $videoSetting->success_num += 1;
            } else {
                $videoSetting->error_num += 1;
            }

            // 检查是否所有任务都已处理完成
            if ($videoSetting->success_num + $videoSetting->error_num == $videoSetting->video_count) {
                if ($videoSetting->error_num == 0) {
                    // 全部成功
                    $videoSetting->status = 3;
                } elseif ($videoSetting->success_num == 0) {
                    // 全部失败
                    $videoSetting->status = 5;
                } else {
                    // 部分成功
                    $videoSetting->status = 4;
                }
            }

            $videoSetting->save();
        } catch (\Exception $e) {
            Log::channel('shanjian')->write('更新视频设置状态错误' . $e->getMessage());
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
                $typeIDArray = [
                    '1' => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
                    '2' => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
                    '3' => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
                    '4' => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN
                ];
                $typeID = $typeIDArray[$task->shanjian_type] ?? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN;
                $sceneArray = [
                    '1' => 'human_video_shanjian',
                    '2' => 'shanjian_realman_broadcast',
                    '3' => 'shanjian_broadcast_mixcut',
                    '4' => 'shanjian_news_mixcut'
                ];
                $scene = $sceneArray[$task->shanjian_type] ?? 'human_video_shanjian';
                $remarkArray = [
                    '1' => '数字人口播混剪视频',
                    '2' => '真人口播混剪视频',
                    '3' => '素材混剪视预',
                    '4' => '新闻体混剪视频'
                ];
                $remark = $remarkArray[$task->shanjian_type] ?? '数字人口播混剪视频';
                switch ($data['status']) {
                    case 'failed':
                        if ($num == 1 && $ShanjianVideoSetting->error_num > 0){
                            $ShanjianVideoSetting->status = 4;
                        }
                        $task->status = 2;
                        $task->remark = $data['errorMessage'] ?? '处理失败';
                        $ShanjianVideoSetting->error_num += 1;

                        $ShanjianVideoSetting->save();
                        $userId = $task->user_id;
                        $taskId = $task->task_id;

                        $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                        }
                        break;
                    case 'succeed':
                        $task->status = 3;
                        if (isset($data['result']['videoUrl'])) {
                            $video_result_url = FileService::downloadFileBySource($data['result']['videoUrl'], 'video');
                            $old = $data['result']['videoUrl'] ?? '没有';
                            $urldata = [
                                'old' => $old,
                                'new' => $video_result_url
                            ];
                            Log::channel('shanjian')->write('获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $task->video_result_url = $video_result_url;
                        }
                        if ($num == 1 && $ShanjianVideoSetting->error_num > 0){
                            $ShanjianVideoSetting->status = 4;
                        }
                        if ($num == 1 && $ShanjianVideoSetting->error_num < 1){
                            $ShanjianVideoSetting->status = 3;
                        }
                        $ShanjianVideoSetting->success_num += 1;
                        $ShanjianVideoSetting->save();


                        $user = User::find($task->user_id);
                        $unit = ModelConfig::where('scene', $scene)->value('score', 0);
                        $duration = $data['result']['duration'] ?? 1;
                        $points = round($duration * $unit, 2);
                        $newpoints = $task->video_token;
                        $sl = $newpoints - $points;
                        $task->video_token = $points;
                        if ($sl > 0) {

                            switch ($task->shanjian_type) {
                                case 1:
                                    $kf = '克隆数字人混剪剪辑视频预扣费超额扣费退费' ;
                                    break;
                                case 2:
                                    $kf = '真人口播混剪视预扣费超额扣费退费';
                                    break;
                                case 3:
                                    $kf = '素材混剪视预扣费超额扣费退费' ;
                                    break;
                                case 4:
                                    $kf = '新闻体混剪视频预扣费超额扣费退费' ;
                                    break;
                                default:
                                    $kf = '克隆数字人混剪剪辑视频预扣费超额扣费退费';
                                    break;
                            }
                            $sl =  round($sl, 2);
                            //调整可用余额
                            $extra = ['扣费项目' => $kf,'实际视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '退费算力' => $sl];

                            $user->tokens += $sl;
                            $user->save();
                            //记录日志
                            AccountLogLogic::add(
                                $user->id,
                                $typeID,
                                AccountLogEnum::INC,
                                $sl,
                                1,
                                $task->task_id,
                                $remark,
                                $extra

                            );
                        } else {
                            $sl = $points - $newpoints;
                            switch ($task->shanjian_type) {
                                case 1:
                                    $kf = '克隆数字人混剪剪辑视频预扣费补足费用补扣' ;
                                    break;
                                case 2:
                                    $kf = '真人口播混剪视频预扣费补足费用补扣' ;
                                    break;
                                case 3:
                                    $kf = '素材混剪视频预扣费补足费用补扣' ;
                                    break;
                                case 4:
                                    $kf = '新闻体混剪视频预扣费补足费用补扣' ;
                                    break;
                                default:
                                    $kf = '克隆数字人混剪剪辑视频预扣费补足费用补扣' ;
                                    break;
                            }
                            $sl =  round($sl, 2);
                            $extra = ['扣费项目' => $kf,'实际视频时长' =>$duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '补扣算力' => $sl];

                            $user->tokens -= $sl;
                            $user->save();
                            //记录日志
                            AccountLogLogic::add(
                                $user->id,
                                $typeID,
                                AccountLogEnum::DEC,
                                $sl,
                                1,
                                $task->task_id,
                                $remark,
                                $extra
                            );
                        }

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
        if (empty($keywords) || empty($number)) {
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


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::Shanjian();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::COPYWRITING_CREATE => ['shanjian_copywriting_create', AccountLogEnum::TOKENS_DEC_COZE_TEXT],
                self::SHANJIAN_VIDEO => ['human_video_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN],
                self::SHANJIAN_REALMAN_BROADCAST => ['shanjian_realman_broadcast', AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN],
                self::SHANJIAN_BROADCAST_MIXCUT => ['shanjian_broadcast_mixcut', AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN],
                self::SHANJIAN_NEWS_MIXCUT => ['shanjian_news_mixcut', AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN],
            };//计费
            $unit = TokenLogService::checkToken($userId, $tokenScene);// 添加辅助参数
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now'] = time();
            switch ($scene) {
                case self::COPYWRITING_CREATE:

                    $response = $response->text($request);
                    break;
                case self::SHANJIAN_VIDEO:
                    $response = $response->virtualmanBroadcast($request);
                    break;
                case self::SHANJIAN_REALMAN_BROADCAST:
                    $response = $response->realmanBroadcast($request);
                    break;
                case self::SHANJIAN_BROADCAST_MIXCUT:
                    $response = $response->mixcutBroadcast($request);
                    break;
                case self::SHANJIAN_NEWS_MIXCUT:
                    $response = $response->newsMixcut($request);
                    break;
                default:
            }//成功响应，需要扣费

            if (isset($response['code']) && $response['code'] == 10000) {
                $duration = $response['data']['data']['duration'] ?? 0;
                $points = $unit;
                if ($points > 0) {
                    $break = true;
                    $extra = [];
                    switch ($scene) {
                        case self::COPYWRITING_CREATE:
                            $break = false;
                            $extra = ['扣费项目' => '口播混剪视频文案生成', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SHANJIAN_VIDEO:
                            break;
                        case self::SHANJIAN_REALMAN_BROADCAST:
                            break;
                        case self::SHANJIAN_BROADCAST_MIXCUT:
                            break;
                        case self::SHANJIAN_NEWS_MIXCUT:
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
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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
            $tasks = ShanjianVideoTask::where(function ($q) use ($taskId) {
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

                    switch ($task->shanjian_type) {
                        case 1:
                            $duration =  (int)(mb_strlen($task->msg, 'UTF-8')/3);
                            $unit = TokenLogService::checkToken($task->user_id, 'human_video_shanjian', $duration);
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_VIDEO;
                            $response = self::requestUrl([
                                'styleId' => $task->clip_id,
                                'virtualmanId' => $task->anchor_id,
                                'title' => $task->title,
                                'content' => $task->msg,
                                'speakerId' => $task->voice_id,
                                'materials' => $task->material,
                                'introduceCard' => [
                                    'name' => $task->card_name,
                                    'description' => $task->card_introduced,
                                ],
                                'packRules' => [
                                    "backgroundMusic" => [
                                        "audioSwitch" => true,
                                        "audioUrl" => $task->music_url,
                                        "volume" => 0.3
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
                                    // 更新视频设置表的错误计数和状态
                                    self::updateVideoSettingStatus($task->video_setting_id, false);
                                }
                                $task->save();
                                return;
                            }
                            if (isset($response['data']['duration']) && $response['data']['duration'] > 0) {
                                $duration = $response['data']['duration'];
                                $points = round($duration * $unit, 2);
                                $extra = ['扣费项目' => '虚拟人混剪视频生成', '音视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                $task->video_token = $points;
                                User::userTokensChange($task->user_id, $points);
                                //记录日志
                                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN, $points, $task->task_id, $extra);
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
                                    // 更新视频设置表的错误计数和状态
                                    self::updateVideoSettingStatus($task->video_setting_id, false);
                                }
                                $task->save();
                                return;
                            }
                            break;

                        case 2:
                            $duration =  (int)(mb_strlen($task->msg, 'UTF-8')/3);
                            $unit = TokenLogService::checkToken($task->user_id, 'shanjian_realman_broadcast', $duration);
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_REALMAN_BROADCAST;
                            $response = self::requestUrl([
                                'styleId' => $task->clip_id,
                                'videoUrl' => $task->anchor_id,
                                'materials' => $task->material,
                                'introduceCard' => [
                                    'name' => $task->card_name,
                                    'description' => $task->card_introduced,
                                ],
                                'packRules' => [
                                    "backgroundMusic" => [
                                        "audioSwitch" => true,
                                        "audioUrl" => $task->music_url,
                                        "volume" => 0.3
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
                            if (isset($response['data']['duration']) && $response['data']['duration'] > 0) {
                                $duration = $response['data']['duration'];
                                $points = round($duration * $unit, 2);
                                $extra = ['扣费项目' => '真人口播混剪视频生成', '音视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                $task->video_token = $points;
                                User::userTokensChange($task->user_id, $points);
                                //记录日志
                                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN, $points, $task->task_id, $extra);
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
                            break;
                        case 3:
                            $duration =  (int)(mb_strlen($task->msg, 'UTF-8')/3);
                            $unit = TokenLogService::checkToken($task->user_id, 'shanjian_broadcast_mixcut', $duration);
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_BROADCAST_MIXCUT;
                            $response = self::requestUrl([
                                'styleId' => $task->clip_id,
                                'title' => $task->title,
                                'content' => $task->msg,
                                'speakerId' => $task->voice_id,
                                'materials' => $task->material,
                                'packRules' => [
                                    "backgroundMusic" => [
                                        "audioSwitch" => true,
                                        "audioUrl" => $task->music_url,
                                        "volume" => 0.3
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
                            if (isset($response['data']['duration']) && $response['data']['duration'] > 0) {
                                $duration = $response['data']['duration'];
                                $points = round($duration * $unit, 2);
                                $extra = ['扣费项目' => '素材混剪视频生成', '音视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                $task->video_token = $points;
                                User::userTokensChange($task->user_id, $points);
                                //记录日志
                                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN, $points, $task->task_id, $extra);
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
                            break;
                        case 4:
                            $duration =  (int)(mb_strlen($task->msg, 'UTF-8')/3);
                            $unit = TokenLogService::checkToken($task->user_id, 'shanjian_news_mixcut', $duration);
                            // 更新状态为视频合成中
                            $scene = self::SHANJIAN_NEWS_MIXCUT;
                            $title = str_replace('\\n', "\n", $task->title);;
                            $response = self::requestUrl([
                                'styleId' => $task->clip_id,
                                'title' => $title,
                                'materials' => $task->material,
                                'introduceCard' => [
                                    'name' => $task->card_name,
                                    'description' => $task->card_introduced,
                                ],
                                'packRules' => [
                                    "backgroundMusic" => [
                                        "audioSwitch" => true,
                                        "audioUrl" => $task->music_url,
                                        "volume" => 0.3
                                    ],
                                ],
                                'processRules' => [
                                    "watermarkShow" => false,
                                ]
                            ], $scene, $task->user_id, $task->task_id);
                            Log::channel('shanjian')->write('新闻合成视频' . json_encode($response));
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
                            if (isset($response['data']['duration']) && $response['data']['duration'] > 0) {
                                $duration = $response['data']['duration'];
                                $points = round($duration * $unit, 2);
                                $extra = ['扣费项目' => '新闻体混剪视频扣费音视频时长' . $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                                $task->video_token = $points;
                                User::userTokensChange($task->user_id, $points);
                                //记录日志
                                AccountLogLogic::recordUserTokensLog(true, $task->user_id, AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN, $points, $task->task_id, $extra);
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
                            break;
                    }
                } catch (\Exception $e) {
                    Log::channel('shanjian')->write('合成视频错误' . $e->getMessage());
                    $task->tries = $task->tries + 1;
                    if ($task->tries == 10) {
                        $task->status = 2;
                        // 更新视频设置表的错误计数和状态
                        self::updateVideoSettingStatus($task->video_setting_id, false);
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
                if (
                    !isset($response['code']) ||
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
                    $typeIDArray = [
                        '1' => AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN,
                        '2' => AccountLogEnum::TOKENS_DEC_REALMAN_BROADCAST_SHANJIAN,
                        '3' => AccountLogEnum::TOKENS_DEC_BROADCAST_MIXCUT_SHANJIAN,
                        '4' => AccountLogEnum::TOKENS_DEC_NEWS_MIXCUT_SHANJIAN
                    ];
                    $typeID = $typeIDArray[$task->shanjian_type] ?? AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_SHANJIAN;
                    $sceneArray = [
                        '1' => 'human_video_shanjian',
                        '2' => 'shanjian_realman_broadcast',
                        '3' => 'shanjian_broadcast_mixcut',
                        '4' => 'shanjian_news_mixcut'
                    ];
                    $scene = $sceneArray[$task->shanjian_type] ?? 'human_video_shanjian';

                    $remarkArray = [
                        '1' => '数字人口播混剪视频',
                        '2' => '真人口播混剪视频',
                        '3' => '素材混剪视预',
                        '4' => '新闻体混剪视频'
                    ];
                    $remark = $remarkArray[$task->shanjian_type] ?? '数字人口播混剪视频';
                    if (isset($response['data']['status'])) {
                        $data = $response['data'];
                        switch ($data['status']) {
                            case 'failed':
                                $item->video_token = 0;
                                $item->status = 2;
                                $item->remark = $data['errorMessage'] ?? '处理失败';
                                // 更新视频设置表的错误计数和状态
                                self::updateVideoSettingStatus($item->video_setting_id, false);
                                $userId = $item->user_id;
                                $taskId = $item->task_id;
                                $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                                //查询是否已返还
                                if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                                    $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                                    AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                                }

                                break;
                            case 'succeed':
                                $item->status = 3;
                                if (isset($data['result']['videoUrl'])) {
                                    $video_result_url = FileService::downloadFileBySource($data['result']['videoUrl'], 'video');
                                    $old = $data['result']['videoUrl'] ?? '没有';
                                    $urldata = [
                                        'old' => $old,
                                        'new' => $video_result_url
                                    ];
                                    Log::channel('shanjian')->write('获取视频链接' . json_encode($urldata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                    $item->video_result_url = $video_result_url;
                                }
                                // 更新视频设置表的成功计数和状态
                                self::updateVideoSettingStatus($item->video_setting_id, true);


                                $user = User::find($item->user_id);
                                $unit = ModelConfig::where('scene', $scene)->value('score', 0);
                                $duration = $data['result']['duration'] ?? 1;
                                $points = round($duration * $unit, 2);
                                $newpoints = $item->video_token;
                                $sl = $newpoints - $points;
                                $item->video_token = $points;
                                if ($sl > 0) {

                                    switch ($item->shanjian_type) {
                                        case 1:
                                            $kf = '克隆数字人混剪剪辑视频预扣费超额扣费退费' ;
                                            break;
                                        case 2:
                                            $kf = '真人口播混剪视预扣费超额扣费退费' ;
                                            break;
                                        case 3:
                                            $kf = '素材混剪视预扣费超额扣费退费' ;
                                            break;
                                        case 4:
                                            $kf = '新闻体混剪视频预扣费超额扣费退费' ;
                                            break;
                                        default:
                                            $kf = '克隆数字人混剪剪辑视频预扣费超额扣费退费';
                                            break;
                                    }
                                    $sl =  round($sl, 2);
                                    //调整可用余额
                                    $extra = ['扣费项目' => $kf,'实际视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '退费算力' => $sl];

                                    $user->tokens += $sl;
                                    $user->save();
                                    //记录日志
                                    AccountLogLogic::add(
                                        $user->id,
                                        $typeID,
                                        AccountLogEnum::INC,
                                        $sl,
                                        1,
                                        $item->task_id,
                                        $remark,
                                        $extra
                                    );
                                } else {
                                    $sl = $points - $newpoints;
                                    switch ($item->shanjian_type) {
                                        case 1:
                                            $kf = '克隆数字人混剪剪辑视频预扣费补足费用补扣' ;
                                            break;
                                        case 2:
                                            $kf = '真人口播混剪视频预扣费补足费用补扣';
                                            break;
                                        case 3:
                                            $kf = '素材混剪视频预扣费补足费用补扣' ;
                                            break;
                                        case 4:
                                            $kf = '新闻体混剪视频预扣费补足费用补扣';
                                            break;
                                        default:
                                            $kf = '克隆数字人混剪剪辑视频预扣费补足费用补扣';
                                            break;
                                    }
                                    $sl =  round($sl, 2);
                                    $extra = ['扣费项目' => $kf,'实际视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points, '之前扣除算力' => $newpoints, '补扣算力' => $sl];

                                    $user->tokens -= $sl;
                                    $user->save();
                                    //记录日志
                                    AccountLogLogic::add(
                                        $user->id,
                                        $typeID,
                                        AccountLogEnum::DEC,
                                        $sl,
                                        1,
                                        $item->task_id,
                                        $kf,
                                        $extra
                                    );
                                }

                        }
                        $item->update_time = time();
                        $item->save();
                    } elseif (isset($response['code']) && in_array($response['code'], [22901, 22902])) {
                        $item->status = 2;
                        $item->video_token = 0;
                        $item->remark = $data['message'] ?? '处理失败';
                        self::updateVideoSettingStatus($item->video_setting_id, false);

                        $userId = $item->user_id;
                        $taskId = $item->task_id;
                        $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
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
        } catch (\Exception|\think\db\exception\DataNotFoundException|\think\db\exception\ModelNotFoundException $e) { // Added specific DB exceptions
            self::setError($e->getMessage());
            Log::channel('shanjian')->error('Check 方法整体异常: ' . $e->getMessage());
            return false;
        }
    }


}
