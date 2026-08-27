<?php

namespace app\common\service\sv;

use app\api\logic\ChatLogic;
use app\api\logic\KnowledgeLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\model\chat\ChatLog;
use app\common\model\sv\SvDevice;
use app\common\service\chat\ChatBillingService;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 小红书私信：调模型 + 扣费（同步）。
 * 从 MessageHandler::beforeSend / handleResponse 原样搬迁，不依赖 TcpConnection。
 */
class XhsPrivateMessageAiChatService
{
    /** 现网默认回复 */
    private const DEFAULT_REPLY = '请稍等，该问题我不太清楚，为您转接给对应的部门同事';

    /**
     * 同步完成调模与扣费
     * @param array $data 现网任务数据：account/friend_id/friend_name/device_code/task_id/user_id/request/robot
     * @return array{ok:bool,reply:string,is_ai_reply:bool,from:string,error:string,error_code:int}
     */
    public static function complete(array $data): array
    {
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return self::result(false, self::DEFAULT_REPLY, false, 'fail', '用户id无效', 0);
        }

        $request = $data['request'] ?? [];
        $robot = $data['robot'] ?? [];
        $taskId = (string)($data['task_id'] ?? '');
        $deviceCode = (string)($data['device_code'] ?? '');
        $model = $request['model'] ?? 'deepseek';

        // 预检：4059 必须原样返回，禁止吞掉当成功回复
        try {
            ChatBillingService::checkBalance($userId, $model);
        } catch (\Throwable $e) {
            if ((int)$e->getCode() === 4059) {
                self::writeLog('小红书私信预检算力不足:' . $e->getMessage());
                return self::result(false, self::DEFAULT_REPLY, false, 'fail', $e->getMessage(), 4059);
            }
            throw $e;
        }

        $log = ChatLog::where('task_id', $taskId)->findOrEmpty();
        if (!$log->isEmpty()) {
            $reply = (string)$log->reply;
            return self::result(true, $reply, self::isSuccessfulAiReply($reply), 'cache');
        }

        $autoType = SvDevice::where('device_code', $deviceCode)->value('auto_type') ?? 0;

        if (!empty($request['knowledge']) || ($robot['kb_type'] == 2 && !empty($robot['kb_ids']))) {
            return self::completeKnowledge($request, $robot, $userId, $autoType, $taskId);
        }

