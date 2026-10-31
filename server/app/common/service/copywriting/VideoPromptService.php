<?php

declare(strict_types=1);

namespace app\common\service\copywriting;

use app\common\enum\ChatEnum;
use app\common\model\chat\ModelsCost;
use app\common\service\ConfigService;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 一句话生成视频 · AI 帮写提示词（P0-b）
 *
 * 与旧链路（/kb.robot/getCopywriting → 中台 Coze 工作流）的区别：
 *   - system prompt 落在本仓库，可版本化、可回归；Coze 那边的提示词本站看不到也改不了；
 *   - 时长 / 比例 / 素材数量 / 口播字数这些**由代码算好塞进 user message**，
 *     不让模型自己推算（worldclaw 的教训：规则是文字，参数是值，文字压不过值）；
 *   - 输出是「镜头级提示词」，不是散文式文案。
 *
 * 同一次调用还顺带产出一个记录标题（首行「【标题】xxx」，由 extractTitle() 剥出来，
 * 正文里不保留）：创作记录列表只显示 name，旧口径截提示词前 10 字会得到
 * 「7秒竖屏9:16，写」这种参数前缀。标题与提示词共用一次模型调用，不额外计费。
 *
 * 素材引用统一写作 @图片1 / @视频1 / @角色1，前后各一个半角空格。
 * 模型不可能百分之百守这个规矩，所以 normalizeRefs() 再兜一层：
 * 补空格、越界编号降级为通用词、没素材时整类降级、同句去重。
 *
 * 计费不在这里：C 端仍按 coze_copywriting 场景每条 1 算力，由 SoraPromptLogic 扣。
 */
class VideoPromptService
{
    /**
     * 默认模型别名。中台把 deepseek 识别为 flash 档（见中台 relay/channel/deepseek/constants.go）。
     * 可用 ConfigService::set('sora', 'prompt_model', ...) 覆盖，改配置不用发版。
     */
    private const DEFAULT_MODEL = 'deepseek';

    /** 输出预算：正文本身 900 字以内就够，留足推理余量 */
    private const MAX_TOKENS = 3000;

    /** 成品提示词字数上限（超了按最后一个句号截断） */
    public const MAX_OUTPUT_CHARS = 900;

    /** 记录标题字数上限：列表卡片一行放得下，超了截断 */
    public const TITLE_MAX_CHARS = 20;

    /**
     * 人设资料长度上限。
     * 人设语料动辄一两千字（个人/企业/本地三型 getClueContent），全塞进去会把
     * max_tokens 预算吃掉、还会让模型把「人设介绍」当成正文写；600 字足够交代
     * 身份、受众、卖点与语气，剩下的交给用户那句话。
     */
    public const MAX_PERSONA_CHARS = 600;

    /** 台词语言的展示名 */
    private const LANG_ZH = [
        'zh' => '中文',
        'en' => '英语',
        'id' => '印尼语',
        'th' => '泰语',
        'vi' => '越南语',
    ];

    /** 比例展示名 */
    private const RATIO_ZH = [
        '16:9'  => '横屏 16:9',
        '4:3'   => '横屏 4:3',
        '1:1'   => '方形 1:1',
        '3:4'   => '竖屏 3:4',
        '9:16'  => '竖屏 9:16',
        '21:9'  => '宽银幕 21:9',
    ];

    /** 引用 token 的三类素材，与降级用的通用词 */
    private const REF_TYPES = [
        '图片' => '参考图',
        '视频' => '参考视频',
        '角色' => '角色',
    ];

