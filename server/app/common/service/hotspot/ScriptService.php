<?php

namespace app\common\service\hotspot;

use app\api\logic\kb\KbRobotLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\ModelConfig;

class ScriptService
{
    public const CHARS_PER_SEC = 4.2;

    public const VIDEO_TYPES = ['digital', 'clips'];

    /** 人设模式：按所选人设的形象/音色/素材合成（历史默认） */
    public const PERSONA_MODE_PERSONA = 1;

    /** 不选人设：形象与音色由用户自选，素材固定 AI 找素材（对齐爆款复刻洗稿模式） */
    public const PERSONA_MODE_MANUAL = 2;

    public const PERSONA_MODES = [self::PERSONA_MODE_PERSONA, self::PERSONA_MODE_MANUAL];

    public const COPYWRITING_SN = 2;

    public const COPYWRITING_TYPE = 1;

    public const COPYWRITING_NUMBER = 1;

    public const COPYWRITING_LENGTH = 500;

    public const CHANNEL_VERSION = 24;

    public const MIN_TITLE_STRIP_CHARS = 6;

    public const ERROR_SCRIPT_MISS_TOPIC = '口播未点到热点话题';

    /**
     * 测试钩子：仅 tests/ 注入，生产勿用。
     * @var array{
     *   copywriting?: callable|array|false,
     *   copywritingError?: string,
     *   copywritingCode?: int,
     *   personaClue?: string,
     *   personaClueError?: string,
     *   personaModel?: AiPersona
     * }
     */
    private static array $testHooks = [];

    private static ?array $lastCopywritingParams = null;

