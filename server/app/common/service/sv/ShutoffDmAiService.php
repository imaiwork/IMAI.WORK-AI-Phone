<?php

namespace app\common\service\sv;

use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\model\knowledge\Knowledge;
use app\common\model\knowledge\KnowledgeBind;
use app\common\service\aiPersona\AgentConfigService;
use app\common\service\chat\ChatModelsService;
use think\facade\Log;

/**
 * 截流私信：按 setting.agent_id 根据评论原文生成可直发文案
 */
class ShutoffDmAiService
{
    /** @var array{generate?:callable} */
    public static array $testHooks = [];

    /**
     * @param array{
     *   userId:int,
     *   robotId:int,
     *   commentText:string,
     *   deviceCode?:string,
     *   taskId?:string|int,
     *   noteTitle?:string,
     *   authorName?:string
     * } $params
     * @return array{ok:bool,reply:string,error:string,error_code:int}
     */
    public static function generate(array $params): array
    {
        if (isset(self::$testHooks['generate']) && is_callable(self::$testHooks['generate'])) {
            return (self::$testHooks['generate'])($params);
        }

        $userId = (int)($params['userId'] ?? 0);
        $robotId = (int)($params['robotId'] ?? 0);
        $commentText = trim((string)($params['commentText'] ?? ''));
        $deviceCode = (string)($params['deviceCode'] ?? '');
        $taskId = (string)($params['taskId'] ?? '');

        if ($userId <= 0) {
            return self::fail('用户无效');
        }
        if ($robotId <= 0) {
            return self::fail('未绑定截流私信智能体');
        }
        if ($commentText === '') {
            return self::fail('评论原文为空');
        }

        $usability = AgentConfigService::resolveAgentUsability($userId, $robotId);
        if (!(bool)($usability['usable'] ?? false)) {
            $reason = trim((string)($usability['status_text'] ?? ''));
            return self::fail($reason !== '' ? $reason : '智能体不可用');
        }

        $robot = KbRobot::where('id', $robotId)->findOrEmpty();
        if ($robot->isEmpty()) {
            return self::fail('智能体不存在');
        }
        //查询原来的模型名
        $robot->model = \app\common\model\chat\ModelsCost::where('id', $robot->model_sub_id)->limit(1)->value('alias');
        try {
            ChatModelsService::assertChatModelUsable(
                (int)$robot->model_id,
                (int)$robot->model_sub_id,
                $userId,
                (string)($robot->model ?? '')
            );
        } catch (\Throwable $e) {
            self::writeLog('截流私信对话模型不可用:' . $e->getMessage());
            return self::fail('对话模型不可用：' . $e->getMessage());
        }

        $userContent = self::buildUserContent(
            $commentText,
            (string)($params['authorName'] ?? ''),
            (string)($params['noteTitle'] ?? '')
        );
        $messages = [
            [
                'role' => 'system',
                'content' => ShutoffDmTouchHelper::buildDmSystemPrompt((string)($robot->roles_prompt ?? '')),
            ],
            [
                'role' => 'user',
                'content' => $userContent,
            ],
        ];

        $knowledge = self::resolveKnowledge($robot, $userId);
        $chatTaskId = self::buildChatTaskId($taskId, $commentText);
        $request = [
            'messages' => $messages,
            'message' => $userContent,
            'model' => $robot->model ?: 'deepseek',
            'stream' => false,
            'user_id' => $userId,
            'task_id' => $chatTaskId,
            'knowledge' => $knowledge,
        ];
        self::writeLog('截流私信调模请求:' . json_encode($request, JSON_UNESCAPED_UNICODE));
        try {
            $result = XhsPrivateMessageAiChatService::complete([
                'user_id' => $userId,
                'device_code' => $deviceCode,
                'task_id' => $chatTaskId,
                'request' => $request,
                'robot' => $robot->toArray(),
            ]);
            self::writeLog('截流私信调模响应:' . json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $code = (int)$e->getCode();
            self::writeLog('截流私信调模异常:' . $e->getMessage());
            return self::fail($e->getMessage() !== '' ? $e->getMessage() : 'AI私信生成失败', $code);
        }

        $errorCode = (int)($result['error_code'] ?? 0);
        $reply = trim((string)($result['reply'] ?? ''));
        if ($errorCode === 4059) {
            self::writeLog('截流私信算力不足:' . (string)($result['error'] ?? ''));
            return self::fail((string)($result['error'] ?? '算力不足'), 4059);
        }
        if (!ShutoffDmTouchHelper::isUsableReply($reply)) {
            $error = trim((string)($result['error'] ?? ''));
            self::writeLog('截流私信生成失败或命中接管兜底句:' . ($error !== '' ? $error : $reply));
            return self::fail($error !== '' ? $error : 'AI私信生成失败');
        }

        return [
            'ok' => true,
            'reply' => $reply,
            'error' => '',
            'error_code' => 0,
        ];
    }

    private static function buildUserContent(string $commentText, string $authorName, string $noteTitle): string
    {
        $parts = [];
        if ($authorName !== '') {
            $parts[] = '作者：' . $authorName;
        }
        if ($noteTitle !== '') {
            $parts[] = '笔记：' . $noteTitle;
        }
        if ($parts === []) {
            return $commentText;
        }
        $parts[] = '评论：' . $commentText;
        return implode("\n", $parts);
    }

    private static function resolveKnowledge(KbRobot $robot, int $userId): array
    {
        if ((int)$robot->kb_type === 1) {
            $bind = KnowledgeBind::where('data_id', $robot->id)
                ->where('user_id', $userId)
                ->where('type', 1)
                ->limit(1)
                ->find();
            if (!empty($bind)) {
                $find = Knowledge::where('id', $bind['kid'])->limit(1)->find();
                return !empty($find) ? $find->toArray() : [];
            }
        }
        if ((int)$robot->kb_type === 2) {
            $bind = KnowledgeBind::where('data_id', $robot->id)
                ->where('user_id', $userId)
                ->where('type', 1)
                ->limit(1)
                ->find();
            if (!empty($bind)) {
                $find = KbKnow::where('id', $bind['kid'])->limit(1)->find();
                return !empty($find) ? $find->toArray() : [];
            }
        }
        return [];
    }

    public static function buildChatTaskId(string $taskId, string $commentText): string
    {
        return 'shutoff_dm_' . $taskId . '_' . substr(hash('sha256', $commentText), 0, 16);
    }

    /**
     * @return array{ok:bool,reply:string,error:string,error_code:int}
     */
    private static function fail(string $error, int $errorCode = 0): array
    {
        return [
            'ok' => false,
            'reply' => '',
            'error' => $error,
            'error_code' => $errorCode,
        ];
    }

    private static function writeLog(string $message): void
    {
        try {
            Log::channel('socket')->write($message, 'shutoff_dm');
        } catch (\Throwable $e) {
            Log::write('截流私信日志写入失败:' . $e->getMessage());
        }
    }
}
