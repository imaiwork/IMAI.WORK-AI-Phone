<?php

namespace app\common\service\phoneAgent;

use app\common\service\ToolsService;
use think\facade\Log;

class PhoneAgentPlanningService
{
    public const PLAN_STATUS_SUCCESS = 'success';
    public const PLAN_STATUS_FAILED = 'failed';
    public const PLAN_STATUS_SKIPPED = 'skipped';

    public static function defaultAnalyzeModel(): string
    {
        return 'glm-4v-flash';
    }

    /**
     * @return array{ok:bool,analysis?:array,display?:string,execution_message?:string,analyze_model?:string,raw?:string,error?:string,usage?:array}
     */
    public static function prepareExecutionTask(
        string $userTask,
        int $userId = 0,
        ?string $analyzeModel = null,
        string $lang = 'cn',
        bool $charge = true
    ): array {
        $userTask = trim($userTask);
        if ($userTask === '') {
            return ['ok' => false, 'error' => '任务描述不能为空'];
        }

        $model = trim((string)$analyzeModel) !== '' ? trim((string)$analyzeModel) : self::defaultAnalyzeModel();
        if ($userId > 0) {
            PhoneAgentBillingService::checkBalanceByModel($userId, $model);
        }

        $result = self::analyzeTask($userTask, $model, $lang);
        if (!$result['ok']) {
            return $result;
        }

        if ($userId > 0 && $charge) {
            $usage = is_array($result['usage'] ?? null) ? $result['usage'] : [];
            PhoneAgentBillingService::chargeByModel(
                $userId,
                $model,
                $usage,
                generate_unique_task_id(),
                [
                    '来源' => 'AI手机任务规划',
                    '模型' => $model,
                ]
            );
        }
        $result['analyze_model'] = $model;
        unset($result['usage']);

        return $result;
    }

