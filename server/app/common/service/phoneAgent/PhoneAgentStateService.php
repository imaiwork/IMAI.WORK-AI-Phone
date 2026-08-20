<?php

namespace app\common\service\phoneAgent;

use app\common\model\phoneAgent\PhoneAgentAction;
use app\common\model\phoneAgent\PhoneAgentObservation;
use app\common\model\phoneAgent\PhoneAgentTask;
use app\common\model\phoneAgent\PhoneAgentTurn;
use app\common\workerman\rpa\WorkerEnum;
use think\facade\Log;

class PhoneAgentStateService
{
    private const MAX_TURNS = 20;
    private const MAX_HISTORY_MESSAGES = 10;

    public static function start(PhoneAgentTask $task): bool
    {
        if ($task->isFinal()) {
            return false;
        }

        $turnNo = max(1, (int)$task->current_turn);
        self::createTurnIfMissing((string)$task->task_id, $turnNo);

        $task->status = PhoneAgentTask::STATUS_OBSERVING;
        $task->current_turn = $turnNo;
        $task->save();

        $sent = PhoneAgentDispatchService::observe((string)$task->device_code, (int)$task->id, $turnNo);
        PhoneAgentDispatchService::createEvent(
            (string)$task->task_id,
            (string)$task->device_code,
            $sent ? 'observe_sent' : 'observe_send_failed',
            ['turn_no' => $turnNo]
        );

        if (!$sent) {
            self::failTask($task, '观察指令下发失败');
        }

        return $sent;
    }

    public static function handleReport(array $content, array $rawPayload = []): array
    {
        $reportTaskId = trim((string)($content['task_id'] ?? ''));
        $turnNo = (int)($content['turn_no'] ?? 0);
        $actionNo = (int)($content['action_no'] ?? 0);

        if ($reportTaskId === '' || $turnNo <= 0) {
            return ['handled' => false, 'event_type' => 'invalid_report', 'message' => '缺少 task_id 或 turn_no'];
        }

        $task = ctype_digit($reportTaskId)
            ? PhoneAgentTask::where('id', (int)$reportTaskId)->findOrEmpty()
            : PhoneAgentTask::where('task_id', $reportTaskId)->findOrEmpty();
        if ($task->isEmpty()) {
            PhoneAgentDispatchService::createEvent($reportTaskId, (string)($content['device_code'] ?? ''), 'unknown_task_report', [
                'content' => $content,
                'raw' => $rawPayload,
            ]);
            return ['handled' => false, 'event_type' => 'unknown_task_report', 'message' => '任务不存在'];
        }

        $taskId = (string)$task->task_id;
        $deviceCode = (string)$task->device_code;
        $reportDeviceCode = trim((string)($content['_connection_device_id'] ?? ($rawPayload['deviceId'] ?? ($content['device_code'] ?? ''))));
        $clientType = (string)($content['_connection_client_type'] ?? '');
        unset($content['_connection_device_id'], $content['_connection_client_type']);

        if ($clientType !== '' && $clientType !== WorkerEnum::WS_DEVICE_TYPE) {
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'ignored_report', [
                'reason' => 'invalid_client_type',
                'client_type' => $clientType,
                'content' => self::safeReportContent($content),
            ]);
            self::pushEvent($task, 'ignored_report', $event->toArray());
            return ['handled' => true, 'event_type' => 'ignored_report', 'message' => '无效的客户端类型'];
        }

