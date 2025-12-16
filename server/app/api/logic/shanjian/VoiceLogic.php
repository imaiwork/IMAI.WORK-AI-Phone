<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\human\HumanVoice;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\exception\HttpResponseException;
use think\facade\Log;

class VoiceLogic extends ApiLogic
{
    const SHANJIAN_VOICE = 'shanjianVoice';

    public static function add($params)
    {
        try {
            $task_id = generate_unique_task_id();
            $params['demo_text'] = $params['demo_text'] ?? '你好，我是你的专属AI克隆声音';
            $scene = self::SHANJIAN_VOICE;
            $response = self::requestUrl([
                'audioUrl' => $params['audio_url'],
                'language' => $params['language'] ?? 'zh-CN',
                'demoText' => $params['demo_text'],
                'task_id' => $task_id,
                'user_id' => self::$uid,
                'now' => time(),
            ], $scene, self::$uid, $task_id);
            Log::channel('shanjian')->write('闪剪音色合成参数' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if((int)$response['code'] !== 10000)
            {
                self::setError($response['msg'] ?? '');
                return false;
            }
            $data = array(
                'user_id' => self::$uid,
                'model_version' => 8,
                'task_id' => $task_id,
                'status' => 0, //生成中
                'gender' => $params['gender'] ?? '',
                'name' => $params['name'] ?? date('YmdHi', time()),
                'type' => 0,
                'audio_url' => $params['audio_url'],
                'language' => $params['language'] ?? 'zh-CN',
                'demo_text' => $params['demo_text'],
                'result_task_id' => $response['data']['data']['taskId'] ?? '',
            );
            $result = HumanVoice::create($data);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function notify(array $payload)
    {
        try {
            $task_id = $payload['task_id'] ?? '';
            $user_id = $payload['user_id'] ?? 0;
            $result_task_id = $payload['taskId'] ?? '';
            $find = HumanVoice::where('task_id', $task_id)->where('status', 0)->where('model_version', 8)->where('user_id', $user_id)->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            if ($payload['status'] === 'succeed') {
                $find->status = 1; //已生成
                $find->voice_urls =  FileService::downloadFileBySource($payload['result']['demoAudioUrl'], 'audio');
                $find->voice_id = $payload['result']['speakerId'] ?? '';
                $find->result_task_id = $result_task_id;
            } else {
                $find->status = 2; //生成失败
                self::refundTokens($find->user_id,  $result_task_id, $find->task_id, 'human_voice_shanjian');
                $find->remark = $payload['errorMessage'] ?? '闪剪音色合成失败';
            }
            $find->update_time = time();

            $find->save();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            Log::channel('shanjian')->write('闪剪音色回调失败' . $e->__toString());
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Shanjian();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::SHANJIAN_VOICE => ['human_voice_shanjian', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {
            case self::SHANJIAN_VOICE:

                $response = $requestService->singleVoiceTrain($request);
                break;
            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;

            if ($points > 0) {

                $extra = [];
                switch ($scene) {
                    case self::SHANJIAN_VOICE:
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

    public static function refundTokens(int $userId, string $result_id, string $taskId, string $type): bool
    {

        try {
            [$typeIndex, $typeID] = match ($type) {
                'human_voice_shanjian' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN],
            };
            // 请求查询接口
            $requestParams = [
                'taskId' => $result_id,
                'task_id' => $taskId
            ];
            $response = \app\common\service\ToolsService::Shanjian()->status($requestParams);
            if (isset($response['code']) && $response['code'] == 10000) {
                return true;
            }
            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('闪剪退费失败' . $e->getMessage());
            return false;
        }
    }
}
