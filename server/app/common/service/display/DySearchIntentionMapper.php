<?php

declare(strict_types=1);

namespace app\common\service\display;

/**
 * 意向客户里抖音账号搜寻的纯映射：拆号、公域口径、统计 pill、四段跟进文案。
 * 不依赖 ThinkPHP，便于 tests/common/display 直接跑。
 */
class DySearchIntentionMapper
{
    public const SOURCE_KEY = 'dy_search';
    public const SOURCE_NAME = '抖音账号搜寻获客';
    public const SOURCE_PILL_NAME = '抖音账号搜寻';
    public const ADD_CHANNEL = 4;
    public const LIST_RESULTS = [1];

    public const WECHAT_PATTERN = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
    public const PHONE_PATTERN = '/^1[3-9]\d{9}$/';
    public const QQ_PATTERN = '/^[1-9]\d{4,10}$/';

    public const CONTACT_RAW_LIMIT = 80;

    /**
     * @var array<int, string>
     */
    private const ADD_STATUS_DESC = [
        0 => '无效线索，添加好友失败',
        1 => '已添加好友',
        2 => '已提交添加好友申请，等待通过',
        3 => '当前账号存在安全风险，暂停添加',
        4 => '待执行添加好友',
        5 => '冷却中，等待后可继续添加',
        6 => '微信号校验中',
    ];

    public static function isListResult(int $result): bool
    {
        return in_array($result, self::LIST_RESULTS, true);
    }

    public static function mapContactFrom(string $scanSource, string $dyId = '', string $contact = ''): string
    {
        $parts = [];
        foreach (explode(',', strtolower($scanSource)) as $part) {
            $part = trim($part);
            if (in_array($part, ['bio', 'dyid', 'cover'], true)) {
                $parts[] = $part;
            }
        }
        $contact = trim($contact);
        $dyId = trim($dyId);
        if ($contact !== '' && $dyId !== '' && strcasecmp($contact, $dyId) === 0 && in_array('dyid', $parts, true)) {
            return 'dyid';
        }
        foreach (['bio', 'dyid', 'cover'] as $key) {
            if (in_array($key, $parts, true)) {
                return $key;
            }
        }
        return '';
    }

