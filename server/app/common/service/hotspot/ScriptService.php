<?php

namespace app\common\service\hotspot;

use app\api\logic\kb\KbRobotLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\ModelConfig;

class ScriptService
{
    public const CHARS_PER_SEC = 4.2;

    public const VIDEO_TYPES = ['digital', 'clips'];

    public const COPYWRITING_SN = 2;

    public const COPYWRITING_TYPE = 1;

    public const COPYWRITING_NUMBER = 1;

    public const COPYWRITING_LENGTH = 500;

    public const CHANNEL_VERSION = 24;

    public const MIN_TITLE_STRIP_CHARS = 6;

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
        $data = self::requestCopywriting($params);
        $result = self::adaptCopywritingResult($topic, $data);

        // 不再按时长卡字数；仅 title2 照抄话题时重试
        $titleEcho = self::isTitle2Echo($result, $topic);
        if ($titleEcho) {
            HotspotLog::write(sprintf(
                '口播文案不合格触发重试：话题=%s 字数=%d 标题照抄=是',
                $topic,
                (int)$result['word_count']
            ));
            $params['keywords'] .= "\n【重要】上一次生成不合格，本次必须严格满足："
                . "title2 必须是原创短视频标题，禁止直接使用热点话题原文「{$topic}」。";
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
            'original' => $summary,
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
            
        ];
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
            $model = AiPersona::with(['individual', 'enterprise', 'local'])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->findOrEmpty();
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
        $mode = (string)($options['material_mode'] ?? 'ai_persona');
        if (!in_array($mode, ['ai', 'ai_persona', 'persona'], true)) {
            $mode = 'ai_persona';
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
        $cta = trim((string)($options['cta'] ?? ''));
        if ($cta === '') {
            $cta = trim((string)($options['ending_guide'] ?? ''));
        }
        if ($cta === '') {
            $cta = trim((string)($options['ending'] ?? ''));
        }
        return [
            'goal' => $goal,
            'direction' => $direction,
            'material_mode' => $mode,
            'duration_sec' => $duration,
            'product' => (string)($options['product'] ?? ''),
            'cta' => $cta,
            'video_type' => $videoType,
            'avatar' => $avatar,
            'avatar_id' => $avatarId,
            'materials' => self::normalizeClipMaterials($options['materials'] ?? []),
            'hashtags' => self::normalizeHashtags($options['hashtags'] ?? []),
            'shots' => self::normalizeShots($options['shots'] ?? []),
        ];
    }

    public static function assertCreateOptions(array $options): void
    {
        $opt = self::normalizeOptions($options);
        if ($opt['video_type'] === '') {
            throw new HotspotUpstreamException('请选择视频类型');
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

        $parts[] = '【输出硬性要求】'
            . "1) title2 必须是原创的短视频标题，禁止直接使用热点话题原文「{$topic}」或其简单复述，要有吸引点击的表达；"
            . '2) script 只写口播正文，禁止把 title 原文写在开头。';

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

    private static function pickStructuredScript(array $content): string
    {
        foreach (['rewritten_text1', 'rewritten_text', 'script', 'content', 'text'] as $key) {
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
