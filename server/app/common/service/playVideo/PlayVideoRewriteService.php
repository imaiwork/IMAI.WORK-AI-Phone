<?php

declare(strict_types=1);

namespace app\common\service\playVideo;

use app\common\enum\ChatEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\chat\Models;
use app\common\model\chat\ModelsCost;
use app\common\model\user\User;
use app\common\service\chat\ChatBillingService;
use app\common\service\ToolsService;
use think\facade\Db;
use think\facade\Log;

/**
 * 爆款玩法 · 一键替换台词（B1，2026-09-01 用户定为 AI 改写）。
 *
 * 通道选型（对照洗稿与 GEO 两条现存链路）：
 *   - coze/settext 工作流（洗稿在用）返回"整篇文案"契约，保不住句数/角色 → 不用；
 *   - 走 GEO 同款的中台通用对话端点 /api/chat/completions，模型默认 deepseek-v4-pro-none（下架/调用失败/
 *     没返回可用正文都会自动换一个本站上架的对话模型重试一次，见 resolveRewriteModel），
 *     response_format=json_object 强 JSON。
 *
 * ⚠️ 默认模型必须用不思考的 -none 变体（2026-09-05 事故）：推理模型的 reasoning tokens 计入 max_tokens，
 *    deepseek-v4-pro 改这三句台词就要思考 2300~3000 tokens，一旦打满 max_tokens 就 finish_reason=length、
 *    content 是空串，前端只看到"AI 返回格式异常"，而且思考长度有波动 → 时好时坏最难查。
 *    实测同一份提示词：pro/2000 → 思考 2966、正文 0 字（失败）；pro-none/2000 → 思考 0、正文 226 字（成功）。
 *    `enable_thinking=false` 对 deepseek-v4-pro **不生效**（照样思考），别指望它。
 * 计费：按模型用量折算算力（models_cost 同对话口径，ChatBillingService::calculatePoints），
 *       量级为几百 token，通常不足 1 算力；余额不足直接拒。
 * 人设语料：复用洗稿链路的组装（main_business/target_pain_points/conversion_hook +
 *       个人/企业/本地三型 getClueContent），保证与仿写口径一致。
 */
class PlayVideoRewriteService
{
    /** 输出预算：推理模型的思考 tokens 也算在里面，留够余量当安全垫（正文本身 200 字左右就够） */
    private const MAX_TOKENS = 4000;
    /** 默认改写模型（.env play_video.rewrite_model 可覆盖；下架时自动换上架模型）。-none = 不思考变体 */
    private const DEFAULT_MODEL = 'deepseek-v4-pro-none';

    /**
     * 按人设改写台词。$lines = [['role'=>'A','text'=>'...'], ...]（1~12 句）。
     * 返回同结构同句数的新台词。
     *
     * @throws \Exception
     */
    public static function rewriteLines(int $userId, int $personaId, array $lines, string $templateName = ''): array
    {
        if ($lines === [] || count($lines) > 12) {
            throw new \Exception('台词需 1~12 句');
        }
        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->find();
        if (!$persona) {
            throw new \Exception('人设不存在');
        }

        $personaContext = self::buildPersonaContext($persona);
        $model = self::resolveRewriteModel();

        $script = [];
        foreach (array_values($lines) as $i => $line) {
            $script[] = ($i + 1) . '. [' . (string)($line['role'] ?? '') . '] ' . (string)($line['text'] ?? '');
        }
        $system = '你是短视频台词改写专家。用户给你一段影视名场面的对白台词和一个 IP 人设，'
            . '你要把台词改写成"还是那个名场面，但说的是这个人设的生意"。硬性要求：'
            . '1) 句数与原文完全一致，角色标记不变，第 N 句只改写第 N 句；'
            . '2) 保留名场面的语气、梗和节奏——前面的句子尽量少改甚至不改，重点把最后一两句自然过渡到人设的业务/钩子上；'
            . '3) 每句不超过 60 字，口语化、适合演员开口说；'
            . '4) 不要出现"改写""人设"等元话语；'
            . '5) 只输出 JSON：{"lines":[{"role":"原角色","text":"新台词"}]}，不要任何其他文字。';
        $user = ($templateName !== '' ? "影视片段：{$templateName}\n" : '')
            . "原台词：\n" . implode("\n", $script)
            . "\n\n人设资料：\n" . $personaContext;

        // 换模型重试一次的触发条件有两类（用户 2026-09-02 定：兜底随机选上架模型）：
        //   调用失败（下架/中台无渠道等），以及**返回了但正文不可用**——后者原来不重试，
        //   推理模型思考打满 max_tokens 时正文恒为空，整单直接判失败（2026-09-05 事故）。
        $data = null;
        $newLines = null;
        $reason = '';
        for ($round = 0; $round < 2; $round++) {
            try {
                $data = self::requestRewrite($model, $system, $user);
                $newLines = self::parseRewriteLines($data, count($lines), $reason);
                if ($newLines !== null) {
                    break;
                }
            } catch (\Exception $e) {
                $reason = '调用失败: ' . $e->getMessage();
            }
            Log::channel('playvideo')->warning("play_video rewrite 模型 {$model} 不可用: {$reason}");
            $alt = self::resolveRewriteModel($model);
            if ($alt === $model) {
                break;
            }
            Log::channel('playvideo')->warning("play_video rewrite 换用模型 {$alt} 重试");
            $model = $alt;
        }
        if ($newLines === null) {
            throw new \Exception('AI 返回格式异常，请重试');
        }
        $out = [];
        foreach (array_values($newLines) as $i => $line) {
            $text = trim((string)($line['text'] ?? ''));
            if ($text === '') {
                throw new \Exception('AI 返回了空台词，请重试');
            }
            $out[] = [
                'role' => (string)($lines[$i]['role'] ?? ($line['role'] ?? '')),
                'text' => mb_substr($text, 0, 200),
            ];
        }

        self::chargeByUsage($userId, $model, (array)($data['usage'] ?? []));
        return $out;
    }

