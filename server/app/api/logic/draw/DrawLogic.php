<?php

declare(strict_types=1);

namespace app\api\logic\draw;

use app\common\enum\draw\DrawEnum;
use app\common\logic\BaseLogic;
use app\common\model\draw\DrawAsset;
use app\common\model\draw\DrawConversation;
use app\common\model\draw\DrawMessage;
use app\common\model\draw\DrawTask;
use app\common\service\draw\DrawGenerateService;
use app\common\service\FileService;
use app\common\service\ToolsService;

/**
 * draw API 逻辑层
 */
class DrawLogic extends BaseLogic
{
    /**
     * 提交生图
     */
    public static function generateImage(array $params, int $userId): bool
    {
        return self::generate($params, $userId, DrawEnum::MEDIA_IMAGE);
    }

    /**
     * 提交生视频
     */
    public static function generateVideo(array $params, int $userId): bool
    {
        return self::generate($params, $userId, DrawEnum::MEDIA_VIDEO);
    }

    /**
     * 图片提示词优化
     */
    public static function optimizeImagePrompt(array $params): bool
    {
        return self::optimizePrompt($params, 'image');
    }

    /**
     * 视频提示词优化（
     */
    public static function optimizeVideoPrompt(array $params): bool
    {
        return self::optimizePrompt($params, 'video');
    }

    private static function optimizePrompt(array $params, string $type): bool
    {
        try {
            $keywords = trim((string)($params['keywords'] ?? ''));
            if ($keywords === '') {
                throw new \Exception('请输入提示词');
            }

            $request = ['keywords' => $keywords];
            if (isset($params['sn']) && $params['sn'] !== '' && !is_array($params['sn'])) {
                $request['sn'] = $params['sn'];
            }
            if (isset($params['number']) && $params['number'] !== '' && !is_array($params['number'])) {
                $request['number'] = $params['number'];
            }
            if (isset($params['length']) && $params['length'] !== '' && !is_array($params['length'])) {
                $request['length'] = $params['length'];
            }
            if (!empty($params['task_id']) && !is_array($params['task_id'])) {
                $request['task_id'] = (string)$params['task_id'];
            }

            $coze = ToolsService::Coze();
            $response = $type === 'video'
                ? $coze->optimizeVideoPrompt($request)
                : $coze->optimizeImagePrompt($request);

            if ((int)($response['code'] ?? 0) !== 10000) {
                throw new \Exception(self::stringifyMidMessage($response));
            }

            // 中台 Coze 常见：data.content 为字符串数组，如 ["优化后文案"]
            $content = self::extractOptimizeContent($response['data'] ?? null);
            if ($content === '') {
                throw new \Exception('优化结果为空');
            }

            $taskId = $response['task_id'] ?? ($response['data']['task_id'] ?? '');
            self::$returnData = [
                'content' => $content,
                'task_id' => is_scalar($taskId) ? (string)$taskId : '',
            ];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 兼容：
     * - data.content = "文案"
     * - data.content = ["文案"]   ← 现有 Coze text/title 同款
     * - data 本身是字符串
     */
    private static function extractOptimizeContent(mixed $data): string
    {
        if (is_string($data)) {
            return trim($data);
        }
        if (!is_array($data)) {
            return '';
        }

        $content = $data['content'] ?? null;
        if (is_string($content)) {
            return trim($content);
        }
        if (is_array($content)) {
            foreach ($content as $item) {
                if (is_string($item) && trim($item) !== '') {
                    return trim($item);
                }
            }
        }

        // 兼容少数直接吐 output* 的形态
        foreach (['output', 'output_10', 'output10', 'output_7', 'output7', 'text'] as $key) {
            $val = $data[$key] ?? null;
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
            if (is_array($val)) {
                foreach ($val as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        return trim($item);
                    }
                }
            }
        }

        return '';
    }

    private static function stringifyMidMessage(array $response): string
    {
        foreach (['message', 'msg'] as $key) {
            $val = $response[$key] ?? null;
            if (is_string($val) && $val !== '') {
                return $val;
            }
            if (is_array($val)) {
                $first = reset($val);
                if (is_string($first) && $first !== '') {
                    return $first;
                }
            }
        }
        return '提示词优化失败';
    }

    private static function generate(array $params, int $userId, string $mediaType): bool
    {
        // 会话与用户消息先落库（即使生成失败也保留提问）
        $prompt = (string)($params['prompt'] ?? '');
        $attachments = is_array($params['attachments'] ?? null) ? $params['attachments'] : [];
        $genParams = self::resolveGenParams($params);

        $conversation = self::resolveConversation(
            (int)($params['conversation_id'] ?? 0),
            $userId,
            $mediaType,
            $prompt
        );

        DrawMessage::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $userId,
            'media_type'      => $mediaType,
            'role'            => 'user',
            'content'         => mb_substr($prompt, 0, 2000),
            'attachments'     => $attachments,
            'params'          => $genParams,
            'draw_task_id'    => 0,
        ]);

