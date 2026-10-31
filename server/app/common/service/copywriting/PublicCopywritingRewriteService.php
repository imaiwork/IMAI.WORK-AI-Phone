<?php

namespace app\common\service\copywriting;

use app\common\enum\user\AccountLogEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibrary;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\copywriting\PublicCopywriting;
use app\common\service\chat\ChatBillingService;
use app\common\service\TeamBillingService;
use app\common\service\TeamMemberService;
use app\common\service\TextQualityValidator;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 公共文案库 -> IP 人设专属文案（AI 仿写）。
 *
 * 与 {@see PublicCopywritingService::addToPersona()} 的区别：导入是「占位符替换 + 原样入库」，
 * 本服务是「把公共文案当骨架，按人设资料让模型重写一遍」。因此：
 * - 产物记为 source=4(SOURCE_AI_REWRITE)、source_id=0，不参与公共模板的版本同步/更新提示；
 * - 按中台返回的实际 token 折算算力（不设一口价，与 PlayVideoRewriteService 同口径）。
 */
class PublicCopywritingRewriteService
{
    /** 单次最多仿写条数：请求内串行调用，太大会撞 uniapp 300s 超时 */
    public const MAX_BATCH = 5;

    private const MAX_TOKENS = 4000;

    /** 正文入库兜底上限（正常远小于此值） */
    private const CONTENT_MAX = 2000;

    private const TITLE_MAX = 20;

    private const MAX_TOPIC = 3;

    /* ══════════════════════════════════════════════════════════
     * 入口
     * ══════════════════════════════════════════════════════════ */

