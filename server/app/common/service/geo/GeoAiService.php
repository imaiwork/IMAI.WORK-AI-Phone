<?php

namespace app\common\service\geo;

use app\common\service\ToolsService;
use think\facade\Log;

/**
 * GEO AI 调用路由层(新中台版)
 * 统一入口 call(system, prompt, json, kind, model, options),按任务种类路由到
 * 中台 GEO 场景端点(ToolsService::Geo());build_context/gen_keyword 这类无专属
 * 场景的辅助调用走中台通用聊天(ToolsService::Chat()->message())。
 * GEO 场景端点不传 model 时由中台 GEO_DEFAULT_MODEL 兜底(默认 deepseek-v4-pro);
 * /api/chat/completions 没有该兜底,空 model 会被网关直接拒绝,辅助调用必须显式传。
 * 模型名含 deepseek 时中台自动注入 thinking.type=disabled,本地不再直连
 * SiliconFlow/方舟,也不保留 mock 降级:所有失败抛 \Exception(中台原始文案),
 * 调用方据空产物判失败、不计费、不落假数据。
 * 中台按 new_api_native 模型 token 计费(data.usage 为基数),与站长端本地
 * model_config 场景扣费是两层独立账务。
 */
class GeoAiService
{
    /**
     * 品牌知识库摘要:供各生成任务作为上下文喂给模型。
     *
     * 原来各处直接 implode('；', GeoKnowledge::column('content')) 全量拼接,没有任何上限。
     * 知识库是累加的(每导入一份文档就多几十条实体),条数上去之后提示词无限膨胀:
     * 输入 token 成本线性上涨,还会把输出挤到 max_tokens 上限触发截断。
     * 这里统一做去重 + 单条截断 + 总长封顶,优先保留最新导入的实体。
     *
     * @param int $limitChars 总长上限(字符),默认 6000 约 4k token
     */
    public static function knowledgeDigest(int $projectId, int $limitChars = 6000): string
    {
        $rows = \app\common\model\geo\GeoKnowledge::where('project_id', $projectId)
            ->order('id desc')->limit(300)->column('content');
        $out = [];
        $len = 0;
        foreach ($rows as $c) {
            $c = trim(preg_replace('/\s+/u', ' ', (string)$c));
            if ($c === '' || isset($out[$c])) continue;
            if (mb_strlen($c) > 300) $c = mb_substr($c, 0, 300) . '…';
            $len += mb_strlen($c) + 1;
            if ($len > $limitChars) break;
            $out[$c] = true;
        }
        return implode('；', array_keys($out));
    }

    /**
     * 生成类请求体统一 max_tokens=4000。
     * 中台按实际消耗 token 计费(new_api_native),这里只是放宽输出上限防止长文截断
     * (中台对 finish_reason=length 拦截返回 40000 不扣费),不产生额外成本。
     */
    protected const MAX_TOKENS = 4000;

    /**
     * 本次请求周期的 token 消耗累计(中台 data.usage),供按实际 token 计费。
     * 计费入口(Logic)在业务流程开始前 resetUsage(),结束后取 usage() 结算;
     * 多步链式调用(如 analyze_brand 链式 gen_keyword)为累计值。
     */
    protected static array $usageAcc = ['prompt' => 0, 'completion' => 0, 'total' => 0, 'model' => ''];

    /** 重置 token 累计(每个计费业务流程开始时调用) */
    public static function resetUsage(): void
    {
        self::$usageAcc = ['prompt' => 0, 'completion' => 0, 'total' => 0, 'model' => ''];
    }

    /** 当前 token 累计(供 GeoChargeService::settleByUsage 结算) */
    public static function usage(): array
    {
        return self::$usageAcc;
    }