    /**
     * 解析改写结果。拿不到可用台词返回 null，并把**具体**原因写进 $reason（只进日志，对客仍是一句通用文案）。
     * 正文为空要单独识别：推理模型把输出预算全烧在思考上时 finish_reason=length、content 恒为空，
     * 与"模型乱答"是两回事，混在一起报"结构不符"会让排查完全走偏（2026-09-05 就是这么卡了两天）。
     *
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>|null
     */
    private static function parseRewriteLines(array $data, int $expect, string &$reason): ?array
    {
        $choice = (array)($data['choices'][0] ?? []);
        $reply = trim((string)($choice['message']['content'] ?? ''));
        if ($reply === '') {
            $usage = (array)($data['usage'] ?? []);
            $reasoningTokens = (int)($usage['completion_tokens_details']['reasoning_tokens'] ?? 0);
            $reason = (string)($choice['finish_reason'] ?? '') === 'length'
                ? '正文为空：输出预算 ' . self::MAX_TOKENS . ' tokens 被思考占满（思考 ' . $reasoningTokens . ' tokens），换不思考的模型或调大 MAX_TOKENS'
                : '正文为空（finish_reason=' . (string)($choice['finish_reason'] ?? '未知') . '）';
            return null;
        }
        $parsed = json_decode(self::stripJsonFence($reply), true);
        $newLines = is_array($parsed) ? ($parsed['lines'] ?? null) : null;
        if (!is_array($newLines) || count($newLines) !== $expect) {
            $reason = '结构不符（期望 ' . $expect . ' 句）: ' . mb_substr($reply, 0, 300);
            return null;
        }
        $reason = '';
        return array_values($newLines);
    }

    /** 剥掉模型爱加的 markdown 围栏（```json ... ```），没有围栏时原样返回 */
    private static function stripJsonFence(string $reply): string
    {
        $reply = trim($reply);
        if (!str_starts_with($reply, '```')) {
            return $reply;
        }
        $reply = (string)preg_replace('/^```[a-zA-Z]*\s*/', '', $reply);
        $reply = (string)preg_replace('/\s*```$/', '', $reply);
        return trim($reply);
    }

    /** 人设语料组装（与洗稿 buildRewriteCopywriting 同口径） */
    /** 调中台通用对话端点，返回 data 段；非 10000 抛异常 */
    private static function requestRewrite(string $model, string $system, string $user): array
    {
        $body = [
            'model'           => $model,
            'messages'        => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens'      => self::MAX_TOKENS,
            'stream'          => false,
            'response_format' => ['type' => 'json_object'],
            'enable_thinking' => false,
        ];
        $response = ToolsService::Chat()->message($body);
        $code = (int)($response['code'] ?? 0);
        if ($code !== 10000) {
            $message = trim((string)($response['message'] ?? $response['msg'] ?? ''));
            throw new \Exception($message !== '' ? $message : 'AI 改写失败，请稍后重试');
        }
        return (array)($response['data'] ?? []);
    }

    /**
     * 选改写模型：首选（.env play_video.rewrite_model，默认 deepseek-v4-pro）在本站上架且主模型启用就用它；
     * 否则从已上架的对话模型里随机挑一个。$exclude 为刚调用失败的模型（换一个再试）。
     * 全都不可用时原样返回首选，让上游把错误透出来。
     */
    public static function resolveRewriteModel(string $exclude = ''): string
    {
        $preferred = trim((string)env('play_video.rewrite_model', '')) ?: self::DEFAULT_MODEL;
        if ($preferred !== $exclude && self::isModelOnShelf($preferred)) {
            return $preferred;
        }
        $candidates = ModelsCost::alias('c')
            ->join('models m', 'm.id = c.model_id')
            ->where('c.type', ChatEnum::MODEL_TYPE_CHAT)
            ->where('c.status', 1)
            ->where('m.type', ChatEnum::MODEL_TYPE_CHAT)
            ->where('m.is_enable', 1)
            ->where('c.alias', '<>', $preferred)
            ->where('c.alias', '<>', $exclude)
            ->column('c.alias');
        // 排除明显不是文本对话用途的别名（视觉/语音/手机端专用模型改写台词会直接失败）
        $candidates = array_values(array_unique(array_filter(array_map('strval', $candidates), function (string $alias): bool {
            return $alias !== '' && !preg_match('/phone|vision|-4v|-vl|image|audio|tts|embed/i', $alias);
        })));
        if ($candidates === []) {
            return $preferred;
        }
        return $candidates[array_rand($candidates)];
    }

