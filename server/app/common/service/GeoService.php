<?php

namespace app\common\service;

use think\facade\Log;

/**
 * GEO 中台客户端
 *
 * 契约：新中台 GEO 场景接口（站长端对接 12 个 /api/geo/* 端点，geo-apipost-api.md v2）
 *  - 信封：成功 code=10000；业务失败 code=40000（中台不扣费，预扣额度自动退还）
 *  - 鉴权：复用 ToolsService 传输层（Header key + sign，HMAC-SHA256 原始 body）
 *  - 中台计费(只影响中台与站长结算，站长端本地 model_config 计费不受影响)：
 *    生成类 1~9 与监测走 new_api_native，按 实际token × 模型倍率 扣费
 *    （data.usage 是扣费基数）；search_suggest / publish_stats 免费。
 *    【媒体代发已下线】发稿 order/submit|status|refund 三个端点站长端不再调用
 *  - 模型：不传 model 由中台 GEO_DEFAULT_MODEL 兜底（默认 deepseek-v4-pro）；
 *    模型名含 deepseek 时中台自动注入 thinking.type=disabled，站长端无需传 thinking
 *  - 约定：全部非流式（stream=false）；本类方法成功返回 data 数组，
 *    失败一律抛 \Exception（消息含中台原始错误文案），调用方不得计费、不得落假数据
 */
class GeoService
{
    /** 生成类默认超时（秒），对齐中台文档建议客户端超时 */
    const TIMEOUT_GENERATE = 120;
    /** 诊断报告（长文） */
    const TIMEOUT_REPORT   = 180;
    /** 监测联网口径 */
    const TIMEOUT_MONITOR_WEB   = 120;
    /** 监测模型直答口径 */
    const TIMEOUT_MONITOR_MODEL = 60;
    /** 搜索联想 / 效果回收 / 发稿 */
    const TIMEOUT_DEFAULT  = 60;
    /** 连接超时（秒） */
    const TIMEOUT_CONNECT  = 10;

    /**
     * GEO 中台是否可用（替代原 geo.ai_key 判定）
     */
    public static function enabled(): bool
    {
        return (string)env('PROJECT_KEY.API_KEY', '') !== '';
    }

    // +----------------------------------------------------------------------
    // | 生成类接口（1~9，OpenAI Chat Completions 结构）
    // +----------------------------------------------------------------------

    /** 接口1：AI生成文章（geo_content） */
    public function content(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/content', $body, $timeout);
    }

    /** 接口2：知识解析导入（geo_knowledge） */
    public function knowledge(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/knowledge', $body, $timeout);
    }

    /** 接口3：品牌分析（geo_analyze） */
    public function analyze(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/analyze', $body, $timeout);
    }

    /** 接口4：优化建议（geo_suggestion） */
    public function suggestion(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/suggestion', $body, $timeout);
    }

    /** 接口5：GEO诊断报告（geo_report，长文场景） */
    public function report(array $body, int $timeout = self::TIMEOUT_REPORT): array
    {
        return $this->generate('/api/geo/report', $body, $timeout);
    }

    /** 接口6：AI推荐话题（geo_topic_ai） */
    public function topic(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/topic', $body, $timeout);
    }

    /**
     * 接口7：AI生成场景问题（geo_question_ai）
     * 中台强制 JSON 结构化输出并禁止流式；返回 data 内含 questions / question_count
     * （v2 起中台按模型 token 计费，question_count 仅用于取问题列表，不再是计费数量）
     */
    public function question(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        $body['stream'] = false;
        return $this->generate('/api/geo/question', $body, $timeout);
    }

    /** 接口8：AI匹配品牌信息（geo_match_brand） */
    public function matchBrand(array $body, int $timeout = self::TIMEOUT_GENERATE): array
    {
        return $this->generate('/api/geo/match_brand', $body, $timeout);
    }

    // 原接口9 video_text(文章转短视频·文案)已下线:GEO 只产口播稿,
    // 由数字人模块的文案接口(/kb.robot/getCopywriting)生成并按其口径计费

    // +----------------------------------------------------------------------
    // | 接口10：AI搜索监测（geo_monitor）
    // +----------------------------------------------------------------------

    /**
     * @param string $engine     doubao / deepseek / tongyi / yuanbao
     * @param string $query      监测问题原文
     * @param string $searchMode web=联网检索；model=模型直答
     * @param int    $maxTokens  最大生成 token 数
     * @return array {answer, citations, engine, model, search_mode}
     */
    public function monitorQuery(string $engine, string $query, string $searchMode = 'web', int $maxTokens = 4000): array
    {
        $timeout = $searchMode === 'model' ? self::TIMEOUT_MONITOR_MODEL : self::TIMEOUT_MONITOR_WEB;

        return self::post('/api/geo/monitor/query', [
            'engine'      => $engine,
            'query'       => $query,
            'search_mode' => $searchMode,
            'max_tokens'  => $maxTokens,
        ], $timeout);
    }

    // +----------------------------------------------------------------------
    // | 接口11~12：TikHub 社媒数据类
    // +----------------------------------------------------------------------

    /**
     * 接口11：AI搜索联想词（geo_search_suggest）
     * @return array {terms:[{term,platform}], failed_platforms?:[]}
     */
    public function searchSuggest(string $keyword, array $platforms = [], int $perPlatform = 10): array
    {
        $body = ['keyword' => $keyword, 'per_platform' => $perPlatform];
        if ($platforms !== []) {
            $body['platforms'] = array_values($platforms);
        }

        return self::post('/api/geo/search_suggest', $body, self::TIMEOUT_DEFAULT);
    }

    /**
     * 接口12：投稿效果回收（geo_publish_stats）
     * @return array {views, likes, comments, collects, shares}
     */
    public function publishStats(string $url, string $platform, string $mediaType = 'article'): array
    {
        return self::post('/api/geo/publish_stats', [
            'url'        => $url,
            'platform'   => $platform,
            'media_type' => $mediaType,
        ], self::TIMEOUT_DEFAULT);
    }

    // +----------------------------------------------------------------------
    // | 内部实现
    // +----------------------------------------------------------------------

    /**
     * 生成类统一入口：强制非流式
     */
    private function generate(string $path, array $body, int $timeout): array
    {
        $body['stream'] = false;
        return self::post($path, $body, $timeout);
    }

    /**
     * 发送请求并解包信封
     * @throws \Exception 非 code=10000 一律抛错（消息含中台原始文案）
     */
    private static function post(string $path, array $body, int $timeout): array
    {
        try {
            $app = app(ToolsService::class)
                ->setRequest($body)
                ->setApiUrl($path)
                ->setMethod('POST')
                ->setTimeout(self::TIMEOUT_CONNECT, $timeout)
                ->sendWithoutThrow();
            $response = $app->response;
        } catch (\Throwable $e) {
            // check() 密钥未配置等传输层异常
            throw new \Exception($e->getMessage());
        }

        $code = (int)($response['code'] ?? 0);
        if ($code !== 10000) {
            $message = trim((string)($response['message'] ?? $response['msg'] ?? ''));
            if ($message === '') {
                $message = 'GEO 中台请求失败(code=' . $code . ')';
            }
            Log::error('GEO 中台请求失败: ' . json_encode([
                'path'    => $path,
                'code'    => $code,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE));
            throw new \Exception($message);
        }

        $data = $response['data'] ?? [];
        return is_array($data) ? $data : [];
    }
}