    /** 累计一次中台调用的 token 消耗(中台 relay 层会补齐缺失的 usage) */
    protected static function accUsage(array $data): void
    {
        $u = (array)($data['usage'] ?? []);
        $prompt = (int)($u['prompt_tokens'] ?? 0);
        $completion = (int)($u['completion_tokens'] ?? 0);
        $total = (int)($u['total_tokens'] ?? 0);
        if ($total <= 0) {
            $total = $prompt + $completion;
        }
        self::$usageAcc['prompt'] += $prompt;
        self::$usageAcc['completion'] += $completion;
        self::$usageAcc['total'] += $total;
        $model = (string)($data['model'] ?? '');
        if ($model !== '') {
            self::$usageAcc['model'] = $model;
        }
    }

    /**
     * 统一调用入口。
     * @param string $system  系统提示
     * @param string $prompt  用户提示
     * @param bool   $json    是否要求返回 JSON(true 则带 response_format 并安全解析为数组)
     * @param string $kind    任务种类(路由用,见下方 switch)
     * @param string $model   显式指定模型;空则读 env('geo.gen_default');
     *                        GEO 场景仍空则不传(中台兜底);辅助聊天仍空则用 deepseek-v4-pro
     * @param array  $options 可选项:via=report 时 gen_suggestion 改走 /api/geo/report
     *                        (报告内嵌刷新场景,保证一次报告只产生一次 report 调用)
     * @return mixed  json=true 返回 array; 否则返回 string
     * @throws \Exception 中台失败(原文)/返回空正文
     */
    public static function call(string $system, string $prompt, bool $json = false, string $kind = '', string $model = '', array $options = [])
    {
        $body = [
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => self::MAX_TOKENS,
        ];
        if ($json) {
            $body['response_format'] = ['type' => 'json_object'];
        }
        // model 仅在显式传入或 env('geo.gen_default') 非空时设置,否则由中台默认模型兜底
        $model = $model !== '' ? $model : (string)env('geo.gen_default', '');
        if ($model !== '') {
            $body['model'] = $model;
        }
        // enable_thinking 只发给国产"默认自动思考"的模型(deepseek/qwen/doubao 等,
        // 不关思考复杂问题会拖到超时);OpenAI 系(gpt-4o 等)不认识该参数,
        // 上游会直接 400「Unrecognized request argument supplied」
        if ($model === '' || preg_match('/^(deepseek|qwen|doubao|hy|hunyuan|glm|kimi|moonshot|ernie|spark)/i', $model)) {
            $body['enable_thinking'] = false;
        }

        try {
            switch ($kind) {
                case 'gen_content':
                    $data = ToolsService::Geo()->content($body);
                    break;
                case 'parse_knowledge':
                    $data = ToolsService::Geo()->knowledge($body);
                    break;
                case 'analyze_brand':
                    $data = ToolsService::Geo()->analyze($body);
                    break;
                case 'gen_suggestion':
                    // 报告内嵌刷新走 report 端点:一次报告只产生一次 /api/geo/report 调用,
                    // 且不与独立建议(geo_suggestion)同时结算
                    $data = ($options['via'] ?? '') === 'report'
                        ? ToolsService::Geo()->report($body)
                        : ToolsService::Geo()->suggestion($body);
                    break;
                case 'recommend_topics':
                    $data = ToolsService::Geo()->topic($body);
                    break;
                case 'match_brand':
                    $data = ToolsService::Geo()->matchBrand($body);
                    break;
                case 'gen_scene_questions':
                    $data = ToolsService::Geo()->question($body);
                    break;
                case 'build_context':
                case 'gen_keyword':
                    // 辅助调用(无专属 GEO 场景):走中台通用聊天端点
                    $reply = self::chatCompletion($body);
                    return $json ? self::parseJson($reply) : $reply;
                default:
                    throw new \Exception('GeoAiService 未知任务类型: ' . $kind);
            }
        } catch (\Throwable $e) {
            Log::error("GeoAiService 中台调用失败 (kind={$kind}, model={$model}): " . $e->getMessage());
            throw $e instanceof \Exception ? $e : new \Exception($e->getMessage());
        }

        // 累计本次调用的 token 消耗(供按实际 token 计费)
        self::accUsage($data);

        // 中台已对截断(finish_reason=length)返回 40000 并抛错,这里只兜底空正文
        $content = (string)($data['message'] ?? '');
        if ($content === '') {
            throw new \Exception('AI接口返回空');
        }
        if (!$json) {
            return $content;
        }
        // JSON 解析为空数组时按 product 语义交由调用方据空产物判失败(不扣费)
        $res = self::parseJson($content);
        if ($kind === 'gen_scene_questions') {
            // 中台已清洗去重:优先用中台返回的 questions,本地解析结果兜底
            $qs = array_values(array_filter(array_map('trim', (array)($data['questions'] ?? $res['questions'] ?? []))));
            $res['questions'] = $qs;
            $res['question_count'] = (int)($data['question_count'] ?? count($qs));
        }
        return $res;
    }

