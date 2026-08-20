<?php

namespace app\adminapi\logic\deviceauth;

use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\pay\PayConfig;

class DeviceAuthOrderLogic extends BaseLogic
{
    public static function detail(int $id): array
    {
        $order = DeviceAuthOrder::alias('o')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->where('o.id', $id)
            ->field('o.*,u.nickname,u.mobile')
            ->findOrEmpty();
        if ($order->isEmpty()) {
            return [];
        }
        $data = $order->toArray();
        $data['biz_type_desc'] = DeviceAuthOrderEnum::getBizTypeDesc($data['biz_type']);
        $data['pay_type_desc'] = DeviceAuthOrderEnum::getPayTypeDesc($data['pay_type']);
        $data['pay_status_desc'] = DeviceAuthOrderEnum::getPayStatusDesc($data['pay_status']);
        $data['auth_type_desc'] = DeviceAuthCodeEnum::getTypeDesc($data['auth_type']);
        $payWay = (int)($data['pay_way'] ?? 0);
        if (
            $payWay <= 0
            && (int)($data['pay_status'] ?? 0) === DeviceAuthOrderEnum::PAY_STATUS_PAID
            && (int)($data['pay_type'] ?? 0) === DeviceAuthOrderEnum::PAY_TYPE_ONLINE
        ) {
            $payWay = PayEnum::WECHAT_PAY;
        }
        if ($payWay === PayEnum::MNP_VIRTUAL_PAY) {
            $data['pay_way_desc'] = '虚拟支付';
        } elseif ($payWay > 0) {
            $data['pay_way_desc'] = PayConfig::where('pay_way', $payWay)->value('name')
                ?: PayEnum::getPayDesc($payWay);
        } else {
            $data['pay_way_desc'] = '';
        }
        $data['pay_time'] = format_datetime($data['pay_time'] ?? '');
        $data['create_time'] = format_datetime($data['create_time'] ?? '');
        // 设备CDK明细：购买单按订单关联；续费单本身不分配新码。
        // TODO 暂时没有续费选项，后续设备CDK可能来自本地激活(device_code)或中台同步(middle_device_code)，故两者都匹配。
//        if ($data['biz_type'] == DeviceAuthOrderEnum::BIZ_TYPE_RENEW && !empty($data['device_code'])) {
//            $deviceCode = $data['device_code'];
//            $codeQuery = DeviceCdkCode::where(function ($q) use ($deviceCode) {
//                $q->where('device_code', $deviceCode)
//                    ->whereOr('middle_device_code', $deviceCode);
//            });
//        } else {
            $codeQuery = DeviceCdkCode::where('order_id', $id);
//        }
        $data['codes'] = self::formatCodeRows(
            $codeQuery
                ->field('id,code,type,status,device_code,middle_device_code,use_time')
                ->select()
        );
        return $data;
    }

    /**
     * @param iterable<int, DeviceCdkCode>|\think\Collection $codes
     * @return array<int, array<string, mixed>>
     */
    private static function formatCodeRows(iterable $codes): array
    {
        $rows = [];
        foreach ($codes as $item) {
            $row = is_array($item) ? $item : $item->toArray();
            $row['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($row['type']);
            $row['status_desc'] = DeviceAuthCodeEnum::getStatusDesc($row['status']);
            $row['use_time'] = format_datetime($row['use_time'] ?? '');
            if (empty($row['device_code'])) {
                $row['device_code'] = $row['middle_device_code'] ?? '';
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