        if ($reportDeviceCode === '' || $reportDeviceCode !== $deviceCode) {
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'ignored_report', [
                'reason' => 'device_mismatch',
                'report_device_code' => $reportDeviceCode,
                'content' => self::safeReportContent($content),
            ]);
            self::pushEvent($task, 'ignored_report', $event->toArray());
            return ['handled' => true, 'event_type' => 'ignored_report', 'message' => '设备不匹配'];
        }
        $eventType = 'report_received';
        $eventData = [
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'status' => $content['status'] ?? '',
            'message' => $content['message'] ?? '',
        ];

        if ($task->isFinal()) {
            $eventType = 'late_report';
            $eventData['content'] = $content;
            self::saveObservation($task, $turnNo, $content, $rawPayload);
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, $eventType, $eventData);
            self::pushEvent($task, $eventType, $event->toArray());
            return ['handled' => true, 'event_type' => $eventType, 'message' => '任务已结束，上报已记录'];
        }

        if ($turnNo !== (int)$task->current_turn) {
            $eventType = 'ignored_report';
            $eventData['reason'] = 'turn_mismatch';
            $eventData['current_turn'] = (int)$task->current_turn;
            $eventData['content'] = $content;
            self::saveObservation($task, $turnNo, $content, $rawPayload);
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, $eventType, $eventData);
            self::pushEvent($task, $eventType, $event->toArray());
            return ['handled' => true, 'event_type' => $eventType, 'message' => '轮次不匹配，上报已忽略'];
        }

        $status = strtolower((string)($content['status'] ?? ''));
        if ($status === '') {
            $status = 'success';
        }

        $observation = self::saveObservation($task, $turnNo, $content, $rawPayload);

        if ($actionNo > 0) {
            return self::handleActionReport($task, $turnNo, $actionNo, $status, $content, $observation);
        }

        if (!in_array($status, ['success', 'ok', 'finished', 'complete', 'completed'], true)) {
            self::failTask($task, (string)($content['message'] ?? '手机观察上报失败'));
            $eventType = 'observe_failed';
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, $eventType, $eventData);
            self::pushEvent($task, $eventType, $event->toArray());
            return ['handled' => true, 'event_type' => $eventType, 'message' => '观察失败'];
        }

        $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'observation_received', [
            'turn_no' => $turnNo,
            'observation_id' => $observation->id,
        ]);
        self::pushEvent($task, 'observation_received', $event->toArray());

        return self::runModelAndDispatch($task, $observation);
    }

    private static function handleActionReport(
        PhoneAgentTask $task,
        int $turnNo,
        int $actionNo,
        string $status,
        array $content,
        PhoneAgentObservation $observation
    ): array {
        $taskId = (string)$task->task_id;
        $deviceCode = (string)$task->device_code;
        $action = PhoneAgentAction::where('task_id', $taskId)
            ->where('turn_id', (int)$observation->turn_id)
            ->where('action_no', $actionNo)
            ->findOrEmpty();

        if ($action->isEmpty()) {
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'ignored_report', [
                'reason' => 'action_not_found',
                'turn_no' => $turnNo,
                'action_no' => $actionNo,
                'content' => $content,
            ]);
            self::pushEvent($task, 'ignored_report', $event->toArray());
            return ['handled' => true, 'event_type' => 'ignored_report', 'message' => '动作不存在，上报已忽略'];
        }

        if (!in_array((string)$action->status, [PhoneAgentAction::STATUS_PENDING, PhoneAgentAction::STATUS_SENT], true)) {
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'ignored_report', [
                'reason' => 'action_status_not_pending',
                'turn_no' => $turnNo,
                'action_no' => $actionNo,
                'action_status' => (string)$action->status,
            ]);
            self::pushEvent($task, 'ignored_report', $event->toArray());
            return ['handled' => true, 'event_type' => 'ignored_report', 'message' => '动作已处理'];
        }

        $action->result = $content;
        if (!in_array($status, ['success', 'ok', 'finished', 'complete', 'completed'], true)) {
            $action->status = PhoneAgentAction::STATUS_FAILED;
            $action->save();
            self::failTask($task, (string)($content['message'] ?? '手机执行上报失败'));
            $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'action_failed', [
                'turn_no' => $turnNo,
                'action_no' => $actionNo,
                'message' => $content['message'] ?? '',
            ]);
            self::pushEvent($task, 'action_failed', $event->toArray());
            return ['handled' => true, 'event_type' => 'action_failed', 'message' => '动作失败'];
        }

        $action->status = PhoneAgentAction::STATUS_SUCCESS;
        $action->save();

        if ((string)$action->action_type === 'launch') {
            PhoneAgentHintService::setLaunchContinueHint((string)$task->task_id);
        }

        $event = PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'action_success', [
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'observation_id' => $observation->id,
        ]);
        self::pushEvent($task, 'action_success', $event->toArray());

        if (in_array($status, ['finished', 'complete', 'completed'], true) || (bool)($content['finished'] ?? false)) {
            self::completeTask($task, (string)($content['message'] ?? '任务完成'));
            return ['handled' => true, 'event_type' => 'task_completed', 'message' => '任务完成'];
        }

        $turn = PhoneAgentTurn::where('id', (int)$action->turn_id)->findOrEmpty();
        if (!$turn->isEmpty() && self::dispatchNextPendingAction($task, $turn, $turnNo)) {
            return ['handled' => true, 'event_type' => 'action_sent', 'message' => '后续动作已下发'];
        }

        return self::nextObserve($task);
    }

    private static function runModelAndDispatch(PhoneAgentTask $task, PhoneAgentObservation $observation): array
    {
        if ((int)$task->current_turn > self::MAX_TURNS) {
            self::failTask($task, '超过最大执行轮次');
            return ['handled' => true, 'event_type' => 'task_failed', 'message' => '超过最大执行轮次'];
        }

        $turn = PhoneAgentTurn::where('id', (int)$observation->turn_id)->findOrEmpty();
        if ($turn->isEmpty()) {
            self::failTask($task, '模型轮次不存在');
            return ['handled' => true, 'event_type' => 'task_failed', 'message' => '模型轮次不存在'];
        }

        try {
            PhoneAgentBillingService::checkBalance($task);
        } catch (\Throwable $e) {
            return self::failBilling($task, $turn, $e->getMessage(), 'precheck');
        }

        $turnNo = (int)$task->current_turn;
        $hint = PhoneAgentHintService::takeNextUserHint((string)$task->task_id);
        $screenInfoJson = PhoneAgentMessageBuilder::buildScreenInfoString((string)$observation->current_app);
        $userText = PhoneAgentMessageBuilder::buildUserText(
            $turnNo,
            PhoneAgentMessageBuilder::resolveTaskMessage($task),
            $screenInfoJson,
            $hint
        );
        $turn->user_text = $userText;

        $messages = self::buildModelMessagesWithUserText($task, $observation, $userText, false);
        $logMessages = self::buildModelMessagesWithUserText($task, $observation, $userText, true);
        $request = PhoneAgentModelRequestService::buildRequest((string)$task->model, $messages);
        $logRequest = PhoneAgentModelRequestService::buildRequest((string)$task->model, $logMessages);
        $debugContext = PhoneAgentModelContextService::buildDebugContext($task, $observation, $turnNo);

        $turn->request_messages = self::safeModelLogData($logMessages);
        $turn->status = PhoneAgentTurn::STATUS_MODEL_PENDING;
        $turn->save();

        $task->status = PhoneAgentTask::STATUS_MODEL_PENDING;
        $task->save();

        $response = PhoneAgentModelRequestService::call($request);
        //\think\facade\Log::channel('glm')->write($response);
        self::logModelResponse(
            $task,
            $logRequest,
            is_array($response) ? $response : ['raw_response' => $response],
            $debugContext
        );
        $turn->model_response = is_array($response) ? $response : ['success' => false, 'message' => '模型返回格式错误'];
        $turn->usage = is_array($response) && is_array($response['usage'] ?? null) ? $response['usage'] : [];

        if (!is_array($response) ||  isset($response['error'])) {
            $message = is_array($response) ? (string)($response['error']['message'] ?? '模型调用失败') : '模型调用失败';
            $turn->status = PhoneAgentTurn::STATUS_FAILED;
            $turn->error_msg = $message;
            $turn->save();
            self::failTask($task, $message);
            return ['handled' => true, 'event_type' => 'model_failed', 'message' => $message];
        }

        $rawUsage = is_array($response['usage'] ?? null) ? $response['usage'] : [];
        if (!self::hasBillableUsage($rawUsage)) {
            $message = '模型用量为空，无法扣费';
            $turn->status = PhoneAgentTurn::STATUS_FAILED;
            $turn->error_msg = $message;
            $turn->save();
            self::failTask($task, $message);
            $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'model_failed', [
                'turn_no' => (int)$task->current_turn,
                'message' => $message,
            ]);
            self::pushEvent($task, 'model_failed', $event->toArray());
            return ['handled' => true, 'event_type' => 'model_failed', 'message' => $message];
        }

        try {
            $usage = PhoneAgentBillingService::billableUsage($rawUsage);
            $turn->usage = $usage;
            PhoneAgentBillingService::chargeTurn($task, $turn, $usage);
        } catch (\Throwable $e) {
            return self::failBilling($task, $turn, $e->getMessage(), 'charge');
        }

        self::persistAssistantContent($turn, is_array($response) ? $response : []);
        $turn->save();

        $parsed = PhoneAgentActionParserService::parse($response);
        $turn->parsed_action = $parsed;

        if (!$parsed['success']) {
            $rawContent = PhoneAgentMessageBuilder::extractModelRawContent(is_array($response) ? $response : []);
            $failure = PhoneAgentHintService::recordParseFailure(
                (string)$task->task_id,
                (string)($parsed['message'] ?? '模型响应无法解析'),
                $rawContent
            );
            $turn->status = PhoneAgentTurn::STATUS_PARSED;
            $turn->error_msg = (string)($parsed['message'] ?? '模型响应无法解析');
            $turn->save();

            if ($failure['should_fail']) {
                self::failTask($task, '模型连续 ' . PhoneAgentHintService::getParseFailStreak((string)$task->task_id) . ' 次无法输出可执行动作');
                PhoneAgentHintService::clear((string)$task->task_id);
                $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'model_parse_failed', [
                    'turn_no' => (int)$task->current_turn,
                    'message' => $turn->error_msg,
                ]);
                self::pushEvent($task, 'model_parse_failed', $event->toArray());
                return ['handled' => true, 'event_type' => 'model_parse_failed', 'message' => $turn->error_msg];
            }

            return self::dispatchRecoveryWait($task, $turn, (int)$task->current_turn);
        }

        PhoneAgentHintService::resetParseFailStreak((string)$task->task_id);

        if (!empty($parsed['bare_coordinate'])) {
            PhoneAgentHintService::setBareCoordinateHint((string)$task->task_id);
        }

        if ($parsed['finish']) {
            $guard = PhoneAgentActionParserService::shouldAcceptFinish($task, $observation, (int)$task->current_turn, (string)($parsed['message'] ?? ''));
            if (!$guard['accept']) {
                $message = (string)$guard['reason'];
                Log::channel('glm')->write(json_encode([
                    'event' => 'goal_guard_rejected',
                    'turn_no' => (int)$task->current_turn,
                    'task_id' => (string)$task->task_id,
                    'reason' => $message,
                    'finish_message' => (string)($parsed['message'] ?? ''),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'warning');
                PhoneAgentHintService::setCustomHint((string)$task->task_id, '【系统】' . $message);
                $turn->status = PhoneAgentTurn::STATUS_PARSED;
                $turn->error_msg = $message;
                $turn->save();
                return self::nextObserve($task);
            }

            $turn->status = PhoneAgentTurn::STATUS_COMPLETED;
            $turn->save();
            self::completeTask($task, (string)($parsed['message'] ?? '任务完成'));
            return ['handled' => true, 'event_type' => 'task_completed', 'message' => '任务完成'];
        }

        $turn->status = PhoneAgentTurn::STATUS_PARSED;
        $turn->save();

        $firstAction = self::resolveParsedActions($parsed)[0] ?? null;
        if (!is_array($firstAction)) {
            self::failTask($task, '模型响应缺少可执行动作');
            return ['handled' => true, 'event_type' => 'model_parse_failed', 'message' => '模型响应缺少可执行动作'];
        }

        if ((string)($firstAction['action_type'] ?? '') === 'tap') {
            $tapPoint = self::extractTapPoint($firstAction['params'] ?? []);
            if ($tapPoint !== null) {
                $repeatGuard = PhoneAgentHintService::evaluateRepeatTap(
                    (string)$task->task_id,
                    (int)$tapPoint[0],
                    (int)$tapPoint[1],
                    (string)$observation->current_app
                );
                if ($repeatGuard['should_fail']) {
                    $message = '连续多次点击同一位置无效，任务已终止';
                    self::failTask($task, $message);
                    PhoneAgentHintService::clear((string)$task->task_id);
                    $event = PhoneAgentDispatchService::createEvent(
                        (string)$task->task_id,
                        (string)$task->device_code,
                        'repeat_tap_failed',
                        [
                            'turn_no' => (int)$task->current_turn,
                            'message' => $message,
                            'streak' => (int)$repeatGuard['streak'],
                        ]
                    );
                    self::pushEvent($task, 'repeat_tap_failed', $event->toArray());
                    return ['handled' => true, 'event_type' => 'repeat_tap_failed', 'message' => $message];
                }
                if ($repeatGuard['should_hint']) {
                    PhoneAgentHintService::setCustomHint(
                        (string)$task->task_id,
                        '【系统】连续多次点击同一位置无效，请尝试 Type/Swipe 或点击其他元素'
                    );
                }
            }
        }

        $dispatchResult = self::dispatchPendingActionAtIndex($task, $turn, (int)$task->current_turn, $parsed, 0);
        if (!$dispatchResult['success']) {
            return $dispatchResult['result'];
        }

        return ['handled' => true, 'event_type' => 'action_sent', 'message' => '动作已下发'];
    }

    private static function resolveParsedActions(array $parsed): array
    {
        if (!($parsed['success'] ?? false) || !empty($parsed['finish'])) {
            return [];
        }

        $actions = $parsed['actions'] ?? null;
        if (is_array($actions) && $actions !== []) {
            return array_values(array_filter($actions, static fn ($action): bool => is_array($action)));
        }

        if (($parsed['action_type'] ?? '') !== '') {
            return [[
                'action_type' => (string)$parsed['action_type'],
                'params' => is_array($parsed['params'] ?? null) ? $parsed['params'] : [],
                'timeout' => max(1, (int)($parsed['timeout'] ?? 60)),
            ]];
        }

        return [];
    }

    private static function dispatchNextPendingAction(PhoneAgentTask $task, PhoneAgentTurn $turn, int $turnNo): bool
    {
        $parsed = is_array($turn->parsed_action ?? null) ? $turn->parsed_action : [];
        $actions = self::resolveParsedActions($parsed);
        $dispatchedCount = max(0, (int)($parsed['dispatched_count'] ?? 0));
        if ($dispatchedCount >= count($actions)) {
            return false;
        }

        $result = self::dispatchPendingActionAtIndex($task, $turn, $turnNo, $parsed, $dispatchedCount);
        return (bool)($result['success'] ?? false);
    }

    private static function dispatchPendingActionAtIndex(
        PhoneAgentTask $task,
        PhoneAgentTurn $turn,
        int $turnNo,
        array $parsed,
        int $actionIndex
    ): array {
        $actions = self::resolveParsedActions($parsed);
        if (!isset($actions[$actionIndex]) || !is_array($actions[$actionIndex])) {
            self::failTask($task, '模型动作队列不存在');
            return [
                'success' => false,
                'result' => ['handled' => true, 'event_type' => 'action_send_failed', 'message' => '模型动作队列不存在'],
            ];
        }

        $action = $actions[$actionIndex];
        $actionNo = (int)PhoneAgentAction::where('task_id', (string)$task->task_id)
                ->where('turn_id', (int)$turn->id)
                ->max('action_no') + 1;

        [$sent, $wsPayload] = PhoneAgentDispatchService::execute(
            (string)$task->device_code,
            (int)$task->id,
            $turnNo,
            $actionNo,
            (string)$action['action_type'],
            is_array($action['params'] ?? null) ? $action['params'] : [],
            max(1, (int)($action['timeout'] ?? 60))
        );

        PhoneAgentAction::create([
            'task_id' => (string)$task->task_id,
            'turn_id' => (int)$turn->id,
            'action_no' => $actionNo,
            'action_type' => (string)$action['action_type'],
            'action_payload' => is_array($action['params'] ?? null) ? $action['params'] : [],
            'ws_payload' => $wsPayload,
            'status' => $sent ? PhoneAgentAction::STATUS_SENT : PhoneAgentAction::STATUS_FAILED,
            'result' => [],
            'create_time' => time(),
            'update_time' => time(),
        ]);

        if (!$sent) {
            $turn->status = PhoneAgentTurn::STATUS_FAILED;
            $turn->error_msg = '执行指令下发失败';
            $turn->save();
            self::failTask($task, '执行指令下发失败');
            return [
                'success' => false,
                'result' => ['handled' => true, 'event_type' => 'action_send_failed', 'message' => '执行指令下发失败'],
            ];
        }

        if ((string)($action['action_type'] ?? '') === 'launch') {
            PhoneAgentHintService::setLaunchContinueHint((string)$task->task_id);
        }

        $parsed['dispatched_count'] = $actionIndex + 1;
        $turn->parsed_action = $parsed;
        $task->status = PhoneAgentTask::STATUS_WAITING_REPORT;
        $task->save();
        $turn->status = PhoneAgentTurn::STATUS_DISPATCHED;
        $turn->save();

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'action_sent', [
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'action_type' => (string)$action['action_type'],
            'action_index' => $actionIndex + 1,
            'action_total' => count($actions),
        ]);
        self::pushEvent($task, 'action_sent', $event->toArray());

        return ['success' => true, 'result' => []];
    }

    private static function nextObserve(PhoneAgentTask $task): array
    {
        $nextTurn = (int)$task->current_turn + 1;
        if ($nextTurn > self::MAX_TURNS) {
            self::failTask($task, '超过最大执行轮次');
            return ['handled' => true, 'event_type' => 'task_failed', 'message' => '超过最大执行轮次'];
        }

        $task->current_turn = $nextTurn;
        $task->status = PhoneAgentTask::STATUS_OBSERVING;
        $task->save();
        self::createTurnIfMissing((string)$task->task_id, $nextTurn);

        $sent = PhoneAgentDispatchService::observe((string)$task->device_code, (int)$task->id, $nextTurn);
        $eventType = $sent ? 'observe_sent' : 'observe_send_failed';
        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, $eventType, [
            'turn_no' => $nextTurn,
        ]);
        self::pushEvent($task, $eventType, $event->toArray());

        if (!$sent) {
            self::failTask($task, '观察指令下发失败');
        }

        return ['handled' => true, 'event_type' => $eventType, 'message' => $sent ? '观察指令已下发' : '观察指令下发失败'];
    }

    private static function saveObservation(
        PhoneAgentTask $task,
        int $turnNo,
        array $content,
        array $rawPayload
    ): PhoneAgentObservation {
        $turn = self::createTurnIfMissing((string)$task->task_id, $turnNo);
        $safeContent = self::safeReportContent($content);
        $observation = PhoneAgentObservation::create([
            'task_id' => (string)$task->task_id,
            'turn_id' => (int)$turn->id,
            'screenshot' => self::normalizeScreenshot(PhoneAgentModelContextService::extractScreenshotFromContent($content)),
            'ocr_text' => (string)($content['ocr_text'] ?? ''),
            'accessibility_tree' => $content['accessibility_tree'] ?? [],
            'current_app' => (string)($content['current_app'] ?? ''),
            'raw_data' => [
                'content' => $safeContent,
                'payload' => self::safeReportContent($rawPayload),
            ],
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $observation->model_screenshot = PhoneAgentModelContextService::extractScreenshotFromContent($content);
        return $observation;
    }

    private static function normalizeScreenshot(string $screenshot): string
    {
        $screenshot = trim($screenshot);
        if ($screenshot === '') {
            return '';
        }
        if (str_starts_with($screenshot, 'data:image/') || strlen($screenshot) > 500) {
            return '';
        }
        if (PhoneAgentModelContextService::isLikelyBase64Image($screenshot)) {
            return '';
        }
        return $screenshot;
    }

    private static function safeReportContent(array $content): array
    {
        $safe = $content;
        foreach (['screenshot', 'screenshot_url', 'image', 'base64'] as $field) {
            if (!isset($safe[$field]) || !is_string($safe[$field])) {
                continue;
            }
            if (str_starts_with($safe[$field], 'data:image/')) {
                $safe[$field] = '[omitted_large_payload]';
            }
        }
        return $safe;
    }

    private static function safeModelLogData(mixed $data): mixed
    {
        if (is_array($data)) {
            $safe = [];
            foreach ($data as $key => $value) {
                $safe[$key] = self::safeModelLogData($value);
            }
            return $safe;
        }

        if (!is_string($data)) {
            return $data;
        }

        if (str_starts_with($data, 'data:image/')) {
            return '[omitted_large_payload]';
        }

        return $data;
    }

    private static function hasBillableUsage(array $usage): bool
    {
        return max(
            (int)($usage['prompt_tokens'] ?? 0),
            (int)($usage['completion_tokens'] ?? 0),
            (int)($usage['total_tokens'] ?? 0)
        ) > 0;
    }

    private static function createTurnIfMissing(string $taskId, int $turnNo): PhoneAgentTurn
    {
        $turn = PhoneAgentTurn::where('task_id', $taskId)->where('turn_no', $turnNo)->findOrEmpty();
        if (!$turn->isEmpty()) {
            return $turn;
        }

        return PhoneAgentTurn::create([
            'task_id' => $taskId,
            'turn_no' => $turnNo,
            'request_messages' => [],
            'model_response' => [],
            'parsed_action' => [],
            'usage' => [],
            'status' => PhoneAgentTurn::STATUS_CREATED,
            'error_msg' => '',
            'create_time' => time(),
            'update_time' => time(),
        ]);
    }

    private static function dispatchRecoveryWait(PhoneAgentTask $task, PhoneAgentTurn $turn, int $turnNo): array
    {
        $actionNo = (int)PhoneAgentAction::where('task_id', (string)$task->task_id)
                ->where('turn_id', (int)$turn->id)
                ->max('action_no') + 1;
        $params = ['seconds' => 1];
        $turn->parsed_action = [
            'success' => true,
            'finish' => false,
            'message' => '解析失败，等待重试',
            'action_type' => 'wait',
            'params' => $params,
            'timeout' => 6,
        ];
        $turn->save();

        [$sent, $wsPayload] = PhoneAgentDispatchService::execute(
            (string)$task->device_code,
            (int)$task->id,
            $turnNo,
            $actionNo,
            'wait',
            $params,
            6
        );

        PhoneAgentAction::create([
            'task_id' => (string)$task->task_id,
            'turn_id' => (int)$turn->id,
            'action_no' => $actionNo,
            'action_type' => 'wait',
            'action_payload' => $params,
            'ws_payload' => $wsPayload,
            'status' => $sent ? PhoneAgentAction::STATUS_SENT : PhoneAgentAction::STATUS_FAILED,
            'result' => [],
            'create_time' => time(),
            'update_time' => time(),
        ]);

        if (!$sent) {
            self::failTask($task, '解析失败后等待动作下发失败');
            PhoneAgentHintService::clear((string)$task->task_id);
            return ['handled' => true, 'event_type' => 'action_send_failed', 'message' => '解析失败后等待动作下发失败'];
        }

        $task->status = PhoneAgentTask::STATUS_WAITING_REPORT;
        $task->save();
        $turn->status = PhoneAgentTurn::STATUS_DISPATCHED;
        $turn->save();

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'action_sent', [
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'action_type' => 'wait',
            'reason' => 'parse_recovery',
        ]);
        self::pushEvent($task, 'action_sent', $event->toArray());

        return ['handled' => true, 'event_type' => 'parse_recovery_wait', 'message' => '解析失败，等待重试'];
    }

    public static function completeTask(PhoneAgentTask $task, string $message = ''): void
    {
        PhoneAgentHintService::clear((string)$task->task_id);
        $task->status = PhoneAgentTask::STATUS_COMPLETED;
        $task->finished_at = time();
        $task->error_msg = '';
        $task->save();
        PhoneAgentConversationService::syncTask($task);

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'task_completed', [
            'message' => $message,
        ]);
        self::pushEvent($task, 'task_completed', $event->toArray());
    }

    public static function failTask(PhoneAgentTask $task, string $message): void
    {
        PhoneAgentHintService::clear((string)$task->task_id);
        $task->status = PhoneAgentTask::STATUS_FAILED;
        $task->error_msg = $message;
        $task->finished_at = time();
        $task->save();
        PhoneAgentConversationService::syncTask($task);

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'task_failed', [
            'message' => $message,
        ]);
        self::pushEvent($task, 'task_failed', $event->toArray());
    }

    public static function cancelTask(PhoneAgentTask $task, string $reason = 'user_cancel'): bool
    {
        if ($task->isFinal()) {
            PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'cancel_ignored', [
                'reason' => 'task_final',
                'status' => (string)$task->status,
            ]);
            return false;
        }

        $sent = PhoneAgentDispatchService::cancel((string)$task->device_code, (int)$task->id, $reason);
        if (!$sent) {
            $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'cancel_failed', [
                'reason' => 'send_failed',
            ]);
            self::pushEvent($task, 'cancel_failed', $event->toArray());
            return false;
        }

        PhoneAgentHintService::clear((string)$task->task_id);
        $task->status = PhoneAgentTask::STATUS_CANCELED;
        $task->error_msg = $reason;
        $task->finished_at = time();
        $task->save();
        PhoneAgentConversationService::syncTask($task);

        PhoneAgentAction::where('task_id', (string)$task->task_id)
            ->where('status', 'in', [PhoneAgentAction::STATUS_PENDING, PhoneAgentAction::STATUS_SENT])
            ->update([
                'status' => PhoneAgentAction::STATUS_CANCELED,
                'update_time' => time(),
            ]);

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'task_canceled', [
            'reason' => $reason,
            'cancel_sent' => $sent,
        ]);
        self::pushEvent($task, 'task_canceled', $event->toArray());
        return $sent;
    }

    private static function failBilling(PhoneAgentTask $task, PhoneAgentTurn $turn, string $message, string $stage): array
    {
        $turn->status = PhoneAgentTurn::STATUS_FAILED;
        $turn->error_msg = $message;
        $turn->charge_error = $message;
        $turn->save();

        $event = PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'billing_failed', [
            'turn_no' => (int)$turn->turn_no,
            'stage' => $stage,
            'message' => $message,
        ]);
        self::pushEvent($task, 'billing_failed', $event->toArray());
        self::failTask($task, $message);

        return ['handled' => true, 'event_type' => 'billing_failed', 'message' => $message];
    }

    private static function pushEvent(PhoneAgentTask $task, string $eventType, array $event): void
    {
        PhoneAgentDispatchService::pushWebEvent((int)$task->user_id, (string)$task->device_code, [
            'event_type' => $eventType,
            'event' => $event,
        ]);
    }

    public static function buildModelMessages(PhoneAgentTask $task, PhoneAgentObservation $observation): array
    {
        $turnNo = (int)$task->current_turn;
        $userText = trim((string)(PhoneAgentTurn::where('task_id', (string)$task->task_id)
            ->where('turn_no', $turnNo)
            ->value('user_text') ?? ''));

        if ($userText === '') {
            $hint = PhoneAgentHintService::takeNextUserHint((string)$task->task_id);
            $screenInfoJson = PhoneAgentMessageBuilder::buildScreenInfoString((string)$observation->current_app);
            $userText = PhoneAgentMessageBuilder::buildUserText(
                $turnNo,
                PhoneAgentMessageBuilder::resolveTaskMessage($task),
                $screenInfoJson,
                $hint
            );
        }

        return self::buildModelMessagesWithUserText($task, $observation, $userText, false);
    }

    public static function buildModelMessagesForLog(PhoneAgentTask $task, PhoneAgentObservation $observation): array
    {
        $turnNo = (int)$task->current_turn;
        $userText = trim((string)(PhoneAgentTurn::where('task_id', (string)$task->task_id)
            ->where('turn_no', $turnNo)
            ->value('user_text') ?? ''));

        if ($userText === '') {
            $screenInfoJson = PhoneAgentMessageBuilder::buildScreenInfoString((string)$observation->current_app);
            $userText = PhoneAgentMessageBuilder::buildUserText(
                $turnNo,
                PhoneAgentMessageBuilder::resolveTaskMessage($task),
                $screenInfoJson
            );
        }

        return self::buildModelMessagesWithUserText($task, $observation, $userText, true);
    }

    private static function buildModelMessagesWithUserText(
        PhoneAgentTask $task,
        PhoneAgentObservation $observation,
        string $userText,
        bool $forLog
    ): array {
        $turnNo = (int)$task->current_turn;
        $messages = [
            [
                'role' => 'system',
                'content' => PhoneAgentMessageBuilder::buildSystemPrompt(),
            ],
        ];
        $messages = array_merge($messages, self::buildHistoricalMessages((string)$task->task_id, $turnNo));

        $screenshotUrl = $forLog
            ? PhoneAgentModelContextService::resolveObservationScreenshot($observation)
            : PhoneAgentModelContextService::formatScreenshotForModelRequest($observation);

        $messages[] = PhoneAgentMessageBuilder::createUserMessage($userText, $screenshotUrl, true);

        return $messages;
    }

    private static function buildHistoricalMessages(string $taskId, int $currentTurnNo): array
    {
        if ($currentTurnNo <= 1) {
            return [];
        }

        $turns = PhoneAgentTurn::where('task_id', $taskId)
            ->where('turn_no', '<', $currentTurnNo)
            ->order('turn_no', 'asc')
            ->select();

        if ($turns->isEmpty()) {
            return [];
        }

        $messages = [];
        foreach ($turns as $turn) {
            $userText = trim((string)($turn->user_text ?? ''));
            if ($userText !== '') {
                $messages[] = PhoneAgentMessageBuilder::buildHistoryUserMessage($userText);
            }

            $assistantContent = trim((string)($turn->assistant_content ?? ''));
            if ($assistantContent !== '') {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $assistantContent,
                ];
            }
        }

        return self::trimHistoricalMessages($messages);
    }

    private static function trimHistoricalMessages(array $messages): array
    {
        if (count($messages) <= self::MAX_HISTORY_MESSAGES) {
            return $messages;
        }

        $pinned = null;
        $rest = [];
        $foundFirstUser = false;
        foreach ($messages as $message) {
            if (!$foundFirstUser && ($message['role'] ?? '') === 'user') {
                $pinned = $message;
                $foundFirstUser = true;
                continue;
            }
            $rest[] = $message;
        }

        $tailBudget = self::MAX_HISTORY_MESSAGES - ($pinned !== null ? 1 : 0);
        if ($tailBudget <= 0) {
            return $pinned !== null ? [$pinned] : array_slice($messages, -self::MAX_HISTORY_MESSAGES);
        }

        $tail = array_slice($rest, -$tailBudget);

        return $pinned !== null ? array_merge([$pinned], $tail) : $tail;
    }

    private static function persistAssistantContent(PhoneAgentTurn $turn, array $modelResponse): void
    {
        $content = PhoneAgentMessageBuilder::createPersistableAssistantContent($modelResponse);
        if ($content !== '') {
            $turn->assistant_content = $content;
        }
    }

    private static function extractTapPoint(array $params): ?array
    {
        if (isset($params['element']) && is_array($params['element']) && count($params['element']) >= 2) {
            return [(int)$params['element'][0], (int)$params['element'][1]];
        }

        if (isset($params['x'], $params['y']) && is_numeric($params['x']) && is_numeric($params['y'])) {
            return [(int)$params['x'], (int)$params['y']];
        }

        return null;
    }

    private static function logModelResponse(PhoneAgentTask $task, array $request, array $response, array $debugContext = []): void
    {
        try {
            $payload = [
                'task_db_id' => (int)$task->id,
                'task_id' => (string)$task->task_id,
                'device_code' => (string)$task->device_code,
                'turn_no' => (int)$task->current_turn,
                'request' => self::safeModelLogData($request),
                'response' => self::safeModelLogData($response),
            ];
            if ($debugContext !== []) {
                //$payload['debug_context'] = self::safeModelLogData($debugContext);
            }
            Log::channel('glm')->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (\Throwable) {
        }
    }
}