    /**
     * 中台通用聊天(/api/chat/completions)辅助调用:build_context/gen_keyword 这类
     * 没有专属 GEO 场景端点的轻任务走这里。非流式;返回完整信封,自行判 code 取正文。
     * @throws \Exception 网关错误/返回空
     */
    protected static function chatCompletion(array $body): string
    {
        $body['stream'] = false;
        // /api/chat/completions 没有 GEO_DEFAULT_MODEL 兜底,空 model 会被网关拒绝
        if (trim((string)($body['model'] ?? '')) === '') {
            $body['model'] = (string)env('geo.gen_default', '') ?: 'deepseek-v4-pro';
        }
        $response = ToolsService::Chat()->message($body);
        $code = (int)($response['code'] ?? 0);
        if ($code !== 10000) {
            $message = trim((string)($response['message'] ?? $response['msg'] ?? ''));
            throw new \Exception($message !== '' ? $message : '模型网关请求失败(code=' . $code . ')');
        }
        self::accUsage((array)($response['data'] ?? [])); // 辅助调用同样累计 token
        $reply = (string)($response['data']['choices'][0]['message']['content'] ?? '');
        if ($reply === '') {
            throw new \Exception('模型网关返回空');
        }
        return $reply;
    }

    /**
     * 生成侧可选模型列表:模型计费口径下,选什么模型直接决定单价,
     * 从 models_cost 取启用中的对话模型(与后台「AI模型-模型计费」同源)。
     * model_id>0 过滤掉监测专用补种行(豆包监测模型不应出现在生成侧选择器)。
     */
    public static function modelOptions(): array
    {
        $rows = \app\common\model\chat\ModelsCost::where('type', 1)
            ->where('status', 1)
            ->where('model_id', '>', 0)
            ->order('sort asc, id asc')
            ->field('alias,name')
            ->select()->toArray();
        $out = [];
        $seen = [];
        foreach ($rows as $r) {
            $alias = trim((string)$r['alias']);
            if ($alias === '' || isset($seen[$alias])) continue;
            $seen[$alias] = true;
            $out[] = ['value' => $alias, 'label' => (string)($r['name'] ?: $alias)];
        }
        return $out;
    }

    /** 「AI匹配品牌信息」的模型列表:与生成侧同一清单 */
    public static function systemModelOptions(): array
    {
        return self::modelOptions();
    }

    /**
     * 「AI匹配品牌信息」的模型解析:显式指定 > env('geo.gen_default') > 空(空=中台默认模型)。
     */
    public static function matchBrandModel(string $model = ''): string
    {
        if ($model !== '') return $model;
        return (string)env('geo.gen_default', '');
    }

    /** 安全解析模型返回的 JSON(剥离 ```json 围栏;容忍字符串里的裸控制字符) */
    public static function parseJson(string $text): array
    {
        $t = trim($text);
        // 思考型模型(claude-*-think 等)常把 JSON 埋在大段推理文字中间的代码围栏里,
        // 只剥首尾围栏取不到——优先提取文中第一个 ```json 围栏内容
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/is', $t, $mFence)) {
            $fenced = json_decode($mFence[1], true);
            if (is_array($fenced)) {
                return $fenced;
            }
        }
        $t = preg_replace('/^```(json)?/i', '', $t);
        $t = preg_replace('/```$/', '', trim($t));
        $t = trim($t);