    /**
     * 已入库客资 reg_content 按逗号拆号，空段丢掉。
     */
    public static function countRegContentTokens(string $regContent): int
    {
        $count = 0;
        foreach (explode(',', $regContent) as $item) {
            if (trim($item) !== '') {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return array{phone: string, wechat: string, qq: string, contact: string, contact_type: string}
     */
    public static function splitRegContent(string $regContent): array
    {
        $phone = '';
        $wechat = '';
        $qq = '';
        foreach (explode(',', $regContent) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            if ($phone === '' && preg_match(self::PHONE_PATTERN, $item) === 1) {
                $phone = $item;
                continue;
            }
            if ($wechat === '' && preg_match(self::WECHAT_PATTERN, $item) === 1) {
                $wechat = $item;
                continue;
            }
            if ($qq === '' && preg_match(self::QQ_PATTERN, $item) === 1) {
                $qq = $item;
            }
        }

        if ($phone !== '') {
            return ['phone' => $phone, 'wechat' => $wechat, 'qq' => $qq, 'contact' => $phone, 'contact_type' => 'phone'];
        }
        if ($wechat !== '') {
            return ['phone' => $phone, 'wechat' => $wechat, 'qq' => $qq, 'contact' => $wechat, 'contact_type' => 'wechat'];
        }
        if ($qq !== '') {
            return ['phone' => $phone, 'wechat' => $wechat, 'qq' => $qq, 'contact' => $qq, 'contact_type' => 'qq'];
        }

        return ['phone' => '', 'wechat' => '', 'qq' => '', 'contact' => '', 'contact_type' => ''];
    }

    public static function resolveDomain(bool $addSuccess): string
    {
        return $addSuccess ? 'private' : 'public';
    }

    /**
     * @param array{phone?:string,wechat?:string,qq?:string,contact?:string} $split
     * @param list<string> $successContacts
     */
    public static function isPrivateByContacts(array $split, array $successContacts): bool
    {
        if ($successContacts === []) {
            return false;
        }
        $set = [];
        foreach ($successContacts as $item) {
            $item = strtolower(trim((string)$item));
            if ($item !== '') {
                $set[$item] = true;
            }
        }
        foreach ([$split['phone'] ?? '', $split['wechat'] ?? '', $split['qq'] ?? '', $split['contact'] ?? ''] as $token) {
            $token = strtolower(trim((string)$token));
            if ($token !== '' && isset($set[$token])) {
                return true;
            }
        }
        return false;
    }

    public static function contactRaw(string $ocrText, bool $hasContact, int $limit = self::CONTACT_RAW_LIMIT): string
    {
        if (!$hasContact) {
            return '';
        }
        $ocrText = trim($ocrText);
        if ($ocrText === '') {
            return '';
        }
        if (mb_strlen($ocrText) > $limit) {
            return mb_substr($ocrText, 0, $limit) . '…';
        }
        return $ocrText;
    }

    public static function coverChecked(string $scanSource, string $ocrText): bool
    {
        return str_contains(strtolower($scanSource), 'cover') || trim($ocrText) !== '';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function dedupeByDyUid(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $uid = trim((string)($row['dy_uid'] ?? ''));
            $key = $uid !== '' ? $uid : ('id:' . (string)($row['id'] ?? uniqid('', true)));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{key: string, name: string, count: int, contact_count: int, unit: string}
     */
    public static function summarize(array $items): array
    {
        $contactCount = 0;
        foreach ($items as $item) {
            if (trim((string)($item['wechat_no'] ?? $item['contact'] ?? '')) !== '') {
                $contactCount++;
            }
        }

        return [
            'key' => self::SOURCE_KEY,
            'name' => self::SOURCE_PILL_NAME,
            'count' => count($items),
            'contact_count' => $contactCount,
            'unit' => '人',
        ];
    }

    public static function sourceText(string $keyword = ''): string
    {
        $base = '通过【' . self::SOURCE_NAME . '】流入';
        $keyword = trim($keyword);
        return $keyword === '' ? $base : $base . '，线索词「' . $keyword . '」';
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $wechatRecord
     * @return list<array<string, mixed>>
     */
    public static function buildFollowRecords(array $customer, array $wechatRecord, int $replyCount = 0, string $replyTime = ''): array
    {
        $keyword = trim((string)($customer['exec_keyword'] ?? ''));
        $contact = trim((string)($customer['wechat_no'] ?? ''));
        $hasContact = $contact !== '';
        $hasRecord = $wechatRecord !== [];
        $addStatus = $hasRecord ? (int)($wechatRecord['status'] ?? 0) : 0;
        $addSuccess = $hasRecord && $addStatus === 1;
        $inflowTime = trim((string)($customer['create_time'] ?? ''));
        $keywordText = $keyword !== '' ? $keyword : '—';

        $publicDesc = $hasContact
            ? '已经拿到联系方式，不需要先在平台私聊'
            : '没识别到联系方式，简介 / 抖音号 / 置顶封面都没有找到';

        if (!$hasContact) {
            $addDesc = '等识别到联系方式之后再加';
            $addDone = false;
            $addTime = '';
        } elseif (!$hasRecord) {
            $addDesc = '已识别、等待添加';
            $addDone = false;
            $addTime = '';
        } else {
            $addDesc = self::addStatusDesc($addStatus);
            if ($addDesc === '') {
                $addDesc = trim((string)($wechatRecord['result'] ?? '')) ?: '已提交添加好友申请';
            }
            $addDone = true;
            $addTime = self::firstTime($wechatRecord['create_time'] ?? '', $wechatRecord['update_time'] ?? '');
        }

        if ($addSuccess) {
            $privateDesc = $replyCount > 0
                ? '微信私域已回复 ' . $replyCount . ' 条'
                : '已加上微信好友';
            $privateTime = $replyCount > 0 ? $replyTime : $addTime;
        } else {
            $privateDesc = '加上好友之后才会有私域跟进';
            $privateTime = '';
        }

        return [
            [
                'key' => 'inflow',
                'title' => '搜寻入库',
                'status' => 'done',
                'time' => $inflowTime,
                'desc' => '通过【' . self::SOURCE_NAME . '】搜到，线索词「' . $keywordText . '」',
                'content' => [],
                'extra' => (object)[],
            ],
            [
                'key' => 'public_chat',
                'title' => '公域聊天',
                'status' => $hasContact ? 'done' : 'pending',
                'time' => '',
                'desc' => $publicDesc,
                'content' => [],
                'extra' => [
                    'message_count' => 0,
                    'reply_count' => 0,
                ],
            ],
            [
                'key' => 'wechat_add',
                'title' => '识别微信加好友',
                'status' => $addDone ? 'done' : 'pending',
                'time' => $addTime,
                'desc' => $addDesc,
                'content' => [],
                'extra' => [
                    'wechat_no' => trim((string)($wechatRecord['reg_wechat'] ?? '')) ?: $contact,
                    'apply_status' => $addStatus,
                    'apply_status_desc' => $hasRecord ? self::addStatusDesc($addStatus) : '',
                ],
            ],
            [
                'key' => 'private_chat',
                'title' => '私域聊天',
                'status' => $addSuccess ? 'done' : 'pending',
                'time' => $privateTime,
                'desc' => $privateDesc,
                'content' => [],
                'extra' => [
                    'wechat_reply_count' => $replyCount,
                ],
            ],
        ];
    }

    public static function addStatusDesc(int $status): string
    {
        return self::ADD_STATUS_DESC[$status] ?? '';
    }

    public static function isDySearchSource(string $sourceKey): bool
    {
        return $sourceKey === self::SOURCE_KEY;
    }

    private static function firstTime(mixed $first, mixed $second = ''): string
    {
        foreach ([$first, $second] as $value) {
            $value = trim((string)$value);
            if ($value !== '' && strtolower($value) !== 'null') {
                if (is_numeric($value) && (int)$value > 0) {
                    return date('Y-m-d H:i:s', (int)$value);
                }
                return $value;
            }
        }
        return '';
    }
}
