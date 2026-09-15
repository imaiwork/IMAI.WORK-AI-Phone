<?php

namespace app\api\logic;

use app\common\enum\user\AccountLogCatEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\ModelConfig;
use app\common\model\user\UserTokensLog;
use app\common\service\draw\DrawBillingService;

/**
 * 小程序算力消耗分类账单
 */
class AccountLogMnpLogic extends ApiLogic
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function lists(int $userId, array $params): array
    {
        $date = self::resolveDate((string)($params['date'] ?? ''));
        $cat = trim((string)($params['cat'] ?? ''));
        if ($cat !== '' && !AccountLogCatEnum::isKnownCat($cat)) {
            $cat = '';
        }

        $rows = self::queryRows($userId, $date, $params);
        $agg = self::aggregate($rows);

        $cats = self::buildCatRows($agg);
        $dayTotal = 0.0;
        $dayCount = 0;
        foreach ($agg as $bucket) {
            $dayTotal += $bucket['total'];
            $dayCount += $bucket['count'];
        }

        $data = [
            'date'  => $date,
            'total' => self::num($dayTotal),
            'count' => $dayCount,
            'cats'  => $cats,
            'items' => [],
        ];

        if ($cat === '') {
            return $data;
        }

        $catBucket = $agg[$cat] ?? ['total' => 0.0, 'count' => 0, 'rows' => []];
        $data['total'] = self::num($catBucket['total']);
        $data['count'] = $catBucket['count'];
        $data['cat'] = $cat;
        $data['cat_name'] = AccountLogCatEnum::nameOf($cat);
        $data['items'] = self::buildItems($catBucket['rows']);

        return $data;
    }

    /** 本地自然日；非法或未来日钳回今天 */
    private static function resolveDate(string $date): string
    {
        $today = date('Y-m-d');
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $today;
        }
        if ($date > $today) {
            return $today;
        }
        return $date;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private static function queryRows(int $userId, string $date, array $params): array
    {
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        $exclude = AccountLogCatEnum::excludeTypes();

        $query = UserTokensLog::where('user_id', $userId)
            ->whereBetween('create_time', [$start, $end]);
        if ($exclude !== []) {
            $query->whereNotIn('change_type', $exclude);
        }

        $source = (string)($params['source'] ?? '');
        if ($source === 'team') {
            $query->where('team_id', '>', 0);
        } elseif ($source === 'personal') {
            $query->where('team_id', '=', 0);
        }
        if (!empty($params['team_id'])) {
            $query->where('team_id', '=', (int)$params['team_id']);
        }

        return $query->field('id,change_type,action,change_amount,extra,create_time,remark')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<string, array{total:float,count:int,rows:list<array<string,mixed>>}>
     */
    private static function aggregate(array $rows): array
    {
        $agg = [];
        foreach ($rows as $row) {
            $key = AccountLogCatEnum::catOf((int)($row['change_type'] ?? 0));
            if (!isset($agg[$key])) {
                $agg[$key] = ['total' => 0.0, 'count' => 0, 'rows' => []];
            }
            $amount = (float)($row['change_amount'] ?? 0);
            if ((int)($row['action'] ?? 0) === AccountLogEnum::INC) {
                $agg[$key]['total'] -= $amount;
            } else {
                $agg[$key]['total'] += $amount;
            }
            $agg[$key]['count']++;
            $agg[$key]['rows'][] = $row;
        }
        return $agg;
    }

    /**
     * @param array<string, array{total:float,count:int,rows:list<array<string,mixed>>}> $agg
     * @return list<array{key:string,name:string,total:float|int,count:int}>
     */
    private static function buildCatRows(array $agg): array
    {
        $rows = [];
        $ord = 0;
        foreach (AccountLogCatEnum::cats() as $meta) {
            $key = $meta['key'];
            $bucket = $agg[$key] ?? ['total' => 0.0, 'count' => 0];
            if ($key === AccountLogCatEnum::OTHER && $bucket['count'] === 0 && (float)$bucket['total'] == 0.0) {
                continue;
            }
            if ($key !== AccountLogCatEnum::OTHER && !in_array($key, AccountLogCatEnum::primaryKeys(), true)) {
                continue;
            }
            $rows[] = [
                'key'   => $key,
                'name'  => $meta['name'],
                'total' => self::num((float)$bucket['total']),
                'count' => (int)$bucket['count'],
                '_ord'  => $ord++,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = ((float)$b['total']) <=> ((float)$a['total']);
            return $cmp !== 0 ? $cmp : ($a['_ord'] <=> $b['_ord']);
        });

        foreach ($rows as &$row) {
            unset($row['_ord']);
        }
        unset($row);

        return array_values($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function buildItems(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $codes = array_values(array_unique(array_map(
            static fn($r) => (int)($r['change_type'] ?? 0),
            $rows
        )));
        $configMap = self::loadModelConfig($codes);

        $groups = [];
        foreach ($rows as $row) {
            $ct = (int)($row['change_type'] ?? 0);
            if (!isset($groups[$ct])) {
                $cfg = $configMap[$ct] ?? ['name' => '', 'scene' => null];
                $name = (string)($cfg['name'] ?? '');
                if ($name === '') {
                    $name = AccountLogEnum::getChangeTypeDesc($ct) ?: (string)($row['remark'] ?? '');
                }
                $groups[$ct] = [
                    'name'         => $name,
                    'scene'        => $cfg['scene'] !== null && $cfg['scene'] !== '' ? (string)$cfg['scene'] : null,
                    'change_type'  => $ct,
                    'total'        => 0.0,
                    'count'        => 0,
                    'records'      => [],
                ];
            }

            $extra = self::decodeExtra($row['extra'] ?? null);
            $amount = (float)($row['change_amount'] ?? 0);
            $action = (int)($row['action'] ?? 0);
            if ($action === AccountLogEnum::INC) {
                $groups[$ct]['total'] -= $amount;
            } else {
                $groups[$ct]['total'] += $amount;
            }
            $groups[$ct]['count']++;
            $symbol = $action === AccountLogEnum::INC ? '+' : '-';
            $groups[$ct]['records'][] = [
                'id'                 => (int)($row['id'] ?? 0),
                'time'               => self::formatTime($row['create_time'] ?? 0),
                'detail'             => self::buildDetail(
                    $extra,
                    (string)($row['remark'] ?? ''),
                    (string)$groups[$ct]['name']
                ),
                'amount'             => self::num($amount),
                'action'             => $action,
                'change_amount_desc' => $symbol . $row['change_amount'],
                'extra'              => $extra,
            ];
        }

        $items = array_values($groups);
        usort($items, static function (array $a, array $b): int {
            $cmp = ((float)$b['total']) <=> ((float)$a['total']);
            return $cmp !== 0 ? $cmp : ((int)$b['change_type'] <=> (int)$a['change_type']);
        });
        foreach ($items as &$item) {
            $item['total'] = self::num((float)$item['total']);
        }
        unset($item);

        return $items;
    }

    /**
     * @param int[] $codes
     * @return array<int, array{name:string,scene:?string}>
     */
    private static function loadModelConfig(array $codes): array
    {
        $codes = array_values(array_filter($codes));
        if ($codes === []) {
            return [];
        }
        $out = [];
        $list = ModelConfig::whereIn('code', $codes)
            ->order('id', 'desc')
            ->field('code,name,scene')
            ->select()
            ->toArray();
        foreach ($list as $row) {
            $code = (int)($row['code'] ?? 0);
            if ($code <= 0 || isset($out[$code])) {
                continue;
            }
            $out[$code] = [
                'name'  => (string)($row['name'] ?? ''),
                'scene' => isset($row['scene']) ? (string)$row['scene'] : null,
            ];
        }
        return $out;
    }

    /**
     * @param mixed $extra
     * @return array<string, mixed>
     */
    private static function decodeExtra($extra): array
    {
        if (is_array($extra)) {
            $decoded = $extra;
        } else {
            $decoded = json_decode((string)$extra, true);
            $decoded = is_array($decoded) ? $decoded : [];
        }
        return DrawBillingService::sanitizeTokensLogExtra($decoded);
    }

    /**
     * 逐笔标题：extra 的「扣费项目」→ 流水备注 → 计费项名。
     * 其余 extra 字段由小程序整体展示，不再拼进标题，避免同一信息出现两次。
     * @param array<string, mixed> $extra
     */
    private static function buildDetail(array $extra, string $remark, string $fallback): string
    {
        $billItem = trim((string)($extra['扣费项目'] ?? ''));
        if ($billItem !== '') {
            return $billItem;
        }
        $remark = trim($remark);
        return $remark !== '' ? $remark : $fallback;
    }

    /**
     * 逐笔时间：完整到秒，同一分钟内多笔退费/补扣才分得清先后
     * @param mixed $value
     */
    private static function formatTime($value): string
    {
        if (is_numeric($value)) {
            $ts = (int)$value;
            return $ts > 0 ? date('Y-m-d H:i:s', $ts) : '';
        }
        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            return $ts === false ? '' : date('Y-m-d H:i:s', $ts);
        }
        return '';
    }

    /** @return float|int */
    private static function num(float $n)
    {
        $rounded = round($n, 2);
        if (abs($rounded - (int)$rounded) < 0.00001) {
            return (int)$rounded;
        }
        return $rounded;
    }
}
