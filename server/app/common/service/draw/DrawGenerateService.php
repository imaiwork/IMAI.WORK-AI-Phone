<?php

declare(strict_types=1);

namespace app\common\service\draw;

use app\common\enum\draw\DrawEnum;
use app\common\model\draw\DrawTask;
use think\facade\Log;

/**
 * draw 生成编排：建单 → 扣费 → 提交中台 → 回调/轮询收敛 → 落盘
 */
class DrawGenerateService
{
    private DrawBillingService $billing;
    private MidPlatformDrawClient $midClient;
    private DrawAssetService $assetService;

    public function __construct(
        ?DrawBillingService $billing = null,
        ?MidPlatformDrawClient $midClient = null,
        ?DrawAssetService $assetService = null
    ) {
        $this->billing = $billing ?? new DrawBillingService();
        $this->midClient = $midClient ?? new MidPlatformDrawClient();
        $this->assetService = $assetService ?? new DrawAssetService();
    }

    /**
     * 提交生成
     *
     * 必填：user_id, media_type(image|video), model
     * 可选：prompt, params, model_name, quantity(可从 params.n/duration 推断),
     *      bill_timing(prehold|on_success，默认 prehold；PPT 用 on_success)
     * 计费：按 la_models_cost.model_price；视频预扣后按实际秒数多退少补
     *
     * @throws \Exception
     */
    public function submit(array $input): DrawTask
    {
        $userId = (int)($input['user_id'] ?? 0);
        $mediaType = (string)($input['media_type'] ?? '');
        $model = trim((string)($input['model'] ?? ''));
        $params = $input['params'] ?? [];
        if (!is_array($params)) {
            $params = [];
        }

        if ($userId <= 0) {
            throw new \Exception('用户无效');
        }
        if (!in_array($mediaType, [DrawEnum::MEDIA_IMAGE, DrawEnum::MEDIA_VIDEO], true)) {
            throw new \Exception('media_type 仅支持 image|video');
        }
        if ($model === '') {
            throw new \Exception('model 不能为空');
        }

        $params = $this->normalizeGenerationParams($params);

        $prompt = $this->stringifyScalar($input['prompt'] ?? ($params['prompt'] ?? ''));
        $taskNo = $this->generateTaskNo();
        $quantity = array_key_exists('quantity', $input) && $input['quantity'] !== null && $input['quantity'] !== ''
            ? (float)$input['quantity']
            : $this->billing->resolveQuantity($params, $mediaType);

        $quote = $this->billing->quoteByModel($model, $mediaType, $quantity);
        $billingCode = (int)($input['billing_code'] ?? 0) ?: $quote['code'];
        // 提交中台用技术 alias
        $modelAlias = $quote['alias'] !== '' ? $quote['alias'] : $model;

        $billTiming = (string)($input['bill_timing'] ?? 'prehold');
        if (!in_array($billTiming, ['prehold', 'on_success'], true)) {
            $billTiming = 'prehold';
        }

        $billSnapshot = [
            'model'        => $modelAlias,
            'unit'         => $quote['unit'],
            'quantity'     => $quote['quantity'],
            'cost'         => $quote['cost'],
            'code'         => $billingCode,
            'name'         => $quote['name'],
            'model_sub_id' => $quote['model_sub_id'],
            'bill_timing'  => $billTiming,
        ];
        if ($mediaType === DrawEnum::MEDIA_IMAGE) {
            $billSnapshot['image_count'] = (int)$quote['quantity'];
        }

        $task = DrawTask::create([
            'task_no'       => $taskNo,
            'user_id'       => $userId,
            'media_type'    => $mediaType,
            'model'         => $modelAlias,
            'model_name'    => $this->stringifyScalar($input['model_name'] ?? $quote['name']),
            'prompt'        => $prompt,
            'params'        => $params,
            'status'        => DrawEnum::STATUS_PENDING,
            'billing_scene' => '',
            'billing_code'  => $billingCode,
            'tokens_cost'   => $billTiming === 'on_success' ? 0 : $quote['cost'],
            'bill_status'   => DrawEnum::BILL_NONE,
            'bill_snapshot' => $billSnapshot,
        ]);

        // 默认预扣；PPT 等 on_success 模式等到落盘成功再扣
        if ($billTiming === 'prehold') {
            $tokensLogId = $this->billing->hold(
                $userId,
                $billingCode,
                $quote['cost'],
                $taskNo,
                $billSnapshot
            );
            $task->tokens_log_id = $tokensLogId;
            $task->bill_status = $quote['cost'] > 0 ? DrawEnum::BILL_HELD : DrawEnum::BILL_CONSUMED;
            $task->save();
        }

        try {
            $payload = array_merge($params, [
                'model'   => $modelAlias,
                'prompt'  => $prompt,
                'task_no' => $taskNo,
            ]);
            // 回调地址备查（与发给中台的 notify_url 保持一致）
            $host = trim((string)config('app.app_host', ''));
            $notifyPath = (string)(config('api_tools.draw.notify_url') ?: '/api/draw.draw/notify');
            if ($host !== '') {
                if ($notifyPath === '' || $notifyPath[0] !== '/') {
                    $notifyPath = '/' . ltrim($notifyPath, '/');
                }
                $task->notify_url = rtrim($host, '/') . $notifyPath . '?task_no=' . rawurlencode($taskNo);
            }

            $response = $mediaType === DrawEnum::MEDIA_VIDEO
                ? $this->midClient->createVideoGeneration($payload)
                : $this->midClient->createImageGeneration($payload);

            $task->mid_raw = $this->sanitizeMidRaw($response);
            $midTaskId = $this->extractMidTaskId($response);
            $requestId = $this->extractRequestId($response);
            $assets = $this->extractAssetSources($response);

            // 中台生图实测为同步返回：无 task_id，产物在 data.data[].url 或 b64_json
            if ($midTaskId === '' && $assets !== []) {
                $created = 0;
                if (isset($response['data']['created']) && is_numeric($response['data']['created'])) {
                    $created = (int)$response['data']['created'];
                }
                $fingerprint = md5(($assets[0]['kind'] ?? '') . substr((string)($assets[0]['value'] ?? ''), 0, 64));
                $midTaskId = 'sync_' . ($created > 0 ? $created : time()) . '_' . substr($fingerprint, 0, 8);
            }

            if ($midTaskId === '' && $assets === []) {
                Log::warning('draw mid response missing task_id and assets', [
                    'task_no' => $taskNo,
                    'keys'    => array_keys(is_array($response['data'] ?? null) ? $response['data'] : []),
                ]);
                throw new \Exception('生成失败');
            }

            $task->mid_task_id = $midTaskId;
            $task->request_id = $requestId;
            $task->status = DrawEnum::STATUS_SUBMITTED;
            $task->submit_time = time();
            $task->save();
            LegacyDrawMirrorService::mirror($task);

            if ($assets !== []) {
                $this->markSuccess($task, $assets, $response);
            }

            return $task->refresh();
        } catch (\Throwable $e) {
            $task->status = DrawEnum::STATUS_FAILED;
            $task->error_msg = mb_substr($e->getMessage(), 0, 1000);
            $task->finished_at = time();
            $task->save();
            $this->billing->refundIfHeld($task);
            LegacyDrawMirrorService::mirror($task);
            throw $e;
        }
    }