        $data = json_decode($t, true);
        if (is_array($data)) {
            return $data;
        }
        // 模型写长正文(尤其 markdown)时经常在 JSON 字符串里直接输出裸换行/制表符,
        // json_decode 会报 Control character error 直接失败。这里对字符串字面量内部的
        // 控制字符做一次转义再解析——否则整篇文章会被丢成空数组,调用方还照常计费。
        if (json_last_error() === JSON_ERROR_CTRL_CHAR) {
            $fixed = self::escapeCtrlCharsInStrings($t);
            $data = json_decode($fixed, true);
            if (is_array($data)) {
                return $data;
            }
        }
        // 再兜底:截取最外层 {...} 后重试(模型有时在 JSON 前后带解释文字)
        $s = strpos($t, '{');
        $e = strrpos($t, '}');
        if ($s !== false && $e !== false && $e > $s) {
            $inner = substr($t, $s, $e - $s + 1);
            $data = json_decode($inner, true) ?: json_decode(self::escapeCtrlCharsInStrings($inner), true);
            if (is_array($data)) {
                return $data;
            }
        }
        // 最后兜底:输出被 max_tokens 截断成半截 JSON(长文档实体抽取高发)——
        // 回退到最后一个完整闭合的对象处,补齐未闭合的括号,救回已完整的部分
        if ($s !== false) {
            $data = self::salvageTruncatedJson(substr($t, $s));
            if (is_array($data)) {
                \think\facade\Log::warning('GeoAiService::parseJson 输出疑似被截断,已按截断修复解析');
                return $data;
            }
        }
        return [];
    }

    /**
     * 截断 JSON 修复:带字符串/转义感知地扫描,记录每个「对象闭合」时点的括号栈,
     * 从最后一个安全时点截断并按栈补 ]/},丢弃半截尾部。修不出来返回 null。
     */
    protected static function salvageTruncatedJson(string $t): ?array
    {
        $stack = [];
        $inStr = false;
        $esc = false;
        $cutPos = -1;
        $cutStack = [];
        $len = strlen($t);
        for ($i = 0; $i < $len; $i++) {
            $ch = $t[$i];
            if ($inStr) {
                if ($esc) { $esc = false; continue; }
                if ($ch === '\\') { $esc = true; continue; }
                if ($ch === '"') { $inStr = false; }
                continue;
            }
            if ($ch === '"') { $inStr = true; continue; }
            if ($ch === '{' || $ch === '[') { $stack[] = $ch; continue; }
            if ($ch === '}' || $ch === ']') {
                $open = array_pop($stack);
                if ($open === null || ($ch === '}' ? '{' : '[') !== $open) {
                    return null; // 括号错配,不是单纯截断,放弃
                }
                if ($ch === '}') { $cutPos = $i; $cutStack = $stack; }
            }
        }
        if ($cutPos < 0 || !$cutStack) {
            return null; // 从未闭合过对象,或本就完整(完整的话前面早解析成功了)
        }
        $repaired = substr($t, 0, $cutPos + 1);
        foreach (array_reverse($cutStack) as $open) {
            $repaired .= $open === '{' ? '}' : ']';
        }
        $data = json_decode($repaired, true) ?: json_decode(self::escapeCtrlCharsInStrings($repaired), true);
        return is_array($data) ? $data : null;
    }

    /** 把 JSON 字符串字面量内部的裸控制字符转义(结构外的空白保持原样) */
    protected static function escapeCtrlCharsInStrings(string $json): string
    {
        $out = '';
        $inStr = false;
        $esc = false;
        $len = strlen($json);
        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];
            if ($inStr) {
                if ($esc) { $out .= $ch; $esc = false; continue; }
                if ($ch === '\\') { $out .= $ch; $esc = true; continue; }
                if ($ch === '"') { $out .= $ch; $inStr = false; continue; }
                $out .= match ($ch) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    default => ord($ch) < 0x20 ? sprintf('\\u%04x', ord($ch)) : $ch,
                };
                continue;
            }
            if ($ch === '"') { $inStr = true; }
            $out .= $ch;
        }
        return $out;
    }
}
