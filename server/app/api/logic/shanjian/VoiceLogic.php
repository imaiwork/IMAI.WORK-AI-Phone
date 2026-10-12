<?php

namespace app\api\logic\shanjian;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\human\HumanVoice;
use app\common\model\user\User;
use app\common\service\FileService;
use think\facade\Log;

class VoiceLogic extends ApiLogic
{
    const SHANJIAN_VOICE = 'shanjianVoice';

    public static function add($params)
    {
        try {
            $task_id = generate_unique_task_id();
            $params['demo_text'] = $params['demo_text'] ?? '你好，我是你的专属AI克隆声音';
            [$modelVersion, $upstreamModel, $tokenScene, $tokenCode] = self::resolveTrain($params);
            $response = self::requestUrl([
                'audioUrl' => $params['audio_url'],
                'language' => $params['language'] ?? 'zh-CN',
                'demoText' => $params['demo_text'],
                'task_id' => $task_id,
                'user_id' => self::$uid,
                'now' => time(),
                'model' => $upstreamModel,
            ], $tokenScene, $tokenCode, self::$uid, $task_id);
            Log::channel('shanjian')->write('闪剪音色合成参数' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if((int)$response['code'] !== 10000)
            {
                self::setError($response['msg'] ?? '');
                return false;
            }
            $data = array(
                'user_id' => self::$uid,
                'model_version' => $modelVersion,
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

    /**
     * @return array{0:int,1:string,2:string,3:int}
     */
    private static function resolveTrain(array $params): array
    {
        $modelVersion = (int)($params['model_version'] ?? 0);
        $model = strtolower(trim((string)($params['model'] ?? '')));
        if ($model === 's3' || $modelVersion === HumanVoice::MODEL_VERSION_SHANJIAN_S3) {
            return [
                HumanVoice::MODEL_VERSION_SHANJIAN_S3,
                's3',
                'human_voice_shanjian_s3',
                AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN_S3,
            ];
        }
        return [
            HumanVoice::MODEL_VERSION_SHANJIAN,
            'v3',
            'human_voice_shanjian',
            AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN,
        ];
    }

    private static function tokenSceneByVersion(int $version): string
    {
        return $version === HumanVoice::MODEL_VERSION_SHANJIAN_S3
            ? 'human_voice_shanjian_s3'
            : 'human_voice_shanjian';
    }

    private static function tokenCodeByVersion(int $version): int
    {
        return $version === HumanVoice::MODEL_VERSION_SHANJIAN_S3
            ? AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN_S3
            : AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN;
    }

    public static function notify(array $payload)
    {
        try {
            $task_id = $payload['task_id'] ?? '';
            $user_id = $payload['user_id'] ?? 0;
            $result_task_id = $payload['taskId'] ?? '';
            $find = HumanVoice::where('task_id', $task_id)
                ->where('status', 0)
                ->whereIn('model_version', HumanVoice::SHANJIAN_VOICE_MODEL_VERSIONS)
                ->where('user_id', $user_id)
                ->findOrEmpty();
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
                $resultTaskId = $result_task_id !== '' ? $result_task_id : (string)$find->result_task_id;
                self::refundTokens(
                    (int)$find->user_id,
                    $resultTaskId,
                    (string)$find->task_id,
                    self::tokenSceneByVersion((int)$find->model_version)
                );
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

    private static function requestUrl(array $request, string $tokenScene, int $tokenCode, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Shanjian();

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        $response = $requestService->singleVoiceTrain($request);
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;

            if ($points > 0) {

                $extra = ['算力单价' => $unit, '实际消耗算力' => $points];
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
        $typeID = match ($type) {
            'human_voice_shanjian_s3' => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN_S3,
            default => AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_SHANJIAN,
        };

        try {
            if ($result_id !== '') {
                $response = \app\common\service\ToolsService::Shanjian()->status([
                    'taskId' => $result_id,
                    'task_id' => $taskId,
                ]);
                $taskStatus = is_array($response['data'] ?? null)
                    ? (string)($response['data']['status'] ?? '')
                    : '';
                // 查单成功只表示中台接口通了，不是任务成功。任务仍是 succeed 才不退。
                if (isset($response['code']) && (int)$response['code'] === 10000 && $taskStatus === 'succeed') {
                    return true;
                }
            }
            self::refundOnce($userId, $taskId, $typeID);
            return true;
        } catch (\Throwable $e) {
            Log::channel('shanjian')->write('闪剪退费失败' . $e->getMessage());
            self::refundOnce($userId, $taskId, $typeID);
            return false;
        }
    }

    /** 按流水幂等退一次，统一走 AccountLogLogic::refundTaskOnce（带锁） */
    private static function refundOnce(int $userId, string $taskId, int $typeID): void
    {
        AccountLogLogic::refundTaskOnce($userId, $typeID, $taskId);
    }

    /**
     * 补退：失败已落库，但旧 refundTokens 把中台查单 10000 当成成功、没退给用户。
     * refundTaskOnce 按流水幂等，已退过的不会再退。
     */
    private static function refundMissedFailedVoices(): void
    {
        $voices = HumanVoice::whereIn('model_version', HumanVoice::SHANJIAN_VOICE_MODEL_VERSIONS)
            ->where('status', 2)
            ->where('update_time', '>=', strtotime('-7 days'))
            ->order('id', 'asc')
            ->limit(10)
            ->select();

        foreach ($voices as $voice) {
            try {
                $ok = AccountLogLogic::refundTaskOnce(
                    (int)$voice->user_id,
                    self::tokenCodeByVersion((int)$voice->model_version),
                    (string)$voice->task_id
                );
                if ($ok) {
                    Log::channel('shanjian')->write('音色失败补退费 task_id: ' . $voice->task_id);
                }
            } catch (\Throwable $e) {
                Log::channel('shanjian')->write('音色失败补退费异常 task_id: ' . ($voice->task_id ?? '') . ' err: ' . $e->getMessage());
            }
        }
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
            $voices = HumanVoice::whereIn('model_version', HumanVoice::SHANJIAN_VOICE_MODEL_VERSIONS)
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
                        self::refundOnce((int)$voice->user_id, (string)$voice->task_id, self::tokenCodeByVersion((int)$voice->model_version));
                        Log::channel('shanjian')->write('音色任务超时收口并退费 task_id: ' . $voice->task_id);
                    }
                } catch (\Throwable $e) {
                    Log::channel('shanjian')->write('音色check处理失败 task_id: ' . ($voice->task_id ?? '') . ' err: ' . $e->getMessage());
                }
            }

            self::refundMissedFailedVoices();

            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function musicLists(){
        self::$returnData = \app\common\service\SystemMusicService::publicList();
        return true;
    }
}
