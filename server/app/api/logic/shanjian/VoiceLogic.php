<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\human\HumanVoice;
use app\common\model\user\User;
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

            // 多 key 池：独立音色 status=1 成功终态，实时上报资产绑定
            if ((int)$find->status === 1) {
                \app\common\service\ShanjianAssetReportService::reportVoiceRow($find);
            }
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
            self::refundOnce($userId, $taskId, $typeID);

            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('闪剪退费失败' . $e->getMessage());
            return false;
        }
    }

    /** 按流水幂等退一次，统一走 AccountLogLogic::refundTaskOnce（带锁） */
    private static function refundOnce(int $userId, string $taskId, int $typeID): void
    {
        AccountLogLogic::refundTaskOnce($userId, $typeID, $taskId);
    }

    /**
     * 音色任务兜底（挂在 shanjian_video_task cron 里）
     *
     * 回调是主链路，这里只补两件事：
     * 1. 提交超过 30 分钟仍 status=0 的，查中台状态，拿到终态就走 notify() 同一套落库/退费/资产上报；
     * 2. 超过 24 小时中台仍不给终态的，直接标失败并按流水幂等退费。
     *    不能复用 refundTokens()：它只在中台明确回失败码时才退，对「永远 processing」的任务一分不退。
     * 僵尸行在第 2 步就被收口，不会像成片那样长期占死 limit 名额，所以这里不需要年龄上界。
     * 中台 shanjian_status 场景为 free 计费，轮询不消耗算力。
     */
    public static function check(): bool
    {
        try {
            $voices = HumanVoice::where('model_version', 8)
                ->where('status', 0)
                ->where('create_time', '<=', strtotime('-30 minutes'))
                ->order('id', 'asc')
                ->limit(5)
                ->select();

            foreach ($voices as $voice) {
                try {
                    $notifyData = null;
                    if ((string)$voice->result_task_id !== '') {
                        $response = \app\common\service\ToolsService::Shanjian()->status([
                            'taskId'  => $voice->result_task_id,
                            'task_id' => $voice->task_id,
                        ]);
                        Log::channel('shanjian')->write(
                            '音色check查询状态 task_id: ' . $voice->task_id
                            . ' 响应: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        );

                        // 失败码沿用 ShanjianVideoTaskLogic::check() 的 22901/22902；
                        // 终态只认 succeed / failed 两个词，其它一律当在途，宁可等 24 小时收口也不误判成失败去退费
                        if (isset($response['code']) && in_array((int)$response['code'], [22901, 22902], true)) {
                            $notifyData = ['status' => 'failed', 'errorMessage' => $response['message'] ?? '处理失败'];
                        } elseif (
                            isset($response['code'])
                            && !empty($response['data']) && is_array($response['data'])
                            && in_array($response['data']['status'] ?? '', ['succeed', 'failed'], true)
                        ) {
                            $notifyData = $response['data'];
                        }
                    }

                    if ($notifyData !== null) {
                        $notifyData['task_id'] = $voice->task_id;
                        $notifyData['user_id'] = $voice->user_id;
                        $notifyData['taskId'] = $notifyData['taskId'] ?? $voice->result_task_id;
                        if (!self::notify($notifyData)) {
                            Log::channel('shanjian')->write('音色check补偿notify失败 task_id: ' . $voice->task_id . ' err: ' . self::getError());
                        }
                        continue;
                    }

                    // create_time 属性读出来是格式化日期串，(int) 会得到 2026；要拿原始时间戳用 getData
                    if ((int)$voice->getData('create_time') > strtotime('-1440 minutes')) {
                        continue;
                    }

                    // 24 小时收口：CAS 只改仍为 0 的行，避免与同时到达的回调互相覆盖
                    $closed = HumanVoice::where('id', $voice->id)->where('status', 0)->update([
                        'status'      => 2,
                        'remark'      => '超过24小时未返回结果，已自动标记失败',
                        'update_time' => time(),
                    ]);
                    if ($closed) {
                        self::refundOnce((int)$voice->user_id, (string)$voice->task_id, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN);
                        Log::channel('shanjian')->write('音色任务超时收口并退费 task_id: ' . $voice->task_id);
                    }
                } catch (\Throwable $e) {
                    Log::channel('shanjian')->write('音色check处理失败 task_id: ' . ($voice->task_id ?? '') . ' err: ' . $e->getMessage());
                }
            }

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function musicLists(){
        for ($i = 1; $i <= 20; $i++) {
            $music[] = config('app.app_host') . '/static/audio/music/' . $i . '.mp3';
        }
        self::$returnData = $music;
        return true;
    }
}
