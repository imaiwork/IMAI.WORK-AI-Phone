<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 小红书私信回复分配：消息 id 规范化、正文配对、欢迎语占位替换。
 * 禁止无 id 回退好友全量；禁止裸 @ 二次替换。
 */
class XhsPrivateMessageReplyAllocator
{
    /**
     * 转正整数、去掉 <=0、保序去重（第一次出现保留）
     */
    public static function normalizeMsgIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $result = [];
        $seen = [];
        foreach ($ids as $id) {
            if (is_bool($id) || is_array($id) || is_object($id)) {
                continue;
            }
            $intId = (int)$id;
            if ($intId <= 0 || isset($seen[$intId])) {
                continue;
            }
            $seen[$intId] = true;
            $result[] = $intId;
        }
        return $result;
    }

    /**
     * 规范化后仍有 id 才允许按 id 更新状态。禁止无 id 回退好友全量。
     */
    public static function shouldUpdateStatusIds(array $msgIds): bool
    {
        return self::normalizeMsgIds($msgIds) !== [];
    }

    /**
     * 空数组不当显式配对，回退到条数规则（避免逐条 0 命中时 reply_pairs=[] 全 skip）。
     */
    public static function normalizeExplicitPairs(?array $explicitPairs): ?array
    {
        if ($explicitPairs === null || $explicitPairs === []) {
            return null;
        }
        return $explicitPairs;
    }

    /**
     * 按消息 id 分配回复正文。
     * explicitPairs 非 null 且非空时以其为准，只保留 $msgIds 内的键。
     * 否则：条数相等按下标一对一；否则仅第一个 id 写合并全文，其余写空串。
     *
     * @return array<int, string>
     */
    public static function pairReplyContents(array $msgIds, array $replyContents, ?array $explicitPairs): array
    {
        $msgIds = self::normalizeMsgIds($msgIds);
        if ($msgIds === []) {
            return [];
        }

        $explicitPairs = self::normalizeExplicitPairs($explicitPairs);
        if ($explicitPairs !== null) {
            $allowed = array_fill_keys($msgIds, true);
            $pairs = [];
            foreach ($explicitPairs as $id => $content) {
                $id = (int)$id;
                if ($id <= 0 || !isset($allowed[$id])) {
                    continue;
                }
                $pairs[$id] = self::stringifyReply($content);
            }
            return $pairs;
        }

        $replyContents = array_values($replyContents);
        if (count($replyContents) === count($msgIds)) {
            $pairs = [];
            foreach ($msgIds as $index => $id) {
                $pairs[$id] = self::stringifyReply($replyContents[$index] ?? '');
            }
            return $pairs;
        }

        $joined = implode("\n", $replyContents);
        $pairs = [];
        foreach ($msgIds as $index => $id) {
            $pairs[$id] = $index === 0 ? $joined : '';
        }
        return $pairs;
    }

    /**
     * 欢迎语占位：{客户名}/{客户} → 客户名；@客户 → @客户名（左侧非空白则补空格）。
     * 客户名为空时去掉占位，不留下原文。禁止再全量替换裸 @。
     */
    public static function replaceGreetingPlaceholders(string $text, string $customerName): string
    {
        $name = trim($customerName);

        $text = str_replace('{客户名}', $name, $text);
        $text = str_replace('{客户}', $name, $text);

        $atReplacement = $name === '' ? '' : '@' . $name;
        $spacedAtReplacement = $name === '' ? '' : ' @' . $name;
        // 左侧非空白才补空格；行首或已有空白不再加空格
        $text = preg_replace('/(?<=\S)@客户/u', $spacedAtReplacement, $text) ?? $text;
        $text = str_replace('@客户', $atReplacement, $text);

        return $text;
    }

    /**
     * 回复正文转字符串；非标量按空串处理，避免 Array 强转
     */
    private static function stringifyReply(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if (is_int($content) || is_float($content)) {
            return (string)$content;
        }
        if ($content === null) {
            return '';
        }
        return '';
    }
}