    public static function options(): array
    {
        return [
            'goals' => [
                ['key' => 'sell', 'label' => '卖产品'],
                ['key' => 'leads', 'label' => '私域获客'],
                ['key' => 'traffic', 'label' => '涨粉引流'],
                ['key' => 'brand', 'label' => '品牌种草'],
                ['key' => 'engage', 'label' => '点击播放'],
            ],
            'directions' => AnalyzeService::DIRECTIONS,
            'materials' => [
                ['key' => 'ai', 'label' => 'AI找素材'],
                ['key' => 'ai_persona', 'label' => 'AI+人设素材'],
                ['key' => 'persona', 'label' => '纯人设素材'],
            ],
            'durations' => [30, 60, 90],
            'video_types' => [
                ['key' => 'digital', 'label' => '数字人口播混剪'],
                ['key' => 'clips', 'label' => '素材混剪'],
            ],
            'costs' => [
                'digital' => self::costItem('human_video_shanjian'),
                'clips' => self::costItem('shanjian_broadcast_mixcut'),
            ],
        ];
    }

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
        self::$lastCopywritingParams = null;
    }

    public static function lastCopywritingParams(): ?array
    {
        return self::$lastCopywritingParams;
    }

    public static function generate(string $topic, string $platform, array $corePoints, string $summary, array $persona, array $analysis, array $options, int $userId = 0): array
    {
        $opt = self::normalizeOptions($options);
        HotspotLog::write(sprintf(
            '口播文案生成开始：话题=%s 平台=%s 人设=%s 目标=%s 方向=%s 素材=%s 时长=%d秒',
            $topic,
            $platform,
            (string)($persona['name'] ?? ''),
            $opt['goal'],
            $opt['direction'],
            $opt['material_mode'],
            $opt['duration_sec']
        ));
        $params = self::buildCopywritingParams($topic, $platform, $corePoints, $summary, $persona, $analysis, $opt, $userId);
        if (trim((string)($params['keywords'] ?? '')) === '') {
            HotspotLog::write('口播文案生成失败：热点主题材料为空，已跳过现写');
            throw new HotspotUpstreamException('生成失败');
        }
        $data = self::requestCopywriting($params);
        $result = self::adaptCopywritingResult($topic, $data);

        $skipTopicGuard = !empty($opt['skip_topic_guard']);
        // 不再按时长卡字数；title2 照抄话题时免费重试一次。剪辑现写可跳过点题校验。
        $titleEcho = self::isTitle2Echo($result, $topic);
        $missTopic = !$skipTopicGuard && !self::scriptTouchesTopic($topic, (string)($result['script'] ?? ''));
        if ($titleEcho || $missTopic) {
            HotspotLog::write(sprintf(
                '口播文案不合格触发重试：话题=%s 字数=%d 标题照抄=%s 未点到话题=%s',
                $topic,
                (int)$result['word_count'],
                $titleEcho ? '是' : '否',
                $missTopic ? '是' : '否'
            ));
            $fix = [];
            if ($titleEcho) {
                $fix[] = "title2 必须是原创短视频标题，禁止整句照抄热点话题原文「{$topic}」";
            }
            if ($missTopic) {
                $fix[] = "上一次口播没有点到热点话题原文「{$topic}」，本次 script 必须完整出现该原文；title2 仍禁止整句照抄";
            }
            $params['keywords'] .= "\n【重要】上一次生成不合格，本次必须严格满足："
                . implode('；', $fix) . '。';
            // 系统纠错重试不重复扣费（首次已足额扣）；重试失败时保留首次结果，不毁掉整单
            $params['skip_charge'] = 1;
            try {
                $data = self::requestCopywriting($params);
                $retryResult = self::adaptCopywritingResult($topic, $data);
                $result = self::preferCopywritingResult($result, $retryResult, $titleEcho, $topic);
            } catch (\Throwable $e) {
                HotspotLog::write('口播文案重试失败，沿用首次结果：' . $e->getMessage());
            }
        }

        $script = trim((string)($result['script'] ?? ''));
        if (!$skipTopicGuard && $script !== '' && !self::scriptTouchesTopic($topic, $script)) {
            $patched = self::ensureTopicMentioned($topic, $script);
            if ($patched !== $script) {
                HotspotLog::write(sprintf(
                    '口播未点到热点话题，已在正文补入话题原文：话题=%s',
                    $topic
                ));
                $result['script'] = $patched;
                $result = self::recountScriptResult($result);
            }
        }

        $result = self::applyCtaToScriptResult($result, (string)($opt['cta'] ?? ''));

        HotspotLog::write(sprintf(
            '口播文案生成完成：话题=%s 标题=%s 字数=%d 标签数=%d 分镜数=%d',
            $topic,
            $result['title'],
            (int)$result['word_count'],
            count($result['hashtags']),
            count($result['shots'])
        ));
        return self::exposeScriptResult($result);
    }

    /**
     * 结尾引导语原文贴到口播末尾；已包含（忽略空白）则不重复。
     */
    public static function ensureCtaAppended(string $script, string $cta): string
    {
        $cta = trim($cta);
        if ($cta === '') {
            return $script;
        }
        $script = trim($script);
        if ($script === '') {
            return $cta;
        }
        $normCta = self::normalizeText($cta);
        if ($normCta !== '' && str_contains(self::normalizeText($script), $normCta)) {
            return $script;
        }
        return $script . "\n" . $cta;
    }

    /**
     * 口播开头若是标题原文（忽略空白）则剥掉；剥完为空或标题过短则保留原文。
     */
    public static function stripLeadingTitle(string $script, string $title): string
    {
        $title = trim($title);
        $script = trim($script);
        if ($title === '' || $script === '') {
            return $script;
        }
        $normTitle = self::normalizeText($title);
        $normScript = self::normalizeText($script);
        if ($normTitle === '' || mb_strlen($normTitle) < self::MIN_TITLE_STRIP_CHARS) {
            return $script;
        }
        if (!str_starts_with($normScript, $normTitle) || $normScript === $normTitle) {
            return $script;
        }

        if (str_starts_with($script, $title)) {
            $rest = self::trimLeadingTitlePunct(trim(mb_substr($script, mb_strlen($title))));
            return $rest !== '' ? $rest : $script;
        }

        $lines = preg_split('/\R/u', $script) ?: [];
        $first = trim((string)($lines[0] ?? ''));
        if (self::normalizeText($first) === $normTitle) {
            array_shift($lines);
            $rest = self::trimLeadingTitlePunct(trim(implode("\n", $lines)));
            return $rest !== '' ? $rest : $script;
        }

        $rest = self::trimLeadingTitlePunct(trim(self::stripNormalizedPrefix($script, $normTitle)));
        return $rest !== '' ? $rest : $script;
    }

    /**
     * 现写口播必须点到话题：topic 有效长度≥4 时，去掉空白与常见标点后，正文须包含话题（或去掉书名号/引号后的核心片段）。
     */
    public static function scriptTouchesTopic(string $topic, string $script): bool
    {
        $normTopic = self::normalizeForTopicGuard($topic);
        if (mb_strlen($normTopic) < 4) {
            return true;
        }
        $normScript = self::normalizeForTopicGuard($script);
        if ($normScript === '') {
            return false;
        }
        if (mb_strpos($normScript, $normTopic) !== false) {
            return true;
        }
        $core = self::normalizeForTopicGuard((string)preg_replace('/[《》〈〉「」『』【】\[\]“”‘’"\']+/u', '', $topic));
        if (mb_strlen($core) >= 4 && $core !== $normTopic && mb_strpos($normScript, $core) !== false) {
            return true;
        }
        return false;
    }

    /**
     * 现写仍未点题时，在口播开头补一句带话题原文的开场，避免整单作废。
     */
    public static function ensureTopicMentioned(string $topic, string $script): string
    {
        $topic = trim($topic);
        $script = trim($script);
        if ($topic === '' || self::scriptTouchesTopic($topic, $script)) {
            return $script;
        }
        $lead = '最近大家都在聊' . $topic . '。';
        return $script === '' ? $lead : $lead . $script;
    }

    public static function normalizeForTopicGuard(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        $text = preg_replace('/[[:punct:]，。！？、；：…—·（）()【】《》〈〉「」『』“”‘’]/u', '', $text) ?? $text;
        return $text;
    }

    public static function applyCtaToScriptResult(array $result, string $cta): array
    {
        $script = (string)($result['script'] ?? '');
        $withCta = self::ensureCtaAppended($script, $cta);
        if ($withCta === $script) {
            return $result;
        }
        $wordCount = mb_strlen(preg_replace('/\s+/u', '', $withCta) ?? '');
        $result['script'] = $withCta;
        $result['word_count'] = $wordCount;
        $result['est_duration_sec'] = max(1, (int)round($wordCount / self::CHARS_PER_SEC));
        HotspotLog::write(sprintf(
            '口播文案已追加结尾引导语：字数=%d 预估秒数=%d',
            $wordCount,
            (int)$result['est_duration_sec']
        ));
        return $result;
    }

    /**
     * @return array{targetWords:int,minWords:int,maxWords:int,ctaChars:int,bodyMax:int}
     */
    public static function durationBand(int $durationSec, string $cta = ''): array
    {
        $durationSec = max(15, min(180, $durationSec));
        $targetWords = (int)floor($durationSec * self::CHARS_PER_SEC);
        $minWords = (int)floor($targetWords * 0.7);
        $maxWords = $targetWords;
        $ctaChars = self::wordCount($cta);
        $bodyMax = $ctaChars > 0 ? max($minWords, $maxWords - $ctaChars) : $maxWords;
        return [
            'targetWords' => $targetWords,
            'minWords' => $minWords,
            'maxWords' => $maxWords,
            'ctaChars' => $ctaChars,
            'bodyMax' => $bodyMax,
        ];
    }

    public static function copywritingLength(array $opt): int
    {
        return self::COPYWRITING_LENGTH;
    }

    public static function wordCount(string $text): int
    {
        return mb_strlen(preg_replace('/\s+/u', '', $text) ?? '');
    }

    public static function trimScriptToMaxChars(string $script, int $maxChars, int $minChars = 0): string
    {
        $script = trim($script);
        if ($script === '') {
            return $script;
        }
        if ($maxChars <= 0) {
            return '';
        }
        if (self::wordCount($script) <= $maxChars) {
            return $script;
        }
        if ($minChars > $maxChars) {
            $minChars = 0;
        }

        $len = mb_strlen($script);
        $count = 0;
        $lastGoodSentenceEnd = -1;
        $lastSoftEnd = -1;
        $cutAt = -1;
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($script, $i, 1);
            if (preg_match('/\s/u', $ch) !== 1) {
                $count++;
            }
            if ($count > $maxChars) {
                $cutAt = $i;
                break;
            }
            if (str_contains('。！？', $ch) && $count >= $minChars) {
                $lastGoodSentenceEnd = $i;
            } elseif ($minChars > 0 && $count >= $minChars && str_contains('，、；;…', $ch)) {
                $lastSoftEnd = $i;
            }
        }
        if ($cutAt < 0) {
            return $script;
        }
        if ($lastGoodSentenceEnd >= 0) {
            $end = $lastGoodSentenceEnd + 1;
        } elseif ($lastSoftEnd >= 0) {
            $end = $lastSoftEnd + 1;
        } else {
            $end = $cutAt;
        }
        $trimmed = trim(mb_substr($script, 0, $end));
        return $trimmed !== '' ? $trimmed : $script;
    }

    public static function recountScriptResult(array $result): array
    {
        $wordCount = self::wordCount((string)($result['script'] ?? ''));
        $result['word_count'] = $wordCount;
        $result['est_duration_sec'] = max(1, (int)round($wordCount / self::CHARS_PER_SEC));
        return $result;
    }

    public static function enforceDurationCap(array $result, array $band, string $cta): array
    {
        $bodyMax = (int)($band['bodyMax'] ?? 0);
        $maxWords = (int)($band['maxWords'] ?? $bodyMax);
        $minWords = (int)($band['minWords'] ?? 0);
        $script = (string)($result['script'] ?? '');
        $trimmed = self::trimScriptToMaxChars($script, $bodyMax, $minWords);
        if ($trimmed !== trim($script)) {
            HotspotLog::write(sprintf(
                '口播文案已按时长截断正文：原文%d字 上限%d字 截后%d字',
                self::wordCount($script),
                $bodyMax,
                self::wordCount($trimmed)
            ));
            $result['script'] = $trimmed;
            $result = self::recountScriptResult($result);
        }

        $result = self::applyCtaToScriptResult($result, $cta);
        $cta = trim($cta);
        $total = self::wordCount((string)($result['script'] ?? ''));
        if ($total <= $maxWords) {
            return $result;
        }

        if ($cta !== '' && self::wordCount($cta) >= $maxWords) {
            HotspotLog::write('结尾引导语长于口播上限，保留完整引导语');
            $result['script'] = $cta;
            return self::recountScriptResult($result);
        }

        $body = trim((string)($result['script'] ?? ''));
        if ($cta !== '' && str_ends_with($body, $cta)) {
            $body = trim(mb_substr($body, 0, mb_strlen($body) - mb_strlen($cta)));
        }
        $ctaChars = $cta !== '' ? self::wordCount($cta) : 0;
        $bodyBudget = $cta !== '' ? max(0, $maxWords - $ctaChars) : $maxWords;
        $bodyMin = $cta !== '' ? max(0, $minWords - $ctaChars) : $minWords;
        $body = self::trimScriptToMaxChars($body, $bodyBudget, $bodyMin);
        $result['script'] = self::ensureCtaAppended($body, $cta);
        return self::recountScriptResult($result);
    }

    private static function preferCopywritingResult(
        array $current,
        array $retry,
        bool $titleEcho,
        string $topic
    ): array {
        $currentTouches = self::scriptTouchesTopic($topic, (string)($current['script'] ?? ''));
        $retryTouches = self::scriptTouchesTopic($topic, (string)($retry['script'] ?? ''));
        if (!$retryTouches) {
            return $current;
        }
        if (!$currentTouches) {
            return $retry;
        }
        $retryEcho = self::isTitle2Echo($retry, $topic);
        if ($titleEcho && !$retryEcho) {
            return $retry;
        }
        return $current;
    }

    private static function inWordBand(int $words, int $minWords, int $bodyMax): bool
    {
        return $words >= $minWords && $words <= $bodyMax;
    }

    private static function isTitle2Echo(array $result, string $topic): bool
    {
        if (empty($result['_title_from_title2'])) {
            return false;
        }
        return self::normalizeText((string)($result['title'] ?? '')) === self::normalizeText($topic);
    }

    private static function exposeScriptResult(array $result): array
    {
        unset($result['_title_from_title2']);
        return $result;
    }

    private static function normalizeText(string $text): string
    {
        return preg_replace('/\s+/u', '', trim($text)) ?? '';
    }

    private static function trimLeadingTitlePunct(string $text): string
    {
        return preg_replace('/^[\s，,、：:；;。.！!？?]+/u', '', $text) ?? $text;
    }

    private static function stripNormalizedPrefix(string $script, string $normTitle): string
    {
        $titleLen = mb_strlen($normTitle);
        $scriptLen = mb_strlen($script);
        $matched = 0;
        $i = 0;
        while ($i < $scriptLen && $matched < $titleLen) {
            $ch = mb_substr($script, $i, 1);
            if (preg_match('/\s/u', $ch) === 1) {
                $i++;
                continue;
            }
            if ($ch !== mb_substr($normTitle, $matched, 1)) {
                return $script;
            }
            $matched++;
            $i++;
        }
        if ($matched < $titleLen) {
            return $script;
        }
        return mb_substr($script, $i);
    }

    public static function buildCopywritingParams(
        string $topic,
        string $platform,
        array $corePoints,
        string $summary,
        array $persona,
        array $analysis,
        array $opt,
        int $userId
    ): array {
        return [
            'model' => 1,
            'persona' => self::resolvePersonaClue($persona, $userId),
            'original' => self::composeOriginal($topic, $summary, !empty($opt['include_topic_in_original'])),
            'voice' => $opt['direction'] ?? '',
            'hook' => self::composeCopywritingHook($opt),
            'channelVersion' => self::CHANNEL_VERSION,
            'sn' => self::COPYWRITING_SN,
            'type' => self::COPYWRITING_TYPE,
            'number' => self::COPYWRITING_NUMBER,
            'length' => self::copywritingLength($opt),
            'persona_id' => max(0, (int)($persona['id'] ?? 0)),
            'user_id' => $userId,
            'keywords' => self::buildCopywritingKeywords($topic, $platform, $corePoints, $summary, $persona, $analysis, $opt),
            'keyword_priority' => 'theme',
            
        ];
    }

    /**
     * 热点生成把话题写在 original 最前，摘要接在后面。摘要里已有完整话题则不重复。
     */
    public static function composeOriginal(string $topic, string $summary, bool $includeTopic): string
    {
        if (!$includeTopic) {
            return $summary;
        }
        $topic = trim($topic);
        $summary = trim($summary);
        if ($topic === '') {
            return $summary;
        }
        if ($summary !== '' && str_contains($summary, $topic)) {
            return $summary;
        }
        $line = '热点话题：' . $topic;
        return $summary === '' ? $line : $line . "\n" . $summary;
    }

    public static function composeCopywritingHook(array $opt): string
    {
        $label = PromptService::goalLabel((string)($opt['goal'] ?? ''));
        $product = trim((string)($opt['product'] ?? ''));
        if (($opt['goal'] ?? '') === 'sell' && $product !== '') {
            return $label . '：' . $product;
        }
        return $label;
    }

    private static function resolvePersonaClue(array $persona, int $userId): string
    {
        if (array_key_exists('personaClueError', self::$testHooks)) {
            $err = trim((string)self::$testHooks['personaClueError']);
            throw new HotspotUpstreamException($err !== '' ? $err : '人设不存在');
        }
        if (array_key_exists('personaClue', self::$testHooks)) {
            return (string)self::$testHooks['personaClue'];
        }

        $id = max(0, (int)($persona['id'] ?? 0));
        if ($id <= 0 || $userId <= 0) {
            return '';
        }
        // 既有冒烟/日志只注入 copywriting，避免误打真实库
        if (array_key_exists('copywriting', self::$testHooks) && !array_key_exists('personaModel', self::$testHooks)) {
            return '';
        }

        $model = self::$testHooks['personaModel'] ?? null;
        if (!$model instanceof AiPersona) {
            // 状态不进查询条件：选择页（PersonaService::list）不按 status 过滤，
            // 冻结的人设照样列着。连 status 一起查会让「被冻结」和「真不存在」
            // 都报成「人设不存在」，用户对着列表里明明还在的人设无从判断。
            $model = AiPersona::with(['individual', 'enterprise', 'local'])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->findOrEmpty();
            if (!$model->isEmpty() && (int)$model->status !== AiPersona::STATUS_ENABLED) {
                throw new HotspotUpstreamException('该人设已冻结，恢复会员权益后可继续使用');
            }
        }
        if ($model->isEmpty()) {
            throw new HotspotUpstreamException('人设不存在');
        }

        $rule = self::resolvePersonaRule($model);
        if ($rule === null || (method_exists($rule, 'isEmpty') && $rule->isEmpty())) {
            throw new HotspotUpstreamException('IP人设规则不存在');
        }
        return (string)$rule->getClueContent($model);
    }

    private static function resolvePersonaRule(AiPersona $persona)
    {
        return match ((int)$persona->persona_type) {
            1 => $persona->individual,
            2 => $persona->enterprise,
            3 => $persona->local,
            default => null,
        };
    }

    /**
     * 接口 title 只取 getCopywriting 的 title2；缺失/空白/非标量回退话题。
     * @return array{title:string,fromTitle2:bool}
     */
    public static function pickCopywritingTitle(array $payload, string $topic): array
    {
        $raw = $payload['title2'] ?? null;
        $fromTitle2 = is_scalar($raw) && trim((string)$raw) !== '';
        return [
            'title' => $fromTitle2 ? trim((string)$raw) : $topic,
            'fromTitle2' => $fromTitle2,
        ];
    }

    public static function adaptCopywritingResult(string $topic, mixed $data): array
    {
        $payload = is_array($data) ? ($data['content'] ?? $data) : $data;
        if (is_array($payload) && self::isStructuredCopywriting($payload)) {
            $script = self::pickStructuredScript($payload);
            if ($script === '') {
                throw new HotspotUpstreamException('生成失败');
            }
            $picked = self::pickCopywritingTitle($payload, $topic);
            $hashtags = self::normalizeHashtags($payload['hashtags'] ?? $payload['analysis_tags'] ?? []);
            $shots = self::normalizeShots($payload['shots'] ?? []);
            return self::finalizeCopywritingResult($topic, $picked['title'], $script, $hashtags, $shots, $picked['fromTitle2']);
        }

        $raw = self::extractCopywritingText($data);
        if ($raw === '') {
            throw new HotspotUpstreamException('生成失败');
        }

        $parsed = JsonBlockParser::parse($raw);
        if (is_array($parsed) && !empty($parsed['script']) && is_scalar($parsed['script'])) {
            $picked = self::pickCopywritingTitle($parsed, $topic);
            $script = trim((string)$parsed['script']);
            $hashtags = self::normalizeHashtags($parsed['hashtags'] ?? []);
            $shots = self::normalizeShots($parsed['shots'] ?? []);
            return self::finalizeCopywritingResult($topic, $picked['title'], $script, $hashtags, $shots, $picked['fromTitle2']);
        }

        HotspotLog::write('口播文案JSON解析失败，回落原文：' . HotspotLog::clip($raw, 400));
        $title = $topic;
        $script = trim($raw);
        $hashtags = [];
        $shots = [];
        if ($script === '' || str_starts_with($script, '{') || str_starts_with($script, '[')) {
            throw new HotspotUpstreamException('文案生成结果异常，请重试');
        }

        return self::finalizeCopywritingResult($topic, $title, $script, $hashtags, $shots, false);
    }

    private static function finalizeCopywritingResult(
        string $topic,
        string $title,
        string $script,
        array $hashtags,
        array $shots,
        bool $fromTitle2 = false
    ): array {
        $finalTitle = $title !== '' ? $title : $topic;
        $stripped = self::stripLeadingTitle($script, $finalTitle);
        if ($stripped !== $script) {
            HotspotLog::write('口播文案已去掉标题前缀：标题=' . $finalTitle);
            $script = $stripped;
        }
        $wordCount = mb_strlen(preg_replace('/\s+/u', '', $script) ?? '');
        return [
            'topic' => $topic,
            'title' => $finalTitle,
            'script' => $script,
            'word_count' => $wordCount,
            'est_duration_sec' => max(1, (int)round($wordCount / self::CHARS_PER_SEC)),
            'hashtags' => array_slice($hashtags, 0, 5),
            'shots' => array_slice($shots, 0, 5),
            '_title_from_title2' => $fromTitle2,
        ];
    }

    public static function normalizeOptions(array $options): array
    {
        $goal = (string)($options['goal'] ?? 'traffic');
        if (!in_array($goal, AnalyzeService::GOALS, true)) {
            $goal = 'traffic';
        }
        $direction = (string)($options['direction'] ?? '观点输出');
        if (!in_array($direction, AnalyzeService::DIRECTIONS, true)) {
            $direction = '观点输出';
        }
        $personaMode = (int)($options['persona_mode'] ?? self::PERSONA_MODE_PERSONA);
        if (!in_array($personaMode, self::PERSONA_MODES, true)) {
            $personaMode = self::PERSONA_MODE_PERSONA;
        }
        $mode = (string)($options['material_mode'] ?? 'ai_persona');
        if (!in_array($mode, ['ai', 'ai_persona', 'persona'], true)) {
            $mode = 'ai_persona';
        }
        // 不选人设时本地素材库无归属可查（素材按 persona_id 归属），素材来源只能是纯 AI 找素材
        if ($personaMode === self::PERSONA_MODE_MANUAL) {
            $mode = 'ai';
        }
        $duration = (int)($options['duration_sec'] ?? 60);
        $duration = max(15, min(180, $duration));
        $videoType = trim((string)($options['video_type'] ?? ''));
        if (!in_array($videoType, self::VIDEO_TYPES, true)) {
            $videoType = '';
        }
        $avatar = mb_substr(trim((string)($options['avatar'] ?? '')), 0, 64);
        $avatarId = (int)($options['avatar_id'] ?? 0);
        if ($avatarId < 0) {
            $avatarId = 0;
        }
        // 自选音色仅「不选人设」模式使用；人设模式的音色跟随人设绑定，落库也不保留
        $voice = mb_substr(trim((string)($options['voice'] ?? '')), 0, 64);
        $voiceId = (int)($options['voice_id'] ?? 0);
        if ($personaMode !== self::PERSONA_MODE_MANUAL) {
            $voiceId = 0;
        } elseif ($voiceId < 0 && $voiceId !== ManualAssetService::ORIGIN_VOICE_ID) {
            // 负数只允许「形象原音」这一个哨兵值，其余当没选
            $voiceId = 0;
        }
        if ($voiceId === 0) {
            $voice = '';
        }
        // 音乐音量与口播语速：人设模式跟随人设合成配置（这里恒为系统默认、下发时不读），
        // 不选人设没有人设配置可依，改由用户在高级设置里自选
        $musicVolume = AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT;
        $speechRate = AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT;
        // 剪辑模板与自选背景音乐同理：人设模式跟随人设合成配置，这里恒为空、下发时不读
        $clipTemplateId = '';
        $musicUrl = '';
        // music_source / music_name 只为还原现场：选了「上传我的音乐」却没传成功时
        // 光看 music_url 分不清是「要系统随机」还是「还没传」，回到页面会显示成系统随机
        $musicSource = ManualAssetService::MUSIC_SOURCE_SYSTEM;
        $musicName = '';
        $systemMusicId = '';
        if ($personaMode === self::PERSONA_MODE_MANUAL) {
            $musicVolume = AiPersonaSynthesisConfig::normalizeMusicVolume(
                $options['music_volume'] ?? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT
            );
            $speechRate = AiPersonaSynthesisConfig::normalizeSpeechRate(
                $options['speech_rate'] ?? AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT
            );
            $clipTemplateId = mb_substr(trim((string)($options['clip_template_id'] ?? '')), 0, 64);
            $musicUrl = mb_substr(trim((string)($options['music_url'] ?? '')), 0, 512);
            $musicName = mb_substr(trim((string)($options['music_name'] ?? '')), 0, 128);
            $rawSource = (int)($options['music_source'] ?? ManualAssetService::MUSIC_SOURCE_SYSTEM);
            $musicSource = in_array($rawSource, [
                ManualAssetService::MUSIC_SOURCE_UPLOAD,
                ManualAssetService::MUSIC_SOURCE_PICK,
            ], true) ? $rawSource : ManualAssetService::MUSIC_SOURCE_SYSTEM;
            $systemMusicId = mb_substr(trim((string)($options['system_music_id'] ?? '')), 0, 32);
            // 传了曲子就一定是自传，来源没传或传错也按实际情况纠正
            if ($musicUrl !== '') {
                $musicSource = ManualAssetService::MUSIC_SOURCE_UPLOAD;
                $systemMusicId = '';
            } elseif ($musicSource === ManualAssetService::MUSIC_SOURCE_PICK) {
                if ($systemMusicId === '') {
                    $musicSource = ManualAssetService::MUSIC_SOURCE_SYSTEM;
                }
            } elseif ($musicSource === ManualAssetService::MUSIC_SOURCE_SYSTEM) {
                $musicName = '';
                $systemMusicId = '';
            }
        }
        $cta = trim((string)($options['cta'] ?? ''));
        if ($cta === '') {
            $cta = trim((string)($options['ending_guide'] ?? ''));
        }
        if ($cta === '') {
            $cta = trim((string)($options['ending'] ?? ''));
        }
        $out = [
            'goal' => $goal,
            'direction' => $direction,
            'persona_mode' => $personaMode,
            'material_mode' => $mode,
            'duration_sec' => $duration,
            'product' => (string)($options['product'] ?? ''),
            'cta' => $cta,
            'video_type' => $videoType,
            'avatar' => $avatar,
            'avatar_id' => $avatarId,
            'voice' => $voice,
            'voice_id' => $voiceId,
            'music_volume' => $musicVolume,
            'speech_rate' => $speechRate,
            'clip_template_id' => $clipTemplateId,
            'music_url' => $musicUrl,
            'music_source' => $musicSource,
            'music_name' => $musicName,
            'system_music_id' => $systemMusicId,
            'materials' => self::normalizeClipMaterials($options['materials'] ?? []),
            'hashtags' => self::normalizeHashtags($options['hashtags'] ?? []),
            'shots' => self::normalizeShots($options['shots'] ?? []),
            'skip_topic_guard' => !empty($options['skip_topic_guard']),
            'include_topic_in_original' => !empty($options['include_topic_in_original']),
        ];
        if (self::hasFrozenMaterialUseMode($options)) {
            $out['material_use_mode'] = ShanjianVideoSettingLogic::normalizeMaterialUseMode(
                $options['material_use_mode']
            );
        }
        return $out;
    }

    public static function hasFrozenMaterialUseMode(array $opt): bool
    {
        return array_key_exists('material_use_mode', $opt)
            && ShanjianVideoSettingLogic::isValidMaterialUseModeInput($opt['material_use_mode']);
    }

    /**
     * 下发用人设素材模式：优先任务创建时冻结的快照，没有才现读人设。
     */
    public static function resolveFrozenMaterialUseMode(array $opt, int $personaId): int
    {
        if (self::hasFrozenMaterialUseMode($opt)) {
            return ShanjianVideoSettingLogic::normalizeMaterialUseMode($opt['material_use_mode']);
        }
        return ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode($personaId);
    }

    /**
     * 按 ID 回表是否滤 keep_original：有快照返回 1/2，没有返回 null（不按现态滤）。
     */
    public static function resolveClipReloadUseMode(array $opt): ?int
    {
        if (!self::hasFrozenMaterialUseMode($opt)) {
            return null;
        }
        return ShanjianVideoSettingLogic::normalizeMaterialUseMode($opt['material_use_mode']);
    }

    /**
     * 勾选了人设素材但回表 0 条则失败，避免数字人口播写成空素材。
     *
     * @param list<int|string> $selectedIds
     * @param list<array<string, mixed>> $loadedItems
     */
    public static function assertSelectedMaterialsReloaded(array $selectedIds, array $loadedItems): void
    {
        $selected = self::normalizePositiveIdList($selectedIds);
        if ($selected === []) {
            return;
        }
        foreach ($loadedItems as $item) {
            if (is_array($item) && $item !== []) {
                return;
            }
        }
        throw new HotspotUpstreamException('已选素材不可用，可能已删、已禁用或与提交时的素材模式不符，请重新选择');
    }

    /**
     * 已选 ID 里回表未命中的部分，保持勾选顺序。
     *
     * @param list<int|string> $selectedIds
     * @param list<int|string> $foundIds
     * @return list<int>
     */
    /**
     * 纯 AI 找素材不使用人设 ID，提交时不必按快照校对残留勾选。
     */
    public static function shouldValidateSelectedMaterialIds(array $opt): bool
    {
        if ((string)($opt['material_mode'] ?? '') === 'ai') {
            return false;
        }
        return self::normalizePositiveIdList($opt['materials'] ?? []) !== [];
    }

    /**
     * 已勾人设素材但闪剪设置 material 为空：重试不能复用该空单，否则后台仍是空列。
     */
    public static function shouldRedispatchEmptyPersonaMaterials(array $opt, mixed $settingMaterial): bool
    {
        if (!self::shouldValidateSelectedMaterialIds($opt)) {
            return false;
        }
        $material = is_array($settingMaterial) ? $settingMaterial : [];
        return ShanjianVideoSettingLogic::flattenMaterialItems($material) === [];
    }

    public static function selectedIdsMissingFromReload(array $selectedIds, array $foundIds): array
    {
        $found = [];
        foreach (self::normalizePositiveIdList($foundIds) as $id) {
            $found[$id] = true;
        }
        $missing = [];
        foreach (self::normalizePositiveIdList($selectedIds) as $id) {
            if (!isset($found[$id])) {
                $missing[] = $id;
            }
        }
        return $missing;
    }

    /**
     * @param list<int|string>|array<int|string, mixed> $raw
     * @return list<int>
     */
    public static function normalizePositiveIdList(array $raw): array
    {
        $out = [];
        foreach ($raw as $id) {
            if (is_array($id)) {
                $id = $id['material_id'] ?? $id['id'] ?? 0;
            }
            $id = (int)$id;
            if ($id <= 0 || in_array($id, $out, true)) {
                continue;
            }
            $out[] = $id;
        }
        return $out;
    }

    public static function assertCreateOptions(array $options): void
    {
        $opt = self::normalizeOptions($options);
        if ($opt['video_type'] === '') {
            throw new HotspotUpstreamException('请选择视频类型');
        }
        // 不选人设：形象与音色都要用户自选（音色不再随人设形象捆绑），素材恒为 AI 找素材无需校验
        if ($opt['persona_mode'] === self::PERSONA_MODE_MANUAL) {
            if ($opt['video_type'] === 'digital' && $opt['avatar_id'] <= 0) {
                throw new HotspotUpstreamException('数字人口播混剪需要选择数字人形象');
            }
            if ($opt['voice_id'] === 0) {
                throw new HotspotUpstreamException('请选择音色');
            }
            // 形象原音取自出镜形象克隆出的音色，素材混剪没有形象就没有原音
            if ($opt['voice_id'] === ManualAssetService::ORIGIN_VOICE_ID && $opt['video_type'] !== 'digital') {
                throw new HotspotUpstreamException('形象原音仅数字人口播混剪可用，请改选音色');
            }
            // 选了自传音乐却没传成功：默默回落系统音乐等于把用户的选择吞了，直接拦下来
            if ($opt['music_source'] === ManualAssetService::MUSIC_SOURCE_UPLOAD && $opt['music_url'] === '') {
                throw new HotspotUpstreamException('请先上传背景音乐，或把背景音乐改回「系统音乐随机」');
            }
            if ($opt['music_source'] === ManualAssetService::MUSIC_SOURCE_PICK
                && !\app\common\service\SystemMusicService::isEnabled((string)($opt['system_music_id'] ?? ''))) {
                throw new HotspotUpstreamException('请先选择一首系统音乐，或把背景音乐改回「系统音乐随机」');
            }
            return;
        }
        if ($opt['video_type'] === 'digital' && $opt['avatar_id'] <= 0 && $opt['avatar'] === '') {
            throw new HotspotUpstreamException('数字人口播混剪需要选择数字人形象');
        }
        if ($opt['material_mode'] === 'ai_persona' && $opt['materials'] === []) {
            throw new HotspotUpstreamException('「AI+人设素材」需选择人设素材');
        }
        if ($opt['material_mode'] === 'persona' && $opt['materials'] === []) {
            throw new HotspotUpstreamException(
                $opt['video_type'] === 'clips' ? '素材混剪必须选择素材' : '纯人设素材必须选择混剪素材'
            );
        }
    }

    public static function normalizeHashtags(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $h) {
            $h = ltrim(trim((string)$h), '#');
            if ($h !== '' && !in_array($h, $out, true)) {
                $out[] = $h;
            }
        }
        return array_slice($out, 0, 5);
    }

    public static function normalizeShots(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $s) {
            $s = trim((string)$s);
            if ($s !== '') {
                $out[] = $s;
            }
        }
        return array_slice($out, 0, 5);
    }

    private static function requestCopywriting(array $params): array
    {
        self::$lastCopywritingParams = $params;
        HotspotLog::json('口播文案调用getCopywriting请求参数：', HotspotLog::safe($params), 3000);
        if (array_key_exists('copywriting', self::$testHooks)) {
            $hook = self::$testHooks['copywriting'];
            $data = is_callable($hook) ? $hook($params) : $hook;
            if ($data === false) {
                $err = (string)(self::$testHooks['copywritingError'] ?? '生成失败');
                HotspotLog::write('口播文案调用getCopywriting失败：' . $err);
                self::throwCopywritingError($err, (int)(self::$testHooks['copywritingCode'] ?? 0));
            }
            if (!is_array($data)) {
                HotspotLog::write('口播文案调用getCopywriting返回值：非数组');
                throw new HotspotUpstreamException('生成失败');
            }
            HotspotLog::json('口播文案调用getCopywriting返回值：', HotspotLog::safe($data), 3000);
            return $data;
        }

        $ok = KbRobotLogic::getCopywriting($params);
        if (!$ok) {
            $err = (string)KbRobotLogic::getError();
            HotspotLog::write('口播文案调用getCopywriting失败：' . $err);
            self::throwCopywritingError($err);
        }
        $data = KbRobotLogic::getReturnData();
        HotspotLog::json('口播文案调用getCopywriting返回值：', is_array($data) ? HotspotLog::safe($data) : $data, 3000);
        if (!is_array($data) || $data === []) {
            throw new HotspotUpstreamException('生成失败');
        }
        return $data;
    }

    private static function throwCopywritingError(string $msg, int $code = 0): void
    {
        if (str_contains($msg, '人设不存在')) {
            throw new HotspotUpstreamException('人设不存在');
        }
        if ($code === 4059 || str_contains($msg, '算力不足')) {
            throw new HotspotUpstreamException($msg, 4059);
        }
        throw new HotspotUpstreamException($msg !== '' ? $msg : '生成失败', $code);
    }

    private static function buildCopywritingKeywords(
        string $topic,
        string $platform,
        array $corePoints,
        string $summary,
        array $persona,
        array $analysis,
        array $opt
    ): string {
        $lines = [];
        foreach ($corePoints as $p) {
            if (is_array($p) && !empty($p['label'])) {
                $lines[] = '- ' . $p['label'] . '：' . ($p['detail'] ?? '');
            }
        }
        $pointsText = $lines !== [] ? implode("\n", $lines) : '（无）';

        $parts = [
            '平台：' . PromptService::platformLabel($platform),
            '热点话题：' . $topic,
            '核心要点：' . "\n" . $pointsText,
            '背景材料：' . "\n" . mb_substr($summary, 0, 2000),
            '目标：' . PromptService::goalLabel((string)($opt['goal'] ?? '')),
            '内容方向：' . (string)($opt['direction'] ?? ''),
            '素材模式：' . PromptService::materialLabel((string)($opt['material_mode'] ?? '')),
            '口播时长：' . (int)($opt['duration_sec'] ?? 60) . '秒',
        ];
        if (($opt['goal'] ?? '') === 'sell' && trim((string)($opt['product'] ?? '')) !== '') {
            $parts[] = '要推的产品/卖点：' . $opt['product'];
        }
        if (trim((string)($opt['cta'] ?? '')) !== '') {
            $parts[] = '结尾引导必须原文原样作为 script 最后一句，不得改写、不得省略：' . $opt['cta'];
        }

        $hooks = $analysis['hooks'] ?? [];
        if (is_array($hooks) && $hooks !== []) {
            $hookParts = [];
            foreach (array_slice($hooks, 0, 2) as $h) {
                if (is_array($h) && !empty($h['label'])) {
                    $hookParts[] = $h['label'] . '（' . ($h['detail'] ?? '') . '）';
                }
            }
            if ($hookParts !== []) {
                $parts[] = '优先切入方式：' . implode('；', $hookParts);
            }
        }
        $risks = $analysis['risks'] ?? [];
        if (is_array($risks) && $risks !== []) {
            $safeRisks = [];
            foreach (array_slice($risks, 0, 2) as $risk) {
                if (is_scalar($risk) && trim((string)$risk) !== '') {
                    $safeRisks[] = trim((string)$risk);
                }
            }
            if ($safeRisks !== []) {
                $parts[] = '必须规避的风险：' . implode('；', $safeRisks);
            }
        }

        if ((int)($persona['id'] ?? 0) <= 0) {
            if (trim((string)($persona['name'] ?? '')) !== '') {
                $parts[] = '人设名称：' . $persona['name'];
            }
            if (trim((string)($persona['tag'] ?? '')) !== '') {
                $parts[] = '人设定位：' . $persona['tag'];
            }
            if (trim((string)($persona['tone'] ?? '')) !== '') {
                $parts[] = '说话风格：' . $persona['tone'];
            }
        }

        // 不选人设：没有人设档案可依，明确禁止编造身份，否则模型会自行虚构「我是XX领域博主/我们公司」
        if ((int)($opt['persona_mode'] ?? self::PERSONA_MODE_PERSONA) === self::PERSONA_MODE_MANUAL) {
            $parts[] = '本次不绑定任何博主人设：请用中立、通用的口播口吻就事论事，'
                . '不得虚构博主身份、职业、主营业务、案例或未在上文出现的产品信息。';
        }

        $parts[] = '【输出硬性要求】'
            . "1) title2 必须是原创短视频标题，禁止整句照抄热点话题原文「{$topic}」，可借热点做钩子但标题要重新组织；"
            . '2) script 只写口播正文，禁止把 title2 原文写在开头；'
            . "3) script 正文必须自然点到热点话题原文「{$topic}」（出现完整话题，可加口语连接或书名号），不得只写人设主营、完全不提该热点。";

        return implode("\n", $parts);
    }

    private static function isStructuredCopywriting(array $content): bool
    {
        foreach (['rewritten_text1', 'rewritten_text', 'script', 'title'] as $key) {
            if (array_key_exists($key, $content)) {
                return true;
            }
        }
        return false;
    }

    public static function pickStructuredScript(array $content): string
    {
        foreach (['script', 'rewritten_text1', 'rewritten_text', 'content', 'text'] as $key) {
            if (isset($content[$key]) && is_scalar($content[$key]) && trim((string)$content[$key]) !== '') {
                return trim((string)$content[$key]);
            }
        }
        return '';
    }

    private static function titleFromScript(string $script): string
    {
        $first = trim((string)strtok(str_replace(["\r\n", "\r"], "\n", $script), "\n"));
        if ($first === '') {
            return '';
        }
        if (preg_match('/^(.+?[。！？])/u', $first, $m)) {
            $first = $m[1];
        }
        if (mb_strlen($first, 'UTF-8') > 30) {
            $first = mb_substr($first, 0, 30, 'UTF-8');
        }
        return trim($first);
    }

    private static function extractCopywritingText(mixed $data): string
    {
        if (is_string($data)) {
            return trim($data);
        }
        if (!is_array($data)) {
            return '';
        }
        if (isset($data['content'])) {
            $content = $data['content'];
            if (is_string($content)) {
                return trim($content);
            }
            if (is_array($content)) {
                if (self::isStructuredCopywriting($content)) {
                    return self::pickStructuredScript($content);
                }
                foreach ($content as $item) {
                    if (is_scalar($item) && trim((string)$item) !== '') {
                        return trim((string)$item);
                    }
                    if (is_array($item)) {
                        foreach (['rewritten_text', 'content', 'text', 'script'] as $key) {
                            if (isset($item[$key]) && is_scalar($item[$key]) && trim((string)$item[$key]) !== '') {
                                return trim((string)$item[$key]);
                            }
                        }
                    }
                }
            }
        }
        foreach (['text', 'script'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key]) && trim((string)$data[$key]) !== '') {
                return trim((string)$data[$key]);
            }
        }
        foreach ($data as $item) {
            if (is_scalar($item) && trim((string)$item) !== '') {
                return trim((string)$item);
            }
        }
        return '';
    }

    private static function costItem(string $scene): array
    {
        $score = 0.0;
        $unit = '算力/秒';
        try {
            $row = ModelConfig::where('scene', $scene)->findOrEmpty();
            if (!$row->isEmpty()) {
                $score = (float)($row->score ?? 0);
                $unit = (string)($row->unit ?? '算力/秒');
            }
        } catch (\Throwable $e) {
            HotspotLog::write('读取算力配置失败：' . $e->getMessage());
        }
        return [
            'scene' => $scene,
            'score' => $score,
            'unit' => $unit !== '' ? $unit : '算力/秒',
            'per_minute' => round($score * 60, 2),
        ];
    }

    private static function normalizeClipMaterials(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $id) {
            $id = (int)$id;
            if ($id <= 0 || in_array($id, $out, true)) {
                continue;
            }
            $out[] = $id;
        }
        return $out;
    }

}