    /**
     * 中台回调 / 轮询结果收敛
     */
    public function handleMidResult(DrawTask $task, array $payload): DrawTask
    {
        if (DrawEnum::isTerminal((int)$task->status)) {
            return $task;
        }

        // 轮询 / 回调都可能带站长信封，先解开到任务体
        $payload = $this->unwrapMidPayload(
            isset($payload['code']) || isset($payload['data']) ? $payload : ['data' => $payload]
        );

        $task->mid_raw = $this->sanitizeMidRaw($payload);
        $status = $this->normalizeMidStatus($payload);

        if ($status === 'failed') {
            $task->status = DrawEnum::STATUS_FAILED;
            $task->error_code = $this->stringifyScalar($payload['error_code'] ?? $payload['code'] ?? '');
            $task->error_msg = mb_substr($this->extractErrorMessage($payload) ?: '生成失败', 0, 1000);
            $task->finished_at = time();
            $task->save();
            $this->billing->refundIfHeld($task);
            LegacyDrawMirrorService::mirror($task);
            return $task;
        }

        if ($status === 'processing' || $status === 'submitted') {
            $task->status = $status === 'submitted'
                ? DrawEnum::STATUS_SUBMITTED
                : DrawEnum::STATUS_PROCESSING;
            $progress = $payload['progress'] ?? null;
            if ($progress !== null) {
                if (is_string($progress)) {
                    $progress = rtrim(trim($progress), '%');
                }
                if (is_numeric($progress)) {
                    $task->progress = (float)$progress;
                }
            }
            $task->save();
            LegacyDrawMirrorService::mirror($task);
            return $task;
        }

        if ($status === 'succeeded') {
            $sources = $this->extractAssetSources($payload);
            if ($sources === []) {
                // 成功但无 URL：保持 processing，等下次轮询
                $task->status = DrawEnum::STATUS_PROCESSING;
                $task->save();
                return $task;
            }
            $this->markSuccess($task, $sources, $payload);
        }

        return $task->refresh();
    }

