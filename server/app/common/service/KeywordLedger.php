<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Log;

/**
 * AI 找素材词级账本（任务内运行时状态，不落库）
 *
 * 设计约束（对应 v4 方案）：
 * - 一词一类型只允许一次付费搜索：pickKeyword() 即记账一次 search_count；
 * - 同一词对视频/图片分别各一次机会（类型隔离：不同类型召回结果与画面来源不同）；
 * - 全候选被拒 markFailed() 后，同类型永久失效，后续阶段（含行业兜底）不得再取；
 * - 替代词只来自本账本 available 队列（长尾词在前、泛词垫底），不改写不造词；
 * - 快照 snapshot() 携带 available/used/failed/reserved/search_count，可离线重放决策链，
 *   且 failed 列表可供 retryFailedGenerate 阻断上一轮失败词（loadBlocked）。
 */
class KeywordLedger
{
    public const TYPE_VIDEO = 'video';
    public const TYPE_IMAGE = 'image';

    public const POOL_LONGTAIL = 'longtail';
    public const POOL_GENERIC = 'generic';

    public const REJECT_TEXT_GATE = 'text_gate';
    public const REJECT_VISUAL_GATE = 'visual_gate';
    public const REJECT_TRANSCODE = 'transcode_fail';
    public const REJECT_DUP_URL = 'dup_url';
    public const REJECT_EMPTY = 'empty';
    public const REJECT_INVALID_URL = 'invalid_url';

    /** REJECT_* → 中文标签（日志用，统一中文口径） */
    public const REJECT_LABELS = [
        self::REJECT_TEXT_GATE => '文本闸门拒绝',
        self::REJECT_VISUAL_GATE => '画面闸门拒绝',
        self::REJECT_TRANSCODE => '转存失败',
        self::REJECT_DUP_URL => '素材重复',
        self::REJECT_EMPTY => '搜索结果为空',
        self::REJECT_INVALID_URL => '来源地址不支持',
    ];

    public const MAX_WORD_LENGTH = 50; // 单词上限，超长截断

    /** @var array<int, array{word: string, pool_type: string}> 原始词池（保持传入顺序） */
    private array $words = [];

    /** @var string 账本所属上下文（日志 trace 用） */
    private string $traceId = '';

    /** @var array<string, array<string, int>> reserved[word][type] 占位时间戳 */
    private array $reserved = [];

    /** @var array<string, array<string, int>> used[word][type] = 入库条数 */
    private array $used = [];

    /** @var array<string, array<string, array<string, int>>> failed[word][type] = REJECT_* 计数 */
    private array $failed = [];

    /** @var array<string, array<string, int>> search_count[word][type] 付费搜索次数（恒 ≤1） */
    private array $searchCount = [];

    private const VALID_TYPES = [self::TYPE_VIDEO, self::TYPE_IMAGE];

    /**
     * @param array<int, string|array{word: string, pool_type?: string}> $words
     *        纯字符串视为长尾词；数组形态必须含 word 下标
     * @param string $traceId
     */
    public function __construct(array $words, string $traceId = '')
    {
        $this->traceId = $traceId;
        $seen = [];
        foreach ($words as $item) {
            $word = is_scalar($item) ? trim((string)$item) : trim((string)($item['word'] ?? ''));
            if ($word === '') {
                continue;
            }
            $word = mb_substr($word, 0, self::MAX_WORD_LENGTH, 'UTF-8');
            $key = mb_strtolower($word, 'UTF-8');
            if (isset($seen[$key])) {
                continue; // 跨形态去重，保持首个出现的词序
            }
            $seen[$key] = true;
            $poolType = (is_array($item) ? (string)($item['pool_type'] ?? '') : self::POOL_LONGTAIL);
            $this->words[] = [
                'word' => $word,
                'pool_type' => $poolType === self::POOL_GENERIC ? self::POOL_GENERIC : self::POOL_LONGTAIL,
            ];
        }
    }

