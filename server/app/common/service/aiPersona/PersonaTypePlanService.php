<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersonaSynthesisConfig;

/**
 * 「视频剪辑」分类型出片计划。
 *
 * 只有无手机模式按类型条数出片；手机模式的条数仍由内容发布时段数决定，
 * 类型按 generation_types 顺序轮转，不经过本服务。
 *
 * 条数来源优先级：type_config 显式条数 > daily_video_count 按启用类型均分 > 兜底 1 条。
 */
class PersonaTypePlanService
{
    /**
     * 无手机模式当日出片总量。
     * type_config 三类条数之和；没配过时回落 daily_video_count，再空回落 1 条
     * （不能回落到发布时段，否则又绕回"要配发布时段才出片"）。
     */
    public static function resolveCloudTargetCount($config): int
    {
        $counts = AiPersonaSynthesisConfig::resolveCloudTypeCounts($config);
        $total = array_sum($counts);
        if ($total > 0) {
            return min($total, AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_MAX);
        }
        $legacy = AiPersonaSynthesisConfig::normalizeDailyVideoCount(
            AiPersonaSynthesisConfig::configField($config, 'daily_video_count', 0)
        );
        if ($legacy > 0) {
            return $legacy;
        }
        return AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_CLOUD_DEFAULT;
    }

    /**
     * 无手机模式的当日建单类型序列（长度 = 出片总量，类型交错铺满）。
     *
     * @return array<int, int>
     */
    public static function buildCloudPlan($config): array
    {
        $counts = AiPersonaSynthesisConfig::resolveCloudTypeCounts($config);
        $order = AiPersonaSynthesisConfig::resolveGenerationOrder($config);
        $plan = AiPersonaSynthesisConfig::buildCloudTypePlan($counts, $order);
        $target = self::resolveCloudTargetCount($config);
        if ($plan !== []) {
            return array_slice($plan, 0, $target);
        }
        // 配了又全是 0 条（或老数据 daily_video_count=0）时按兜底条数出首类型，
        // 保证云端人设不会因为一次"全部置 0"就永久不出片
        $fallbackType = $order[0] ?? AiPersonaSynthesisConfig::GENERATION_TYPE_DIGITAL_HUMAN;
        return array_fill(0, $target, $fallbackType);
    }

    /**
     * 从当日计划里取「第 $offset 条之后还要建几条」的类型序列。
     * 无手机模式每条任务占一个时段，offset 用已建任务数即可，不必再看最后一条任务的类型。
     *
     * @return array<int, int>
     */
    public static function sliceCloudPlan($config, int $offset, int $count): array
    {
        if ($count <= 0) {
            return [];
        }
        $plan = self::buildCloudPlan($config);
        return array_slice($plan, max(0, $offset), $count);
    }

    /** 无手机模式单类型条数（无文案模式只支持 type=3，靠它决定建几条） */
    public static function resolveCloudTypeCount($config, int $type): int
    {
        return AiPersonaSynthesisConfig::resolveCloudTypeCount($config, $type);
    }
}
