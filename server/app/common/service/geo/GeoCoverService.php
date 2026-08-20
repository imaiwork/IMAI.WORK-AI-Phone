<?php

namespace app\common\service\geo;

use app\api\logic\HdLogic;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoProject;
use think\facade\Log;

/**
 * GEO 文章封面图。
 *
 * 复用系统已有的文生图能力(HdLogic::txt2img → ToolsService::HiDream()),
 * 不另起通道:算力扣费、任务日志、失败恢复都沿用主系统那套(TOKENS_DEC_TEXT_TO_IMAGE)。
 *
 * 为什么异步:文生图通常 10~30s,而 GEO 的文章生成本身已经 15~25s。
 * 串起来必然逼近网关超时,所以生成文章时只【提交】任务并记 task_id,
 * 前端拿到文章后轮询 fetch() 取图。
 */
class GeoCoverService
{
    /** HdLogic 的文生图任务类型(getTaskStatus 的 type 参数) */
    protected const TASK_TYPE_TXT2IMG = 3;

    /**
     * 为一篇文章提交封面图生成任务(不等待出图)。
     * 失败只记日志、不抛 —— 封面图是增强项,不能因为它让整篇文章判失败。
     * @param int $payerUserId 扣费用户;0 时回退项目创建者(兼容旧调用)
     */
    public static function submit(GeoContent $content, GeoProject $project, int $payerUserId = 0): void
    {
        if (!self::enabled()) {
            return;
        }
        try {
            // HdLogic 计费/HdLog 归属都走 ApiLogic::$uid 静态上下文;
            // 优先用触发内容生成的操作者,避免团队成员操作却扣项目创建者。
            $uid = $payerUserId > 0 ? $payerUserId : (int)$project->user_id;
            \app\api\logic\HdLogic::$uid = $uid;
            $tags = json_decode((string)$content->tags, true) ?: [];
            $prompt = GeoPrompts::coverImage(
                (string)$project->brand_name,
                (string)$content->title,
                (string)$project->industry,
                $tags
            );
            $ok = HdLogic::txt2img([
                'prompt' => $prompt,
                'aspect_ratio' => '3:4',   // 小红书/抖音图文以竖版为主
                'img_count' => 1,
            ]);
            if (!$ok) {
                throw new \Exception(HdLogic::getError() ?: '提交文生图失败');
            }
            $taskId = (string)(HdLogic::getReturnData()['result']['task_id'] ?? '');
            if ($taskId === '') {
                throw new \Exception('文生图未返回任务号');
            }
            $content->cover_task_id = $taskId;
            $content->cover_status = 'pending';
            $content->cover_prompt = mb_substr($prompt, 0, 500);
            $content->save();
        } catch (\Throwable $e) {
            $content->cover_status = 'failed';
            $content->save();
            Log::warning('GeoCoverService::submit 失败 content=' . $content->id . ' ' . $e->getMessage());
        }
    }

    /**
     * 查询并回填封面图。前端在文章详情页轮询本方法(经 GeoLogic::contentCover)。
     * @return array ['status'=>pending|success|failed, 'cover_url'=>string]
     */
    public static function fetch(GeoContent $content): array
    {
        $status = (string)$content->cover_status;
        if ($status === 'success' || (string)$content->cover_url !== '') {
            return ['status' => 'success', 'cover_url' => (string)$content->cover_url];
        }
        if ($status !== 'pending' || (string)$content->cover_task_id === '') {
            return ['status' => $status ?: 'none', 'cover_url' => ''];
        }
        try {
            $ok = HdLogic::getTaskStatus(['task_id' => (string)$content->cover_task_id, 'type' => self::TASK_TYPE_TXT2IMG]);
            if (!$ok) {
                return ['status' => 'pending', 'cover_url' => ''];
            }
            // geo 分支 getTaskStatus 的 returnData 是双层包裹:
            // handleResult 返回 ['result'=>X],外层又套一次 => ['result'=>['result'=>X]]
            $raw = HdLogic::getReturnData()['result'] ?? [];
            $result = (is_array($raw) && isset($raw['result']) && is_array($raw['result'])) ? $raw['result'] : $raw;
            $subs = is_array($result) ? ($result['sub_task_results'] ?? []) : [];
            foreach ((array)$subs as $s) {
                $img = (string)($s['image'] ?? '');
                if ($img !== '') {
                    $content->cover_url = $img;
                    $content->cover_status = 'success';
                    $content->save();
                    return ['status' => 'success', 'cover_url' => $img];
                }
            }
            // 3/4 = 生成失败(主系统那边已按失败恢复算力)
            $statuses = array_map(fn($s) => (int)($s['task_status'] ?? 0), (array)$subs);
            if ($statuses && !array_diff($statuses, [3, 4])) {
                $content->cover_status = 'failed';
                $content->save();
                return ['status' => 'failed', 'cover_url' => ''];
            }
        } catch (\Throwable $e) {
            Log::warning('GeoCoverService::fetch content=' . $content->id . ' ' . $e->getMessage());
        }
        return ['status' => 'pending', 'cover_url' => ''];
    }

    /**
     * 是否具备生图条件。
     * 主系统文生图走数据中台,没有 API_KEY 时调用必然失败 —— 直接跳过,
     * 免得每篇文章都白提交一次任务并在日志里刷错误。
     * (env 段名大小写不敏感,统一按 geo 分支既有大写写法,同 GeoService::enabled)
     */
    public static function enabled(): bool
    {
        return trim((string)env('PROJECT_KEY.API_KEY', '')) !== '';
    }
}
