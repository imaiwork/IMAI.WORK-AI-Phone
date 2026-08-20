<?php

namespace app\common\service\geo;

/**
 * GEO 通用小工具:时间戳兼容、入参列表规范化、任务失败文案。
 * 集中处理避免各处 (int)$create_time / (array)$string 踩坑。
 */
class GeoHelper
{
    /**
     * 把 ORM 读出的 create_time 转为 unix 秒。
     * datetime_format=Y-m-d H:i:s 时直接 (int) 会得到年份(如 2026)。
     */
    public static function ts($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_numeric($v)) {
            return (int)$v;
        }
        $t = strtotime((string)$v);
        return $t !== false ? $t : 0;
    }

    /** 公司全称常见地域前缀(省/直辖市/常见城市,推导简称时剥离) */
    private const REGION_PREFIXES = [
        '黑龙江', '内蒙古', '北京', '上海', '天津', '重庆', '广州', '深圳', '杭州', '南京',
        '苏州', '成都', '武汉', '西安', '长沙', '郑州', '青岛', '济南', '合肥', '福州',
        '厦门', '昆明', '南宁', '贵阳', '南昌', '太原', '长春', '沈阳', '大连', '哈尔滨',
        '石家庄', '兰州', '银川', '西宁', '乌鲁木齐', '拉萨', '海口', '三亚', '宁波', '无锡',
        '佛山', '东莞', '珠海', '中山', '惠州', '温州', '嘉兴', '金华', '绍兴', '泉州',
        '河北', '山西', '辽宁', '吉林', '江苏', '浙江', '安徽', '福建', '江西', '山东',
        '河南', '湖北', '湖南', '广东', '海南', '四川', '贵州', '云南', '陕西', '甘肃',
        '青海', '广西', '西藏', '宁夏', '新疆', '中国',
    ];

    /** 公司全称通用后缀(长词在前保证最长匹配,推导简称时逐层剥离) */
    private const NAME_SUFFIXES = [
        '股份有限公司', '有限责任公司', '有限公司', '股份公司', '集团公司',
        '电子商务', '信息科技', '网络科技', '信息技术', '科技发展', '文化传媒', '文化传播',
        '企业管理', '管理咨询', '技术服务', '智能科技', '数字科技', '生物科技', '医疗科技',
        '教育科技', '互联网', '工作室', '实业', '控股', '集团', '科技', '网络', '软件',
        '信息', '传媒', '文化', '咨询', '电商', '智能', '数码', '品牌', '公司',
    ];

    /**
     * 从品牌/公司全称推导简称别名:纯字符串剥离地域前缀与通用后缀,不引入外部知识。
     * AI 不了解的小众品牌按约束不返回"常见别名",但监测命中最需要的恰是简称
     * (AI 回答里通常写「爱脉」而非「爱脉网络科技」);字面推导不属于编造,宁短勿错。
     * @return list<string> 推导不出(纯简称输入/剥离后过短)时为空数组
     */
    public static function deriveBrandAliases(string $name): array
    {
        $core = trim($name);
        if ($core === '') {
            return [];
        }
        foreach (self::REGION_PREFIXES as $p) {
            if (mb_strpos($core, $p) === 0 && mb_strlen($core) - mb_strlen($p) >= 2) {
                $core = mb_substr($core, mb_strlen($p));
                break;
            }
        }
        // 逐层剥后缀(如「网络科技有限公司」需剥两次),每层都保证剩余 >= 2 字
        $stripped = true;
        while ($stripped) {
            $stripped = false;
            foreach (self::NAME_SUFFIXES as $s) {
                $len = mb_strlen($core) - mb_strlen($s);
                if ($len >= 2 && mb_substr($core, -mb_strlen($s)) === $s) {
                    $core = mb_substr($core, 0, $len);
                    $stripped = true;
                    break;
                }
            }
        }
        // 不能用 trim($core, " \t-·、,，"):charlist 按字节匹配,「、」的尾字节 0x80
        // 会撞上「刀」等汉字的尾字节,把合法汉字剥成非法 UTF-8,最终 json_encode
        // 报 Malformed UTF-8 把整个接口炸掉。改用 /u 正则按字符剥边
        $core = (string)preg_replace('/^[\s\-·、,，]+|[\s\-·、,，]+$/u', '', $core);
        if ($core === '' || $core === trim($name) || mb_strlen($core) < 2 || !mb_check_encoding($core, 'UTF-8')) {
            return [];
        }
        return [$core];
    }

    /**
     * 规范化字符串列表入参。
     * 避免 (array)"faq" → ['f','a','q']; 兼容 JSON 数组、逗号分隔、单值。
     * @param mixed $v
     * @return list<string>
     */
    public static function normalizeStringList($v): array
    {
        if ($v === null || $v === '') {
            return [];
        }
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') {
                return [];
            }
            if (($v[0] ?? '') === '[') {
                $decoded = json_decode($v, true);
                if (is_array($decoded)) {
                    return self::normalizeStringList($decoded);
                }
            }
            if (str_contains($v, ',')) {
                return array_values(array_filter(array_map('trim', explode(',', $v)), fn($x) => $x !== ''));
            }
            return [$v];
        }
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            if (is_array($item)) {
                continue;
            }
            $s = trim((string)$item);
            if ($s !== '') {
                $out[] = $s;
            }
        }
        return array_values($out);
    }

    /**
     * 规范化正整型 ID 列表。
     * @param mixed $v
     * @return list<int>
     */
    public static function normalizeIntList($v): array
    {
        return array_values(array_filter(
            array_map('intval', self::normalizeStringList($v)),
            fn($x) => $x > 0
        ));
    }

    /** 从 geo_task 数组提取可读失败原因 */
    public static function taskErrorMessage(array $task, string $fallback = '任务执行失败'): string
    {
        $logs = $task['logs'] ?? [];
        if (is_string($logs)) {
            $logs = json_decode($logs, true) ?: [];
        }
        if (is_array($logs)) {
            for ($i = count($logs) - 1; $i >= 0; $i--) {
                $row = $logs[$i] ?? [];
                if (($row['step'] ?? '') === '错误' && !empty($row['message'])) {
                    return (string)$row['message'];
                }
            }
        }
        return $fallback;
    }
}
