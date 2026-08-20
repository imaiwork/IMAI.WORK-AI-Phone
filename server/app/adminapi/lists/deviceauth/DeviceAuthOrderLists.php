<?php

namespace app\adminapi\lists\deviceauth;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\pay\PayConfig;

class DeviceAuthOrderLists extends BaseAdminDataLists implements ListsExcelInterface
{
    public function setSearch(): array
    {
        $where = [];
        if (isset($this->params['pay_type']) && $this->params['pay_type'] !== '') {
            $where[] = ['o.pay_type', '=', $this->params['pay_type']];
        }
        if (isset($this->params['pay_status']) && $this->params['pay_status'] !== '') {
            $where[] = ['o.pay_status', '=', $this->params['pay_status']];
        }
        if (isset($this->params['biz_type']) && $this->params['biz_type'] !== '') {
            $where[] = ['o.biz_type', '=', $this->params['biz_type']];
        }
        if (isset($this->params['sn']) && $this->params['sn'] !== '') {
            $where[] = ['o.sn', 'like', '%' . $this->params['sn'] . '%'];
        }
        if (isset($this->params['user_keyword']) && $this->params['user_keyword'] !== '') {
            $where[] = ['u.nickname|u.mobile', 'like', '%' . $this->params['user_keyword'] . '%'];
        }
        if (isset($this->params['start_time']) && $this->params['start_time']) {
            $where[] = ['o.create_time', '>=', strtotime($this->params['start_time'])];
        }
        if (isset($this->params['end_time']) && $this->params['end_time']) {
            $where[] = ['o.create_time', '<=', strtotime($this->params['end_time'])];
        }
        return $where;
    }

    public function lists(): array
    {
        $lists = DeviceAuthOrder::alias('o')
            ->join('user u', 'u.id = o.user_id')
            ->where($this->setSearch())
            ->field('o.id,o.sn,o.user_id,o.biz_type,o.auth_type,o.quantity,o.order_amount,o.tokens_amount,o.pay_type,o.pay_way,o.pay_status,o.pay_time,o.create_time,o.device_code,u.nickname,u.mobile')
            ->limit($this->limitOffset, $this->limitLength)
            ->order('o.id desc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['biz_type_desc'] = DeviceAuthOrderEnum::getBizTypeDesc($item['biz_type']);
            $item['pay_type_desc'] = DeviceAuthOrderEnum::getPayTypeDesc($item['pay_type']);
            $item['pay_status_desc'] = DeviceAuthOrderEnum::getPayStatusDesc($item['pay_status']);
            $item['auth_type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['auth_type']);
            $item['pay_way_desc'] = self::formatPayWayName(
                (int)($item['pay_way'] ?? 0),
                (int)($item['pay_status'] ?? 0),
                (int)($item['pay_type'] ?? 0)
            );
            $item['pay_time'] = format_datetime($item['pay_time'] ?? '');
            $item['create_time'] = format_datetime($item['create_time'] ?? '');
        }
        unset($item);
        return $lists;
    }

    private static function formatPayWayName(int $payWay, int $payStatus = 0, int $payType = 0): string
    {
        if ($payWay <= 0) {
            // 历史在线已支付单未写 pay_way 时，与礼包默认一致展示微信支付
            if (
                $payStatus === DeviceAuthOrderEnum::PAY_STATUS_PAID
                && $payType === DeviceAuthOrderEnum::PAY_TYPE_ONLINE
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

    public function count(): int
    {
        return DeviceAuthOrder::alias('o')
            ->join('user u', 'u.id = o.user_id')
            ->where($this->setSearch())
            ->count();
    }

    public function setExcelFields(): array
    {
        return [
            'sn'              => '订单编号',
            'nickname'        => '用户',
            'mobile'          => '手机号',
            'biz_type_desc'   => '业务类型',
            'auth_type_desc'  => '授权类型',
            'quantity'        => '数量',
            'order_amount'    => '订单金额(元)',
            'tokens_amount'   => '订单算力',
            'pay_type_desc'   => '支付类型',
            'pay_way_desc'    => '支付方式',
            'pay_status_desc' => '支付状态',
            'pay_time'        => '支付时间',
            'create_time'     => '下单时间',
        ];
    }

    public function setFileName(): string
    {
        return '设备CDK购买记录';
    }
}