    /**
     * 主动向中台拉一次状态
     */
    public function poll(DrawTask $task): DrawTask
    {
        if (DrawEnum::isTerminal((int)$task->status) || $task->mid_task_id === '') {
            return $task;
        }

        try {
            $response = $this->midClient->getGenerationStatus(
                (string)$task->media_type,
                (string)$task->mid_task_id
            );
            return $this->handleMidResult($task, $response);
        } catch (\Throwable $e) {
            Log::warning('draw poll failed: ' . $e->getMessage(), ['task_no' => $task->task_no]);
            return $task;
        }
    }

    /**
     * 解开中台统一信封 / 上游双层 data，暴露带 status/result 的任务体
     * 例：{code:10000,data:{code:"success",data:{status:"SUCCESS",result_url:"..."}}}
     */
    private function unwrapMidPayload(array $response): array
    {
        $payload = $response;
        // 顶层站长信封：优先取 data
        if (isset($response['data']) && is_array($response['data'])
            && (isset($response['code']) || isset($response['message']))
        ) {
            $payload = $response['data'];
        }
        if (!is_array($payload)) {
            return [];
        }

        for ($i = 0; $i < 2; $i++) {
            if (!isset($payload['data']) || !is_array($payload['data'])) {
                break;
            }
            $inner = $payload['data'];
            $looksLikeTask = isset($inner['status'])
                || isset($inner['task_status'])
                || isset($inner['task_id'])
                || isset($inner['result'])
                || isset($inner['result_url'])
                || (isset($inner['content']) && is_array($inner['content']));
            if (!$looksLikeTask) {
                break;
            }
            $payload = $inner;
        }

        return $payload;
    }

    /**
     * 批量轮询未完成任务
     */
    public function pollPending(int $limit = 50): int
    {
        $list = DrawTask::whereIn('status', [
            DrawEnum::STATUS_SUBMITTED,
            DrawEnum::STATUS_PROCESSING,
        ])
            ->where('mid_task_id', '<>', '')
            ->order('id', 'asc')
            ->limit($limit)
            ->select();

        $n = 0;
        foreach ($list as $task) {
            $this->poll($task);
            $n++;
        }
        return $n;
    }

    /**
     * @param array<int, array{kind:string,value:string,mime?:string}> $sources
     */
    private function markSuccess(DrawTask $task, array $sources, array $payload): void
    {
        $task->status = DrawEnum::STATUS_PROCESSING;
        $task->progress = 100;
        $task->save();

        try {
            $this->assetService->materialize($task, $sources);
        } catch (\Throwable $e) {
            Log::error('draw materialize failed: ' . $e->getMessage(), ['task_no' => $task->task_no]);
            $task->status = DrawEnum::STATUS_FAILED;
            $task->error_msg = mb_substr('生成失败: ' . $e->getMessage(), 0, 1000);
            $task->finished_at = time();
            $task->save();
            $this->billing->refundIfHeld($task);
            LegacyDrawMirrorService::mirror($task);
            return;
        }

        $task->status = DrawEnum::STATUS_SUCCESS;
        $task->finished_at = time();
        $task->mid_raw = $this->sanitizeMidRaw($payload);
        $task->save();

        // 视频：按实际秒数结算（多退少补）；图片：预扣确认 或 PPT 成功后扣费
        if ((string)$task->media_type === DrawEnum::MEDIA_VIDEO) {
            $actualSeconds = $this->billing->resolveActualVideoSeconds($task, $payload);
            $this->billing->settleVideo($task, $actualSeconds);
        } else {
            $snapshot = is_array($task->bill_snapshot) ? $task->bill_snapshot : [];
            if (($snapshot['bill_timing'] ?? 'prehold') === 'on_success') {
                try {
                    $this->billing->chargeOnSuccess($task);
                } catch (\Throwable $e) {
                    $task->status = DrawEnum::STATUS_FAILED;
                    $task->error_msg = mb_substr('算力不足或扣费失败: ' . $e->getMessage(), 0, 1000);
                    $task->finished_at = time();
                    $task->save();
                    LegacyDrawMirrorService::mirror($task);
                    return;
                }
            } else {
                $this->billing->consume($task);
            }
        }
        LegacyDrawMirrorService::mirror($task);
    }

    private function generateTaskNo(): string
    {
        return 'D' . date('YmdHis') . substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
    }

