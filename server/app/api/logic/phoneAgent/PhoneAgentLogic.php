<?php

namespace app\api\logic\phoneAgent;

use app\api\logic\ApiLogic;
use app\common\model\phoneAgent\PhoneAgentAction;
use app\common\model\phoneAgent\PhoneAgentConversation;
use app\common\model\phoneAgent\PhoneAgentEvent;
use app\common\model\phoneAgent\PhoneAgentObservation;
use app\common\model\phoneAgent\PhoneAgentTask;
use app\common\model\phoneAgent\PhoneAgentTurn;
use app\common\model\sv\SvDevice;
use app\common\service\phoneAgent\PhoneAgentBillingService;
use app\common\service\phoneAgent\PhoneAgentConversationService;
use app\common\service\phoneAgent\PhoneAgentDispatchService;
use app\common\service\phoneAgent\PhoneAgentPlanningService;
use app\common\service\phoneAgent\PhoneAgentStateService;
use think\facade\Db;

class PhoneAgentLogic extends ApiLogic
{
    private const REPLY_MODE = 'agent_task';

    public static function devices(int $userId): array
    {
        $devices = SvDevice::where('user_id', $userId)
            ->field('id,user_id,device_code,device_model,status,sdk_version,create_time,update_time')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        return array_map(static function (array $device): array {
            $deviceCode = (string)($device['device_code'] ?? '');
            $isOnline = PhoneAgentDispatchService::isDeviceOnline($deviceCode);
            return [
                'device_code' => $deviceCode,
                'device_name' => $device['device_name'] ?? $deviceCode,
                'device_model' => (string)($device['device_model'] ?? ''),
                'online' => $isOnline,
                'status' => $isOnline ? 1 : 0,
                'last_heartbeat' => self::lastHeartbeat($deviceCode),
                'capabilities' => [
                    'observe',
                    'execute',
                    'cancel',
                    'screenshot',
                    'ocr',
                    'accessibility_tree',
                ],
            ];
        }, $devices);
    }