    /**
     * 取下一个未使用关键词（取到即占位并记一次付费搜索）。
     * 词序：构造时的顺序（长尾在前，泛词垫底由调用方在构造时排好）。
     */
    public function pickKeyword(string $type, string $stage = ''): ?string
    {
        if (!$this->isValidType($type)) {
            self::logStage('keyword_pick_rejected', ['type' => $type, 'reason' => '未知素材类型']);
            return null;
        }
        $word = $this->nextAvailableWord($type);
        if ($word === null) {
            self::logStage('keywords_exhausted', $this->typeContext($type, [
                'type_label' => $type === self::TYPE_VIDEO ? '视频' : '图片',
                'used_count' => count(array_filter($this->used, fn(array $t) => isset($t[$type]))),
                'failed_words' => $this->failedWordsFor($type),
            ]));
            return null;
        }
        $this->reserved[$word][$type] = time();
        $this->searchCount[$word][$type] = ($this->searchCount[$word][$type] ?? 0) + 1;
        self::logStage('keyword_picked', $this->typeContext($type, [
            'keyword' => $word,
            'pool_type' => $this->poolTypeOf($word),
            'stage' => $stage,
            'search_count' => $this->searchCount[$word][$type],
        ]));
        return $word;
    }

    /** 该词成功产出 ≥1 条素材 */
    public function markUsed(string $word, string $type, int $materialCount): bool
    {
        if (!isset($this->reserved[$word][$type])) {
            self::logStage('keyword_mark_rejected', ['keyword' => $word, 'type' => $type, 'reason' => '该词未被当前账本占位，拒绝标记成功']);
            return false;
        }
        unset($this->reserved[$word][$type]);
        $this->used[$word][$type] = max(1, $materialCount);
        self::logStage('keyword_marked_used', $this->typeContext($type, [
            'keyword' => $word,
            'material_count' => $this->used[$word][$type],
            'material_total' => array_sum($this->used[$word]),
        ]));
        return true;
    }

    /**
     * 兜底场景：同词跨类型附加占用（该词已被 reserve 为某一类型，另一类型尚未消费时补占位）。
     * 附加占用同样记一次该类型的付费搜索，保证"一词一类型一搜"账本恒为真。
     */
    public function reserveType(string $word, string $type): bool
    {
        if (!$this->isValidType($type) || !$this->hasWord($word) || !$this->isAvailableFor($word, $type)) {
            self::logStage('keyword_reserve_rejected', ['keyword' => $word, 'type' => $type, 'reason' => '词/类型未知或该词对该类型已消费']);
            return false;
        }
        $this->reserved[$word][$type] = time();
        $this->searchCount[$word][$type] = ($this->searchCount[$word][$type] ?? 0) + 1;
        self::logStage('keyword_picked', $this->typeContext($type, [
            'keyword' => $word,
            'pool_type' => $this->poolTypeOf($word),
            'stage' => 'reserve_type',
            'search_count' => $this->searchCount[$word][$type],
        ]));
        return true;
    }

    /** 该词所有候选被拒/不可用，标记失败并记录拒绝原因分布 */
    public function markFailed(string $word, string $type, array $rejectBreakdown = []): bool
    {
        if (!$this->isValidType($type) || !isset($this->reserved[$word][$type])) {
            self::logStage('keyword_mark_rejected', ['keyword' => $word, 'type' => $type, 'reason' => '词或类型未知/未占位，拒绝标记失败']);
            return false;
        }
        unset($this->reserved[$word][$type]);
        $breakdown = [];
        foreach ($rejectBreakdown as $reason => $count) {
            if (isset(self::REJECT_LABELS[$reason]) && (int)$count > 0) {
                $breakdown[$reason] = (int)$count;
            }
        }
        $this->failed[$word][$type] = $breakdown;
        $labels = [];
        foreach ($breakdown as $reason => $count) {
            $labels[] = self::REJECT_LABELS[$reason] . $count . '条';
        }
        self::logStage('keyword_marked_failed', $this->typeContext($type, [
            'keyword' => $word,
            'reject_breakdown' => $breakdown,
            'reject_summary' => $labels === [] ? '无候选可用' : implode('，', $labels),
        ]));
        return true;
    }