    /**
     * 生成提示词。
     *
     * @param array<string, mixed> $params
     *   content        用户那句话（必填）
     *   duration       4~15
     *   aspect_ratio   16:9 / 4:3 / 1:1 / 3:4 / 9:16 / 21:9
     *   image_count    参考图数量
     *   video_count    参考视频数量
     *   anchor_ids     角色 id（sora_create 当前不传）
     *   first_last_frame 1 表示首尾帧模式
     *   mode           commerce / creative
     *   language       zh / en / id / th / vi
     *   persona_context 人设资料纯文本（已由逻辑层拼好并截断），'' = 未绑定人设
     * @return array{content: string, title: string, model: string, usage: array, meta: array}
     * @throws \Exception
     */
    public static function generate(array $params): array
    {
        $content   = trim((string)($params['content'] ?? ''));
        $duration  = self::clampInt((int)($params['duration'] ?? 5), 4, 15);
        $ratio     = (string)($params['aspect_ratio'] ?? '9:16');
        $mode      = ($params['mode'] ?? 'commerce') === 'creative' ? 'creative' : 'commerce';
        $language  = self::normalizeLanguage((string)($params['language'] ?? 'zh'));
        $firstLast = (int)($params['first_last_frame'] ?? 0) === 1;
        $anchorIds = array_values(array_filter(array_map('strval', (array)($params['anchor_ids'] ?? []))));
        $persona   = self::normalizePersona((string)($params['persona_context'] ?? ''));

        $imageCount = $firstLast ? 0 : max(0, (int)($params['image_count'] ?? 0));
        $videoCount = max(0, (int)($params['video_count'] ?? 0));

        if ($content === '') {
            throw new \Exception('请输入您的想法');
        }

        $shots        = self::recommendedShots($duration);
        $maxDialogue  = self::maxDialogueChars($duration, $language);

        $userMessage = self::buildUserMessage([
            'content'          => $content,
            'duration'         => $duration,
            'aspect_ratio'     => $ratio,
            'image_count'      => $imageCount,
            'video_count'      => $videoCount,
            'anchor_count'     => count($anchorIds),
            'first_last_frame' => $firstLast,
            'mode'             => $mode,
            'language'         => $language,
            'shots'            => $shots,
            'max_dialogue'     => $maxDialogue,
            'persona'          => $persona,
        ]);

        $model = self::resolveModel();
        $data  = null;
        $reply = '';
        $reason = '';

        // 换模型重试一次：调用失败（下架/无渠道）与「返回了但正文为空」都算，
        // 推理模型把预算烧在思考上时 content 恒为空，不重试就直接整单失败。
        for ($round = 0; $round < 2; $round++) {
            try {
                $data  = self::requestModel($model, self::systemPrompt(), $userMessage);
                $reply = trim((string)($data['choices'][0]['message']['content'] ?? ''));
                if ($reply !== '') {
                    break;
                }
                $reason = '正文为空（finish_reason=' . (string)($data['choices'][0]['finish_reason'] ?? '未知') . '）';
            } catch (\Exception $e) {
                $reason = '调用失败: ' . $e->getMessage();
            }
            Log::channel('sora')->warning("video prompt 模型 {$model} 不可用: {$reason}");
            $alt = self::resolveModel($model);
            if ($alt === $model) {
                break;
            }
            Log::channel('sora')->warning("video prompt 换用模型 {$alt} 重试");
            $model = $alt;
        }

        if ($reply === '') {
            Log::channel('sora')->error('video prompt 生成失败: ' . $reason . ' user=' . $userMessage);
            throw new \Exception('AI 生成失败，请稍后重试');
        }

        $text = self::sanitize($reply);

        // 标题行必须剥掉再往下走：这串正文最终是投给 seedance 的提示词，
        // 留着「【标题】xxx」模型大概率把它当画面文字画上去。
        [$title, $text] = self::extractTitle($text);
        if ($title === '') {
            $title = self::fallbackTitle($text);
        }
        if ($text === '') {
            // 模型只吐了一行标题：正文为空等于这条提示词作废，按生成失败处理（此时还没扣费）
            Log::channel('sora')->error('video prompt 正文为空（只有标题行） user=' . $userMessage);
            throw new \Exception('AI 生成失败，请稍后重试');
        }

        $text = self::normalizeRefs($text, $imageCount, $videoCount, count($anchorIds));

        // 角色在提交口径上是 ` @<anchor_id> `（见 SoraVideoSettingLogic::add 的替换），
        // 所以 @角色N 只在本 service 内部出现，出去前换掉；越界或未传角色时降级成「角色」。
        if ($anchorIds !== []) {
            $text = (string)preg_replace_callback('/@角色(\d+)/u', static function (array $m) use ($anchorIds): string {
                $idx = (int)$m[1] - 1;
                return isset($anchorIds[$idx]) ? ' @' . $anchorIds[$idx] . ' ' : '角色';
            }, $text);
        }

        $text = self::truncate($text, self::MAX_OUTPUT_CHARS);
        $dialogue = self::measureDialogue($text);
        if ($dialogue['chars'] > $maxDialogue) {
            // 不擅自改模型产物，只留痕：这条规则只能靠提示词约束 + 事后观察
            Log::channel('sora')->warning(sprintf(
                'video prompt 口播超限: %d > %d（%s, %ds）',
                $dialogue['chars'],
                $maxDialogue,
                $language,
                $duration
            ));
        }

        return [
            'content' => $text,
            'title'   => $title,
            'model'   => $model,
            'usage'   => (array)($data['usage'] ?? []),
            'meta'    => [
                'title'              => $title,
                'shots'              => $shots,
                'max_dialogue_chars' => $maxDialogue,
                'dialogue_chars'     => $dialogue['chars'],
                'model'              => $model,
                'duration'           => $duration,
                'image_count'        => $imageCount,
                'video_count'        => $videoCount,
                'has_persona'        => $persona !== '',
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════
     * 参数计算：能算的一律不让模型算
     * ══════════════════════════════════════════════════════════ */

    /**
     * 镜头数。
     *
     * 4~15 秒的单次生成走「≈4 秒一镜」，不是 worldclaw script 模板的 1.4 秒一镜：
     * 那是人拍脚本的密度，AI 一次生成里切得越碎，跨镜头连贯性越差、主体越容易变形。
     * 单镜不低于 2 秒这条由取值表天然满足（4s→1、15s→4）。
     */
    public static function recommendedShots(int $duration): int
    {
        return self::clampInt((int)round($duration / 4), 1, 4);
    }

    /**
     * 口播字数上限。
     *
     * 中文 4.5 字/秒；泰文无空格按字符 4.0（估值，宁紧勿松）；其余拉丁语系按词 2.7。
     * 只算口播，不算画面描述 —— 前端那个「建议 N 字」用的是同一套数。
     */
    public static function maxDialogueChars(int $duration, string $language = 'zh'): int
    {
        $d = max(1, $duration);
        return match ($language) {
            'zh'    => (int)floor($d * 4.5),
            'th'    => (int)floor($d * 4.0),
            default => (int)floor($d * 2.7),
        };
    }

    /** 台词长度：按实际写出来的文字量判，不按声明语言判 */
    public static function dialogueLen(string $text): int
    {
        $t = trim($text);
        if ($t === '') {
            return 0;
        }
        // 含 CJK 或泰文：无空格分词，按字符数
        if (preg_match('/[\x{4e00}-\x{9fff}\x{3040}-\x{30ff}\x{0e00}-\x{0e7f}]/u', $t) === 1) {
            return mb_strlen((string)preg_replace('/\s+/u', '', $t));
        }
        $parts = preg_split('/\s+/u', $t) ?: [];
        return count(array_filter($parts, static fn($p) => $p !== ''));
    }

    /** 归一化台词语言码，认不出来按中文 */
    public static function normalizeLanguage(string $language): string
    {
        $code = strtolower(trim($language));
        return isset(self::LANG_ZH[$code]) ? $code : 'zh';
    }

    /**
     * 人设资料清洗：压掉多余空白与缩进，截断到 MAX_PERSONA_CHARS。
     * 逻辑层已经截过一次，这里是兜底 —— generate() 是 public，别的调用方可能直接塞原文进来。
     */
    public static function normalizePersona(string $persona): string
    {
        $text = trim($persona);
        if ($text === '') {
            return '';
        }
        $text = (string)preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string)preg_replace('/\n{2,}/u', "\n", $text);
        $text = (string)preg_replace('/^[ \t]*\n?/mu', '', $text);
        return self::truncate(trim($text), self::MAX_PERSONA_CHARS);
    }

    /* ══════════════════════════════════════════════════════════
     * 提示词
     * ══════════════════════════════════════════════════════════ */

    /** 落库版本 system prompt —— 改这里就是改行为，改完要跑基线 */
    public static function systemPrompt(): string
    {
        return <<<'PROMPT'
你是短视频分镜与提示词工程师。输出的是【直接投喂seedance视频生成模型的提示词】，
不是文案、不是解释。不要 markdown、不要星号、不要「以下是」。

一、每个镜头按这个格式写（几镜就是几段）：
镜头N（起.止秒）：景别，机位，运镜；可观测动作；口播「…」；字幕「…」

二、硬参数（已算好，不许改）：
- 镜头数 = 输入【镜头数】，单镜不低于 2 秒，时间轴首尾相接并精确收在总时长。
- 全片口播合计 ≤ 输入【口播字数上限】，超了压台词，不许延长镜头。
- 台词语言按输入；描述性文字一律中文。

三、素材引用（写错了等于没引用，而且不会报错）：
- 参考图写作 @图片1、@图片2……；参考视频写作 @视频1、@视频2……；角色写作 @角色1……
  编号从 1 开始，与输入【素材】清单里的编号一一对应。
- 每个引用前后必须各留一个半角空格。例：以 @图片1 中的咖啡杯为唯一外观依据
- 引用要紧紧贴着它指代的那个东西嵌在句子里：
  对：一只手拿起 @图片1 的瓶子，拇指按上 @图片1 的喷头
  错：本镜使用 @图片1。一只手拿起瓶子，拇指按上喷头
  错：一只手拿起瓶子，拇指按上喷头 @图片1 @图片2
- 同一物体不必每个动作都重标一次，更不要在一句末尾堆一串 token；画面里新增的东西（用户要求的人物、场景、道具）不标。
- 只标真实存在的素材：输入没给参考图就绝不写 @图片1，编号不得超过清单里的最大号。

四、内容规则：
- 第一行固定只写「【标题】主题短语」：8~14 个汉字，只概括主体与场景（谁 / 什么商品 / 在什么场景 / 做什么），
  不带时长、比例、风格、镜头号、引号、书名号和句末标点，也不写成「视频」「提示词」这类空词。
- 第二行起是提示词正文，正文首行一句话总述：时长 + 比例 + 风格 + 参考图用途。
- 商品锁定：以 @图片1 为唯一外观依据，点名锁的是那件商品（不是那张照片里的环境、家具、墙面）。
- 场景只写一次，人物只写一次，后续镜头不重复描述长相。
- 动作必须是摄像机能拍到的物理运动；把运镜词划掉后不能什么都不剩。
- 每个主体至少一个自己的动作，画面里不许凭空多出第二件同类商品。
- 带货模式：至少一个镜头演示「怎么用 + 用后画面变化」，全片只讲一个卖点。
- 有【人设】时，台词口吻、卖点角度、目标人群须贴合人设身份；人设只影响台词与角度，不得覆盖用户原话里的场景/动作/人物，也不得把人设介绍原文写进提示词。
- 禁用评价词：8K、4K、电影级、大片感、震撼、极致、氛围感拉满、masterpiece、best quality。
- 禁止虚构价格、折扣、库存、销量、认证、功效；图上的销量徽章不是卖点；人设里没有的资质、荣誉、数据也不许编。
- 保留用户原话里写明的场景、动作、人物、拍法，不得替换成你认为更好的版本。
- 结尾一段【禁止】：水印、logo、二维码、画外音、额外文字、改变商品外观、风格突变。
- 总字数 ≤ 900，超了先压描述，不许压口播。
只输出标题行和提示词正文，不要任何其它说明。
PROMPT;
    }

    /**
     * user message。素材清单必须逐条列出 token ——
     * 只写「参考图 1 张」时模型照样会瞎编号，写错的表现是参考图被静默忽略、成片不像，且不报错。
     *
     * @param array<string, mixed> $p
     */
    public static function buildUserMessage(array $p): string
    {
        $lines = [];
        $lines[] = '【主体】' . (string)$p['content'];
        $lines[] = '【用户描述】' . (string)$p['content'] . '（原话，不得改写）';

        $lines[] = '【素材】';
        if (!empty($p['first_last_frame'])) {
            $lines[] = '首帧 = 用户上传的第 1 张图（必须作为画面起点）';
            $lines[] = '尾帧 = 用户上传的第 2 张图（必须作为画面终点）';
            $lines[] = '本模式没有参考图，不要写 @图片1，用「首帧」「尾帧」称呼。';
        } else {
            $imageCount = (int)($p['image_count'] ?? 0);
            if ($imageCount > 0) {
                for ($i = 1; $i <= $imageCount; $i++) {
                    $lines[] = '@图片' . $i . ' = 参考图（第 ' . $i . ' 张'
                        . ($i === 1 ? '，主外观依据' : '') . '）';
                }
            } else {
                $lines[] = '无参考图（本单不要出现 @图片1）';
            }
        }
        $videoCount = (int)($p['video_count'] ?? 0);
        if ($videoCount > 0) {
            for ($i = 1; $i <= $videoCount; $i++) {
                $lines[] = '@视频' . $i . ' = 参考视频（第 ' . $i . ' 段，只参考运镜与节奏，不照搬画面）';
            }
        } else {
            $lines[] = '无参考视频';
        }
        if ((int)($p['anchor_count'] ?? 0) > 0) {
            for ($i = 1; $i <= (int)$p['anchor_count']; $i++) {
                $lines[] = '@角色' . $i . ' = 出镜角色（第 ' . $i . ' 个，外貌与该角色一致）';
            }
        }

        // 人设在【素材】之后、硬参数之前：模型先知道「这批素材是什么」，再知道「谁在讲」，
        // 最后才看到不可改的时长/镜头数，顺序一乱就容易把人设介绍当成画面描述。
        $persona = self::normalizePersona((string)($p['persona'] ?? ''));
        if ($persona !== '') {
            $lines[] = '【人设】';
            $lines[] = $persona;
            $lines[] = '（口播口吻、卖点角度、目标人群按此人设；人设只影响台词，不影响画面主体）';
        }

        $lines[] = '【时长】' . (int)$p['duration'] . ' 秒';
        $lines[] = '【画面比例】' . (string)$p['aspect_ratio']
            . '（' . (self::RATIO_ZH[(string)$p['aspect_ratio']] ?? (string)$p['aspect_ratio']) . '）';
        $lines[] = '【镜头数】' . (int)$p['shots'] . '（已由代码算好，必须精确等于）';
        $lines[] = '【口播字数上限】' . (int)$p['max_dialogue'] . ' 字（'
            . (self::LANG_ZH[(string)$p['language']] ?? '中文') . '）';
        $lines[] = '【台词语言】' . (self::LANG_ZH[(string)$p['language']] ?? '中文');
        $lines[] = '【模式】' . ($p['mode'] === 'creative' ? '创作' : '带货');
        $lines[] = '【额外要求】无';

        return implode("\n", $lines);
    }

    /* ══════════════════════════════════════════════════════════
     * 输出处理
     * ══════════════════════════════════════════════════════════ */

    /** 去掉模型爱加的围栏、标题井号、加粗星号、开场白 */
    public static function sanitize(string $raw): string
    {
        $text = trim($raw);
        // ```json ... ``` / ``` ... ```
        $text = (string)preg_replace('/^```[a-zA-Z]*\s*/u', '', $text);
        $text = (string)preg_replace('/\s*```$/u', '', $text);
        $text = str_replace('```', '', $text);
        // markdown 强调与标题：这些符号最终落进纯文本输入框，用户看到的就是一串莫名其妙的符号
        $text = str_replace(['**', '__'], '', $text);
        $text = (string)preg_replace('/^[ \t]*#{1,6}[ \t]*/mu', '', $text);
        // 开场白
        $text = (string)preg_replace('/^[ \t]*以下是[^\n]*\n/u', '', $text);
        $text = (string)preg_replace('/^[ \t]*提示词[:：][ \t]*\n/u', '', $text);
        // 连续空行
        $text = (string)preg_replace('/\n{3,}/u', "\n\n", $text);
        return trim($text);
    }

    /**
     * 从模型输出里剥出标题行，返回 [标题, 去掉标题行后的正文]。
     *
     * 只在**首行**认标题：正文里出现「标题」两个字多半是约束说明，误删会吃掉提示词。
     * 认不出来时返回 ['', 原文]，由 fallbackTitle() 兜底。
     *
     * @return array{0: string, 1: string}
     */
    public static function extractTitle(string $text): array
    {
        $lines = preg_split('/\R/u', trim($text)) ?: [];
        if ($lines === []) {
            return ['', $text];
        }

        // 允许前面有空行
        $head = 0;
        while ($head < count($lines) && trim($lines[$head]) === '') {
            $head++;
        }
        if ($head >= count($lines)) {
            return ['', $text];
        }

        $first = trim($lines[$head]);
        $matched = (bool)preg_match('/^[【\[（(]?\s*(?:视频|记录|作品)?标题\s*[】\]）)]?\s*[:：\-—]?\s*(.+)$/u', $first, $m);
        if (!$matched) {
            return ['', $text];
        }

        $title = self::normalizeTitle($m[1]);
        if ($title === '') {
            return ['', $text];
        }

        array_splice($lines, 0, $head + 1);
        return [$title, trim(implode("\n", $lines))];
    }

    /**
     * 没有标题行时的兜底：从提示词里取一句最像主题的话。
     *
     * 模型的首行总述按约定是「时长 + 比例 + 风格 + 参考图用途」，直接截前 10 字就是
     * 「7秒竖屏9:16，写」这种垃圾（真实工单）。所以先按逗号切段、丢掉参数段与风格段，
     * 再取第一个剩下的段；整段都是参数时返回空串，交给上层用默认名。
     */
    public static function fallbackTitle(string $text): string
    {
        $firstLine = '';
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            if (trim($line) !== '') {
                $firstLine = trim($line);
                break;
            }
        }
        if ($firstLine === '') {
            return '';
        }

        // 首行总述里的「参考图用途」常在括号里，括号内容单独成段后同样是噪声，先去掉
        $flat = (string)preg_replace('/[（(][^）)]*[）)]/u', '，', $firstLine);
        // 别按冒号切：会把 9:16 拆成 9 和 16 两段，反而更容易把参数当主题
        $parts = preg_split('/[，,、;；|]+/u', $flat) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $v): bool => $v !== ''));
        if ($parts === []) {
            return '';
        }

        foreach ($parts as $part) {
            if (!self::looksLikeParamSegment($part)) {
                return self::normalizeTitle(self::stripCommandPrefix($part));
            }
        }
        // 整段都是参数/风格（纯 AI 提示词首行的常态之一）：宁可留空让上层用兜底名，
        // 也不要把「7秒竖屏9」这种残段当标题
        return '';
    }

    /**
     * 去掉用户原话开头的祈使词，让「帮我生成一个京都美容海报」变成「京都美容海报」。
     *
     * 只在动词后面跟着量词时才剥：光剥「写」会把「写实风格」剥成「实风格」。
     */
    private static function stripCommandPrefix(string $text): string
    {
        $stripped = (string)preg_replace(
            '/^(?:请|帮我|帮忙|麻烦)?\s*(?:生成|制作|做|写|拍|来)\s*(?:一个|一张|一段|一条|一份|个)\s*/u',
            '',
            $text
        );
        $stripped = trim($stripped);
        return $stripped === '' ? $text : $stripped;
    }

    /** 纯参数 / 风格描述段，不配当标题 */
    private static function looksLikeParamSegment(string $segment): bool
    {
        $patterns = [
            '/\d+\s*秒/u',                       // 7秒
            '/\d+\s*[:：]\s*\d+/u',              // 9:16
            '/^\d+\s*[fF][pP][sS]$/u',
            '/(竖屏|横屏|竖版|横版|方形|宽银幕)/u',
            '/(高清|超清|蓝光|\d+[kK])/u',
            '/(写实|实拍|电影感|大片感|动漫|二次元|插画|3[Dd]|CG|卡通|国风|油画|水彩|像素|赛博)/u',
            '/(风格|画风|色调|光影|构图|镜头|运镜|景别|机位)/u',
            '/(以|参考).{0,6}(图|视频|首帧|尾帧)/u',
            '/^(时长|比例|分辨率)/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $segment)) {
                return true;
            }
        }
        // 一个汉字都没有（纯英文/数字/符号）不当标题
        return !preg_match('/\p{Han}/u', $segment);
    }

    /** 标题规整：去包裹符号、去尾部标点、压空白、按字宽截断 */
    public static function normalizeTitle(string $raw): string
    {
        $title = trim($raw);
        // 去包裹的引号/书名号/方括号，模型很爱加。
        // 注意不能用 trim($title, "“”《》")：trim 的字符表按**字节**剥，
        // 多字节字符会被剥掉半个（「写」= E5 86 99，命中 0x86 就没了）。
        $title = (string)preg_replace('/^[\s"\'“”‘’《》〈〉【】\[\]（）()]+/u', '', $title);
        $title = (string)preg_replace('/[\s"\'“”‘’《》〈〉【】\[\]（）()]+$/u', '', $title);
        // 素材引用 token 不进标题（模型偶尔会把「@图片1」写进标题里）
        $title = (string)preg_replace('/@\s*(?:图片|视频|角色)\s*\d+/u', ' ', $title);
        // 去掉结尾的句读与省略号
        $title = (string)preg_replace('/[。．\.，,、；;：:！!？?~～…\-\s]+$/u', '', $title);
        $title = (string)preg_replace('/\s+/u', ' ', $title);
        $title = trim($title);
        if ($title === '') {
            return '';
        }
        return self::truncate($title, self::TITLE_MAX_CHARS);
    }

    /**
     * 素材引用归一化。
     *
     * 1) 全角＠ → 半角 @
     * 2) @图片 1 / @ 图片1 等写法归一成 @图片1
     * 3) 越界编号或整类素材不存在 → 降级成通用词（@图片9 → 参考图），
     *    绝不把 token 留给用户看：那是「指向空气的假引用」
     * 4) 后置补一个半角空格（标点前也补）；前置补空格但句读/括号后不补
     * 5) 连续重复的同一 token 收敛成一个（不做整句去重，见下方注释）
     */
    public static function normalizeRefs(string $text, int $imageCount, int $videoCount, int $anchorCount): string
    {
        if ($text === '') {
            return $text;
        }
        $text = str_replace(['＠', '﹫'], '@', $text);

        $limits = ['图片' => $imageCount, '视频' => $videoCount, '角色' => $anchorCount];

        // 归一 + 越界降级。降级时**连前置空格一起吃掉**：
        // 「以 @图片9 为准」要变成「以参考图为准」，留下「以 参考图为准」这种断口很扎眼
        $text = (string)preg_replace_callback(
            '/([ \t]*)@[ \t]*(图片|视频|角色)[ \t]*(\d+)[ \t]*/u',
            static function (array $m) use ($limits): string {
                $type = $m[2];
                $num  = (int)$m[3];
                $max  = (int)($limits[$type] ?? 0);
                if ($max <= 0 || $num < 1 || $num > $max) {
                    return self::REF_TYPES[$type] ?? $type;
                }
                return $m[1] . '@' . $type . $num;
            },
            $text
        );

        // 前置空格：行首、括号内、句读之后不补（中文排版里「。 @图片1」很怪）
        $text = (string)preg_replace('/(?<![\s（(【「。，、；：！？,.;:!?])@(图片|视频|角色)(\d+)/u', ' @$1$2', $text);
        // 后置空格：只要后面还有非空白字符就补一个，标点也算（提示词口径是「前后各留一个空格」）
        $text = (string)preg_replace('/(@(?:图片|视频|角色)\d)(?=[^\s])/u', '$1 ', $text);

        // 连续重复的同一 token 收敛成一个（「…喷头 @图片1 @图片1」这种尾巴）。
        // 注意**不做整句去重**：system prompt 里点名的正确写法就是
        // 「一只手拿起 @图片1 的瓶子，拇指按上 @图片1 的喷头」——
        // 删掉第二个引用会留下「按上 的喷头」这种断句，反而比多标一次更糟。
        $text = (string)preg_replace('/(@(?:图片|视频|角色)\d)(?:[ \t]+\1)+/u', '$1', $text);

        // 收拾去重/降级留下的多余空格（不动行首缩进）
        $text = (string)preg_replace('/(?<=\S)[ \t]{2,}(?=\S)/u', ' ', $text);
        return trim($text);
    }

    /** 超长时在最后一个句末标点处截断，不在句子中间砍 */
    public static function truncate(string $text, int $limit): string
    {
        if ($limit <= 0 || mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $pos = max(
            (int)mb_strrpos($cut, '。'),
            (int)mb_strrpos($cut, '！'),
            (int)mb_strrpos($cut, '？'),
            (int)mb_strrpos($cut, '；')
        );
        if ($pos > (int)($limit * 0.5)) {
            return mb_substr($cut, 0, $pos + 1);
        }
        return $cut;
    }

    /**
     * 量口播字数：只统计「口播」后面的引号内容，画面描述与字幕不算。
     * 返回 ['chars' => 实际字数, 'items' => 口播条数]
     *
     * 模型可能写成「口播「…」」也可能写成「口播（中文）：「…」」，
     * 冒号后面还可能换行再给引号，所以先按要求把「冒号+换行」拍平再切。
     *
     * @return array{chars: int, items: int}
     */
    public static function measureDialogue(string $text): array
    {
        if ($text === '') {
            return ['chars' => 0, 'items' => 0];
        }
        $flat = (string)preg_replace('/([：:])\s*\n/u', '$1', $text);
        // 一段口播到「字幕/音效/镜头/禁止」为止，避免把字幕当成台词
        if (preg_match_all('/口播((?:(?!字幕|音效|镜头|禁止)[^\n])*)/u', $flat, $m) === false) {
            return ['chars' => 0, 'items' => 0];
        }
        $segments = (array)($m[1] ?? []);
        if ($segments === []) {
            return ['chars' => 0, 'items' => 0];
        }
        $chars = 0;
        $items = 0;
        foreach ($segments as $segment) {
            $items++;
            $segment = (string)$segment;
            if (preg_match_all('/[「『"“]([^」』"”]+)[」』"”]/u', $segment, $q) > 0) {
                foreach ($q[1] as $piece) {
                    $chars += self::dialogueLen((string)$piece);
                }
                continue;
            }
            $plain = (string)preg_replace('/^[（(][^）)]*[）)]/u', '', $segment);
            $plain = (string)preg_replace('/[：:]/u', '', $plain);
            $chars += self::dialogueLen($plain);
        }
        return ['chars' => $chars, 'items' => $items];
    }

    /* ══════════════════════════════════════════════════════════
     * 模型与调用
     * ══════════════════════════════════════════════════════════ */

    /**
     * 首选模型：配置覆盖 → 常量 deepseek。
     *
     * 首选**不校验是否在 models_cost 里**：中台按别名路由，站长端只负责把别名发出去，
     * 上架与否是中台的账。真失败了由 generate() 换本站上架模型重试一次。
     */
    public static function preferredModel(): string
    {
        try {
            $configured = trim((string)ConfigService::get('sora', 'prompt_model', ''));
        } catch (\Throwable $e) {
            $configured = '';
        }
        return $configured !== '' ? $configured : self::DEFAULT_MODEL;
    }

    /**
     * 选模型。$exclude 为空 = 用首选；非空 = 换一个本站已上架的对话模型（避开刚失败的）。
     * 一个都挑不出来时原样返回 $exclude，让调用方中止重试、把上游错误透出去
     * —— 宁可报错，也不要静默换成别的模型跑出一版用户没预期的结果。
     */
    public static function resolveModel(string $exclude = ''): string
    {
        if ($exclude === '') {
            return self::preferredModel();
        }
        $preferred = self::preferredModel();
        try {
            $candidates = ModelsCost::alias('c')
                ->join('models m', 'm.id = c.model_id')
                ->where('c.type', ChatEnum::MODEL_TYPE_CHAT)
                ->where('c.status', 1)
                ->where('m.type', ChatEnum::MODEL_TYPE_CHAT)
                ->where('m.is_enable', 1)
                ->where('c.alias', '<>', $preferred)
                ->where('c.alias', '<>', $exclude)
                ->column('c.alias');
        } catch (\Throwable $e) {
            return $exclude;
        }
        $candidates = array_values(array_unique(array_filter(array_map('strval', (array)$candidates), static function (string $alias): bool {
            return $alias !== '' && preg_match('/phone|vision|-4v|-vl|image|audio|tts|embed/i', $alias) !== 1;
        })));
        if ($candidates === []) {
            return $exclude;
        }
        return $candidates[array_rand($candidates)];
    }

    /**
     * 调中台通用对话端点，返回 data 段（choices / usage 在这里面）。
     *
     * enable_thinking=false 只对支持该开关的模型有效；deepseek 的「不思考」要走 -none 变体。
     * 这里仍传 false，搭配下面的空正文重试兜底。
     *
     * @return array<string, mixed>
     * @throws \Exception
     */
    private static function requestModel(string $model, string $system, string $user): array
    {
        $body = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.2,
            'max_tokens'  => self::MAX_TOKENS,
            'stream'      => false,
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

    private static function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
}
