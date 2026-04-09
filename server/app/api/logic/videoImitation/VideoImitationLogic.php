<?php

namespace app\api\logic\videoImitation;

use app\api\logic\auto\AutoDeviceSettingLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\model\aiPersona\AiPersona;
use app\common\service\ToolsService;
use app\api\logic\service\TokenLogService;
use think\facade\Log;

class VideoImitationLogic extends BaseLogic
{
    /**
     * 视频仿写解析与生成 (变更为异步下发)
     * @param string $url 视频分享链接
     * @param int $userId 当前用户ID
     * @param int $personaId 使用的AI人设ID
     * @param int $id   视频仿写任务主键ID
     * @return array|bool
     * @throws \Exception
     */
    public static function createOrUpdateTask(string $url, int $userId, int $personaId, int $id = 0)
    {
        // 1. 验证算力是否充足
        $unit = TokenLogService::checkToken($userId, 'video_copywriting_imitation');

        // 2. 检查或创建基本记录 (状态 0: 解析中)
        $saveData = [
            'user_id' => $userId,
            'prompt' => $url,
            'persona_id' => $personaId,
            'status' => 0
        ];

        if ($id > 0) {
            $task = VideoImitationTask::where('id', $id)->where('user_id', $userId)->find();
            if ($task) {
                // 重置相关信息，重新排队解析
                $saveData['original_text'] = '';
                $saveData['rewritten_text'] = '';
                $saveData['word_count'] = 0;
                $saveData['analysis_tags'] = '';
                $saveData['remarks'] = '';
                $task->save($saveData);
            } else {
                self::setError("任务不存在");
                return false;
            }
        } else {
            $task = VideoImitationTask::create($saveData);
        }

        // 3. 预扣费
        if ($unit > 0) {
            User::userTokensChange($userId, $unit);
            $extra = ['扣费项目' => '视频文案解析仿写', '算力单价' => $unit, '实际消耗算力' => $unit];
            AccountLogLogic::recordUserTokensLog(true, $userId, AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION, $unit, (string) $task->id, $extra);
        }

        // 4. 发送到本地异步接口进行解析（替代原有队列进程）
        $asyncUrl = request()->domain() . '/api/videoImitation.task/asyncParse';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $asyncUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'task_id' => $task->id,
            'url' => $url,
            'persona_id' => $personaId,
            'user_id' => $userId,
            'unit' => $unit
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1秒超时，不等待结果直接返回给前端
        curl_exec($ch);
        curl_close($ch);

        return $task->toArray();
    }

    /**
     * 处理异步解析任务的核心逻辑（替代原队列handle）
     */
    public static function processParseTask(array $data)
    {
        try {
            $taskId = $data['task_id'];
            $url = $data['url'];
            $personaId = $data['persona_id'];
            $userId = $data['user_id'];
            $unit = $data['unit'] ?? 0;

            $task = VideoImitationTask::where('id', $taskId)->find();
            if (!$task) {
                return;
            }

            // 获取人设信息
            $personaName = '';
            $quickDesc = '';
            $persona = AiPersona::where('id', $personaId)->find();
            if ($persona) {
                $personaName = $persona['persona_name'] ?? '';
                $quickDesc = $persona['quick_desc'] ?? '';
            }

            // 封装发给大模型的提示词
            $promptContent = "链接：{$url}\nIP人设：\n人设名称：{$personaName}\n人设简介：{$quickDesc}\n";

            $requestParams = [
                'input' => [
                    'prompt' => $promptContent
                ]
            ];

            // 调用远程平台接口
            $response = ToolsService::Copywriting()->videoImitation($requestParams);

            if (isset($response['code']) && $response['code'] == 10000) {
                $resData = $response['data'] ?? [];
                $messageJson = $resData['message'] ?? '';
                $parsedMsg = json_decode($messageJson, true);

                if ($parsedMsg) {
                    $titlecoze['sn'] = 8;
                    $titlecoze['number'] = 1;
                    $titlecoze['length'] = 10;
                    $titlecoze['keywords'] = $parsedMsg['original_text'];
                    $titleResult = AutoDeviceSettingLogic::copywriting ($titlecoze, $userId,4);
                    $taskTitle = $titleResult['content']['0'] ?? '';
                    $task->title = $taskTitle ?: mb_substr($parsedMsg['original_text'], 0, 10, 'utf-8');
                    
                    $task->platform_task_id = $resData['task_id'] ?? ($parsedMsg['task_id'] ?? '');
                    $task->original_text = $parsedMsg['original_text'] ?? '';
                    $task->rewritten_text = $parsedMsg['rewritten_text'] ?? '';
                    $task->word_count = $parsedMsg['word_count'] ?? 0;
                    $task->analysis_tags = json_encode($parsedMsg['analysis_tags'] ?? [], JSON_UNESCAPED_UNICODE);
                    $task->compliance_status = $parsedMsg['compliance_status'] ?? '';
                    $task->persona_role = $parsedMsg['persona_role'] ?? '';
                    $task->persona_tone = $parsedMsg['persona_tone'] ?? '';

                    // 标记为待确认文案
                    $task->status = 1;
                    $task->save();
                    return;
                }
            }

            $task->refresh();
            if ($task->status == 0) {
                // 解析失败（未正常返回 content）
                $task->status = 4; // 标记为任务失败
                $task->remarks = '文案解析失败或第三方格式解析错误';
                $task->save();

                // 退回算力
                if ($unit > 0) {
                    AccountLogLogic::recordUserTokensLog(false, $userId, AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION, $unit, (string) $taskId, ['扣费项目' => '异步文案解析失败算力退回']);
                }
            }
        } catch (\Exception $e) {
            Log::channel('shanjian')->write("VideoImitationParseTask 执行抛出异常: " . $e->getMessage(), 'error');

            // 异常兜底：失败处理
            $task = VideoImitationTask::where('id', $data['task_id'] ?? 0)->find();
            if ($task && $task->status == 0) {
                $task->status = 4;
                $task->remarks = $e->getMessage() ?: '文案解析执行异常';
                $task->save();
                if (($data['unit'] ?? 0) > 0) {
                    AccountLogLogic::recordUserTokensLog(false, $data['user_id'], AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION, $data['unit'], (string) $task->id, ['扣费项目' => '异步文案解析异常退回']);
                }
            }
        }
    }
}
