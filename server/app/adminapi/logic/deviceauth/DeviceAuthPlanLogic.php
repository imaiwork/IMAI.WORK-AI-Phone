<?php

namespace app\adminapi\logic\deviceauth;

use app\adminapi\logic\recharge\GiftPackageLogic;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\logic\BaseLogic;
use app\common\model\deviceauth\DeviceAuthPlan;

class DeviceAuthPlanLogic extends BaseLogic
{
    public const TYPE_UNIQUE_MESSAGE = '一种类型的套餐只能创建一次';

    public static function add(array $params): bool
    {
        try {
            if (!self::assertTypeUnique((int)$params['type'])) {
                return false;
            }

            $productId = self::normalizeProductId($params);
            self::assertVirtualProductId($productId);

            $durationDays = $params['duration_days'] ?? DeviceAuthCodeEnum::getDurationDays((int)$params['type']);
            DeviceAuthPlan::create([
                'name'         => $params['name'],
                'type'         => $params['type'],
                'duration_days'=> $durationDays,
                'price'        => $params['price'] ?? 0,
                'tokens_price' => $params['tokens_price'] ?? 0,
                'is_recommend' => $params['is_recommend'] ?? 0,
                'sort'         => $params['sort'] ?? 0,
                'status'       => $params['status'] ?? 1,
                'remark'       => $params['remark'] ?? '',
                'product_id'   => $productId,
            ]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function edit(array $params)
    {
        try {
            $updateData = [];
            foreach (['name', 'duration_days', 'price', 'tokens_price', 'is_recommend', 'sort', 'status', 'remark'] as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }
            if (array_key_exists('product_id', $params)) {
                $updateData['product_id'] = self::normalizeProductId($params);
                self::assertVirtualProductId($updateData['product_id']);
            }
            if (!empty($updateData)) {
                DeviceAuthPlan::update($updateData, ['id' => $params['id']]);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function normalizeProductId(array $params): string
    {
        $productId = trim((string)($params['product_id'] ?? ''));
        if (mb_strlen($productId) > 64) {
            throw new \Exception('虚拟支付产品ID不能超过64个字符');
        }
        return $productId;
    }

    private static function assertVirtualProductId(string $productId): void
    {
        if (!GiftPackageLogic::isMnpVirtualPayEnabled()) {
            return;
        }
        if ($productId === '') {
            throw new \Exception('当前为小程序虚拟支付，请填写虚拟支付产品ID');
        }
    }

    public static function detail(int $id): array
    {
        $plan = DeviceAuthPlan::findOrEmpty($id);
        if ($plan->isEmpty()) {
            return [];
        }
        $data = $plan->toArray();
        $data['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($data['type']);
        return $data;
    }

    public static function delete(int $id)
    {
        return DeviceAuthPlan::destroy($id);
    }

    public static function status(int $id, int $status)
    {
        return DeviceAuthPlan::update(['status' => $status], ['id' => $id]);
    }

    public static function typeExists(int $type, int $excludeId = 0): bool
    {
        $query = DeviceAuthPlan::where('type', $type);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return !$query->findOrEmpty()->isEmpty();
    }

    public static function assertTypeUnique(int $type, int $excludeId = 0): bool
    {
        if (self::typeExists($type, $excludeId)) {
            self::setError(self::TYPE_UNIQUE_MESSAGE);
            return false;
        }
        return true;
    }
}