    /**
     * @param array<int|string> $ids 公共文案 id
     * @return array{succ_count:int,fail_count:int,points:float,message:string,list:array,fail_list:array}
     * @throws \Exception
     */
    public static function rewrite(int $userId, int $personaId, array $ids): array
    {
        $ids = self::normalizeIds($ids);
        if (!$ids) {
            throw new \Exception('没有选中任何文案');
        }
        if (count($ids) > self::MAX_BATCH) {
            throw new \Exception('一次最多仿写 ' . self::MAX_BATCH . ' 条');
        }

        $persona = self::assertPersona($userId, $personaId);
        $personaContext = self::buildPersonaContext($persona);
        $map = PublicCopywritingService::personaMap($persona->toArray());

        $templates = PublicCopywriting::whereIn('id', $ids)
            ->where('enabled', 1)
            ->select()
            ->toArray();
        if (!$templates) {
            throw new \Exception('选中的文案已停用，请刷新后重试');
        }
        $byId = [];
        foreach ($templates as $tpl) {
            // 提示词框架是「给 AI 看的骨架」，本身没有成品正文可仿写，走了会产出无意义文本
            if ((int)($tpl['ui_type'] ?? PublicCopywritingService::UI_TYPE_READY) === PublicCopywritingService::UI_TYPE_FRAME) {
                throw new \Exception('「提示词框架」类请用「导入」，不支持 AI 仿写');
            }
            $byId[(int)$tpl['id']] = $tpl;
        }

        // 余额预检放在模型调用之前：不足直接抛 4059，不产生「扣了钱没结果」
        self::assertBalance($userId);

        $taskId = generate_unique_task_id();
        $now = time();
        $list = [];
        $failList = [];
        $usage = [];

        foreach ($ids as $id) {
            $tpl = $byId[$id] ?? null;
            if (!$tpl) {
                $failList[] = ['id' => $id, 'title' => '', 'reason' => '文案已停用'];
                continue;
            }

            try {
                $result = self::generate($tpl, $map, $personaContext);
                AiPersonaCopywritingLibrary::create(
                    self::buildRow($userId, $personaId, $tpl, $result, $now)
                );

                $alias = (string)$result['model'];
                if (!isset($usage[$alias])) {
                    $usage[$alias] = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
                }
                $usage[$alias]['prompt_tokens'] += (int)($result['usage']['prompt_tokens'] ?? 0);
                $usage[$alias]['completion_tokens'] += (int)($result['usage']['completion_tokens'] ?? 0);
                $usage[$alias]['total_tokens'] += (int)($result['usage']['total_tokens'] ?? 0);

                $list[] = [
                    'source_id' => $id,
                    'title' => $result['title'],
                    'content' => $result['content'],
                    'topic' => $result['topic'],
                    'model' => $alias,
                ];
            } catch (\Throwable $e) {
                Log::channel('shanjian')->warning(sprintf(
                    '公共文案仿写失败 template=%d persona=%d user=%d: %s',
                    $id,
                    $personaId,
                    $userId,
                    $e->getMessage()
                ));
                $failList[] = [
                    'id' => $id,
                    'title' => (string)($tpl['title'] ?? ''),
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $succCount = count($list);
        $points = $succCount > 0 ? self::charge($userId, $usage, $succCount, $taskId) : 0.0;

        return [
            'succ_count' => $succCount,
            'fail_count' => count($failList),
            'points' => $points,
            'message' => self::buildMessage($succCount, count($failList), $points),
            'list' => $list,
            'fail_list' => $failList,
        ];
    }

    /* ══════════════════════════════════════════════════════════
     * 生成
     * ══════════════════════════════════════════════════════════ */

    /**
     * 单条仿写：模型调用 -> 解析 -> 清洗。
     *
     * @param array<string,mixed> $tpl
     * @param array<string,string> $map
     * @return array{title:string,content:string,topic:string,model:string,usage:array}
     * @throws \Exception
     */
    private static function generate(array $tpl, array $map, string $personaContext): array
    {
        $libraryType = (int)$tpl['library_type'];
        $driverType = (int)$tpl['driver_type'];
        $typeName = self::typeName($libraryType, $driverType);

        // 占位符先换成当前人设的真实信息再喂模型：裸的 {姓名} 只会让模型照抄占位符
        $sourceText = PublicCopywritingService::rewrite((string)($tpl['content'] ?? ''), $map);
        if ($sourceText === '') {
            $sourceText = PublicCopywritingService::rewrite((string)($tpl['title'] ?? ''), $map);
        }
        if ($sourceText === '') {
            throw new \Exception('参考文案内容为空');
        }

        $system = self::systemPrompt();
        $user = self::buildUserMessage($typeName, $tpl, $sourceText, $personaContext);

        $model = VideoPromptService::preferredModel();
        $raw = '';
        $data = [];
        $reason = '';

        // 换模型重试一次：调用失败与「返回空正文」都算（推理档把预算烧在思考上时 content 恒为空）
        for ($round = 0; $round < 2; $round++) {
            try {
                $data = self::requestModel($model, $system, $user);
                $raw = trim((string)($data['choices'][0]['message']['content'] ?? ''));
                if ($raw !== '') {
                    break;
                }
                $reason = '正文为空（finish_reason=' . (string)($data['choices'][0]['finish_reason'] ?? '未知') . '）';
            } catch (\Throwable $e) {
                $reason = '调用失败: ' . $e->getMessage();
            }
            $alt = VideoPromptService::resolveModel($model);
            if ($alt === $model) {
                break;
            }
            Log::channel('shanjian')->warning("公共文案仿写模型 {$model} 不可用({$reason})，换用 {$alt} 重试");
            $model = $alt;
        }

        if ($raw === '') {
            throw new \Exception('AI 生成失败，请稍后重试');
        }

        $parsed = self::parse($raw, $libraryType, $driverType);

        return [
            'title' => $parsed['title'],
            'content' => $parsed['content'],
            'topic' => $parsed['topic'],
            'model' => $model,
            'usage' => (array)($data['usage'] ?? []),
        ];
    }

    /**
     * 单条模型调用，返回上游 data 段（正文在 choices[0].message.content）。
     *
     * @throws \Exception
     */
    private static function requestModel(string $model, string $system, string $user): array
    {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.7,
            'max_tokens' => self::MAX_TOKENS,
            'stream' => false,
            'enable_thinking' => false,
        ];
        $response = (array)ToolsService::Chat()->message($body);
        $code = (int)($response['code'] ?? 0);
        if ($code !== 10000) {
            $message = trim((string)($response['message'] ?? $response['msg'] ?? ''));
            throw new \Exception($message !== '' ? $message : '模型网关请求失败(code=' . $code . ')');
        }
        return (array)($response['data'] ?? []);
    }

    /* ══════════════════════════════════════════════════════════
     * 提示词
     * ══════════════════════════════════════════════════════════ */

    public static function systemPrompt(): string
    {
        return <<<'PROMPT'
你是短视频口播与社媒文案改写专家。用户会给你一篇「公共参考文案」和一份「IP 人设资料」，你要把参考文案改写成这个人设自己的文案：骨架照抄，血肉换掉。

【改写原则】
1. 抓骨架：保留参考文案的切入角度、开场钩子类型、段落推进顺序、情绪节奏和结尾的行动引导方式。
2. 换血肉：把主体身份、行业、地点、产品与服务、目标人群、卖点、场景，全部换成人设资料里的真实信息。
3. 不复述资料：不要把「我的IP人设名称是…」「主营业务/产品：…」这类资料原文抄进正文，要把它化进句子里。
4. 不编造：人设资料里没有的价格、折扣、库存、销量、认证、荣誉、从业年限、疗效、客户数量、案例结果，一律不写；参考文案里的具体数字和承诺也不得照搬。
5. 资料缺失就写概括：缺什么就绕开或写泛一点，宁可少一个卖点，也不要虚构一个。
6. 不写元话术：不出现「作为AI」「以下是」「希望对你有帮助」「改写如下」「注：」。
7. 不复读原文：除专有名词外不要整句照抄，至少七成句子要换一种说法。
8. 只输出成稿：不要解释、不要备注、不要 Markdown 符号（# * - > 等），不要编号，不要加书名号。

【按类型调整】
- 新闻体：以「现象或变化 → 观点 → 结论」推进；句子短、信息密度高；不用「家人们」「宝子们」这类口播称呼；每条标题单独一行，行与行角度不同（变化、误区、方法、结果），互不重复。
- 口播文案：面向镜头的口语独白；开头 3 秒内抛出钩子；中段给具体做法或判断标准；结尾一句行动引导；多用短句，避免长定语和书面语。
- 素材混剪口播：比口播更短、更有节奏，一句一个意思，方便和画面对齐；不写依赖字幕才成立的指代。
- 发布文案：第一行是标题式钩子；正文 2~4 个短段，每段以一句话为主；结尾一行行动引导；全篇 emoji 不超过 3 个。

【输出格式】
严格只输出下面三段，顺序固定，标签原样保留，标签后换行写内容：
【标题】
<内容>
【正文】
<内容>
【话题】
<内容>
PROMPT;
    }

    /**
     * @param array<string,mixed> $tpl
     */
    public static function buildUserMessage(string $typeName, array $tpl, string $sourceText, string $personaContext): string
    {
        $libraryType = (int)$tpl['library_type'];
        $driverType = (int)$tpl['driver_type'];
        $lineCount = self::newsLineCount($sourceText);
        $length = self::targetLength($sourceText, $libraryType);
        $paragraphs = self::targetParagraphs($length);

        $lines = [];
        $lines[] = '【参考文案】';
        $lines[] = '类型：' . $typeName;
        if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            $lines[] = '需要输出的标题行数：' . $lineCount . ' 行';
        }
        $lines[] = '原文：';
        $lines[] = $sourceText;
        $lines[] = '';
        $lines[] = '【人设资料】';
        $lines[] = $personaContext !== '' ? $personaContext : '（人设资料缺失，请输出一版通用、不绑定具体身份的口播稿）';
        $lines[] = '';
        $lines[] = '【本次要求】';
        $lines[] = '- 输出类型：' . $typeName;

        if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            $lines[] = '- 标题：输出 ' . $lineCount . ' 行，每行一条可独立使用的新闻标题；不加编号、不加句号、不加引号；行与行角度不同';
            $lines[] = '- 正文：留空';
            $lines[] = '- 话题：留空';
        } elseif ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            $lines[] = '- 标题：一行，不超过 16 字，不用句号结尾';
            $lines[] = '- 正文：约 ' . $length . ' 字，2~4 段';
            $lines[] = '- 话题：最多 3 个，每个以 # 开头、空格分隔；按你的业务重写，不要照搬参考文案的话题';
        } else {
            $lines[] = '- 标题：一行，不超过 16 字，不用句号结尾，不加引号';
            $lines[] = '- 正文：约 ' . $length . ' 字，分 ' . $paragraphs . ' 段，口语化，能被直接念出来';
            $lines[] = '- 话题：留空';
        }