        [$model] = self::resolveModelScene($params, $mediaType);

        try {
            self::$error = null;
            $service = new DrawGenerateService();
            $task = $service->submit([
                'user_id'    => $userId,
                'media_type' => $mediaType,
                'model'      => $model,
                'model_name' => $params['model_name'] ?? '',
                'prompt'     => $prompt,
                'params'     => $genParams,
                'quantity'   => $params['quantity'] ?? null,
            ]);

            DrawMessage::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $userId,
                'media_type'      => $mediaType,
                'role'            => 'assistant',
                'content'         => '',
                'draw_task_id'    => (int)$task->id,
            ]);

            self::touchConversation($conversation, $prompt);

            self::$returnData = [
                'conversation_id' => $conversation->id,
                'task'            => self::formatTask($task, true),
            ];
            return true;
        } catch (\think\exception\HttpResponseException $e) {
            self::touchConversation($conversation, $prompt);
            $payload = $e->getResponse()->getData();
            $msg = '请求异常';
            if (is_array($payload)) {
                foreach (['msg', 'message', 'error'] as $key) {
                    $raw = $payload[$key] ?? null;
                    if (is_string($raw) || is_int($raw) || is_float($raw)) {
                        $text = trim((string)$raw);
                        if ($text !== '') {
                            $msg = $text;
                            break;
                        }
                    }
                    if (is_array($raw)) {
                        $encoded = json_encode($raw, JSON_UNESCAPED_UNICODE);
                        if (is_string($encoded) && $encoded !== '') {
                            $msg = $encoded;
                            break;
                        }
                    }
                }
            }
            self::setError($msg);
            return false;
        } catch (\Throwable $e) {
            self::touchConversation($conversation, $prompt);
            self::setError($e->getMessage() ?: '生成失败');
            return false;
        }
    }

    /**
     * 会话列表（按媒体类型）
     */
    public static function conversationLists(array $params, int $userId): array
    {
        $mediaType = (string)($params['media_type'] ?? '');
        $query = DrawConversation::where('user_id', $userId);
        if (in_array($mediaType, [DrawEnum::MEDIA_IMAGE, DrawEnum::MEDIA_VIDEO, DrawEnum::MEDIA_PPT], true)) {
            $query->where('media_type', $mediaType);
        }
        $rows = $query->order('update_time', 'desc')->order('id', 'desc')->select();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id'            => $row->id,
                'media_type'    => $row->media_type,
                'title'         => $row->title,
                'last_prompt'   => $row->last_prompt,
                'message_count' => $row->message_count,
                'update_time'   => $row->update_time,
                'create_time'   => $row->create_time,
            ];
        }
        return ['lists' => $list];
    }

    /**
     * 会话详情（消息 + 助手消息关联任务/产物）
     */
    public static function conversationDetail(array $params, int $userId): array
    {
        $id = (int)($params['id'] ?? $params['conversation_id'] ?? 0);
        if ($id <= 0) {
            return [];
        }
        $conversation = DrawConversation::where('id', $id)->where('user_id', $userId)->findOrEmpty();
        if ($conversation->isEmpty()) {
            return [];
        }

        $messages = DrawMessage::where('conversation_id', $id)->order('id', 'asc')->select();
        $list = [];
        foreach ($messages as $msg) {
            $item = [
                'id'          => $msg->id,
                'role'        => $msg->role,
                'content'     => $msg->content,
                'attachments' => $msg->attachments ?: [],
                'params'      => $msg->params ?: [],
                'create_time' => $msg->create_time,
                'task'        => null,
            ];
            if ($msg->role === 'assistant' && $msg->draw_task_id > 0) {
                $task = DrawTask::findOrEmpty($msg->draw_task_id);
                if (!$task->isEmpty()) {
                    $item['task'] = self::formatTask($task, true);
                }
            }
            $list[] = $item;
        }

        return [
            'id'         => $conversation->id,
            'media_type' => $conversation->media_type,
            'title'      => $conversation->title,
            'messages'   => $list,
        ];
    }

    /**
     * 删除会话（连同消息）
     */
    public static function conversationDelete(array $params, int $userId): bool
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            self::setError('缺少会话id');
            return false;
        }
        $conversation = DrawConversation::where('id', $id)->where('user_id', $userId)->findOrEmpty();
        if ($conversation->isEmpty()) {
            self::setError('会话不存在');
            return false;
        }
        DrawConversation::destroy($id);
        DrawMessage::where('conversation_id', $id)->select()->delete();
        return true;
    }

    public static function detail(array $params, int $userId = 0): array
    {
        $taskNo = (string)($params['task_no'] ?? '');
        if ($taskNo === '') {
            return [];
        }
        $query = DrawTask::where('task_no', $taskNo);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }
        $task = $query->findOrEmpty();
        if ($task->isEmpty()) {
            return [];
        }
        return self::formatTask($task, true);
    }

    public static function lists(array $params, int $userId): array
    {
        $page = max(1, (int)($params['page_no'] ?? $params['page'] ?? 1));
        $size = min(50, max(1, (int)($params['page_size'] ?? $params['limit'] ?? 15)));
        $mediaType = (string)($params['media_type'] ?? '');

        $query = DrawTask::where('user_id', $userId);
        if (in_array($mediaType, [DrawEnum::MEDIA_IMAGE, DrawEnum::MEDIA_VIDEO], true)) {
            $query->where('media_type', $mediaType);
        }

        $total = (clone $query)->count();
        $rows = $query->order('id', 'desc')
            ->page($page, $size)
            ->select();

        $list = [];
        foreach ($rows as $task) {
            $list[] = self::formatTask($task, true);
        }

        return [
            'lists' => $list,
            'count' => $total,
            'page_no' => $page,
            'page_size' => $size,
        ];
    }

    /**
     * 中台回调
     */
    public static function notify(array $payload): bool
    {
        try {
            $taskNo = (string)($payload['task_no'] ?? '');
            $midTaskId = (string)($payload['task_id'] ?? $payload['mid_task_id'] ?? '');

            $task = null;
            if ($taskNo !== '') {
                $task = DrawTask::where('task_no', $taskNo)->find();
            }
            if (!$task && $midTaskId !== '') {
                $task = DrawTask::where('mid_task_id', $midTaskId)->find();
            }
            if (!$task) {
                self::setError('任务不存在');
                return false;
            }

            $service = new DrawGenerateService();
            $task = $service->handleMidResult($task, $payload);
            self::$returnData = self::formatTask($task, true);
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 查询并顺带 poll 一次
     */
    public static function getTaskStatus(array $params, int $userId): bool
    {
        $taskNo = (string)($params['task_no'] ?? '');
        if ($taskNo === '') {
            self::setError('task_no 不能为空');
            return false;
        }
        $task = DrawTask::where('task_no', $taskNo)->where('user_id', $userId)->findOrEmpty();
        if ($task->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }

        $service = new DrawGenerateService();
        $task = $service->poll($task);
        self::$returnData = self::formatTask($task, true);
        return true;
    }

    private static function resolveConversation(int $conversationId, int $userId, string $mediaType, string $prompt): DrawConversation
    {
        if ($conversationId > 0) {
            $conversation = DrawConversation::where('id', $conversationId)
                ->where('user_id', $userId)
                ->where('media_type', $mediaType)
                ->findOrEmpty();
            if (!$conversation->isEmpty()) {
                return $conversation;
            }
        }

        return DrawConversation::create([
            'user_id'       => $userId,
            'media_type'    => $mediaType,
            'title'         => mb_substr($prompt !== '' ? $prompt : '未命名会话', 0, 200),
            'last_prompt'   => mb_substr($prompt, 0, 500),
            'message_count' => 0,
        ]);
    }

    private static function touchConversation(DrawConversation $conversation, string $prompt): void
    {
        $conversation->last_prompt = mb_substr($prompt, 0, 500);
        if ($conversation->title === '' || $conversation->title === '未命名会话') {
            $conversation->title = mb_substr($prompt !== '' ? $prompt : '未命名会话', 0, 200);
        }
        $conversation->message_count = DrawMessage::where('conversation_id', $conversation->id)->count();
        $conversation->save();
    }

    /**
     * 解析生成参数：支持 params 为数组或 JSON 字符串
     */
    private static function resolveGenParams(array $params): array
    {
        $raw = $params['params'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        // 兼容扁平传参：去掉业务字段后剩余当生成参数
        $exclude = [
            'prompt',
            'attachments',
            'model',
            'model_name',
            'billing_scene',
            'billing_code',
            'quantity',
            'conversation_id',
            'params',
            'media_type',
        ];
        return array_diff_key($params, array_flip($exclude));
    }

    /**
     * model：优先请求参数，缺省用 config 默认
     *
     * @return array{0:string}
     */
    private static function resolveModelScene(array $params, string $mediaType): array
    {
        $model = trim((string)($params['model'] ?? ''));

        $conf = config('api_tools.draw') ?: [];
        if ($mediaType === DrawEnum::MEDIA_VIDEO) {
            $model = $model !== '' ? $model : (string)($conf['video_default_model'] ?? '');
        } else {
            $model = $model !== '' ? $model : (string)($conf['image_default_model'] ?? '');
        }

        return [$model];
    }

    private static function formatTask(DrawTask $task, bool $withAssets = false): array
    {
        $data = $task->toArray();

        // 对前端隐藏内部字段
        unset($data['notify_url'], $data['mid_raw']);

        $data['submit_time'] = self::formatTaskTime($data['submit_time'] ?? null);
        $data['finished_at'] = self::formatTaskTime($data['finished_at'] ?? null);

        if ($withAssets) {
            $assets = DrawAsset::where('task_id', $task->id)
                ->order('asset_type', 'asc')
                ->order('sort', 'asc')
                ->select()
                ->toArray();
            foreach ($assets as &$asset) {
                $asset['file_full_url'] = $asset['file_url']
                    ? FileService::getFileUrl($asset['file_url'])
                    : '';
            }
            unset($asset);
            $data['assets'] = $assets;
        }
        return $data;
    }

    /**
     * 任务时间字段格式化：unix 秒/毫秒 → Y-m-d H:i:s；已是字符串则原样
     */
    private static function formatTaskTime(mixed $value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        if (is_numeric($value)) {
            $ts = (int)$value;
            if ($ts <= 0) {
                return '';
            }
            if ($ts > 1_000_000_000_000) {
                $ts = (int)floor($ts / 1000);
            }
            return date('Y-m-d H:i:s', $ts);
        }
        return (string)$value;
    }
}
