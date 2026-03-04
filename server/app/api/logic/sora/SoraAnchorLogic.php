<?php

namespace app\api\logic\sora;

use app\api\logic\ApiLogic;
use app\api\logic\HdLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\sora\SoraAnchor;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

class SoraAnchorLogic extends ApiLogic
{
    const SORA_AVATAR = 'soraAvatar';
    const SORA_VIDEO_CREATE = 'sora_video_create';
    const SORA_DRAW_AVATAR = 'sora_draw_avatar'; //角色转绘

    public static function add(array $params)
    {
        $name   = $params['name'] ?? '角色' . date('YmdHi');
        $anchor = SoraAnchor::where('name', $name)->where('user_id', self::$uid)->findOrEmpty();
        if (!$anchor->isEmpty()) {
            self::setError('已存在同名的角色');
            return false;
        }
        try {
            $task_id = generate_unique_task_id();

            if (empty($params['upload_type'])) {
                throw new \Exception('请选择上传类型');
            }

            //图片转绘创建真人角色
            if ($params['upload_type'] == 1) {
                if (empty($params['image_url'])) {
                    throw new \Exception('请上传图片');
                }
                $requestDraw   = [
                    'height'    => '1328',
                    'width'     => '1328',
                    'image_url' => $params['image_url'],
                    'prompt'    => '将这张图片，尽可能保真的转成线稿，面部五官需要做到素描100%完整还原，保留面部特征但不需要加入素描的阴影',
                    'model'     => 3
                ];
                $scene         = self::SORA_DRAW_AVATAR;
                $imageResponse = self::requestUrl($requestDraw, $scene, self::$uid, $task_id);
                if ($imageResponse['code'] == 10000 && isset($imageResponse['data']['image_urls'])) {
                    $imageUrl = $imageResponse['data']['image_urls'];
                    $imageUrl = HdLogic::saveLogReturn(4, '', $params, $task_id, [''], 1, 3, $imageUrl);
                    //生成转绘角色视频
                    $requestVideo  = [
                        'model'        => 'sora-2',
                        'prompt'       => '素材中的角色正面站在一片空地中，脸部旋转特写1秒，上半身旋转特写1秒，全身旋转特写1秒，在视频第1秒用最符合角色的声音说出"大家好，我是'.$name.'，欢迎大家来创建视频"',
                        'aspect_ratio' => '9:16',
                        'duration'     => 10,
                        'image_urls'   => [FileService::getFileUrl($imageUrl)]
                    ];
                    $scene         = self::SORA_VIDEO_CREATE;
                    $videoResponse = self::requestUrl($requestVideo, $scene, self::$uid, $task_id);
                    if ($videoResponse['code'] == 10000 && isset($videoResponse['data']['id'])) {
                        $data  = [
                            'user_id'            => self::$uid,
                            'task_id'            => $task_id,
                            'sora_task_id'       => '',
                            'sora_video_task_id' => $videoResponse['data']['id'],
                            'upload_type'        => $params['upload_type'],
                            'pic'                => $params['pic'] ?? '',
                            'name'               => $name,
                            'status'             => 0,
                            'image_url'          => FileService::setFileUrl($params['image_url']),
                            'draw_image_url'     => $imageUrl,
                            'anchor_url'         => '',
                            'anchor_url_start'   => 0,
                            'anchor_url_end'     => 3,
                            'create_time'        => time(),
                        ];
                        $model = new SoraAnchor();
                        $model->save($data);
                        $data['id']       = $model->id;
                        self::$returnData = $data;
                        return true;
                    } else {
                        $msg = $videoResponse['message'] ?? '转绘视频创建失败';
                        throw new \Exception($msg);
                    }
                } else {
                    $msg = $videoResponse['message'] ?? '图片转绘失败';
                    throw new \Exception($msg);
                }
            }
            //视频创建角色
            if ($params['upload_type'] == 2) {
                $request['url']        = $params['anchor_url'];
                $request['timestamps'] = $params['start'] . ',' . $params['end'];
                if (empty($params['pic'])) {
                    throw new \Exception('请上传封面图');
                }
                if (empty($params['anchor_url'])) {
                    throw new \Exception('请上传视频');
                }
                if (!isset($params['start']) || empty($params['end'])) {
                    throw new \Exception('请截取创建角色的视频片段');
                }
            }
            $scene    = self::SORA_AVATAR;
            $response = self::requestUrl($request, $scene, self::$uid, $task_id);
            Log::channel('sora')->write('Sora形象' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (isset($response['code']) && $response['code'] == 10000) {
                $data  = [
                    'user_id'          => self::$uid,
                    'task_id'          => $task_id,
                    'sora_task_id'     => $response['data']['id'] ?? '',
                    'upload_type'      => $params['upload_type'],
                    'pic'              => $params['pic'] ?? '',
                    'name'             => $name,
                    'status'           => 0,
                    'image_url'        => $params['image_url'] ?? '',
                    'anchor_url'       => $params['anchor_url'] ?? '',
                    'anchor_url_start' => $params['start'] ?? 0,
                    'anchor_url_end'   => $params['end'] ?? 0,
                    'create_time'      => time(),
                ];
                $model = new SoraAnchor();
                $model->save($data);
                $data['id']       = $model->id;
                self::$returnData = $data;
                return true;
            } else {
                $msg = $response['message'] ?? '创建失败';
                throw new \Exception($msg);
            }
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            $name = $params['name'];
            $id   = $params['id'];
            if (empty($name)) {
                throw new \Exception('角色不存在');
            }

            $anchor = SoraAnchor::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($anchor->isEmpty()) {
                throw new \Exception('角色不存在');
            }

            $same = SoraAnchor::where('name', $name)->where('user_id', self::$uid)->findOrEmpty();
            if (!$same->isEmpty()) {
                self::setError('已存在同名角色，请修改角色名称');
                return false;
            }
            $anchor->name = $name;
            $anchor->save();
            self::$returnData = $anchor->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                SoraAnchor::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                SoraAnchor::whereIn('id', $id)->where('user_id', self::$uid)
                          ->select()->delete();
            }
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        $model = SoraAnchor::where('id', $params['id'])
                           ->where('user_id', self::$uid)
                           ->findOrEmpty();
        if ($model->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        self::$returnData = $model->toArray();
        return true;
    }


    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::sora();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::SORA_AVATAR       => ['human_avatar_sora', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA],
            self::SORA_DRAW_AVATAR  => ['sora_draw_avatar', AccountLogEnum::TOKENS_DEC_SORA_DRAW_AVATAR],
            self::SORA_VIDEO_CREATE => ['sora_video_create', AccountLogEnum::TOKENS_DEC_SORA_VIDEO],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now']     = time();
        switch ($scene) {
            case self::SORA_AVATAR:
                $response = $requestService->anchorCreate($request);
                break;
            case self::SORA_DRAW_AVATAR:
                $response = $requestService->drawAnchor($request);
                break;
            case self::SORA_VIDEO_CREATE:
                $response = $requestService->drawAnchorCreate($request);
                break;
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {
            $points = $unit;
            if ($points > 0) {
                $extra = [];
                switch ($scene) {
                    case self::SORA_AVATAR:
                        $extra = ['算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    case self::SORA_DRAW_AVATAR:
                        $extra = ['扣费项目' => '一句话生成视频角色转绘', '算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    case self::SORA_VIDEO_CREATE:
                        $extra = ['扣费项目' => '一句话生成视频创建角色转绘视频', '算力单价' => $unit, '实际消耗算力' => $points];
                        break;
                    default:
                }

                //token扣除
                User::userTokensChange($userId, $points);
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response;
    }

    public static function updateAnchor(array $data): bool
    {
        $typeID       = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
        $data['unit'] = ModelConfig::where('id', '=', $typeID)->value('score', 0);
        $model        = SoraAnchor::where('task_id', $data['task_id'])->where('user_id', $data['user_id'])->where('status', 0)->select()
                                  ->each(function ($item) use ($data) {
                                      if (in_array($data['state'], ['error', 'succeeded'])) {
                                          $item->status = ($data['state'] == 'succeeded') ? 1 : 2;
                                          // TODO 失败退费
                                          if ($item->status == 2) {
                                              $userId = $data['user_id'];
                                              $taskId = $data['task_id'];
                                              $typeID = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
                                              $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)
                                                                     ->count();
                                              //查询是否已返还
                                              if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)
                                                               ->count() < $count) {
                                                  $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)
                                                                         ->value('change_amount') ?? 0;
                                                  AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                                              }
                                              $item->remark = self::formatMessage($data['message']);
                                          } else {
                                              $item->anchor_id = $data['data']['characters'][0]['id'];
                                              $item->token     = $data['unit'];
                                          }
                                      }
                                      $item->save();
                                  });
        return true;
    }

    public static function videoNotify(array $data)
    {
        if (empty($data['task_id'])) {
            self::setError('缺少任务ID');
            return false;
        }
        // 先初步查找任务，减少不必要的事务锁定
        $task = SoraAnchor::where('task_id', $data['task_id'])
                          ->where('status', '=', 0)
                          ->where('sora_task_id', '=', '')
                          ->find();
        if (!$task) {
            // 任务不存在
            Log::channel('sora')->info('Notify: 任务不存在，task_id: ' . $data['task_id']);
            return true;
        }
        Db::startTrans();
        try {
            if (isset($data['state'])) {
                $userId = $task->user_id;
                $taskId = $task->task_id;
                switch ($data['state']) {
                    case 'error':
                        $typeID       = AccountLogEnum::TOKENS_DEC_SORA_VIDEO;
                        $task->status = 2;
                        $task->remark = $data['message'] ?? '角色转绘视频处理失败';
                        if (str_contains($task->remark, 'containing photorealistic people')) {
                            $task->remark = '角色转绘视频：目前不支持上传包含真人的图像';
                        }
                        if (str_contains($task->remark, 'system error')) {
                            $task->remark = '角色转绘视频：系统错误生成失败，请重新生成';
                        }
                        if (str_contains($task->remark, 'third-party')) {
                            $task->remark = '角色转绘视频：此内容违反第三方肖像权、内容相似性的防护规定，请重新生成';
                        }
                        Log::channel('sora')->write($task->remark);
                        $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                            $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                        }
                        break;
                    case 'succeeded':
                        $typeID = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
                        if (isset($data['data']['videos'])) {
                            $video_result_url = FileService::downloadFileBySource($data['data']['videos'][0]['url'], 'video');
                            Log::channel('sora')->write('获取角色转绘视频链接' . $video_result_url);
                            $task->anchor_url = $video_result_url;
                        }
                        //发起角色创建请求
                        $request['from_task']  = $task->sora_video_task_id;
                        $request['timestamps'] = '0,3';
                        $scene                 = self::SORA_AVATAR;
                        $response              = self::requestUrl($request, $scene, $userId, $task['task_id']);
                        Log::channel('sora')->write('Sora转绘角色请求结果' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        if (isset($response['code']) && $response['code'] == 10000) {
                            $task->sora_task_id = $response['data']['id'] ?? '';
                        } else {
                            //失败返还角色创建算力
                            $task->status = 2;
                            $task->remark = $response['message'] ?? '创建失败';
                            $count        = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                            //查询是否已返还
                            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
                            }
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
            Log::channel('sora')->error('Notify 处理失败, task_id: ' . $data['task_id'] . ', Error: ' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function checkStatus()
    {

        $tasks = SoraAnchor::where('status', '=', 0)
                           ->where('create_time', '<', time() - 600)
                           ->where('anchor_url', '!=', '')
                           ->select()
                           ->toArray();
        Log::channel('sora')->write('超过10分钟无回调的角色创建任务' . json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $result   = [];
        $response = \app\common\service\ToolsService::sora();
        $typeID   = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
        foreach ($tasks as $task) {
            if (!empty($task['sora_task_id'])) {
                $result = $response->status(['task_id' => $task['sora_task_id'], 'scene' => 'anchor']);
            }
            Log::channel('sora')->write('超过10分钟无回调的角色创建任务处理' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 超过10分钟无回调的任务处理
            if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                // 角色创建中跳过处理
                if (isset($result['data']['state']) && $result['data']['state'] == 'pending') {
                    continue;
                }
                if (isset($result['data']['data']['characters'][0]['id'])) {
                    if ($task['upload_type'] == 1) {
                        $unit1 = ModelConfig::where('code', '=', $typeID)->value('score', 0);
                        $unit2 = ModelConfig::where('code', '=', AccountLogEnum::TOKENS_DEC_SORA_DRAW_AVATAR)->value('score', 0);
                        $unit3 = ModelConfig::where('code', '=', AccountLogEnum::TOKENS_DEC_SORA_VIDEO)->value('score', 0);
                        $unit  = $unit1 + $unit2 + $unit3;
                    } else {
                        $unit = ModelConfig::where('code', '=', $typeID)->value('score', 0);
                    }
                    $update = [
                        'anchor_id'   => $result['data']['data']['characters'][0]['id'],
                        'token'       => (int)$unit,
                        'status'      => 1,
                        'update_time' => time()
                    ];
                    SoraAnchor::where('id', $task['id'])->update($update);
                    continue;
                } else {
                    $errorUpdate = [
                        'status'      => 2,
                        'remark'      => isset($result['data']['message']) ? self::formatMessage($result['data']['message']) : '角色创建超时，创建失败',
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
            $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }
            SoraAnchor::where('id', $task['id'])->update($errorUpdate);
        }

        return true;
    }

    public static function checkVideoStatus()
    {

        $tasks = SoraAnchor::where('status', '=', 0)
                           ->where('upload_type', '=', 1)
                           ->where('anchor_url', '=', '')
                           ->where('create_time', '<', time() - 1200)
                           ->order('id', 'asc')
                           ->limit(1)
                           ->select()
                           ->toArray();
        Log::channel('sora')->write('角色转绘视频状态检测任务' . json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $result   = [];
        $response = \app\common\service\ToolsService::sora();
        $typeID   = AccountLogEnum::TOKENS_DEC_SORA_VIDEO;
        foreach ($tasks as $task) {
            if (!empty($task['sora_video_task_id'])) {
                $result = $response->status(['task_id' => $task['sora_video_task_id']]);
            }
            Log::channel('sora')->write('超过20分钟无回调的角色转绘视频状态检测任务处理' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            // 超过20分钟无回调的任务处理
            if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                if (isset($result['data']['videos'])) {
                    $video_result_url = FileService::downloadFileBySource($result['data']['videos'][0]['url'], 'video');
                    Log::channel('sora')->write('定时任务查询获取角色转绘视频链接:' . $video_result_url);
                    $update = [
                        'anchor_url'  => $video_result_url,
                        'update_time' => time()
                    ];
                    SoraAnchor::where('id', $task['id'])->update($update);
                    //发起角色创建请求
                    $request['from_task']  = $task['sora_video_task_id'];
                    $request['timestamps'] = '0,3';
                    $scene                 = self::SORA_AVATAR;
                    $response              = self::requestUrl($request, $scene, $task['user_id'], $task['task_id']);
                    Log::channel('sora')->write('Sora角色转绘创建' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    if (isset($response['code']) && $response['code'] == 10000) {
                        $update = [
                            'sora_task_id' => $response['data']['id'] ?? '',
                            'update_time'  => time(),
                        ];
                        SoraAnchor::where('id', $task['id'])->update($update);
                        continue;
                    } else {
                        //失败返还角色创建算力
                        $errorUpdate = [
                            'status'      => 2,
                            'remark'      => $response['message'] ?? '创建失败',
                            'update_time' => time()
                        ];
                        $typeID      = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
                    }

                } else {
                    //失败返还视频创建算力
                    $errorUpdate = [
                        'status'      => 2,
                        'remark'      => $result['data']['message'] ?? '请求超时',
                        'update_time' => time()
                    ];
                }
            } else {
                //失败返还视频创建算力
                $errorUpdate = [
                    'status'      => 2,
                    'remark'      => '请求超时',
                    'update_time' => time()
                ];
            }

            //失败返还算力
            $userId = $task['user_id'];
            $taskId = $task['task_id'];
            $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }
            SoraAnchor::where('id', $task['id'])->update($errorUpdate);
        }

        return true;
    }

    private static function formatMessage($message)
    {
        if (str_contains($message, 'audio')) {
            $message = '视频文件无音频';
        }
        if (str_contains($message, 'realistic human') || str_contains($message, 'human')) {
            $message = '视频中存在真人，法律受限无法通过上传视频创建真人角色';
        }
        if (str_contains($message, 'task timeout')) {
            $message = '任务超时，请稍后再试';
        }
        if (str_contains($message, 'violate our policies')) {
            $message = '生成的角色可能违反了我们的政策，请更换图片后再试';
        }
        if (str_contains($message, 'the criteria') || str_contains($message, 'Failed to generate')) {
            $message = '该图片不符合创建角色的标准，请更换图片后再试';
        }
        return $message;
    }
}