        return implode("\n", $lines);
    }

    /* ══════════════════════════════════════════════════════════
     * 输出解析
     * ══════════════════════════════════════════════════════════ */

    /**
     * 解析【标题】/【正文】/【话题】三段并按类型归位。
     *
     * @return array{title:string,content:string,topic:string}
     * @throws \Exception
     */
    public static function parse(string $raw, int $libraryType, int $driverType): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($raw));

        $segments = self::splitSegments($text);
        $title = trim($segments['标题'] ?? '');
        $content = trim($segments['正文'] ?? '');
        $topic = trim($segments['话题'] ?? '');

        // 模型漏了标签时不能把整段结果白白丢掉：正文位置直接吃全文
        if ($title === '' && $content === '' && $topic === '') {
            Log::channel('shanjian')->warning('公共文案仿写输出缺少分段标签，按整段正文处理');
            if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
                $title = $text;
            } else {
                $content = $text;
            }
        }

        $clean = TextQualityValidator::sanitizeArray([
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
        ]);
        $title = trim((string)($clean['content']['title'] ?? ''));
        $content = trim((string)($clean['content']['content'] ?? ''));
        $topic = trim((string)($clean['content']['topic'] ?? ''));

        $title = self::stripSuffix(self::stripMarkdown($title));
        $content = self::stripMarkdown($content);

        if ($driverType === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            $titles = self::splitLines($title !== '' ? $title : $content);
            if (!$titles) {
                throw new \Exception('AI 未返回可用标题');
            }
            $titles = array_slice($titles, 0, 8);
            return [
                'title' => implode("\n", $titles),
                'content' => '',
                'topic' => '',
            ];
        }

        if ($content === '') {
            // 只给了标题没给正文的残稿直接判失败，避免把标题当口播稿入库
            throw new \Exception('AI 未返回正文');
        }
        $content = mb_substr($content, 0, self::CONTENT_MAX);

        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            $title = $title !== '' ? mb_substr($title, 0, PublicCopywritingService::TITLE_MAX) : PublicCopywritingService::autoTitle($content);
            return [
                'title' => $title,
                'content' => $content,
                'topic' => self::normalizeTopic($topic),
            ];
        }

        $title = $title !== '' ? mb_substr($title, 0, self::TITLE_MAX) : PublicCopywritingService::autoTitle($content);
        return [
            'title' => $title,
            'content' => $content,
            'topic' => '',
        ];
    }

    /**
     * 按【标题】/【正文】/【话题】切段。
     * 注意：PREG_OFFSET_CAPTURE 给的是字节偏移，这里同一套字节口径 substr 才对齐。
     *
     * @return array<string,string>
     */
    private static function splitSegments(string $text): array
    {
        $labels = ['标题', '正文', '话题'];
        $spans = [];
        foreach ($labels as $label) {
            if (preg_match('/【' . $label . '】/u', $text, $m, PREG_OFFSET_CAPTURE)) {
                $spans[$label] = [
                    'label' => $m[0][1],
                    'start' => $m[0][1] + strlen($m[0][0]),
                ];
            }
        }
        if (!$spans) {
            return [];
        }

        // 按标签出现顺序切：段尾取「下一个标签的起始位置」，不能取下一个正文起点，
        // 否则后一个标签本身会被算进前一段（标题里会混进【正文】）
        uasort($spans, static function (array $a, array $b): int {
            return $a['label'] <=> $b['label'];
        });

        $labelsInOrder = array_keys($spans);
        $out = [];
        foreach ($labelsInOrder as $i => $label) {
            $start = $spans[$label]['start'];
            $end = isset($labelsInOrder[$i + 1]) ? $spans[$labelsInOrder[$i + 1]]['label'] : strlen($text);
            $out[$label] = substr($text, $start, max(0, $end - $start));
        }
        return $out;
    }

    private static function normalizeTopic(string $topic): string
    {
        if ($topic === '') {
            return '';
        }
        $topic = str_replace(['，', ',', '、', ';', '；', "\n"], ' ', $topic);
        $parts = [];
        foreach (preg_split('/\s+/u', $topic) ?: [] as $part) {
            $part = trim(str_replace('#', '', $part));
            if ($part !== '') {
                $parts[$part] = '#' . $part;
            }
            if (count($parts) >= self::MAX_TOPIC) {
                break;
            }
        }
        return implode(' ', array_values($parts));
    }

    private static function stripMarkdown(string $text): string
    {
        $text = (string)preg_replace('/^```[a-zA-Z]*\s*|\s*```$/u', '', $text);
        $text = (string)preg_replace('/^#{1,6}\s*/mu', '', $text);
        $text = (string)preg_replace('/\*\*(.+?)\*\*/u', '$1', $text);
        return trim($text);
    }

    private static function stripSuffix(string $title): string
    {
        $title = trim($title);
        $title = trim($title, "\"'“”「」『』");
        return (string)preg_replace('/[。，,；;：:]+$/u', '', $title);
    }

    /**
     * @return array<int,string>
     */
    private static function splitLines(string $text): array
    {
        $out = [];
        foreach (preg_split('/\n+/u', $text) ?: [] as $line) {
            $line = self::stripSuffix((string)preg_replace('/^[\s\-*•\d\.、]+/u', '', $line));
            if ($line !== '') {
                $out[$line] = $line;
            }
        }
        return array_values($out);
    }

    /* ══════════════════════════════════════════════════════════
     * 参数计算（能算的不让模型推）
     * ══════════════════════════════════════════════════════════ */

    private static function newsLineCount(string $sourceText): int
    {
        $lines = array_filter(array_map('trim', preg_split('/\n+/u', $sourceText) ?: []));
        return max(1, min(8, count($lines)));
    }

    private static function targetLength(string $sourceText, int $libraryType): int
    {
        $chars = mb_strlen((string)preg_replace('/\s+/u', '', $sourceText));
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            return max(100, min(800, $chars));
        }
        return max(80, min(400, $chars));
    }

    private static function targetParagraphs(int $length): int
    {
        return max(2, min(4, (int)ceil($length / 120)));
    }

    public static function typeName(int $libraryType, int $driverType): string
    {
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            return '发布文案';
        }
        return match ($driverType) {
            AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS => '新闻体',
            AiPersonaCopywritingLibrary::DRIVER_TYPE_MATERIAL_MIXCUT => '素材混剪口播',
            default => '口播文案',
        };
    }

    /* ══════════════════════════════════════════════════════════
     * 人设
     * ══════════════════════════════════════════════════════════ */

    public static function assertPersona(int $userId, int $personaId): AiPersona
    {
        if ($personaId <= 0) {
            throw new \Exception('人设不能为空');
        }
        $persona = AiPersona::where('id', $personaId)
            ->where('user_id', $userId)
            ->where('status', AiPersona::STATUS_ENABLED)
            ->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \Exception('人设不存在或已停用');
        }
        return $persona;
    }

    /**
     * 人设资料：结构化字段 + 分表线索文本，整段过 VideoPromptService::normalizePersona() 截断。
     */
    public static function buildPersonaContext(AiPersona $persona): string
    {
        $personaId = (int)$persona->id;
        $clue = '';
        try {
            $ext = match ((int)$persona->persona_type) {
                1 => AiPersonaIndividual::where('persona_id', $personaId)->find(),
                2 => AiPersonaEnterprise::where('persona_id', $personaId)->find(),
                3 => AiPersonaLocal::where('persona_id', $personaId)->find(),
                default => null,
            };
            if ($ext) {
                $clue = (string)$ext->getClueContent($persona);
            }
        } catch (\Throwable $e) {
            // 分表资料取不到不阻断：下面的基础字段已经能撑起一版通用稿
            Log::channel('shanjian')->warning('公共文案仿写取人设资料失败 persona=' . $personaId . ': ' . $e->getMessage());
        }

        $map = PublicCopywritingService::personaMap($persona->toArray());
        $parts = [];
        $parts[] = '人设名：' . $map['name'];
        if ((string)$persona->industry !== '') {
            $parts[] = '行业：' . (string)$persona->industry;
        }
        if ($map['city'] !== '') {
            $parts[] = '城市/地区：' . $map['city'];
        }
        if ($map['service'] !== '') {
            $parts[] = '主营业务/产品：' . $map['service'];
        }
        if ($map['customers'] !== '') {
            $parts[] = '目标客户与痛点：' . $map['customers'];
        }
        if ($map['selling'] !== '') {
            $parts[] = '差异化优势与行为引导：' . $map['selling'];
        }
        if ($clue !== '') {
            $parts[] = $clue;
        }

        return VideoPromptService::normalizePersona(implode("\n", array_filter($parts)));
    }

    /* ══════════════════════════════════════════════════════════
     * 入库 & 计费
     * ══════════════════════════════════════════════════════════ */

    /**
     * @param array<string,mixed>                  $tpl
     * @param array{title:string,content:string,topic:string} $result
     * @return array<string,mixed>
     */
    private static function buildRow(int $userId, int $personaId, array $tpl, array $result, int $now): array
    {
        return [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'library_type' => (int)$tpl['library_type'],
            'driver_type' => (int)$tpl['driver_type'],
            'title' => $result['title'],
            'content' => $result['content'],
            'topic' => $result['topic'],
            'source' => AiPersonaCopywritingLibrary::SOURCE_AI_REWRITE,
            'source_id' => 0,
            'source_version' => 0,
            'edited' => 0,
            'sort' => 0,
            'status' => AiPersonaCopywritingLibrary::STATUS_ENABLED,
            'use_count' => 0,
            'last_used_time' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ];
    }

    private static function assertBalance(int $userId): void
    {
        TeamMemberService::assertActive($userId);
        $cost = ChatBillingService::findModelCost(VideoPromptService::preferredModel());
        $need = ChatBillingService::estimateMinimumPoints($cost);
        if (TeamBillingService::spendableTokens($userId) < $need) {
            $msg = TeamBillingService::resolveSpender($userId) !== null
                ? '当前团队算力不足，请联系团队主' : '用户算力不足';
            throw new \Exception($msg, 4059);
        }
    }

    /**
     * 按实际 usage 折算算力，成功的条数合计扣一次流水。
     *
     * @param array<string,array<string,int>> $usage 模型别名 => 累计用量
     */
    private static function charge(int $userId, array $usage, int $count, string $taskId): float
    {
        $total = 0.0;
        foreach ($usage as $alias => $item) {
            try {
                $total += ChatBillingService::charge(
                    $userId,
                    (string)$alias,
                    $item,
                    AccountLogEnum::TOKENS_DEC_PUBLIC_COPYWRITING_REWRITE,
                    $taskId,
                    ['生成条数' => $count, '场景' => '公共文案AI仿写']
                );
            } catch (\Throwable $e) {
                // 扣费失败不回滚已入库的文案，留痕人工对账（与 PlayVideoRewriteService 同口径）
                Log::channel('shanjian')->error('公共文案仿写扣费失败 user=' . $userId . ': ' . $e->getMessage());
            }
        }
        return round($total, 2);
    }

    private static function buildMessage(int $succ, int $fail, float $points): string
    {
        if ($succ === 0) {
            return '仿写失败，未扣算力';
        }
        $parts = ['已仿写 ' . $succ . ' 条'];
        if ($fail > 0) {
            $parts[] = $fail . ' 条失败';
        }
        if ($points > 0) {
            $parts[] = '消耗 ' . rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.') . ' 算力';
        }
        return implode('，', $parts);
    }

    /**
     * @param array<int|string> $ids
     * @return array<int,int>
     */
    private static function normalizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }
}
