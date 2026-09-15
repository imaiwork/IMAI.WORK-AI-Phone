<?php

namespace app\common\service\sv;

/**
 * 截流私信 / 截流评论触达口径（无框架依赖，便于单测）
 *
 * 两套枚举相反，禁止原样互写：
 * - 人设 shutoff_msg_type / shutoff_comment_type：1=AI智能体，2=固定话术
 * - 任务表 touch_type：1=固定话术，2=AI回复，3=AI根据固话优化（本期不用 3）
 */
class ShutoffDmTouchHelper
{
    public const SHUTOFF_MSG_TYPE_AI = 1;
    public const SHUTOFF_MSG_TYPE_SPEECH = 2;

    public const TOUCH_TYPE_SPEECH = 1;
    public const TOUCH_TYPE_AI = 2;
    public const TOUCH_TYPE_AI_OPTIMIZE = 3;

    /** 接管默认兜底句，截流场景视为生成失败 */
    public const TAKEOVER_FALLBACK_REPLY = '请稍等，该问题我不太清楚，为您转接给对应的部门同事';

    public static function isAiTouch(int $touchType, int $agentId): bool
    {
        return $touchType === self::TOUCH_TYPE_AI && $agentId > 0;
    }

    public static function resolveCommentText(array $payload): string
    {
        foreach (['content', 'comment_content', 'comment_text', 'commentStr'] as $key) {
            $text = trim((string)($payload[$key] ?? ''));
            if ($text !== '') {
                return $text;
            }
        }
        return '';
    }

    public static function isUsableReply(string $reply): bool
    {
        $reply = trim($reply);
        if ($reply === '') {
            return false;
        }
        if (strcasecmp($reply, 'SKIP') === 0) {
            return false;
        }
        return $reply !== self::TAKEOVER_FALLBACK_REPLY;
    }

    /**
     * 手工截流私信入库前规范化
     * @return array{ok:bool,error:string,touch_type:int,agent_id:int,content:array}
     */
    public static function normalizeManualDmParams(int $taskType, mixed $touchType, mixed $agentId, mixed $content): array
    {
        $type = $touchType === null || $touchType === '' ? self::TOUCH_TYPE_SPEECH : (int)$touchType;
        $id = (int)$agentId;
        if (!in_array($taskType, [1, 2], true)) {
            if (!is_array($content)) {
                return [
                    'ok' => false,
                    'error' => '内容不能为空',
                    'touch_type' => $type,
                    'agent_id' => 0,
                    'content' => [],
                ];
            }
            return [
                'ok' => true,
                'error' => '',
                'touch_type' => $type,
                'agent_id' => 0,
                'content' => $content,
            ];
        }
        if ($type === self::TOUCH_TYPE_AI_OPTIMIZE) {
            return [
                'ok' => false,
                'error' => '暂不支持AI根据固话优化',
                'touch_type' => $type,
                'agent_id' => 0,
                'content' => [],
            ];
        }
        if (!in_array($type, [self::TOUCH_TYPE_AI, self::TOUCH_TYPE_SPEECH], true)) {
            return [
                'ok' => false,
                'error' => '触达方式必须是1固定话术或2AI回复',
                'touch_type' => $type,
                'agent_id' => 0,
                'content' => [],
            ];
        }
        if (self::isAiTouch($type, $id)) {
            return [
                'ok' => true,
                'error' => '',
                'touch_type' => self::TOUCH_TYPE_AI,
                'agent_id' => $id,
                'content' => is_array($content) ? $content : [],
            ];
        }
        if ($type === self::TOUCH_TYPE_AI && $id <= 0) {
            return [
                'ok' => false,
                'error' => $taskType === 1 ? '请选择截流评论智能体' : '请选择截流私信智能体',
                'touch_type' => self::TOUCH_TYPE_AI,
                'agent_id' => 0,
                'content' => [],
            ];
        }
        if (!is_array($content) || $content === []) {
            return [
                'ok' => false,
                'error' => '内容不能为空',
                'touch_type' => self::TOUCH_TYPE_SPEECH,
                'agent_id' => 0,
                'content' => [],
            ];
        }
        return [
            'ok' => true,
            'error' => '',
            'touch_type' => self::TOUCH_TYPE_SPEECH,
            'agent_id' => 0,
            'content' => $content,
        ];
    }

    /**
     * 102 回包契约
     * @return array{isProceed:int,touch_content?:string,comment_content?:string,skip_reason?:string}
     */
    public static function buildCheckReply(bool $proceed, string $commentText = '', string $touchContent = '', string $skipReason = ''): array
    {
        if (!$proceed) {
            $reply = ['isProceed' => 0];
            if ($skipReason !== '') {
                $reply['skip_reason'] = $skipReason;
            }
            return $reply;
        }
        $reply = ['isProceed' => 1];
        if ($touchContent !== '') {
            $reply['touch_content'] = $touchContent;
            $reply['comment_content'] = $commentText;
        }
        return $reply;
    }

    public static function buildDmSystemPrompt(string $rolesPrompt): string
    {
        $system = trim($rolesPrompt);
        if ($system === '') {
            $system = '你是一个乐意助人的助手';
        }
        return $system . "\n根据用户在评论区发布的这句话，写一条可直接发出的私信。要求：短、口语、不提自己是AI、不要Markdown、不要分段。";
    }

