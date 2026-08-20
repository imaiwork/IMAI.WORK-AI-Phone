<?php

namespace app\adminapi\lists\recharge;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\pay\PayConfig;
use app\common\model\recharge\GiftPackage;
use app\common\model\recharge\GiftPackageOrder;
use app\common\service\FileService;

/**
 * 财务汇总订单列表（算力礼包 + CDK在线支付，含虚拟支付）
 */
class GiftPackageOrderLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     */
    public function lists(): array
    {
        // 各取 offset+limit 条再归并，保证跨表全局按时间分页正确
        $need = $this->limitOffset + $this->limitLength;
        if ($need <= 0) {
            $need = $this->limitLength;
        }

        $giftRows = $this->giftQuery()
            ->order('gpo.create_time', 'desc')
            ->order('gpo.id', 'desc')
            ->limit($need)
            ->select()
            ->toArray();

        // 指定礼包 type 时不混入 CDK
        $authRows = [];
        if (!$this->hasGiftTypeFilter()) {
            $authRows = $this->authQuery()
                ->order('o.create_time', 'desc')
                ->order('o.id', 'desc')
                ->limit($need)
                ->select()
                ->toArray();
        }

        $merged = [];
        foreach ($giftRows as $row) {
            $row['order_source'] = 'gift';
            $merged[] = $row;
        }
        foreach ($authRows as $row) {
            $row['order_source'] = 'device_auth';
            $merged[] = $row;
        }

        usort($merged, static function (array $a, array $b): int {
            $ta = self::toTimestamp($a['create_time'] ?? 0);
            $tb = self::toTimestamp($b['create_time'] ?? 0);
            if ($ta === $tb) {
                return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            }
            return $tb <=> $ta;
        });

        $pageRows = array_slice($merged, $this->limitOffset, $this->limitLength);
        foreach ($pageRows as &$item) {
            $item = $this->formatRow($item);
        }
        unset($item);

        return array_values($pageRows);
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     */
    public function count(): int
    {
        $count = (int)$this->giftQuery()->count();
        if (!$this->hasGiftTypeFilter()) {
            $count += (int)$this->authQuery()->count();
        }
        return $count;
    }

    private function hasGiftTypeFilter(): bool
    {
        $type = $this->request->get('type');
        return $type !== null && $type !== '';
    }

    /**
     * 搜索条件由子查询自行处理（两表字段不完全一致）
     */
    public function setSearch(): array
    {
        return [];
    }

    private function giftQuery()
    {
        $query = GiftPackageOrder::alias('gpo')
            ->join('user u', 'u.id = gpo.user_id')
            ->field('gpo.id, gpo.sn, gpo.pay_way, gpo.order_amount, gpo.pay_status, gpo.pay_time, gpo.create_time, gpo.package_id, u.nickname, u.mobile, u.avatar');

        $this->applyCommonFilters($query, 'gpo');

        $type = $this->request->get('type');
        if ($type !== null && $type !== '') {
            $query->where('gpo.type', '=', $type);
        }

        return $query;
    }

    private function authQuery()
    {
        $query = DeviceAuthOrder::alias('o')
            ->join('user u', 'u.id = o.user_id')
            // 与工作台财务汇总一致：仅统计在线支付（含微信/虚拟支付）
            ->where('o.pay_type', '=', DeviceAuthOrderEnum::PAY_TYPE_ONLINE)
            ->field('o.id, o.sn, o.pay_way, o.pay_type, o.order_amount, o.pay_status, o.pay_time, o.create_time, o.plan_id, o.biz_type, o.tokens_amount, u.nickname, u.mobile, u.avatar');

        $this->applyCommonFilters($query, 'o');

        return $query;
    }

    private function applyCommonFilters($query, string $alias): void
    {
        $payStatus = $this->request->get('pay_status');
        if ($payStatus !== null && $payStatus !== '') {
            $query->where($alias . '.pay_status', '=', $payStatus);
        }

        if ($this->request->get('start_time') && $this->request->get('end_time')) {
            $query->whereBetween($alias . '.create_time', [
                strtotime($this->request->get('start_time')),
                strtotime($this->request->get('end_time')),
            ]);
        }

        if ($this->request->get('user')) {
            $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
        }

        if ($this->request->get('user_id') !== null && $this->request->get('user_id') !== '') {
            $query->where($alias . '.user_id', '=', $this->request->get('user_id'));
        }
    }

    private function formatRow(array $item): array
    {
        $payTime = $item['pay_time'] ?? '';
        if ($payTime === '' || $payTime === null || (int)$payTime === 0) {
            $item['pay_time'] = '未支付';
        } elseif (is_numeric($payTime)) {
            $item['pay_time'] = date('Y-m-d H:i:s', (int)$payTime);
        }

        if (isset($item['create_time']) && is_numeric($item['create_time'])) {
            $item['create_time'] = date('Y-m-d H:i:s', (int)$item['create_time']);
        }

        $item['avatar'] = FileService::getFileUrl($item['avatar'] ?? '');
        $payWayName = self::formatPayWayName(
            (int)($item['pay_way'] ?? 0),
            (string)($item['order_source'] ?? 'gift'),
            (int)($item['pay_status'] ?? 0),
            (int)($item['pay_type'] ?? 0)
        );
        $item['pay_way'] = $payWayName;
        $item['pay_way_text'] = $payWayName;

        if (($item['order_source'] ?? '') === 'device_auth') {
            $planName = '';
            if (!empty($item['plan_id'])) {
                $planName = (string)DeviceAuthPlan::where('id', (int)$item['plan_id'])->value('name');
            }
            $bizDesc = DeviceAuthOrderEnum::getBizTypeDesc((int)($item['biz_type'] ?? 0));
            $item['package_name'] = $planName !== '' ? $planName : ($bizDesc ?: '设备CDK');
            $item['package_tokens'] = (int)($item['tokens_amount'] ?? 0);
        } else {
            $package = GiftPackage::where('id', (int)($item['package_id'] ?? 0))
                ->field('name, package_info')
                ->json(['package_info'], true)
                ->findOrEmpty();
            if ($package->isEmpty()) {
                $item['package_name'] = '';
                $item['package_tokens'] = 0;
            } else {
                $item['package_name'] = $package['name'];
                $packageInfo = $package['package_info'] ?? [];
                $item['package_tokens'] = is_array($packageInfo) ? (int)($packageInfo['tokens'] ?? 0) : 0;
            }
        }

        return $item;
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

    /**
     * 支付类型文案：优先枚举（含虚拟支付），再回落到支付配置自定义名称
     * CDK 历史单可能 pay_way=0，已支付的在线单按微信支付展示，与礼包默认一致
     */
    private static function formatPayWayName(
        int $payWay,
        string $orderSource = 'gift',
        int $payStatus = 0,
        int $payType = 0
    ): string {
        if ($payWay <= 0) {
            if (
                $orderSource === 'device_auth'
                && $payStatus === DeviceAuthOrderEnum::PAY_STATUS_PAID
                && ($payType === 0 || $payType === DeviceAuthOrderEnum::PAY_TYPE_ONLINE)
            ) {
                $payWay = PayEnum::WECHAT_PAY;
            } else {
                return '';
            }
        }
        if ($payWay === PayEnum::MNP_VIRTUAL_PAY) {
            return '虚拟支付';
        }
        $name = (string)(PayConfig::where('pay_way', $payWay)->value('name') ?? '');
        if ($name !== '') {
            return $name;
        }
        return (string)PayEnum::getPayDesc($payWay);
    }
}
