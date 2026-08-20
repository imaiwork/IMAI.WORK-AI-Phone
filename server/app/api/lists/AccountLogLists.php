<?php


namespace app\api\lists;

use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\pay\PayConfig;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\user\UserAccountLog;
use app\common\model\user\UserTokensLog;
use app\common\service\draw\DrawBillingService;


/**
 * 账户流水列表
 * Class AccountLogLists
 * @package app\shopapi\lists
 */
class AccountLogLists extends BaseApiDataLists
{

    /**
     * @notes 搜索条件
     * @return array
     * @author 段誉
     * @date 2023/2/24 14:43
     */
    public function queryWhere()
    {
        // 指定用户
        $where[] = ['user_id', '=', $this->userId];

        // 用户月明细
        if (isset($this->params['type']) && $this->params['type'] == 'um') {
            $where[] = ['change_type', 'in', AccountLogEnum::getUserMoneyChangeType()];
        }

        // 用户月算力明细
        if (isset($this->params['type']) && $this->params['type'] == 'tokens') {
            $where[] = ['change_type', 'in', AccountLogEnum::getUserTokensChangeType()];

            // 来源过滤(仅算力明细有 team_id):team=企业空间消耗, personal=个人消耗
            $source = $this->params['source'] ?? '';
            if ($source === 'team') {
                $where[] = ['team_id', '>', 0];
            } elseif ($source === 'personal') {
                $where[] = ['team_id', '=', 0];
            }
            // 指定企业
            if (!empty($this->params['team_id'])) {
                $where[] = ['team_id', '=', (int)$this->params['team_id']];
            }
        }

        // 变动类型
        if (!empty($action = $this->params['action'])) {
            if ((int)$action === AccountLogEnum::DEC) {
                // 消耗记录：由 lists/count 里 whereOr 补充面试等特殊类型
            } else {
                // 订阅记录：按充值/兑换类型展示，不强制 source_sn
                $where[] = ['action', '=', AccountLogEnum::INC];
                if (($this->params['type'] ?? '') === 'tokens') {
                    $where[] = ['change_type', 'in', [
                        AccountLogEnum::TOKENS_INC_RECHARGE,
                        AccountLogEnum::TOKENS_INC_CARDCODE_GIVE,
                    ]];
                } else {
                    $where[] = ['source_sn', '<>', ''];
                }
            }
        }

        return $where;
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/24 14:43
     */
    public function lists(): array
    {
        // 订阅记录：算力充值流水 + CDK 在线支付订单（微信/虚拟支付）
        if ($this->isSubscribeTokensList()) {
            return $this->subscribeLists();
        }

        $model = $this->params['type'] == 'tokens' ? (new UserTokensLog()) : (new UserAccountLog());
        $lists = $model::where($this->queryWhere());
        if (isset($this->params['action']) && $this->params['action'] == 2) {
            $lists = $lists->whereOr(function ($q) {
                $q->where([
                    ['user_id', '=', $this->userId],
                    ['change_type', '=', AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT],
                ]);
            });
        }

        $lists = $lists->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        $isTokens = ($this->params['type'] ?? '') == 'tokens';
        // 批量取团队名,避免 N+1
        $teamNames = [];
        if ($isTokens) {
            $teamIds = array_values(array_unique(array_filter(array_map(
                fn($r) => (int)($r['team_id'] ?? 0),
                $lists
            ))));
            if (!empty($teamIds)) {
                $teamNames = \app\common\model\team\Team::withTrashed()
                    ->whereIn('id', $teamIds)->column('name', 'id');
            }
        }

        // 历史备注里的 uid/sn → 昵称(账单「变动来源」可读)
        $remarkLabelMap = $isTokens ? $this->buildRemarkUserLabelMap($lists) : [[], []];

        foreach ($lists as &$item) {
            $remark = (string)($item['remark'] ?? '');
            // 历史注册赠送等误把文案写进 source_sn、remark 为空:回退枚举描述
            // 小程序余额明细直接展示 remark,须同步回填
            if ($remark === '' && $isTokens) {
                $fallback = (string)($item['source_sn'] ?? '');
                if ($fallback === '' || is_numeric($fallback)) {
                    $fallback = AccountLogEnum::getChangeTypeDesc((int)($item['change_type'] ?? 0)) ?: '';
                }
                $remark = $fallback;
                $item['remark'] = $remark;
            }
            $item['type_desc'] = $isTokens
                ? $this->humanizeTeamTokensRemark($remark, $remarkLabelMap[0], $remarkLabelMap[1])
                : $remark;
            $symbol = $item['action'] == AccountLogEnum::DEC ? '-' : '+';
            $item['change_amount_desc'] = $symbol . $item['change_amount'];

            if (isset($item['extra'])) {
                $decoded = is_array($item['extra'])
                    ? $item['extra']
                    : (json_decode((string)$item['extra'], true) ?: []);
                $item['extra'] = DrawBillingService::sanitizeTokensLogExtra(
                    is_array($decoded) ? $decoded : []
                );
            }

            // 算力明细:标注来源(团队/个人)与剩余算力口径
            if ($isTokens) {
                $teamId = (int)($item['team_id'] ?? 0);
                // 后台平台调账始终按个人展示,避免历史误挂 team_id 打上「团队」标签造成误会
                $isAdminAdjust = in_array((int)($item['change_type'] ?? 0), [
                    AccountLogEnum::TOKENS_DEC_ADMIN,
                    AccountLogEnum::TOKENS_INC_ADMIN,
                ], true);
                if ($isAdminAdjust) {
                    $teamId = 0;
                }
                $item['team_id'] = $teamId; // 显式回传,前端用 team_id/is_team 打企业标
                $item['is_team'] = $teamId > 0 ? 1 : 0;
                $item['team_name'] = $teamId > 0 ? (string)($teamNames[$teamId] ?? '') : '';
                $item['source_desc'] = $teamId > 0
                    ? ('企业空间' . ($item['team_name'] !== '' ? '·' . $item['team_name'] : '') . '消耗')
                    : '个人消耗';
                // 剩余算力口径:团队消耗时 left_tokens 为「企业钱包+团队长个人算力」的可用余额
                $item['left_tokens_label'] = $teamId > 0 ? '团队剩余算力' : '剩余算力';
            }
        }
        unset($item);

        return $lists;
    }

    /**
     * 从本页备注提取 uid/sn,批量换成展示名
     * @return array{0: array<int,string>, 1: array<string,string>} [uidMap, snMap]
     */
    private function buildRemarkUserLabelMap(array $lists): array
    {
        $uids = [];
        $sns = [];
        foreach ($lists as $row) {
            $remark = (string)($row['remark'] ?? '');
            if ($remark === '') {
                continue;
            }
            if (preg_match_all('/uid:(\d+)/', $remark, $m)) {
                foreach ($m[1] as $id) {
                    $uids[] = (int)$id;
                }
            }
            // 移出退回后跟 SN;或 给用户(SN)
            if (preg_match_all('/(?:退回企业算力[:：]\s*|给用户\()(\d+)/u', $remark, $m2)) {
                foreach ($m2[1] as $sn) {
                    $sns[] = (string)$sn;
                }
            }
        }
        $uids = array_values(array_unique(array_filter($uids)));
        $sns = array_values(array_unique(array_filter($sns)));
        if (!$uids && !$sns) {
            return [[], []];
        }
        $q = \app\common\model\user\User::field('id,nickname,sn');
        if ($uids && $sns) {
            $q->where(function ($w) use ($uids, $sns) {
                $w->whereIn('id', $uids)->whereOr('sn', 'in', $sns);
            });
        } elseif ($uids) {
            $q->whereIn('id', $uids);
        } else {
            $q->whereIn('sn', $sns);
        }
        $uidMap = [];
        $snMap = [];
        foreach ($q->select()->toArray() as $u) {
            $label = trim((string)($u['nickname'] ?? ''));
            if ($label === '') {
                $label = trim((string)($u['sn'] ?? '')) !== '' ? (string)$u['sn'] : ('用户' . $u['id']);
            }
            $uidMap[(int)$u['id']] = $label;
            if (!empty($u['sn'])) {
                $snMap[(string)$u['sn']] = $label;
            }
        }
        return [$uidMap, $snMap];
    }

    /** 把备注里的 uid/纯数字 SN 换成昵称,供账单变动来源展示 */
    private function humanizeTeamTokensRemark(string $remark, array $uidMap, array $snMap): string
    {
        if ($remark === '') {
            return $remark;
        }
        $out = preg_replace_callback('/\(uid:(\d+)\)/', function ($m) use ($uidMap) {
            $id = (int)$m[1];
            $label = $uidMap[$id] ?? ('用户' . $id);
            return '「' . $label . '」';
        }, $remark);
        $out = preg_replace_callback('/(退回企业算力[:：]\s*)(\d+)/u', function ($m) use ($snMap) {
            $sn = (string)$m[2];
            $label = $snMap[$sn] ?? $sn;
            return $m[1] . $label;
        }, $out);
        $out = preg_replace_callback('/给用户\((\d+)\)/', function ($m) use ($snMap) {
            $sn = (string)$m[1];
            $label = $snMap[$sn] ?? $sn;
            return '给「' . $label . '」';
        }, $out);
        return is_string($out) ? $out : $remark;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/24 14:44
     */
    public function count(): int
    {
        if ($this->isSubscribeTokensList()) {
            return $this->subscribeCount();
        }

        $model = $this->params['type'] == 'tokens' ? (new UserTokensLog()) : (new UserAccountLog());
        $model = $model::where($this->queryWhere());
        if (isset($this->params['action']) && $this->params['action'] == 2) {
            $model = $model->whereOr(function ($q) {
                $q->where([
                    ['user_id', '=', $this->userId],
                    ['change_type', '=', AccountLogEnum::TOKENS_DEC_AI_INTERVIEW_CHAT],
                ]);
            });
        }
        return (int)$model->count();
    }

    /**
     * 是否为小程序「订阅记录」：type=tokens 且 action=增加
     */
    private function isSubscribeTokensList(): bool
    {
        return ($this->params['type'] ?? '') === 'tokens'
            && !empty($this->params['action'])
            && (int)$this->params['action'] === AccountLogEnum::INC;
    }

    private function subscribeCount(): int
    {
        $tokenCount = (int)UserTokensLog::where($this->queryWhere())->count();
        $cdkCount = (int)$this->cdkOrderQuery()->count();
        return $tokenCount + $cdkCount;
    }

    private function subscribeLists(): array
    {
        $need = $this->limitOffset + $this->limitLength;
        if ($need <= 0) {
            $need = $this->limitLength;
        }

        $tokenLogs = UserTokensLog::where($this->queryWhere())
            ->order('id', 'desc')
            ->limit($need)
            ->select()
            ->toArray();

        $cdkOrders = $this->cdkOrderQuery()
            ->order('id', 'desc')
            ->limit($need)
            ->select()
            ->toArray();

        $merged = [];
        foreach ($tokenLogs as $item) {
            $merged[] = $this->formatTokenLogItem($item);
        }
        foreach ($cdkOrders as $order) {
            $merged[] = $this->formatCdkOrderItem($order);
        }

        usort($merged, static function (array $a, array $b): int {
            $ta = self::toTimestamp($a['create_time'] ?? 0);
            $tb = self::toTimestamp($b['create_time'] ?? 0);
            if ($ta === $tb) {
                return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            }
            return $tb <=> $ta;
        });

        return array_values(array_slice($merged, $this->limitOffset, $this->limitLength));
    }

    private function cdkOrderQuery()
    {
        return DeviceAuthOrder::where([
            ['user_id', '=', $this->userId],
            ['pay_type', '=', DeviceAuthOrderEnum::PAY_TYPE_ONLINE],
            ['pay_status', '=', DeviceAuthOrderEnum::PAY_STATUS_PAID],
        ]);
    }

    private function formatTokenLogItem(array $item): array
    {
        $item['type_desc'] = $item['remark'] ?? '';
        $symbol = ((int)($item['action'] ?? 0) === AccountLogEnum::DEC) ? '-' : '+';
        $item['change_amount_desc'] = $symbol . ($item['change_amount'] ?? 0);
        // 订阅记录优先展示业务订单号
        if (!empty($item['source_sn'])) {
            $item['sn'] = $item['source_sn'];
        }
        $item['record_type'] = 'tokens';

        if (isset($item['extra'])) {
            $decoded = is_array($item['extra'])
                ? $item['extra']
                : (json_decode((string)$item['extra'], true) ?: []);
            $item['extra'] = DrawBillingService::sanitizeTokensLogExtra(
                is_array($decoded) ? $decoded : []
            );
        }

        if (isset($item['create_time']) && is_numeric($item['create_time'])) {
            $item['create_time'] = date('Y-m-d H:i:s', (int)$item['create_time']);
        }

        // 订阅记录：关联礼包订单支付方式；微信/虚拟支付不展示「剩余」
        $payWay = 0;
        if (!empty($item['source_sn'])) {
            $payWay = (int)GiftPackageOrder::where('sn', $item['source_sn'])->value('pay_way');
        }
        if ($payWay > 0) {
            $item['pay_way'] = self::formatPayWayName($payWay);
        }
        $item['show_left_tokens'] = !self::isOnlineCashPayWay($payWay);

        return $item;
    }

    private function formatCdkOrderItem(array $order): array
    {
        $planName = '';
        if (!empty($order['plan_id'])) {
            $planName = (string)DeviceAuthPlan::where('id', (int)$order['plan_id'])->value('name');
        }
        $bizDesc = DeviceAuthOrderEnum::getBizTypeDesc((int)($order['biz_type'] ?? 0)) ?: '设备CDK';
        $remark = $planName !== '' ? ($bizDesc . '-' . $planName) : $bizDesc;

        $payWay = (int)($order['pay_way'] ?? 0);
        if ($payWay <= 0) {
            $payWay = PayEnum::WECHAT_PAY;
        }
        $payWayName = self::formatPayWayName($payWay);

        $amount = $order['order_amount'] ?? 0;
        $createTime = $order['pay_time'] ?: ($order['create_time'] ?? 0);
        if (is_numeric($createTime)) {
            $createTime = ((int)$createTime > 0) ? date('Y-m-d H:i:s', (int)$createTime) : '';
        }

        return [
            'id'                  => (int)($order['id'] ?? 0),
            'sn'                  => (string)($order['sn'] ?? ''),
            'user_id'             => (int)($order['user_id'] ?? 0),
            'action'              => AccountLogEnum::INC,
            'change_type'         => 0,
            'change_amount'       => $amount,
            'change_amount_desc'  => '¥' . $amount,
            'left_tokens'         => 0,
            'show_left_tokens'    => false,
            'source_sn'           => (string)($order['sn'] ?? ''),
            'remark'              => $remark,
            'type_desc'           => $remark,
            'create_time'         => $createTime,
            'record_type'         => 'device_auth',
            'pay_way'             => $payWayName,
            'extra'               => array_filter([
                '支付方式' => $payWayName,
                '套餐'     => $planName !== '' ? $planName : null,
                '数量'     => (int)($order['quantity'] ?? 1),
            ], static fn($v) => $v !== null && $v !== ''),
        ];
    }

    /** 微信 / 虚拟支付等现金在线支付 */
    private static function isOnlineCashPayWay(int $payWay): bool
    {
        return in_array($payWay, [
            PayEnum::WECHAT_PAY,
            PayEnum::ALI_PAY,
            PayEnum::MNP_VIRTUAL_PAY,
        ], true);
    }

    private static function formatPayWayName(int $payWay): string
    {
        if ($payWay === PayEnum::MNP_VIRTUAL_PAY) {
            return '虚拟支付';
        }
        $name = (string)(PayConfig::where('pay_way', $payWay)->value('name') ?? '');
        if ($name !== '') {
            return $name;
        }
        return (string)(PayEnum::getPayDesc($payWay) ?: '');
    }

    private static function toTimestamp($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            return $ts === false ? 0 : $ts;
        }
        return 0;
    }
}
