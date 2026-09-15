<?php

declare(strict_types=1);

namespace app\common\service\sv;

/**
 * 从简介 / 抖音号 / 封面 OCR 文本提取联系方式。
 * 正则规则复制自 TaskRecordSaveHandler，另加默认抖音号排除与 QQ 提示词门槛。
 */
class DyContactExtractService
{
    /** @var array<string, string> */
    private array $provinces = [
        '北京' => 'Beijing',
        '上海' => 'Shanghai',
        '天津' => 'Tianjin',
        '重庆' => 'Chongqing',
        '河北' => 'Hebei',
        '山西' => 'Shanxi',
        '辽宁' => 'Liaoning',
        '吉林' => 'Jilin',
        '黑龙江' => 'Heilongjiang',
        '江苏' => 'Jiangsu',
        '浙江' => 'Zhejiang',
        '安徽' => 'Anhui',
        '福建' => 'Fujian',
        '江西' => 'Jiangxi',
        '山东' => 'Shandong',
        '河南' => 'Henan',
        '湖北' => 'Hubei',
        '湖南' => 'Hunan',
        '广东' => 'Guangdong',
        '海南' => 'Hainan',
        '四川' => 'Sichuan',
        '贵州' => 'Guizhou',
        '云南' => 'Yunnan',
        '陕西' => 'Shaanxi',
        '甘肃' => 'Gansu',
        '青海' => 'Qinghai',
        '台湾' => 'Taiwan',
        '内蒙古' => 'Neimenggu',
        '广西' => 'Guangxi',
        '西藏' => 'Xizang',
        '宁夏' => 'Ningxia',
        '新疆' => 'Xinjiang',
        '香港' => 'Xianggang',
        '澳门' => 'Aomen',
    ];

    /** @var list<string> */
    private array $checkArray = [
        "加vx",
        "加VX",
        "加v",
        "加V",
        "加wx",
        "加WX",
        "+wx",
        "+WX",
        "+vx",
        "+VX",
        "vx:",
        "VX:",
        "vx：",
        "VX：",
        "vx",
        "VX",
        "Vx",
        "+v",
        "+V",
        "V:",
        "V：",
        "v:",
        "v：",
        "+",
        ", ",
        " ",
        "-",
        "*",
        "❤",
    ];

    private string $wechatPattern = '/[a-zA-Z][a-zA-Z0-9_-]{5,19}/';
    private string $phonePattern = '/1[3-9]\d{9}/';

    /** @var list<string> */
    private array $blacklist = [
        'wechat',
    ];

    /** @var list<string> */
    private array $serviceNumberBlacklist = [
        '10000',
        '10086',
        '10010',
        '10011',
        '10001',
    ];

    /**
     * @param array{contact_phone?:bool,contact_wechat?:bool,contact_qq?:bool} $opts
     * @return array{phones: list<string>, wechats: list<string>, qqs: list<string>, raw: list<string>}
     */
    public static function extract(string $text, array $opts = []): array
    {
        $self = new self();
        $wantPhone = (bool)($opts['contact_phone'] ?? true);
        $wantWechat = (bool)($opts['contact_wechat'] ?? true);
        $wantQq = (bool)($opts['contact_qq'] ?? false);

        $content = str_replace(array_values($self->provinces), '', $text);

        $phones = $wantPhone ? $self->extractPhoneNumbers($content) : [];
        $wechats = $wantWechat ? $self->extractWechatNumbers($content) : [];
        $qqs = $wantQq ? $self->extractQQNumbers($content, $phones) : [];

        return [
            'phones' => $phones,
            'wechats' => $wechats,
            'qqs' => $qqs,
            'raw' => $self->uniqueContacts(array_merge($phones, $wechats, $qqs)),
        ];
    }