    /** 模型是否上架：计费行启用且主模型启用（与电话智能体的扣费前校验同口径） */
    private static function isModelOnShelf(string $alias): bool
    {
        $cost = ModelsCost::where('alias', $alias)->where('type', ChatEnum::MODEL_TYPE_CHAT)->where('status', 1)->find();
        if (!$cost) {
            return false;
        }
        $main = Models::where('id', (int)$cost->model_id)->find();
        return $main !== null && (int)$main->is_enable === 1;
    }

    private static function buildPersonaContext($persona): string
    {
        $personaId = (int)$persona['id'];
        $personaStr = '';
        try {
            $ext = match ((int)$persona['persona_type']) {
                1 => \app\common\model\aiPersona\AiPersonaIndividual::where('persona_id', $personaId)->find(),
                2 => \app\common\model\aiPersona\AiPersonaEnterprise::where('persona_id', $personaId)->find(),
                3 => \app\common\model\aiPersona\AiPersonaLocal::where('persona_id', $personaId)->find(),
                default => null,
            };
            if ($ext) {
                $personaStr = (string)$ext->getClueContent($persona);
            }
        } catch (\Throwable $e) {
            // 扩展资料取不到不阻断，用基础字段兜底
        }
        return "人设名：{$persona['persona_name']}\n"
            . ($personaStr !== '' ? $personaStr . "\n" : '')
            . "主营业务/产品：{$persona['main_business']}\n"
            . "目标客户与痛点：{$persona['target_pain_points']}\n"
            . "差异化优势与行为引导：{$persona['conversion_hook']}";
    }

    /** 按模型用量折算算力扣费（models_cost 同对话口径；未配价或 0 则不扣） */
    private static function chargeByUsage(int $userId, string $model, array $usage): void
    {
        try {
            $costRow = ModelsCost::where('alias', $model)->where('status', 1)->find();
            $cost = $costRow ? $costRow->toArray() : null;
            $points = ChatBillingService::calculatePoints($cost, $usage);
            if ($points <= 0) {
                return;
            }
            $points = round($points, 2);
            Db::startTrans();
            try {
                User::userTokensChange($userId, $points);
                AccountLogLogic::add(
                    $userId,
                    AccountLogEnum::TOKENS_DEC_PLAY_FACE,
                    AccountLogEnum::DEC,
                    $points,
                    1,
                    'pv_rewrite_' . time(),
                    '爆款玩法AI改写台词减少算力',
                    self::buildChargeExtra($cost, $usage, $points, $model)
                );
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            // 扣费异常不回滚已生成的结果，记日志人工对账（量级 <1 算力）
            Log::channel('playvideo')->error('play_video rewrite 扣费失败: ' . $e->getMessage());
        }
    }

    /**
     * 消耗记录明细（C 端展示，口径对齐 PhoneAgentBillingService::buildPriceExtra）：
     * 只放用户看得懂的中文字段，不透出上游原始 usage JSON 与内部模型别名。
     *
     * @param array<string, mixed>|null $cost models_cost 行
     * @param array<string, mixed>      $usage 上游用量
     * @return array<string, mixed>
     */
    private static function buildChargeExtra(?array $cost, array $usage, float $points, string $alias): array
    {
        $modelName = $alias;
        if (!empty($cost['model_id'])) {
            $name = Models::where('id', (int)$cost['model_id'])->value('name');
            if (is_string($name) && trim($name) !== '') {
                $modelName = trim($name);
            }
        }
        $promptTokens = (int)($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int)($usage['completion_tokens'] ?? 0);
        $reasoningTokens = (int)($usage['completion_tokens_details']['reasoning_tokens'] ?? 0);
        $extra = [
            '扣费项目' => '一键替换台词',
            '模型'     => $modelName,
            '输入tokens' => $promptTokens,
            '输出tokens' => $reasoningTokens > 0 ? $completionTokens . '（含思考 ' . $reasoningTokens . '）' : $completionTokens,
        ];
        if ((int)($cost['quota_type'] ?? 0) === 1) {
            $extra['计费方式'] = '按次';
            $extra['单价'] = (float)($cost['model_price'] ?? 0) . ' 算力/次';
        } else {
            $inputPrice = (float)($cost['price'] ?? 0);
            $outputPrice = (float)($cost['output_price'] ?? 0);
            if ($outputPrice <= 0) {
                $outputPrice = $inputPrice;
            }
            $extra['计费方式'] = '按tokens';
            $extra['输入单价'] = $inputPrice . ' 算力/千tokens';
            $extra['输出单价'] = $outputPrice . ' 算力/千tokens';
        }
        $extra['实扣算力'] = $points;
        return $extra;
    }
}