    /**
     * 规范中台 generations 数值字段类型。
     * 表单/部分客户端会把 n/width/height 传成字符串，Go 侧 uint/int 会反序列化失败。
     */
    private function normalizeGenerationParams(array $params): array
    {
        $intKeys = [
            'n', 'width', 'height', 'seed', 'fps', 'frames',
            'num_frames', 'steps', 'sample_steps',
        ];
        foreach ($intKeys as $key) {
            if (!array_key_exists($key, $params) || $params[$key] === '' || $params[$key] === null) {
                continue;
            }
            if (is_numeric($params[$key])) {
                $params[$key] = (int)$params[$key];
            }
        }

        $floatKeys = ['duration', 'cfg_scale', 'guidance_scale', 'motion_strength'];
        foreach ($floatKeys as $key) {
            if (!array_key_exists($key, $params) || $params[$key] === '' || $params[$key] === null) {
                continue;
            }
            if (is_numeric($params[$key])) {
                $params[$key] = (float)$params[$key];
            }
        }

        // n 至少为 1
        if (isset($params['n'])) {
            $params['n'] = max(1, (int)$params['n']);
        }

        return $params;
    }

    private function extractMidTaskId(array $response): string
    {
        $candidates = [];
        $data = $response['data'] ?? null;
        if (is_array($data)) {
            $candidates[] = $data;
            if (isset($data['task']) && is_array($data['task'])) {
                $candidates[] = $data['task'];
            }
            if (isset($data['result']) && is_array($data['result'])) {
                $candidates[] = $data['result'];
            }
        } elseif (is_string($data) && $data !== '') {
            return $data;
        }
        $candidates[] = $response;

        foreach ($candidates as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }
            foreach (['task_id', 'mid_task_id', 'taskId', 'id', 'job_id'] as $key) {
                $val = $this->stringifyScalar($bucket[$key] ?? null);
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return '';
    }

    private function extractRequestId(array $response): string
    {
        $candidates = [];
        $data = $response['data'] ?? null;
        if (is_array($data)) {
            $candidates[] = $data;
        }
        $candidates[] = $response;

        foreach ($candidates as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }
            $val = $this->stringifyScalar($bucket['request_id'] ?? $bucket['requestId'] ?? null);
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

    private function extractErrorMessage(array $response): string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        foreach (['message', 'msg', 'error', 'error_msg'] as $key) {
            $val = $this->stringifyScalar($response[$key] ?? null);
            if ($val !== '') {
                return $val;
            }
            $val = $this->stringifyScalar($data[$key] ?? null);
            if ($val !== '') {
                return $val;
            }
        }
        return '';
    }

    /**
     * 仅接受标量；数组/对象避免 (string) 触发 Array to string conversion
     */
    private function stringifyScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value) || is_string($value)) {
            return trim((string)$value);
        }
        return '';
    }

    /**
     * 归一化中台状态文案
     */
    private function normalizeMidStatus(array $payload): string
    {
        $raw = strtolower($this->stringifyScalar(
            $payload['status']
            ?? $payload['task_status']
            ?? ($payload['data']['status'] ?? '')
        ));
        // 兼容 SUCCESS / IN_PROGRESS 等带下划线写法
        $raw = str_replace(['-', ' '], '_', $raw);

        if (in_array($raw, ['success', 'succeeded', 'completed', 'done'], true)) {
            return 'succeeded';
        }
        if (in_array($raw, ['fail', 'failed', 'error', 'failure'], true)) {
            return 'failed';
        }
        if (in_array($raw, ['submitted', 'pending', 'queued'], true)) {
            return 'submitted';
        }
        if (in_array($raw, ['processing', 'running', 'in_progress'], true)) {
            return 'processing';
        }

        // 占位：带产物 URL/b64 视为成功
        if ($this->extractAssetSources($payload) !== []) {
            return 'succeeded';
        }

        return 'processing';
    }

    /**
     * 解析中台产物
     *
     * 兼容：
     * - Seedream：data.data[{url,size}]
     * - GPT Image：data.data[{b64_json}]
     * - 异步常见：data.assets / images / image_urls
     *
     * @return array<int, array{kind:string,value:string,mime?:string}>
     */
    private function extractAssetSources(array $payload): array
    {
        $sources = [];
        $buckets = [];

        $rootData = $payload['data'] ?? null;
        if (is_array($rootData)) {
            $buckets[] = $rootData;
            if (isset($rootData['data']) && is_array($rootData['data'])) {
                $buckets[] = ['items' => $rootData['data']];
            }
        }
        $buckets[] = $payload;

        foreach ($buckets as $bucket) {
            if (!is_array($bucket)) {
                continue;
            }

            foreach (['assets', 'image_urls', 'images', 'videos', 'result_urls', 'items', 'data'] as $key) {
                if (empty($bucket[$key]) || !is_array($bucket[$key])) {
                    continue;
                }
                if ($key === 'data' && !$this->isListArray($bucket[$key])) {
                    continue;
                }
                foreach ($bucket[$key] as $item) {
                    $source = $this->pickAssetSource($item);
                    if ($source !== null) {
                        $sources[] = $source;
                    }
                }
            }

            foreach (['url', 'video_url', 'image_url', 'result_url', 'result', 'b64_json'] as $key) {
                if (!array_key_exists($key, $bucket)) {
                    continue;
                }
                $source = $this->pickAssetSource([$key => $bucket[$key]]);
                if ($source !== null) {
                    $sources[] = $source;
                }
            }

            // Seedance 等：data.content.video_url
            if (isset($bucket['content']) && is_array($bucket['content'])) {
                $source = $this->pickAssetSource($bucket['content']);
                if ($source !== null) {
                    $sources[] = $source;
                }
            }

            // 新中台视频查询：data.metadata.url
            if (isset($bucket['metadata']) && is_array($bucket['metadata'])) {
                $source = $this->pickAssetSource($bucket['metadata']);
                if ($source !== null) {
                    $sources[] = $source;
                }
            }
        }

        // 去重：url 按值；b64 按前缀指纹
        $unique = [];
        $seen = [];
        foreach ($sources as $source) {
            $fp = ($source['kind'] ?? '') . ':' . substr((string)($source['value'] ?? ''), 0, 96);
            if (isset($seen[$fp])) {
                continue;
            }
            $seen[$fp] = true;
            $unique[] = $source;
        }
        return $unique;
    }

    /**
     * @return array{kind:string,value:string,mime?:string}|null
     */
    private function pickAssetSource(mixed $item): ?array
    {
        if (is_string($item)) {
            $text = trim($item);
            if ($text === '') {
                return null;
            }
            if (str_starts_with($text, 'data:') || str_starts_with($text, 'http://') || str_starts_with($text, 'https://')) {
                return ['kind' => str_starts_with($text, 'data:') ? 'b64' : 'url', 'value' => $text];
            }
            return ['kind' => 'url', 'value' => $text];
        }
        if (!is_array($item)) {
            return null;
        }

        foreach (['url', 'file_url', 'video_url', 'image_url', 'result_url', 'result'] as $key) {
            if (!isset($item[$key])) {
                continue;
            }
            if (is_string($item[$key]) || is_int($item[$key]) || is_float($item[$key])) {
                $url = trim((string)$item[$key]);
                if ($url !== '') {
                    return ['kind' => 'url', 'value' => $url];
                }
            }
        }

        if (isset($item['content']) && is_array($item['content'])) {
            $nested = $this->pickAssetSource($item['content']);
            if ($nested !== null) {
                return $nested;
            }
        }

        // GPT Image / OpenAI：b64_json
        if (!empty($item['b64_json']) && is_string($item['b64_json'])) {
            $b64 = trim($item['b64_json']);
            if ($b64 !== '') {
                $mime = $this->guessMimeFromBase64($b64);
                return [
                    'kind'  => 'b64',
                    'value' => $b64,
                    'mime'  => $mime,
                ];
            }
        }

        return null;
    }

    private function guessMimeFromBase64(string $b64): string
    {
        // 常见魔数前缀（base64）
        if (str_starts_with($b64, '/9j/')) {
            return 'image/jpeg';
        }
        if (str_starts_with($b64, 'iVBOR')) {
            return 'image/png';
        }
        if (str_starts_with($b64, 'R0lGOD')) {
            return 'image/gif';
        }
        if (str_starts_with($b64, 'UklGR')) {
            return 'image/webp';
        }
        return 'image/png';
    }

    /**
     * mid_raw 落库时去掉超长 b64，避免撑爆 JSON 字段
     */
    private function sanitizeMidRaw(array $payload): array
    {
        $walk = function (&$node) use (&$walk): void {
            if (!is_array($node)) {
                return;
            }
            foreach ($node as $key => &$value) {
                if ($key === 'b64_json' && is_string($value)) {
                    $value = '[omitted base64 length=' . strlen($value) . ']';
                    continue;
                }
                if (is_string($value) && str_starts_with($value, 'data:') && strlen($value) > 512) {
                    $value = substr($value, 0, 64) . '...[omitted]';
                    continue;
                }
                if (is_array($value)) {
                    $walk($value);
                }
            }
            unset($value);
        };
        $copy = $payload;
        $walk($copy);
        return $copy;
    }

    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }
        return array_keys($value) === range(0, count($value) - 1);
    }
}