    /**
     * @return array{ok:bool,analysis?:array,display?:string,execution_message?:string,analyze_model?:string,raw?:string,error?:string,usage?:array}
     */
    public static function analyzeTask(string $task, string $model, string $lang = 'cn'): array
    {
        $task = trim($task);
        if ($task === '') {
            return ['ok' => false, 'error' => '任务描述不能为空'];
        }

        $systemPrompt = self::systemPrompt($lang);
        $request = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "待分析任务：\n{$task}"],
            ],
            'stream' => false,
            'temperature' => 0.2,
            'max_tokens' => 1024,
            'task_id' => generate_unique_task_id(),
        ];

        try {
            $response = ToolsService::AutoGlm()->analyzeTask($request);
            self::logAnalyzeTask($request, $response);
        } catch (\Throwable $e) {
            self::logAnalyzeTask($request, null, $e->getMessage());
            return ['ok' => false, 'error' => '任务分析失败：' . $e->getMessage()];
        }
        //$data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $raw = trim((string)($response['choices'][0]['message']['content'] ?? ''));
        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        $parsed = self::extractJson($raw);
        if ($parsed !== null) {
            $analysis = self::normalizeParsed($parsed);
            $display = self::formatAnalysisDisplay($analysis);
            if ($display === '') {
                $display = $raw;
            }

            return [
                'ok' => true,
                'analysis' => $analysis,
                'display' => $display,
                'execution_message' => self::executionTaskFromAnalysis($task, $analysis),
                'analyze_model' => $model,
                'raw' => $raw,
                'usage' => $usage,
            ];
        }
        
        $analysis = self::normalizeParsed([]);
        $analysis['suggestion'] = '模型未返回结构化结果，请阅读下方原文';

        return [
            'ok' => true,
            'analysis' => $analysis,
            'display' => $raw !== '' ? $raw : '（模型未返回内容）',
            'execution_message' => self::executionTaskFromAnalysis($task, $analysis),
            'analyze_model' => $model,
            'raw' => $raw,
            'usage' => $usage,
        ];
    }

    public static function formatAnalysisDisplay(array $data): string
    {
        $lines = [];

        if (!empty($data['summary'])) {
            $lines[] = "【任务理解】\n" . $data['summary'];
        }

        if (!empty($data['steps']) && is_array($data['steps'])) {
            $stepLines = [];
            foreach ($data['steps'] as $index => $step) {
                $stepLines[] = ($index + 1) . '. ' . $step;
            }
            $lines[] = "【建议步骤】\n" . implode("\n", $stepLines);
        }

        if (!empty($data['valid_rules']) && is_array($data['valid_rules'])) {
            $ruleLines = array_map(static fn (string $rule): string => '  ✅ ' . $rule, $data['valid_rules']);
            $lines[] = "【有效结果判断】\n" . implode("\n", $ruleLines) . "\n  ⏭️ 不满足则跳过，继续下一条";
        }

        if (!empty($data['collect_fields']) && is_array($data['collect_fields'])) {
            $fieldLines = array_map(static fn (string $field): string => '  • ' . $field, $data['collect_fields']);
            $lines[] = "【需记录字段】\n" . implode("\n", $fieldLines);
        }

        if (!empty($data['apps']) && is_array($data['apps'])) {
            $lines[] = "【可能涉及应用】\n" . implode('、', $data['apps']);
        }

        if (!empty($data['risks']) && is_array($data['risks'])) {
            $riskLines = array_map(static fn (string $risk): string => '• ' . $risk, $data['risks']);
            $lines[] = "【风险与注意】\n" . implode("\n", $riskLines);
        }

        if (!empty($data['complete_conditions']) && is_array($data['complete_conditions'])) {
            $conditionLines = array_map(static fn (string $item): string => '• ' . $item, $data['complete_conditions']);
            $lines[] = "【完成条件】\n" . implode("\n", $conditionLines);
        }

        if (!empty($data['fail_conditions']) && is_array($data['fail_conditions'])) {
            $failLines = array_map(static fn (string $item): string => '• ' . $item, $data['fail_conditions']);
            $lines[] = "【失败条件】\n" . implode("\n", $failLines);
        }

        $suggestion = trim((string)($data['suggestion'] ?? ''));
        if ($suggestion !== '' && $suggestion !== '无') {
            $lines[] = "【建议补充】\n" . $suggestion;
        }

        if (($data['clarity'] ?? '') === 'ambiguous') {
            $lines[] = '【清晰度】任务描述存在歧义，确认前请核对是否理解正确。';
        }

        return $lines !== [] ? implode("\n\n", $lines) : '';
    }

    public static function executionTaskFromAnalysis(string $userTask, array $analysis): string
    {
        $userTask = trim($userTask);
        $lines = ["用户原始任务：{$userTask}"];

        if (!empty($analysis['summary'])) {
            $lines[] = '任务理解：' . $analysis['summary'];
        }

        $steps = is_array($analysis['steps'] ?? null) ? $analysis['steps'] : [];
        if ($steps !== []) {
            $lines[] = "\n请严格按以下步骤在手机上操作：";
            foreach ($steps as $index => $step) {
                $lines[] = ($index + 1) . '. ' . $step;
            }
        }

        $validRules = is_array($analysis['valid_rules'] ?? null) ? $analysis['valid_rules'] : [];
        if ($validRules !== []) {
            $lines[] = "\n有效结果判断规则（每条结果需满足以下条件才计入）：";
            foreach ($validRules as $rule) {
                $lines[] = '  ✅ ' . $rule;
            }
            $lines[] = '  ⏭️ 不满足以上条件则跳过，继续浏览下一条';
        }

        $collectFields = is_array($analysis['collect_fields'] ?? null) ? $analysis['collect_fields'] : [];
        if ($collectFields !== []) {
            $lines[] = "\n每条有效结果需记录以下信息：";
            foreach ($collectFields as $field) {
                $lines[] = '  • ' . $field;
            }
        }

        $apps = is_array($analysis['apps'] ?? null) ? $analysis['apps'] : [];
        if ($apps !== []) {
            $lines[] = "\n可能用到的应用：" . implode('、', $apps);
        }

        $risks = is_array($analysis['risks'] ?? null) ? $analysis['risks'] : [];
        if ($risks !== []) {
            $lines[] = "\n注意：" . implode('；', $risks);
        }

        $completeConditions = is_array($analysis['complete_conditions'] ?? null) ? $analysis['complete_conditions'] : [];
        if ($completeConditions !== []) {
            $lines[] = "\n任务完成条件（满足以下全部条件后结束）：";
            foreach ($completeConditions as $condition) {
                $lines[] = '  • ' . $condition;
            }
        } else {
            $lines[] = "\n完成上述目标后结束任务。";
        }

        $failConditions = is_array($analysis['fail_conditions'] ?? null) ? $analysis['fail_conditions'] : [];
        if ($failConditions !== []) {
            $lines[] = "\n任务失败条件（遇到以下情况立即停止并报告原因）：";
            foreach ($failConditions as $condition) {
                $lines[] = '  • ' . $condition;
            }
        }

        $outputFormat = trim((string)($analysis['output_format'] ?? ''));
        if ($outputFormat !== '') {
            $lines[] = "\n完成后请按以下格式输出结果：\n{$outputFormat}";
        } else {
            $lines[] = "\n完成后请输出：\n  • 任务状态：成功/失败\n  • 执行步骤数\n  • 收集结果汇总";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalizeParsed(array $data): array
    {
        $clarity = strtolower(trim((string)($data['clarity'] ?? 'ambiguous')));
        if (!in_array($clarity, ['clear', 'ambiguous'], true)) {
            $clarity = 'ambiguous';
        }

        return [
            'summary' => trim((string)($data['summary'] ?? '')),
            'steps' => self::toStringList($data['steps'] ?? null),
            'apps' => self::toStringList($data['apps'] ?? null),
            'risks' => self::toStringList($data['risks'] ?? null),
            'clarity' => $clarity,
            'suggestion' => trim((string)($data['suggestion'] ?? '')) ?: '无',
            'valid_rules' => self::toStringList($data['valid_rules'] ?? null),
            'collect_fields' => self::toStringList($data['collect_fields'] ?? null),
            'complete_conditions' => self::toStringList($data['complete_conditions'] ?? null),
            'fail_conditions' => self::toStringList($data['fail_conditions'] ?? null),
            'output_format' => trim((string)($data['output_format'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function extractJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array{plan_status:string,execution_message:string,plan_json:string,plan_display:string,analyze_model:string}
     */
    public static function resolvePlanForDispatch(
        string $message,
        int $userId,
        string $executionMessage = '',
        bool $skipAnalyze = false,
        ?string $analyzeModel = null,
        string $lang = 'cn'
    ): array {
        if ($skipAnalyze) {
            return [
                'plan_status' => self::PLAN_STATUS_SKIPPED,
                'execution_message' => '',
                'plan_json' => '',
                'plan_display' => '',
                'analyze_model' => '',
            ];
        }

        $executionMessage = trim($executionMessage);
        if ($executionMessage !== '') {
            return [
                'plan_status' => self::PLAN_STATUS_SUCCESS,
                'execution_message' => $executionMessage,
                'plan_json' => '',
                'plan_display' => '',
                'analyze_model' => trim((string)$analyzeModel) !== '' ? trim((string)$analyzeModel) : self::defaultAnalyzeModel(),
            ];
        }

        $result = self::prepareExecutionTask($message, $userId, $analyzeModel, $lang, true);
        if (!$result['ok']) {
            throw new \RuntimeException((string)($result['error'] ?? '任务规划失败'));
        }

        $analysis = is_array($result['analysis'] ?? null) ? $result['analysis'] : [];

        return [
            'plan_status' => self::PLAN_STATUS_SUCCESS,
            'execution_message' => (string)($result['execution_message'] ?? ''),
            'plan_json' => json_encode($analysis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            'plan_display' => (string)($result['display'] ?? ''),
            'analyze_model' => (string)($result['analyze_model'] ?? self::defaultAnalyzeModel()),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed>|null $response
     */
    private static function logAnalyzeTask(array $request, ?array $response, string $error = ''): void
    {
        try {
            $payload = [
                'event' => 'analyze_task',
                'request' => $request,
                'response' => $response,
            ];
            if ($error !== '') {
                $payload['error'] = $error;
            }
            Log::channel('glm')->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (\Throwable) {
        }
    }

    /**
     * @return string[]
     */
    private static function toStringList(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? [$value] : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    private static function systemPrompt(string $lang): string
    {
        if ($lang === 'en') {
            return (string)(include __DIR__ . '/prompts/autoglm_phone_plan_en.php');
        }

        return (string)(include __DIR__ . '/prompts/autoglm_phone_plan_zh.php');
    }
}
