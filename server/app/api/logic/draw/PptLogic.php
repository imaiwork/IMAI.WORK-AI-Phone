<?php

declare(strict_types=1);

namespace app\api\logic\draw;

use app\common\enum\draw\DrawEnum;
use app\common\logic\BaseLogic;
use app\common\model\draw\DrawConversation;
use app\common\model\draw\DrawMessage;
use app\common\service\ToolsService;
use app\common\service\draw\DrawGenerateService;
use app\common\service\draw\MediaModelsService;
use app\common\service\draw\PptPromptService;

/**
 * PPT 编排：追问/章节（Coze 不计费）+ 按页生图（有结果才扣费）
 */
class PptLogic extends BaseLogic
{
    /** PPT 生图仅允许 gpt-image-2（展示名 image-2） */
    private const ALLOWED_MODEL_ALIASES = ['gpt-image-2', 'image-2'];

    public static function followup(array $params): bool
    {
        try {
            $topic = trim((string)($params['topic'] ?? ''));
            if ($topic === '') {
                throw new \Exception('topic 不能为空');
            }

            $response = ToolsService::Coze()->pptFollowup([
                'input' => $topic,
            ]);
            if ((int)($response['code'] ?? 0) !== 10000) {
                throw new \Exception(self::stringifyMidMessage($response));
            }

            $content = self::extractMidContent($response['data'] ?? null);
            if (!is_array($content) || !isset($content['fields']) || !is_array($content['fields'])) {
                throw new \Exception('工作流返回缺少 fields 字段,无法渲染表单');
            }

            self::$returnData = [
                'description' => (string)($content['description'] ?? ''),
                'ppt_type'    => (string)($content['ppt_type'] ?? ''),
                'fields'      => $content['fields'],
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function chapters(array $params): bool
    {
        try {
            $topic = trim((string)($params['topic'] ?? ''));
            $pageCount = (int)($params['page_count'] ?? 0);
            if ($topic === '') {
                throw new \Exception('topic 不能为空');
            }
            if ($pageCount < 1) {
                throw new \Exception('page_count 必须 >= 1');
            }

            $input1 = $topic;
            $scene = trim((string)($params['ppt_scene'] ?? ''));
            if ($scene !== '') {
                $input1 .= "【{$scene}】";
            }
            $summary = $params['summary'] ?? null;
            if (is_array($summary) && $summary !== []) {
                $lines = [];
                foreach ($summary as $k => $v) {
                    if (is_scalar($v) || $v === null) {
                        $lines[] = $k . '：' . (string)$v;
                    }
                }
                if ($lines !== []) {
                    $input1 .= "\n\n补充信息:\n" . implode("\n", $lines);
                }
            }

            $response = ToolsService::Coze()->pptChapters([
                'input1' => $input1,
                'input2' => (string)$pageCount,
            ]);
            if ((int)($response['code'] ?? 0) !== 10000) {
                throw new \Exception(self::stringifyMidMessage($response));
            }

            // 中台文档：data.content 即为 pages 数组
            $pages = self::extractMidContent($response['data'] ?? null);
            if (!is_array($pages)) {
                throw new \Exception('工作流返回缺少章节列表');
            }
            // 兼容少数仍包一层 pages 的形态
            if (isset($pages['pages']) && is_array($pages['pages'])) {
                $pages = $pages['pages'];
            }
            if ($pages === [] || !self::isListArray($pages)) {
                throw new \Exception('工作流返回章节格式异常');
            }

            self::$returnData = ['pages' => $pages];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function submitSlides(array $params, int $userId): bool
    {
        try {
            $model = trim((string)($params['model'] ?? ''));
            if ($model === '') {
                throw new \Exception('model 不能为空');
            }
            $model = self::assertAllowedPptModel($model);
            MediaModelsService::findCostByAlias($model, true);

            $topic = trim((string)($params['topic'] ?? ''));
            if ($topic === '') {
                throw new \Exception('topic 不能为空');
            }
            $slides = $params['slides'] ?? null;
            if (!is_array($slides) || $slides === []) {
                throw new \Exception('slides 不能为空');
            }
            if (count($slides) !== 1) {
                throw new \Exception('每次仅支持提交 1 页，请逐页调用');
            }
            $slide = $slides[0];
            if (!is_array($slide)) {
                throw new \Exception('slides[0] 必须是对象');
            }
            if (!isset($slide['title']) && !isset($slide['content']) && !isset($slide['page'])) {
                throw new \Exception('slides[0] 缺少 page/title/content');
            }

            $pptType = trim((string)($params['ppt_type'] ?? ''));
            $audience = trim((string)($params['audience'] ?? ''));
            $style = trim((string)($params['style'] ?? ''));
            $page = (int)($slide['page'] ?? 1);
            $title = (string)($slide['title'] ?? '');
            $content = (string)($slide['content'] ?? '');
            $totalPages = max(1, (int)($params['total_pages'] ?? $page));
            $isCover = array_key_exists('is_cover', $params)
                ? (bool)$params['is_cover']
                : ($page <= 1);

            $conversation = self::resolveConversation(
                (int)($params['conversation_id'] ?? 0),
                $userId,
                $topic
            );

            // 与图片创作一致：同一会话可多轮；每轮首页写一条 user，随后各页写 assistant
            $writeUser = !empty($params['write_user']);
            $turnKey = trim((string)($params['turn_key'] ?? ''));
            if ($writeUser) {
                DrawMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $userId,
                    'media_type'      => DrawEnum::MEDIA_PPT,
                    'role'            => 'user',
                    'content'         => $topic,
                    'attachments'     => [],
                    'params'          => [
                        'biz'      => 'ppt',
                        'model'    => $model,
                        'pages'    => $totalPages,
                        'turn_key' => $turnKey,
                    ],
                    'draw_task_id'    => 0,
                ]);
            }

            $promptSvc = new PptPromptService();
            $gen = new DrawGenerateService();
            $prompt = $promptSvc->buildSlidePrompt([
                'is_cover' => $isCover,
                'page_num' => $page,
                'total'    => $totalPages,
                'title'    => $title,
                'content'  => $content,
                'topic'    => $topic,
                'ppt_type' => $pptType,
                'audience' => $audience,
                'style'    => $style,
            ]);

            $tasksOut = [];
            try {
                $task = $gen->submit([
                    'user_id'     => $userId,
                    'media_type'  => DrawEnum::MEDIA_IMAGE,
                    'model'       => $model,
                    'prompt'      => $prompt,
                    'bill_timing' => 'on_success',
                    'quantity'    => 1,
                    'params'      => [
                        'n'        => 1,
                        'biz'      => 'ppt',
                        'ppt_page' => $page,
                        'title'    => $title,
                        'content'  => $content,
                        'size'     => '1536x1024',
                        'turn_key' => $turnKey,
                    ],
                ]);

                DrawMessage::create([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $userId,
                    'media_type'      => DrawEnum::MEDIA_PPT,
                    'role'            => 'assistant',
                    'content'         => mb_substr($title, 0, 500),
                    'draw_task_id'    => (int)$task->id,
                    'params'          => [
                        'biz'      => 'ppt',
                        'ppt_page' => $page,
                        'title'    => $title,
                        'content'  => $content,
                        'turn_key' => $turnKey,
                    ],
                ]);

                $tasksOut[] = [
                    'page'    => $page,
                    'task_no' => $task->task_no,
                    'status'  => (int)$task->status,
                    'task_id' => (int)$task->id,
                ];
            } catch (\Throwable $e) {
                $tasksOut[] = [
                    'page'    => $page,
                    'task_no' => '',
                    'status'  => DrawEnum::STATUS_FAILED,
                    'error'   => $e->getMessage(),
                ];
            }

            $conversation->last_prompt = mb_substr($topic, 0, 10000);
            $conversation->message_count = DrawMessage::where('conversation_id', $conversation->id)->count();
            $conversation->save();

            self::$returnData = [
                'conversation_id' => $conversation->id,
                'tasks'           => $tasksOut,
            ];
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function regenerateSlide(array $params, int $userId): bool
    {
        $params['slides'] = [[
            'page'    => (int)($params['page'] ?? 1),
            'title'   => (string)($params['title'] ?? ''),
            'content' => (string)($params['content'] ?? ''),
        ]];
        if (!isset($params['total_pages'])) {
            $params['total_pages'] = max(1, (int)($params['page'] ?? 1));
        }
        if (!isset($params['is_cover'])) {
            $params['is_cover'] = ((int)($params['page'] ?? 1)) <= 1;
        }
        return self::submitSlides($params, $userId);
    }

    private static function resolveConversation(int $conversationId, int $userId, string $topic): DrawConversation
    {
        if ($conversationId > 0) {
            $c = DrawConversation::where('id', $conversationId)
                ->where('user_id', $userId)
                ->whereIn('media_type', [DrawEnum::MEDIA_PPT, DrawEnum::MEDIA_IMAGE])
                ->findOrEmpty();
            if (!$c->isEmpty()) {
                return $c;
            }
        }
        return DrawConversation::create([
            'user_id'       => $userId,
            'media_type'    => DrawEnum::MEDIA_PPT,
            'title'         => mb_substr($topic !== '' ? $topic : 'PPT', 0, 200),
            'last_prompt'   => mb_substr($topic, 0, 10000),
            'message_count' => 0,
        ]);
    }

    /**
     * PPT 仅允许 image-2 / gpt-image-2，统一返回技术 alias
     * @throws \Exception
     */
    private static function assertAllowedPptModel(string $model): string
    {
        $key = strtolower(trim($model));
        $tech = strtolower(MediaModelsService::resolveTechnicalAlias($model));
        $allowed = array_map('strtolower', self::ALLOWED_MODEL_ALIASES);
        if (!in_array($key, $allowed, true) && !in_array($tech, $allowed, true)) {
            throw new \Exception('PPT 生成仅支持 image-2 模型');
        }
        // 提交中台统一用技术 alias
        return $tech !== '' ? MediaModelsService::resolveTechnicalAlias($model) : 'gpt-image-2';
    }

    /**
     * 取中台 data.content（文档约定业务载荷）
     */
    private static function extractMidContent(mixed $data): mixed
    {
        if (!is_array($data)) {
            return null;
        }
        if (array_key_exists('content', $data)) {
            return $data['content'];
        }
        return $data;
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
        return 'Coze 业务调用失败';
    }

    /** @param array<mixed> $arr */
    private static function isListArray(array $arr): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($arr);
        }
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }
}
