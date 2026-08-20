<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDeviceViral;
use app\common\model\sv\SvDeviceViralAccount;
use app\common\model\user\User;
use app\common\service\ToolsService;
use think\facade\Log;

/**
 * 爆款仿写词库：成功消耗 / 空库补充（人设 hot_words + 任务 keywords）
 */
class ViralKeywordService
{
    /**
     * 仿写成功后消耗词条；词库空则 AI 补库
     */
    public static function consumeOnSuccess(AiPersona $persona, SvDeviceViral $task, string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        self::removeKeyword($persona, $task, $keyword);
        self::refillIfEmpty($persona, $task);
    }

    /**
     * 从人设 hot_words 与当日任务 keywords 幂等移除词条
     */
    public static function removeKeyword(AiPersona $persona, SvDeviceViral $task, string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        $rule = self::resolveRule($persona);
        if ($rule) {
            $hotWords = self::normalizeKeywords($rule->hot_words ?? []);
            $remain = array_values(array_filter($hotWords, static fn($item) => (string)$item !== $keyword));
            if (count($remain) !== count($hotWords)) {
                $rule->hot_words = $remain;
                $rule->update_time = time();
                $rule->save();
            }
        }

        $taskKeywords = self::normalizeKeywords($task->keywords ?? []);
        $taskRemain = array_values(array_filter($taskKeywords, static fn($item) => (string)$item !== $keyword));
        if (count($taskRemain) !== count($taskKeywords)) {
            $task->keywords = $taskRemain;
            $task->save();
        }

        SvDeviceViralAccount::where('viral_id', $task->id)->select()->each(function (SvDeviceViralAccount $account) use ($keyword) {
            $accountKeywords = self::normalizeKeywords($account->keywords ?? []);
            $accountRemain = array_values(array_filter($accountKeywords, static fn($item) => (string)$item !== $keyword));
            if (count($accountRemain) === count($accountKeywords)) {
                return;
            }
            $account->keywords = $accountRemain;
            $account->save();
        });
    }

    /**
     * 人设词库为空时 AI 补库，并刷新任务 keywords
     *
     * @return array<int, string>
     */
    public static function refillIfEmpty(AiPersona $persona, SvDeviceViral $task): array
    {
        $rule = self::resolveRule($persona);
        if (!$rule) {
            return [];
        }

        $hotWords = self::normalizeKeywords($rule->hot_words ?? []);
        if (!empty($hotWords)) {
            return $hotWords;
        }

        try {
            $tokenScene = 'get_hot_words';
            $tokenCode = AccountLogEnum::TOKENS_DEC_COZE_HOT_WORDS;
            $unit = TokenLogService::checkToken((int)$persona->user_id, $tokenScene);
            $response = ToolsService::Coze()->getHotWords([
                'keywords' => $rule->getClueContent($persona),
            ]);
            if ((int)($response['code'] ?? 0) !== 10000) {
                Log::write(
                    'ViralKeywordService refill failed: ' . json_encode($response, JSON_UNESCAPED_UNICODE),
                    'error'
                );
                return [];
            }

            $newKeywords = self::normalizeKeywords($response['data']['content'] ?? []);
            if (empty($newKeywords)) {
                return [];
            }

            $points = $unit;
            if ($points > 0) {
                $isImageText = (int)($task->publish_media_type ?? AiPersona::PUBLISH_MEDIA_TYPE_VIDEO) === AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT;
                $description = $isImageText
                    ? '根据输入内容提取图文热点搜索关键词'
                    : '根据输入内容提取短视频热点搜索关键词';
                $extra = [
                    '生成关键词数' => count($newKeywords),
                    '算力单价' => $unit,
                    '实际消耗算力' => $points,
                    '描述' => $description,
                ];
                $taskId = generate_unique_task_id();
                User::userTokensChange((int)$persona->user_id, $points);
                AccountLogLogic::recordUserTokensLog(true, (int)$persona->user_id, $tokenCode, $points, $taskId, $extra);
            }

            $rule->hot_words = $newKeywords;
            $rule->update_time = time();
            $rule->save();

            $task->keywords = $newKeywords;
            $task->save();

            SvDeviceViralAccount::where('viral_id', $task->id)->update([
                'keywords' => json_encode($newKeywords, JSON_UNESCAPED_UNICODE),
            ]);

            return $newKeywords;
        } catch (\Throwable $e) {
            Log::write('ViralKeywordService refill exception: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * @param mixed $keywords
     * @return array<int, string>
     */
    public static function normalizeKeywords($keywords): array
    {
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            $keywords = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($keywords)) {
            return [];
        }

        $result = [];
        foreach ($keywords as $keyword) {
            if (is_array($keyword)) {
                $keyword = $keyword['keyword'] ?? $keyword['title'] ?? '';
            }
            $keyword = trim((string)$keyword);
            if ($keyword === '') {
                continue;
            }
            $result[] = $keyword;
        }

        return array_values($result);
    }

    /**
     * @return object|null 人设规则模型（含 hot_words）
     */
    private static function resolveRule(AiPersona $persona)
    {
        if ((int)$persona->persona_type === 1) {
            return $persona->individual;
        }
        if ((int)$persona->persona_type === 2) {
            return $persona->enterprise;
        }
        if ((int)$persona->persona_type === 3) {
            return $persona->local;
        }
        return null;
    }
}
