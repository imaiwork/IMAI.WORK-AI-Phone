<?php

namespace app\api\logic\sora;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\ModelConfig;
use app\common\model\sora\SoraAnchor;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use think\facade\Log;

class SoraAnchorLogic extends ApiLogic
{
    const SORA_AVATAR = 'soraAvatar';

    public static function add(array $params)
    {
        $name   = $params['name'] ?? '角色' . date('YmdHi');
        $anchor = SoraAnchor::where('name', $name)->findOrEmpty();
        if (!$anchor->isEmpty()) {
            self::setError('已存在同名的角色');
            return false;
        }
        try {
            $task_id               = generate_unique_task_id();
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
            $scene    = self::SORA_AVATAR;
            $response = self::requestUrl($request, $scene, self::$uid, $task_id);
            Log::channel('sora')->write('Sora形象' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (isset($response['code']) && $response['code'] == 10000) {
                $data  = [
                    'user_id'          => self::$uid,
                    'task_id'          => $task_id,
                    'sora_task_id'     => $response['data']['id'] ?? '',
                    'pic'              => $params['pic'] ?? '',
                    'name'             => $name,
                    'status'           => 0,
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
            self::SORA_AVATAR => ['human_avatar_sora', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA],
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
        $model = SoraAnchor::where('task_id', $data['task_id'])->where('user_id', $data['user_id'])->where('status', 0)->select()
                           ->each(function ($item) use ($data) {
                               if (in_array($data['state'], ['error', 'succeeded'])) {
                                   $item->status = ($data['state'] == 'succeeded') ? 1 : 2;
                                   // TODO 失败退费
                                   if ($item->status == 2) {
                                       $userId = $data['user_id'];
                                       $taskId = $data['task_id'];
                                       $typeID = AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_SORA;
                                       $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
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
                                   }
                               }
                               $item->save();
                           });
        return true;
    }

    public static function checkStatus()
    {

        $tasks = SoraAnchor::where('status', '=', 0)->where('create_time', '<', time() - 600)->select()->toArray();
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
                if (isset($result['data']['data']['characters'][0]['id'])) {
                    $unit   = ModelConfig::where('id', '=', $typeID)->value('score', 0);
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

    private static function formatMessage($message)
    {
        if (str_contains($message, 'audio')) {
            $message = '视频文件无音频';
        }
        if (str_contains($message, 'realistic human') || str_contains($message, 'human')) {
            $message = '视频中存在真人，法律受限无法通过上传视频创建真人角色';
        }
        return $message;
    }
}