        return self::completeChat($request, $userId, $model, $autoType, $taskId);
    }

    /**
     * 知识库路径：禁止 ChatBillingService::charge
     */
    private static function completeKnowledge(
        array $request,
        array $robot,
        int $userId,
        $autoType,
        string $taskId
    ): array {
        $reply = self::DEFAULT_REPLY;
        $isAiReply = false;
        $_message = is_array($request['message']) ? implode("\n", $request['message']) : $request['message'];

        [$chatStatus, $response] = KnowledgeLogic::socketChat([
            'auto_type' => $autoType,
            'message' => $_message,
            'messages' => $request['messages'],
            'indexid' => $request['knowledge']['index_id'] ?? '',
            'rerank_min_score' => $request['knowledge']['rerank_min_score'] ?? 0.2,
            'stream' => false,
            'user_id' => $userId,
            'scene' => '小红书',
            'model' => $request['model'],
            'robot' => $robot,
            'temperature' => $request['temperature'] ?? 0.5,
            'top_p' => $request['top_p'] ?? 0.85,
            'presence_penalty' => $request['presence_penalty'] ?? 0.2, //避免重复力度
            'frequency_penalty' => $request['frequency_penalty'] ?? 0.3, //避免重复用词力度
            'max_tokens' => $request['max_tokens'] ?? 4096, //token上限
            'context_num' => $request['context_num'] ?? 0, //智能体上下文数
            'kb_id' => $robot['kb_ids'],
        ]);

        if ($chatStatus === false) {
            self::writeLog($taskId . '队列请求知识库失败:' . (is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE)));
        } else {
            $response['msg'] = '知识库消息回复结果';
            self::writeLog($response);
            if (isset($response['choices'][0]) && !empty($response['choices'][0])) {
                $reply = $response['choices'][0]['message']['content'];
                $reply = formatMarkdown($reply);
                $isAiReply = self::isSuccessfulAiReply($reply);
            }
        }

        return self::result(true, $reply, $isAiReply, 'kb');
    }

    /**
     * 非知识库：按 auto_type / model 调模，仅 code==10000 才扣费
     */
    private static function completeChat(
        array $request,
        int $userId,
        string $model,
        $autoType,
        string $taskId
    ): array {
        $reply = self::DEFAULT_REPLY;

        if ($autoType == 0) {
            if ($request['model'] == 'deepseek') {
                $response = ToolsService::Sv()->chat($request);
            } else {
                $request['stream'] = false;
                $response = ToolsService::Sv()->openaiChat($request);
            }
        } else {
            if ($request['model'] != 'deepseek') {
                $request['stream'] = false;
            }
            self::writeLog('socialMediaObtain 回复');
            $response = ToolsService::Automation()->socialMediaObtain($request);
        }

        $response['msg'] = 'chat ai消息回复结果';
        self::writeLog($response);
        if (isset($response['code']) && $response['code'] == 10000) {
            try {
                $reply = self::applyChatCharge($response, $model, $autoType, $userId, $request, $taskId);
                return self::result(true, $reply, self::isSuccessfulAiReply($reply), 'chat');
            } catch (\Exception $e) {
                self::writeLog($e);
                if ((int)$e->getCode() === 4059) {
                    return self::result(false, self::DEFAULT_REPLY, false, 'fail', $e->getMessage(), 4059);
                }
                return self::result(true, self::DEFAULT_REPLY, false, 'chat', $e->getMessage(), 0);
            }
        }

        self::writeLog($taskId . '队列请求失败' . json_encode($response, JSON_UNESCAPED_UNICODE));
        return self::result(true, $reply, false, 'chat');
    }

    /**
     * 原 MessageHandler::handleResponse：二次余额校验 + 记日志 + 按 autoType/model 扣费
     * @param mixed $autoType
     */
    private static function applyChatCharge(
        array $response,
        string $model,
        $autoType,
        int $userId,
        array $request,
        string $taskId
    ): string {
        ChatBillingService::checkBalance($userId, $model);

        $reply = $response['data']['message'] ?? '';
        $usage = $response['data']['usage'] ?? [];

        if (!$reply || empty($usage['total_tokens'])) {
            throw new \Exception('获取内容失败');
        }

        if (is_array($request['message'])) {
            $request['message'] = implode(';', $request['message']);
        }

        ChatLogic::saveChatResponseLog($request, [
            'reply' => $reply,
            'usage_tokens' => $usage,
        ], 'msg');

        $logType = $autoType == 0
            ? ($model == 'deepseek' ? AccountLogEnum::TOKENS_DEC_AI_XHS : AccountLogEnum::TOKENS_DEC_OPENAI_CHAT)
            : AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN;

        ChatBillingService::charge(
            $userId,
            $model,
            $usage,
            $logType,
            $taskId
        );

        return $reply;
    }

    /**
     * 判断AI回复是否成功
     * @param mixed $reply
     */
    private static function isSuccessfulAiReply($reply): bool
    {
        return is_string($reply) && trim($reply) !== '';
    }

    /**
     * @return array{ok:bool,reply:string,is_ai_reply:bool,from:string,error:string,error_code:int}
     */
    private static function result(
        bool $ok,
        string $reply,
        bool $isAiReply,
        string $from,
        string $error = '',
        int $errorCode = 0
    ): array {
        return [
            'ok' => $ok,
            'reply' => $reply,
            'is_ai_reply' => $isAiReply,
            'from' => $from,
            'error' => $error,
            'error_code' => $errorCode,
        ];
    }

    /**
     * 业务日志（中文）；不写 connection
     * @param mixed $content
     */
    private static function writeLog($content): void
    {
        try {
            if ($content instanceof \Throwable) {
                $content = (string)$content;
            } elseif (is_array($content)) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            Log::channel('socket')->write((string)$content, 'msg');
        } catch (\Throwable $e) {
            Log::write('小红书私信调模日志写入失败:' . $e->getMessage());
        }
    }
}