    /**
     * 阻断一批历史失败词（如上一轮任务失败词不进入本轮 available）。
     *
     * @param array<string, array<int, string>> $failedSnap 形如 ['video'=>['词A'], 'image'=>['词B']]
     */
    public function loadBlocked(array $failedSnap): void
    {
        foreach (self::VALID_TYPES as $type) {
            foreach ($failedSnap[$type] ?? [] as $word) {
                $word = trim((string)$word);
                if ($word === '' || !$this->hasWord($word) || isset($this->failed[$word][$type])) {
                    continue;
                }
                $this->failed[$word][$type] = [];
            }
        }
    }

    /** 任务/阶段终局快照：可离线重放，验收字段（同类型同词搜索次数恒 ≤1） */
    public function snapshot(): array
    {
        $availableBalance = [];
        foreach (self::VALID_TYPES as $type) {
            $availableBalance[$type] = count($this->availableWordsFor($type));
        }
        return [
            'words' => array_column($this->words, 'word'),
            'pool_types' => array_column($this->words, 'pool_type'),
            'available_balance' => $availableBalance,
            'reserved' => $this->reserved,
            'used' => $this->used,
            // failed 以 [词 => 类型分布] 形态同时暴露成快照，两张视图都可读
            'failed' => $this->failedViewByType(),
            'search_count' => $this->searchCount,
        ];
    }

    private function nextAvailableWord(string $type): ?string
    {
        foreach ($this->words as $item) {
            $word = $item['word'];
            if (!$this->isAvailableFor($word, $type)) {
                continue;
            }
            return $word;
        }
        return null;
    }

    private function isAvailableFor(string $word, string $type): bool
    {
        return !isset($this->searchCount[$word][$type])
            && !isset($this->used[$word][$type])
            && !isset($this->failed[$word][$type]);
    }

    private function availableWordsFor(string $type): array
    {
        $list = [];
        foreach ($this->words as $item) {
            if ($this->isAvailableFor($item['word'], $type)) {
                $list[] = $item['word'];
            }
        }
        return $list;
    }

    private function failedWordsFor(string $type): array
    {
        $list = [];
        foreach ($this->failed as $word => $types) {
            if (isset($types[$type])) {
                $list[] = $word;
            }
        }
        return $list;
    }

    private function failedViewByType(): array
    {
        $view = [];
        foreach (self::VALID_TYPES as $type) {
            $view[$type] = $this->failedWordsFor($type);
        }
        return $view;
    }

    private function poolTypeOf(string $word): string
    {
        foreach ($this->words as $item) {
            if ($item['word'] === $word) {
                return $item['pool_type'];
            }
        }
        return self::POOL_LONGTAIL;
    }

    private function typeContext(string $type, array $extra): array
    {
        // type 入库：回放时可按“类型|词”核对同一类型同一词只搜一次
        return array_merge(['ledger' => 'ai_material_keyword', 'trace_id' => $this->traceId, 'type' => $type], $extra);
    }

    public function hasWord(string $word): bool
    {
        foreach ($this->words as $item) {
            if ($item['word'] === $word) {
                return true;
            }
        }
        return false;
    }

    private function isValidType(string $type): bool
    {
        return in_array($type, self::VALID_TYPES, true);
    }

    /** 统一监控日志：复用 explosionVideoSynthesis 通道，中文口径，失败静默不影响主流程 */
    private static function logStage(string $stage, array $payload): void
    {
        try {
            // 走统一出口，补齐 trace_id/task_id，保证词账本事件与抓取事件能按同一 trace 回放
            if (class_exists(\app\api\logic\videoSynthesis\CopywritingImitationLogic::class)) {
                \app\api\logic\videoSynthesis\CopywritingImitationLogic::emitLogEvent($stage, $payload);
                return;
            }
            Log::channel('explosionVideoSynthesis')->write('getAiMaterials监控: ' . json_encode(
                array_merge(['stage' => $stage], $payload),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
        } catch (\Throwable $e) {
            // 日志不可用不能影响找素材主流程
        }
    }
}
