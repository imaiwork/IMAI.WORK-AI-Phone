<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\model\sv\SvPrivateMessage;

/**
 * 小红书私信入站写入幂等：按 client_msg_id 强去重，按内容+时间弱去重。
 * 回复正文是否可写只看 reply_content 是否为空，不看 is_reply。
 */
class XhsPrivateMessageWriteGuard
{
    /** extra 中客户端消息 id 的键名 */
    public const EXTRA_KEY_CLIENT_MSG_ID = 'client_msg_id';

    /** 按 client_msg_id 查找时的时间窗口（秒） */
    private const CLIENT_MSG_ID_LOOKBACK = 86400;

    /** 按内容+时间弱去重的时间窗口（秒） */
    private const CONTENT_TIMER_LOOKBACK = 600;

    /**
     * extra 可能是 JSON 字符串或数组，失败返回空数组
     */
    public static function parseExtra(mixed $extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }
        if (is_string($extra) && $extra !== '') {
            $decoded = json_decode($extra, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * 把 client_msg_id 合并进 extra，返回可入库结构（数组；需 JSON 时由调用方编码）
     */
    public static function mergeClientMsgId(mixed $extra, string $clientMsgId): array|string
    {
        $parsed = self::parseExtra($extra);
        $clientMsgId = self::normalizeClientMsgId($clientMsgId);
        if ($clientMsgId === '') {
            return $parsed;
        }
        $parsed[self::EXTRA_KEY_CLIENT_MSG_ID] = $clientMsgId;
        return $parsed;
    }

    /**
     * 规范化客户端消息 id：trim，空则返回空串
     */
    public static function normalizeClientMsgId(mixed $id): string
    {
        if (is_bool($id) || is_array($id) || is_object($id)) {
            return '';
        }
        if ($id === null) {
            return '';
        }
        return trim((string)$id);
    }

    /**
     * 入站强去重键：原始 messageId + 正文指纹。
     * RPA 的 messageId 可能只是短序号（如 4），不能单独当唯一键。
     */
    public static function composeClientMsgId(mixed $messageId, mixed $replyContent): string
    {
        $id = self::normalizeClientMsgId($messageId);
        if ($id === '') {
            return '';
        }
        $fingerprint = self::contentFingerprint($replyContent);
        if ($fingerprint === '') {
            return $id;
        }
        return $id . '|' . $fingerprint;
    }

    /**
     * 同会话已有入站 id（device_code+account+friend_id+type）。
     * 近 24h 再按 extra.client_msg_id 过滤，禁止无条件全表扫。
     */
    public static function findInboundIdsByClientMsgId(
        string $deviceCode,
        string $account,
        string $friendId,
        int $type,
        string $clientMsgId
    ): array {
        $clientMsgId = self::normalizeClientMsgId($clientMsgId);
        if ($clientMsgId === '') {
            return [];
        }

        $rows = SvPrivateMessage::where('device_code', $deviceCode)
            ->where('account', $account)
            ->where('friend_id', $friendId)
            ->where('type', $type)
            ->where('create_time', '>=', time() - self::CLIENT_MSG_ID_LOOKBACK)
            ->field(['id', 'extra'])
            ->select();

        $ids = [];
        foreach ($rows as $row) {
            $extra = self::parseExtra($row['extra'] ?? $row->extra ?? null);
            $existId = self::normalizeClientMsgId($extra[self::EXTRA_KEY_CLIENT_MSG_ID] ?? '');
            if ($existId === $clientMsgId) {
                $ids[] = (int)$row['id'];
            }
        }
        return $ids;
    }

    /**
     * 弱去重：同会话近 10 分钟、相同 message_content + message_timer
     */
    public static function findInboundIdsByContentTimer(
        string $deviceCode,
        string $account,
        string $friendId,
        int $type,
        string $content,
        string $timer
    ): array {
        if ($content === '' || $timer === '') {
            return [];
        }

        $ids = SvPrivateMessage::where('device_code', $deviceCode)
            ->where('account', $account)
            ->where('friend_id', $friendId)
            ->where('type', $type)
            ->where('message_content', $content)
            ->where('message_timer', $timer)
            ->where('create_time', '>=', time() - self::CONTENT_TIMER_LOOKBACK)
            ->column('id');

        if (!is_array($ids)) {
            return [];
        }
        return array_values(array_map('intval', $ids));
    }

    /**
     * 仅当当前回复正文为空时才允许写入。禁止依据 is_reply 判断。
     */
    public static function shouldWriteReplyContent(string $currentReply): bool
    {
        return trim($currentReply) === '';
    }

    /**
     * 正文指纹：数组按出现顺序拼接后取 sha1 前 16 位。空正文返回空串。
     */
    private static function contentFingerprint(mixed $content): string
    {
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $item) {
                if (is_bool($item) || is_array($item) || is_object($item)) {
                    continue;
                }
                $text = trim((string)$item);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
            $joined = implode("\n", $parts);
        } elseif (is_bool($content) || is_object($content)) {
            $joined = '';
        } else {
            $joined = trim((string)$content);
        }

        if ($joined === '') {
            return '';
        }
        return substr(sha1($joined), 0, 16);
    }
}