    /**
     * @return list<string>
     */
    private function extractPhoneNumbers(string $text): array
    {
        $separator = '[加\s\-\+_=＝—–~～·.,，。:：;；、\/\\\\()\[\]{}<>《》【】（）"\'`^*&#!?？❤️❤♥️♥💕💖💗💓💞💘💝\x{FE0F}]*';
        $digit = '[0-9０-９Il\|Oo]';
        $prefix = '(?:[+＋]?(?:v|V|vx|VX|wx|WX|dh|DH|tel|TEL|phone|PHONE)|手机|电话|联系方式)';
        $pattern = '/(?<![A-Za-z0-9_])(?:' . $prefix . $separator . ')?[1１Il\|](?:' . $separator . $digit . '){10}(?![A-Za-z0-9_])/u';

        preg_match_all($pattern, $text, $matches);

        $phones = [];
        foreach ($matches[0] as $match) {
            $normalized = $this->normalizePhoneCandidate((string)$match);
            if (preg_match('/^1[3-9]\d{9}$/', $normalized) === 1) {
                $phones[] = $normalized;
            }
        }

        $cleanText = str_replace($this->checkArray, '', $text);
        $cleanText = str_replace([' ', '-', '—', '–'], '', $cleanText);
        preg_match_all($this->phonePattern, $cleanText, $cleanMatches);
        foreach ($cleanMatches[0] as $match) {
            $phones[] = $match;
        }

        return $this->uniqueContacts($phones);
    }

    /**
     * @return list<string>
     */
    private function extractWechatNumbers(string $text): array
    {
        preg_match_all($this->wechatPattern, $text, $matches);

        $wechatNos = [];
        foreach ($matches[0] as $match) {
            $match = (string)$match;
            if (
                $this->isBlacklistedContact($match)
                || $this->containsPhoneNumber($match)
                || DyDefaultIdFilter::isDefaultId($match)
            ) {
                continue;
            }
            $wechatNos[] = $match;
        }

        return $this->uniqueContacts($wechatNos);
    }

    /**
     * @param list<string> $phones
     * @return list<string>
     */
    private function extractQQNumbers(string $text, array $phones = []): array
    {
        if (!$this->hasQqHint($text)) {
            return [];
        }

        preg_match_all('/(?<!\d)([1-9]\d{4,10})(?!\d)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        $qqNumbers = [];
        foreach ($matches[1] as $match) {
            [$qq, $offset] = $match;
            $qq = (string)$qq;
            if (
                $this->isPhoneNumber($qq)
                || $this->isPartOfPhoneNumber($qq, $phones)
                || $this->isLikelyStatisticNumber($text, (int)$offset, strlen($qq))
            ) {
                continue;
            }
            $qqNumbers[] = $qq;
        }

        return $this->uniqueContacts($qqNumbers);
    }

    private function hasQqHint(string $text): bool
    {
        return preg_match('/[Qq扣]/u', $text) === 1;
    }

    private function normalizePhoneCandidate(string $candidate): string
    {
        $candidate = strtr($candidate, [
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'I' => '1',
            'l' => '1',
            '|' => '1',
            'O' => '0',
            'o' => '0',
        ]);

        return preg_replace('/\D/', '', $candidate) ?: '';
    }

    private function isPhoneNumber(string $content): bool
    {
        return preg_match($this->phonePattern, $content) === 1;
    }

    private function containsPhoneNumber(string $content): bool
    {
        return preg_match($this->phonePattern, $content) === 1;
    }

    /**
     * @param list<string> $phones
     */
    private function isPartOfPhoneNumber(string $content, array $phones): bool
    {
        foreach ($phones as $phone) {
            if (strpos($phone, $content) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isBlacklistedContact(string $content): bool
    {
        return in_array(strtolower($content), $this->blacklist, true);
    }

    private function isServiceNumber(string $contact): bool
    {
        return in_array($contact, $this->serviceNumberBlacklist, true);
    }

    private function isLikelyStatisticNumber(string $text, int $offset, int $length): bool
    {
        $before = mb_substr(substr($text, 0, $offset), -8);
        $after = mb_substr(substr($text, $offset + $length), 0, 8);

        if (preg_match('/[第约近超逾满成就培育服务客]?$/u', $before) && preg_match('/^\s*(?:[+＋]|(家|个|多|条|年|岁|后|点|时|分|原创|内容|粉丝|品牌|公司|上市|直播))/u', $after)) {
            return true;
        }

        return preg_match('/(原创内容|上市公司|知名品牌|昨天直播|每天|条原创|服务客户)$/u', $before . $after) === 1;
    }

    /**
     * @param list<string> $contacts
     * @return list<string>
     */
    private function uniqueContacts(array $contacts): array
    {
        $result = [];
        foreach ($contacts as $contact) {
            $contact = trim((string)$contact);
            if ($contact === '' || isset($result[$contact]) || $this->isServiceNumber($contact)) {
                continue;
            }
            $result[$contact] = $contact;
        }

        return array_values($result);
    }
}