    public static function buildCommentSystemPrompt(string $rolesPrompt): string
    {
        $system = trim($rolesPrompt);
        if ($system === '') {
            $system = '你是一个乐意助人的助手';
        }
        return $system . "\n根据用户在评论区发布的这句话，写一条可直接发出的评论回复。要求：短、口语、不提自己是AI、不要Markdown、不要分段。若明显不是潜在客户或没有回复价值，只输出SKIP。";
    }

    /**
     * 人设 shutoff_msg_type → 任务表 touch_type，禁止原样拷贝
     */
    public static function mapShutoffMsgTypeToTouchType(int $shutoffMsgType): int
    {
        if ($shutoffMsgType === self::SHUTOFF_MSG_TYPE_AI) {
            return self::TOUCH_TYPE_AI;
        }
        return self::TOUCH_TYPE_SPEECH;
    }

    /**
     * 自动化建任务：入参是人设 shutoff_msg_type（1=AI 2=固定），不是任务表 touch_type
     * @return array{skip:bool,agent_id:int,reason:string}
     */
    public static function resolveAutoSceneAgentId(int $scene, int $shutoffMsgType, int $shutoffMsgAgentId): array
    {
        if ($scene !== 1 && $scene !== 2) {
            return ['skip' => false, 'agent_id' => 0, 'reason' => ''];
        }
        if ($shutoffMsgType === self::SHUTOFF_MSG_TYPE_AI) {
            if ($shutoffMsgAgentId <= 0) {
                return [
                    'skip' => true,
                    'agent_id' => 0,
                    'reason' => $scene === 1
                        ? '截流评论未绑定智能体，跳过该时段建任务'
                        : '截流私信未绑定智能体，跳过该时段建任务',
                ];
            }
            return ['skip' => false, 'agent_id' => $shutoffMsgAgentId, 'reason' => ''];
        }
        return ['skip' => false, 'agent_id' => 0, 'reason' => ''];
    }

    /**
     * 自动化 scene=1/2 建任务字段：映射 touch_type + 解析 agent_id
     * @return array{skip:bool,agent_id:int,touch_type:int,reason:string}
     */
    public static function resolveAutoDmCreate(int $scene, int $shutoffMsgType, int $shutoffMsgAgentId): array
    {
        $resolved = self::resolveAutoSceneAgentId($scene, $shutoffMsgType, $shutoffMsgAgentId);
        $resolved['touch_type'] = ($scene === 1 || $scene === 2)
            ? self::mapShutoffMsgTypeToTouchType($shutoffMsgType)
            : 0;
        return $resolved;
    }

    /**
     * 去重通过后的 102 放行决策（不含扣费）
     * @param array{ok?:bool,reply?:string,error?:string,error_code?:int}|null $aiResult
     * @return array{isProceed:int,touch_content?:string,comment_content?:string,skip_reason?:string,error_code?:int}
     */
    public static function decideProceed(int $touchType, int $agentId, string $commentText, ?array $aiResult = null): array
    {
        if ($touchType === self::TOUCH_TYPE_AI && $agentId <= 0) {
            return self::buildCheckReply(false, '', '', '未绑定截流私信智能体');
        }
        if (!self::isAiTouch($touchType, $agentId)) {
            return self::buildCheckReply(true);
        }
        if ($commentText === '') {
            return self::buildCheckReply(false, '', '', '评论原文为空');
        }
        if ($aiResult === null) {
            return self::buildCheckReply(false, '', '', 'AI私信生成失败');
        }
        $errorCode = (int)($aiResult['error_code'] ?? 0);
        $error = trim((string)($aiResult['error'] ?? ''));
        if ($errorCode === 4059) {
            $reply = self::buildCheckReply(false, '', '', $error !== '' ? $error : '算力不足');
            $reply['error_code'] = 4059;
            return $reply;
        }
        $replyText = trim((string)($aiResult['reply'] ?? ''));
        if (empty($aiResult['ok']) || !self::isUsableReply($replyText)) {
            return self::buildCheckReply(false, '', '', $error !== '' ? $error : 'AI私信生成失败');
        }
        return self::buildCheckReply(true, $commentText, $replyText);
    }

    /**
     * 104 落库字段口径：对方评论 / 我方发出 / 触达文案
     * @return array{content:string,comment_content:string,touch_content:string}
     */
    public static function mapTouchPostRecord(array $rpaPayload): array
    {
        $sent = trim((string)($rpaPayload['touch_content'] ?? ''));
        if ($sent === '') {
            $sent = trim((string)($rpaPayload['replyCommentStr'] ?? ''));
        }
        return [
            'content' => (string)($rpaPayload['commentStr'] ?? ''),
            'comment_content' => trim((string)($rpaPayload['replyCommentStr'] ?? '')) !== ''
                ? (string)$rpaPayload['replyCommentStr']
                : $sent,
            'touch_content' => $sent,
        ];
    }

    /**
     * 日限 / 已触达 / 重复 hash 的跳过原因（与现网 102 顺序一致）
     */
    public static function resolveDedupSkipReason(bool $dailyLimitReached, bool $execedClueHit, bool $hashExists): string
    {
        if ($dailyLimitReached) {
            return '当日互动已达上限';
        }
        if ($execedClueHit) {
            return '该线索已触达';
        }
        if ($hashExists) {
            return '该评论已处理';
        }
        return '';
    }
}
