<?php

namespace app\common\Jobs;

use app\common\model\aiPersona\AiPersona;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\ToolsService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use think\facade\Log;

/**
 * 视频复刻异步文案解析任务
 */
class VideoImitationParseJob
{
    public function handle($job, $data)
    {
        try {
            $taskId = $data['task_id'];
            $url = $data['url'];
            $personaId = $data['persona_id'];
            $userId = $data['user_id'];
            $unit = $data['unit'] ?? 0;

            $task = VideoImitationTask::where('id', $taskId)->find();
            if (!$task) {
                $job->delete();
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

                    // 任务成功，删除消息
                    $job->delete();
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

            $job->delete();
        } catch (\Exception $e) {
            Log::write("VideoImitationParseJob 执行抛出异常: " . $e->getMessage(), 'error');

            // 失败3次以上直接抛弃并执行失败逻辑
            if ($job->attempts() > 3) {
                $task = VideoImitationTask::where('id', $data['task_id'] ?? 0)->find();
                if ($task && $task->status == 0) {
                    $task->status = 4;
                    $task->remarks = '文案解析失败(队列重试超限)';
                    $task->save();
                    if (($data['unit'] ?? 0) > 0) {
                        AccountLogLogic::recordUserTokensLog(false, $data['user_id'], AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION, $data['unit'], (string) $task->id, ['扣费项目' => '异步文案解析异常退回']);
                    }
                }
                $job->delete();
            } else {
                // 延迟 3 秒后重试
                $job->release(3);
            }
        }
    }
}