    public static function analyze(array $params, int $userId): array|false
    {
        try {
            $message = trim((string)$params['message']);
            $analyzeModel = trim((string)($params['analyze_model'] ?? ''));
            $lang = trim((string)($params['lang'] ?? 'cn')) ?: 'cn';

            PhoneAgentBillingService::checkBalanceByModel(
                $userId,
                $analyzeModel !== '' ? $analyzeModel : PhoneAgentPlanningService::defaultAnalyzeModel()
            );

            $result = PhoneAgentPlanningService::prepareExecutionTask(
                $message,
                $userId,
                $analyzeModel !== '' ? $analyzeModel : null,
                $lang,
                true
            );

            if (!$result['ok']) {
                self::setError((string)($result['error'] ?? '任务规划失败'));
                return false;
            }

            return [
                'analysis' => is_array($result['analysis'] ?? null) ? $result['analysis'] : [],
                'display' => (string)($result['display'] ?? ''),
                'execution_message' => (string)($result['execution_message'] ?? ''),
                'analyze_model' => (string)($result['analyze_model'] ?? PhoneAgentPlanningService::defaultAnalyzeModel()),
                'raw' => (string)($result['raw'] ?? ''),
            ];
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function dispatch(array $params, int $userId): bool
    {
        try {
            $deviceCode = trim((string)$params['device_code']);
            $device = self::userDevice($deviceCode, $userId);
            if ($device === null) {
                self::setError('设备不存在或无权限');
                return false;
            }

            PhoneAgentBillingService::checkBalanceByModel($userId, (string)($params['model'] ?? ''));

            $message = trim((string)$params['message']);
            $model = trim((string)($params['model'] ?? '')) ?: 'autoglm-phone';
            $skipAnalyze = (int)($params['skip_analyze'] ?? 0) === 1;
            $executionMessage = trim((string)($params['execution_message'] ?? ''));
            $analyzeModel = trim((string)($params['analyze_model'] ?? ''));
            $lang = trim((string)($params['lang'] ?? 'cn')) ?: 'cn';
            $now = time();
            $taskId = generate_unique_task_id();

            $plan = PhoneAgentPlanningService::resolvePlanForDispatch(
                $message,
                $userId,
                $executionMessage,
                $skipAnalyze,
                $analyzeModel !== '' ? $analyzeModel : null,
                $lang
            );

            Db::startTrans();
            try {
                $conversation = PhoneAgentConversationService::prepareForDispatch(
                    $userId,
                    $deviceCode,
                    $message,
                    (string)($params['conversation_id'] ?? '')
                );

                $task = PhoneAgentTask::create([
                    'task_id' => $taskId,
                    'conversation_id' => (string)$conversation->conversation_id,
                    'user_id' => $userId,
                    'device_code' => $deviceCode,
                    'message' => $message,
                    'execution_message' => (string)$plan['execution_message'],
                    'plan_json' => (string)$plan['plan_json'],
                    'plan_display' => (string)$plan['plan_display'],
                    'analyze_model' => (string)$plan['analyze_model'],
                    'plan_status' => (string)$plan['plan_status'],
                    'model' => $model,
                    'status' => PhoneAgentTask::STATUS_CREATED,
                    'current_turn' => 1,
                    'error_msg' => '',
                    'started_at' => $now,
                    'finished_at' => 0,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                PhoneAgentConversationService::markTaskCreated($conversation, $task);
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }

            if (!PhoneAgentDispatchService::isDeviceOnline($deviceCode)) {
                PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'device_offline', [
                    'message' => '设备不在线',
                ]);
                PhoneAgentStateService::failTask($task, '设备不在线');
                self::$returnData = self::formatTask($task->refresh());
                self::setError('设备不在线');
                return false;
            }

            PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'task_created', [
                'message' => (string)$task->message,
            ]);

            if ((string)$task->plan_status !== PhoneAgentPlanningService::PLAN_STATUS_SKIPPED) {
                PhoneAgentDispatchService::createEvent($taskId, $deviceCode, 'plan_ready', [
                    'plan_display' => (string)$task->plan_display,
                    'execution_message' => (string)$task->execution_message,
                    'analyze_model' => (string)$task->analyze_model,
                    'plan_status' => (string)$task->plan_status,
                ]);
            }

            PhoneAgentStateService::start($task);
            self::$returnData = self::formatTask($task->refresh());
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params, int $userId): array|false
    {
        $task = self::userTask((string)$params['task_id'], $userId);
        if ($task === null) {
            self::setError('任务不存在或无权限');
            return false;
        }

        $taskId = (string)$task->task_id;
        $turns = PhoneAgentTurn::where('task_id', $taskId)->order('turn_no', 'asc')->select()->toArray();
        $actions = PhoneAgentAction::where('task_id', $taskId)->order('id', 'asc')->select()->toArray();
        $observations = PhoneAgentObservation::where('task_id', $taskId)->order('id', 'asc')->select()->toArray();
        $events = PhoneAgentEvent::where('task_id', $taskId)->order('id', 'asc')->limit(100)->select()->toArray();

        return [
            'task' => self::formatTask($task),
            'turns' => $turns,
            'actions' => $actions,
            'observations' => $observations,
            'events' => $events,
            'messages' => self::buildDetailMessages($task, $turns, $actions, $observations, $events),
        ];
    }

    public static function conversationDetail(array $params, int $userId): array|false
    {
        $conversation = PhoneAgentConversationService::userConversation((string)$params['conversation_id'], $userId);
        if ($conversation === null) {
            self::setError('会话不存在或无权限');
            return false;
        }

        $tasks = PhoneAgentTask::where('conversation_id', (string)$conversation->conversation_id)
            ->where('user_id', $userId)
            ->order('id', 'asc')
            ->select();

        $formattedTasks = [];
        $messages = [];
        foreach ($tasks as $task) {
            $taskId = (string)$task->task_id;
            $turns = PhoneAgentTurn::where('task_id', $taskId)->order('turn_no', 'asc')->select()->toArray();
            $actions = PhoneAgentAction::where('task_id', $taskId)->order('id', 'asc')->select()->toArray();
            $observations = PhoneAgentObservation::where('task_id', $taskId)->order('id', 'asc')->select()->toArray();
            $events = PhoneAgentEvent::where('task_id', $taskId)->order('id', 'asc')->limit(100)->select()->toArray();

            $formattedTasks[] = self::formatTask($task);
            $messages = array_merge(
                $messages,
                self::buildDetailMessages($task, $turns, $actions, $observations, $events)
            );
        }

        $conversationDevices = PhoneAgentConversationService::devicesByConversationIds(
            [(string)$conversation->conversation_id],
            $userId
        );

        return [
            'conversation' => self::formatConversation(
                $conversation,
                $conversationDevices[(string)$conversation->conversation_id] ?? []
            ),
            'tasks' => $formattedTasks,
            'messages' => self::sortMessages($messages),
        ];
    }

    public static function events(array $params, int $userId): array|false
    {
        $task = self::userTask((string)$params['task_id'], $userId);
        if ($task === null) {
            self::setError('任务不存在或无权限');
            return false;
        }

        $lastId = max(0, (int)($params['last_id'] ?? 0));
        $limit = (int)($params['limit'] ?? 50);
        $limit = min(max($limit, 1), 200);

        $lists = PhoneAgentEvent::where('task_id', (string)$task->task_id)
            ->where('id', '>', $lastId)
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();

        return [
            'lists' => $lists,
            'last_id' => empty($lists) ? $lastId : (int)end($lists)['id'],
            'task_status' => (string)$task->status,
            'messages' => self::buildEventMessages($task, $lists),
        ];
    }

    public static function deleteConversation(array $params, int $userId): bool
    {
        try {
            PhoneAgentConversationService::deleteConversation((string)$params['conversation_id'], $userId);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function cancel(array $params, int $userId): bool
    {
        try {
            $task = self::userTask((string)$params['task_id'], $userId);
            if ($task === null) {
                self::setError('任务不存在或无权限');
                return false;
            }

            if ($task->isFinal()) {
                PhoneAgentStateService::cancelTask($task, 'user_cancel');
                self::$returnData = self::formatTask($task->refresh());
                return true;
            }

            if (!PhoneAgentDispatchService::isDeviceOnline((string)$task->device_code)) {
                PhoneAgentDispatchService::createEvent((string)$task->task_id, (string)$task->device_code, 'cancel_failed', [
                    'reason' => 'device_offline',
                ]);
                self::$returnData = self::formatTask($task->refresh());
                self::setError('设备不在线');
                return false;
            }

            $sent = PhoneAgentStateService::cancelTask($task, 'user_cancel');
            if (!$sent) {
                self::$returnData = self::formatTask($task->refresh());
                self::setError('取消指令下发失败');
                return false;
            }

            self::$returnData = self::formatTask($task->refresh());
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function userDevice(string $deviceCode, int $userId): ?SvDevice
    {
        $device = SvDevice::where('device_code', $deviceCode)
            ->where('user_id', $userId)
            ->findOrEmpty();
        return $device->isEmpty() ? null : $device;
    }

    private static function userTask(string $taskId, int $userId): ?PhoneAgentTask
    {
        $task = PhoneAgentTask::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->findOrEmpty();
        return $task->isEmpty() ? null : $task;
    }

    private static function formatTask(PhoneAgentTask $task): array
    {
        $conversationId = self::conversationId($task);
        return [
            'id' => (int)$task->id,
            'task_id' => (string)$task->task_id,
            'user_id' => (int)$task->user_id,
            'device_code' => (string)$task->device_code,
            'message' => (string)$task->message,
            'execution_message' => (string)($task->execution_message ?? ''),
            'plan_display' => (string)($task->plan_display ?? ''),
            'plan_status' => (string)($task->plan_status ?? ''),
            'analyze_model' => (string)($task->analyze_model ?? ''),
            'model' => (string)$task->model,
            'status' => (string)$task->status,
            'current_turn' => (int)$task->current_turn,
            'error_msg' => (string)$task->error_msg,
            'started_at' => self::formatTime($task->started_at),
            'finished_at' => self::formatTime($task->finished_at),
            'create_time' => self::formatTime($task->create_time),
            'update_time' => self::formatTime($task->update_time),
            'conversation_id' => $conversationId,
            'message_id' => self::userMessageId($task),
            'reply_mode' => self::REPLY_MODE,
            'last_message' => self::taskLastMessage($task),
        ];
    }

    private static function formatConversation(PhoneAgentConversation $conversation, array $devices = []): array
    {
        $status = (string)$conversation->last_task_status;
        return [
            'id' => (int)$conversation->id,
            'conversation_id' => (string)$conversation->conversation_id,
            'user_id' => (int)$conversation->user_id,
            'device_code' => (string)$conversation->device_code,
            'title' => (string)$conversation->title,
            'last_message' => (string)$conversation->last_message,
            'last_task_id' => (string)$conversation->last_task_id,
            'task_count' => (int)$conversation->task_count,
            'last_task_status' => $status,
            'status_text' => PhoneAgentConversationService::statusText($status),
            'context_summary' => (string)$conversation->context_summary,
            'devices' => $devices,
            'device_count' => count($devices),
            'create_time' => self::formatTime($conversation->create_time),
            'update_time' => self::formatTime($conversation->update_time),
        ];
    }

    private static function buildDetailMessages(
        PhoneAgentTask $task,
        array $turns,
        array $actions,
        array $observations,
        array $events
    ): array {
        $messages = [
            self::makeMessage(
                self::userMessageId($task),
                'user',
                'text',
                (string)$task->message,
                'success',
                0,
                0,
                $task->started_at ?: $task->create_time,
                [
                    'task_id' => (string)$task->task_id,
                    'conversation_id' => self::conversationId($task),
                    'device_code' => (string)$task->device_code,
                ]
            ),
        ];

        $turnNoById = [];
        foreach ($turns as $turn) {
            $turnNoById[(int)$turn['id']] = (int)$turn['turn_no'];
            $message = self::turnToMessage($task, $turn);
            if ($message !== null) {
                $messages[] = $message;
            }
        }

        foreach ($actions as $action) {
            $messages[] = self::actionToMessage($task, $action, $turnNoById[(int)$action['turn_id']] ?? 0);
        }

        foreach ($observations as $observation) {
            $messages[] = self::observationToMessage($task, $observation, self::observationTurnNo($observation, $turnNoById));
        }

        $observationMap = self::mapById($observations);
        $actionMap = self::buildActionMap($actions, $turnNoById);
        foreach ($events as $event) {
            $message = self::eventToMessage($task, $event, $observationMap, $actionMap, true);
            if ($message !== null) {
                $messages[] = $message;
            }
        }

        return self::sortMessages($messages);
    }

    private static function buildEventMessages(PhoneAgentTask $task, array $events): array
    {
        if (empty($events)) {
            return [];
        }

        $observationMap = self::loadObservationMapByEvents($task, $events);
        $actionMap = self::loadActionMapByEvents($task, $events);
        $messages = [];
        foreach ($events as $event) {
            $message = self::eventToMessage($task, $event, $observationMap, $actionMap);
            if ($message !== null) {
                $messages[] = $message;
            }
        }

        return self::sortMessages($messages);
    }

    private static function turnToMessage(PhoneAgentTask $task, array $turn): ?array
    {
        $content = self::extractModelContent($turn['model_response'] ?? []);
        $parsedAction = is_array($turn['parsed_action'] ?? null) ? $turn['parsed_action'] : [];
        if ($content === '' && !empty($parsedAction)) {
            $content = self::parsedActionContent($parsedAction);
        }
        if ($content === '' && (string)($turn['error_msg'] ?? '') !== '') {
            $content = (string)$turn['error_msg'];
        }
        if ($content === '') {
            return null;
        }

        $status = self::turnMessageStatus((string)($turn['status'] ?? ''));
        return self::makeMessage(
            'turn:' . (int)$turn['id'] . ':assistant',
            'assistant',
            !empty($parsedAction) ? 'action' : ($status === 'failed' ? 'error' : 'text'),
            self::limitText($content),
            $status,
            (int)($turn['turn_no'] ?? 0),
            0,
            $turn['update_time'] ?? $turn['create_time'] ?? 0,
            [
                'task_id' => (string)$task->task_id,
                'conversation_id' => self::conversationId($task),
                'device_code' => (string)$task->device_code,
                'parsed_action' => $parsedAction,
                'usage' => is_array($turn['usage'] ?? null) ? $turn['usage'] : [],
            ]
        );
    }

    private static function actionToMessage(PhoneAgentTask $task, array $action, int $turnNo): array
    {
        $params = is_array($action['action_payload'] ?? null) ? $action['action_payload'] : [];
        $actionType = (string)($action['action_type'] ?? '');

        return self::makeMessage(
            'action:' . (int)$action['id'],
            'tool',
            'action',
            self::actionContent($actionType, $params),
            self::actionMessageStatus((string)($action['status'] ?? '')),
            $turnNo,
            (int)($action['action_no'] ?? 0),
            $action['create_time'] ?? 0,
            [
                'task_id' => (string)$task->task_id,
                'conversation_id' => self::conversationId($task),
                'device_code' => (string)$task->device_code,
                'action_type' => $actionType,
                'params' => $params,
                'raw_action' => $action,
            ]
        );
    }

    private static function observationToMessage(PhoneAgentTask $task, array $observation, int $turnNo): array
    {
        return self::makeMessage(
            'observation:' . (int)$observation['id'],
            'device',
            'observation',
            self::observationContent($observation),
            'success',
            $turnNo,
            0,
            $observation['create_time'] ?? 0,
            [
                'task_id' => (string)$task->task_id,
                'conversation_id' => self::conversationId($task),
                'device_code' => (string)$task->device_code,
                'screenshot' => (string)($observation['screenshot'] ?? ''),
                'ocr_text' => (string)($observation['ocr_text'] ?? ''),
                'current_app' => (string)($observation['current_app'] ?? ''),
            ]
        );
    }

    private static function eventToMessage(
        PhoneAgentTask $task,
        array $event,
        array $observationMap = [],
        array $actionMap = [],
        bool $detailMode = false
    ): ?array {
        $eventType = (string)($event['event_type'] ?? '');
        if ($detailMode && in_array($eventType, ['task_created', 'action_sent', 'observation_received', 'action_success'], true)) {
            return null;
        }

        $eventData = is_array($event['event_data'] ?? null) ? $event['event_data'] : [];
        $turnNo = (int)($eventData['turn_no'] ?? 0);
        $actionNo = (int)($eventData['action_no'] ?? 0);

        if ($eventType === 'observation_received' && isset($eventData['observation_id'], $observationMap[(int)$eventData['observation_id']])) {
            return self::observationToMessage($task, $observationMap[(int)$eventData['observation_id']], $turnNo);
        }

        if ($eventType === 'action_sent') {
            $key = $turnNo . ':' . $actionNo;
            if (isset($actionMap[$key])) {
                return self::actionToMessage($task, $actionMap[$key], $turnNo);
            }
        }

        if (in_array($eventType, ['action_success', 'action_failed'], true)) {
            $content = $eventType === 'action_success' ? '手机端动作执行成功' : '手机端动作执行失败';
            if ((string)($eventData['message'] ?? '') !== '') {
                $content .= '：' . (string)$eventData['message'];
            }
            return self::makeMessage(
                'event:' . (int)$event['id'],
                'device',
                $eventType === 'action_success' ? 'status' : 'error',
                $content,
                $eventType === 'action_success' ? 'success' : 'failed',
                $turnNo,
                $actionNo,
                $event['create_time'] ?? 0,
                self::eventExtra($task, $eventType, $eventData)
            );
        }

        [$role, $type, $status, $content] = self::eventMessageMeta($eventType, $eventData);
        return self::makeMessage(
            'event:' . (int)$event['id'],
            $role,
            $type,
            $content,
            $status,
            $turnNo,
            $actionNo,
            $event['create_time'] ?? 0,
            self::eventExtra($task, $eventType, $eventData)
        );
    }

    private static function eventMessageMeta(string $eventType, array $eventData): array
    {
        $message = (string)($eventData['message'] ?? '');
        $reason = (string)($eventData['reason'] ?? '');
        if ($eventType === 'plan_ready') {
            $planDisplay = trim((string)($eventData['plan_display'] ?? ''));
            $content = $planDisplay !== '' ? $planDisplay : '任务规划已完成';
            return ['system', 'status', 'success', $content];
        }

        $contentMap = [
            'task_created' => '任务已创建',
            'device_offline' => '设备不在线',
            'observe_sent' => '已请求手机观察当前页面',
            'observe_send_failed' => '观察指令下发失败',
            'observation_received' => '手机观察已返回',
            'observe_failed' => '手机观察失败',
            'model_failed' => '模型调用失败',
            'model_parse_failed' => '模型响应解析失败',
            'action_sent' => '动作已下发',
            'action_send_failed' => '执行指令下发失败',
            'task_failed' => '任务失败',
            'task_completed' => '任务完成',
            'task_canceled' => '任务已取消',
            'plan_ready' => '任务规划已完成',
            'cancel_failed' => '取消失败',
            'cancel_ignored' => '取消请求已忽略',
            'late_report' => '收到迟到上报，已记录但不推进任务',
            'ignored_report' => '收到不匹配上报，已忽略',
            'unknown_task_report' => '收到未知任务上报',
        ];

        $content = $contentMap[$eventType] ?? ('任务事件：' . $eventType);
        if ($message !== '') {
            $content .= '：' . $message;
        } elseif ($reason !== '') {
            $content .= '：' . $reason;
        }

        $failed = str_contains($eventType, 'failed') || in_array($eventType, ['device_offline', 'observe_send_failed', 'cancel_failed'], true);
        $canceled = in_array($eventType, ['task_canceled', 'cancel_ignored'], true);
        $assistant = in_array($eventType, ['model_failed', 'model_parse_failed', 'task_failed', 'task_completed'], true);
        $device = in_array($eventType, ['late_report', 'ignored_report', 'unknown_task_report', 'observe_failed'], true);

        return [
            $assistant ? 'assistant' : ($device ? 'device' : 'system'),
            $failed ? 'error' : 'status',
            $failed ? 'failed' : ($canceled ? 'canceled' : 'success'),
            $content,
        ];
    }

    private static function eventExtra(PhoneAgentTask $task, string $eventType, array $eventData): array
    {
        return [
            'task_id' => (string)$task->task_id,
            'conversation_id' => self::conversationId($task),
            'device_code' => (string)$task->device_code,
            'event_type' => $eventType,
            'raw_event' => self::safeMessageData($eventData),
        ];
    }

    private static function makeMessage(
        string $id,
        string $role,
        string $type,
        string $content,
        string $status,
        int $turnNo,
        int $actionNo,
        mixed $createTime,
        array $extra = []
    ): array {
        return [
            'id' => $id,
            'role' => $role,
            'type' => $type,
            'content' => $content,
            'status' => $status,
            'turn_no' => $turnNo,
            'action_no' => $actionNo,
            'create_time' => self::formatTime($createTime),
            'extra' => $extra,
        ];
    }

    private static function sortMessages(array $messages): array
    {
        foreach ($messages as $index => &$message) {
            $message['_sort_time'] = self::timeValue($message['create_time'] ?? 0);
            $message['_sort_index'] = $index;
        }
        unset($message);

        usort($messages, static function (array $left, array $right): int {
            $timeCompare = ((int)$left['_sort_time']) <=> ((int)$right['_sort_time']);
            if ($timeCompare !== 0) {
                return $timeCompare;
            }
            return ((int)$left['_sort_index']) <=> ((int)$right['_sort_index']);
        });

        foreach ($messages as &$message) {
            unset($message['_sort_time'], $message['_sort_index']);
        }
        unset($message);

        return $messages;
    }

    private static function mapById(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private static function buildActionMap(array $actions, array $turnNoById): array
    {
        $map = [];
        foreach ($actions as $action) {
            $turnNo = $turnNoById[(int)$action['turn_id']] ?? 0;
            $map[$turnNo . ':' . (int)$action['action_no']] = $action;
        }
        return $map;
    }

    private static function loadObservationMapByEvents(PhoneAgentTask $task, array $events): array
    {
        $ids = [];
        foreach ($events as $event) {
            $eventData = is_array($event['event_data'] ?? null) ? $event['event_data'] : [];
            if ((int)($eventData['observation_id'] ?? 0) > 0) {
                $ids[] = (int)$eventData['observation_id'];
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return [];
        }

        $rows = PhoneAgentObservation::where('task_id', (string)$task->task_id)
            ->where('id', 'in', $ids)
            ->select()
            ->toArray();
        return self::mapById($rows);
    }

    private static function loadActionMapByEvents(PhoneAgentTask $task, array $events): array
    {
        $needsAction = false;
        foreach ($events as $event) {
            if ((string)($event['event_type'] ?? '') === 'action_sent') {
                $needsAction = true;
                break;
            }
        }
        if (!$needsAction) {
            return [];
        }

        $turns = PhoneAgentTurn::where('task_id', (string)$task->task_id)
            ->field('id,turn_no')
            ->select()
            ->toArray();
        $turnNoById = [];
        foreach ($turns as $turn) {
            $turnNoById[(int)$turn['id']] = (int)$turn['turn_no'];
        }

        $actions = PhoneAgentAction::where('task_id', (string)$task->task_id)
            ->select()
            ->toArray();
        return self::buildActionMap($actions, $turnNoById);
    }

    private static function observationTurnNo(array $observation, array $turnNoById): int
    {
        return $turnNoById[(int)($observation['turn_id'] ?? 0)] ?? 0;
    }

    private static function extractModelContent(array $response): string
    {
        $message = $response['choices'][0]['message'] ?? [];
        if (!is_array($message)) {
            return '';
        }

        $content = $message['content'] ?? '';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $content = trim((string)$content);
        if ($content !== '') {
            return $content;
        }

        $toolCalls = $message['tool_calls'] ?? [];
        if (is_array($toolCalls) && !empty($toolCalls)) {
            $first = $toolCalls[0];
            $function = is_array($first) ? ($first['function'] ?? []) : [];
            $name = is_array($function) ? (string)($function['name'] ?? '') : '';
            return $name !== '' ? '模型选择动作：' . $name : '模型已返回工具调用';
        }

        return '';
    }

    private static function parsedActionContent(array $parsedAction): string
    {
        if ((bool)($parsedAction['finish'] ?? false)) {
            return (string)($parsedAction['message'] ?? '任务完成');
        }

        $actionType = (string)($parsedAction['action_type'] ?? '');
        $params = is_array($parsedAction['params'] ?? null) ? $parsedAction['params'] : [];
        return $actionType !== '' ? ('模型已解析动作：' . self::actionContent($actionType, $params)) : '模型已返回动作';
    }

    private static function actionContent(string $actionType, array $params): string
    {
        return match ($actionType) {
            'launch' => '启动应用：' . (string)($params['app_name'] ?? ($params['app'] ?? '')),
            'tap' => '点击坐标：' . self::pointText($params),
            'double_tap' => '双击坐标：' . self::pointText($params),
            'long_press' => '长按坐标：' . self::pointText($params),
            'type' => '输入文本：' . (string)($params['text'] ?? ''),
            'swipe' => '滑动屏幕：' . self::swipeText($params),
            'wait' => '等待 ' . max(1, (int)($params['seconds'] ?? 1)) . ' 秒',
            'back' => '返回上一页',
            'home' => '返回桌面',
            'take_over' => '请求人工接管',
            default => '执行动作：' . $actionType,
        };
    }

    private static function observationContent(array $observation): string
    {
        $parts = ['手机观察已返回'];
        $currentApp = (string)($observation['current_app'] ?? '');
        if ($currentApp !== '') {
            $parts[] = '当前应用：' . $currentApp;
        }
        $ocrText = trim((string)($observation['ocr_text'] ?? ''));
        if ($ocrText !== '') {
            $parts[] = 'OCR：' . self::limitText($ocrText, 120);
        }
        return implode('；', $parts);
    }

    private static function pointText(array $params): string
    {
        if (isset($params['x'], $params['y'])) {
            return '(' . (int)$params['x'] . ', ' . (int)$params['y'] . ')';
        }
        if (isset($params['element']) && is_array($params['element']) && count($params['element']) >= 2) {
            return '(' . (int)$params['element'][0] . ', ' . (int)$params['element'][1] . ')';
        }
        return '未知坐标';
    }

    private static function swipeText(array $params): string
    {
        if (isset($params['start_x'], $params['start_y'], $params['end_x'], $params['end_y'])) {
            return '(' . (int)$params['start_x'] . ', ' . (int)$params['start_y'] . ') -> (' . (int)$params['end_x'] . ', ' . (int)$params['end_y'] . ')';
        }
        if (isset($params['start'], $params['end']) && is_array($params['start']) && is_array($params['end'])) {
            return '(' . (int)$params['start'][0] . ', ' . (int)$params['start'][1] . ') -> (' . (int)$params['end'][0] . ', ' . (int)$params['end'][1] . ')';
        }
        return '未知滑动路径';
    }

    private static function turnMessageStatus(string $status): string
    {
        return match ($status) {
            PhoneAgentTurn::STATUS_CREATED => 'pending',
            PhoneAgentTurn::STATUS_MODEL_PENDING => 'running',
            PhoneAgentTurn::STATUS_FAILED => 'failed',
            default => 'success',
        };
    }

    private static function actionMessageStatus(string $status): string
    {
        return match ($status) {
            PhoneAgentAction::STATUS_PENDING => 'pending',
            PhoneAgentAction::STATUS_SENT => 'running',
            PhoneAgentAction::STATUS_SUCCESS => 'success',
            PhoneAgentAction::STATUS_CANCELED => 'canceled',
            default => 'failed',
        };
    }

    private static function taskLastMessage(PhoneAgentTask $task): string
    {
        return match ((string)$task->status) {
            PhoneAgentTask::STATUS_CREATED => '任务已创建',
            PhoneAgentTask::STATUS_OBSERVING => '正在观察手机页面',
            PhoneAgentTask::STATUS_MODEL_PENDING => '正在请求模型决策',
            PhoneAgentTask::STATUS_DISPATCHING => '正在下发手机动作',
            PhoneAgentTask::STATUS_WAITING_REPORT => '等待手机执行结果',
            PhoneAgentTask::STATUS_COMPLETED => '任务已完成',
            PhoneAgentTask::STATUS_FAILED => (string)$task->error_msg !== '' ? (string)$task->error_msg : '任务失败',
            PhoneAgentTask::STATUS_CANCELED => '任务已取消',
            default => (string)$task->status,
        };
    }

    private static function userMessageId(PhoneAgentTask $task): string
    {
        return 'task:' . (string)$task->task_id . ':user';
    }

    private static function conversationId(PhoneAgentTask $task): string
    {
        return PhoneAgentConversationService::taskConversationId($task);
    }

    private static function formatTime(mixed $value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        if (is_numeric($value)) {
            return (int)$value > 0 ? date('Y-m-d H:i:s', (int)$value) : '';
        }
        $timestamp = strtotime((string)$value);
        return $timestamp === false ? (string)$value : date('Y-m-d H:i:s', $timestamp);
    }

    private static function timeValue(mixed $time): int
    {
        if (is_numeric($time)) {
            return (int)$time;
        }
        if (is_string($time) && $time !== '') {
            $timestamp = strtotime($time);
            return $timestamp === false ? 0 : $timestamp;
        }
        return 0;
    }

    private static function limitText(string $text, int $limit = 500): string
    {
        if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
            return strlen($text) <= $limit ? $text : substr($text, 0, $limit) . '...';
        }
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . '...';
    }

    private static function safeMessageData(mixed $data): mixed
    {
        if (is_array($data)) {
            $safe = [];
            foreach ($data as $key => $value) {
                $safe[$key] = self::safeMessageData($value);
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

    private static function lastHeartbeat(string $deviceCode): string
    {
        try {
            $redis = \think\facade\Cache::store('redis');
            $redis->select((int)env('redis.WS_SELECT', 8));
            $heart = $redis->get("xhs:device:{$deviceCode}:heart");
            if ($heart) {
                return (string)$heart;
            }
            $onlineTime = $redis->get("xhs:device:{$deviceCode}:onlinetime");
            return $onlineTime ? (string)$onlineTime : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
