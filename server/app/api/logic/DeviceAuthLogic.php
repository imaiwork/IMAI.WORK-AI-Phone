<?php

namespace app\api\logic;

use app\adminapi\logic\deviceauth\DeviceAuthCodeLogic;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\enum\deviceauth\DeviceAuthOrderEnum;
use app\common\enum\PayEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\deviceauth\DeviceAuthBatch;
use app\common\model\deviceauth\DeviceAuthOrder;
use app\common\model\deviceauth\DeviceAuthPlan;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceUsed;
use app\common\model\user\User;
use app\common\service\ConfigService;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;
use think\facade\Db;
use think\facade\Log;

class DeviceAuthLogic extends BaseLogic
{
    public static function phoneList(int $userId, string $tab = 'all'): array
    {
        $query = SvDevice::where('user_id', $userId);
        $now = time();
        if ($tab === 'active') {
            $query->where('auth_status', DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE)
                ->where(function ($q) use ($now) {
                    $q->where('auth_expire_time', 0)->whereOr('auth_expire_time', '>', $now);
                });
        } elseif ($tab === 'inactive') {
            $query->where(function ($q) use ($now) {
                $q->where('auth_status', '<>', DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE)
                    ->whereOr(function ($q2) use ($now) {
                        $q2->where('auth_status', DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE)
                            ->where('auth_expire_time', '>', 0)
                            ->where('auth_expire_time', '<=', $now);
                    });
            });
        }
        $lists = $query->order('id desc')->select()->toArray();
        foreach ($lists as &$item) {
            $lastCode = self::getLastDeviceCdk((int)($item['last_cdk_code_id'] ?? 0));
            $item['cdk_type_desc'] = $lastCode ? DeviceAuthCodeEnum::getTypeDesc((int)$lastCode->type) : '';
            $item['auth_type_desc'] = $item['cdk_type_desc'];
            $item['auth_code'] = $lastCode ? (string)$lastCode->code : '';
            $item['auth_status_desc'] = DeviceAuthCodeEnum::getDeviceAuthStatusDesc($item['auth_status']);
            if ($item['auth_expire_time'] == 0 && $item['auth_status'] == DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE) {
                $item['remain_days'] = -1;
            } elseif ($item['auth_expire_time'] > $now) {
                $item['remain_days'] = (int)ceil(($item['auth_expire_time'] - $now) / 86400);
            } else {
                $item['remain_days'] = 0;
            }
        }
        unset($item);
        return $lists;
    }

    public static function myCodes(int $userId): array
    {
        $lists = DeviceCdkCode::where('owner_user_id', $userId)
            ->where('status', '<>', DeviceAuthCodeEnum::STATUS_DISABLED)
            ->order('id desc')
            ->select()
            ->toArray();
        foreach ($lists as &$item) {
            $item['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['type']);
            $item['status_desc'] = DeviceAuthCodeEnum::getStatusDesc($item['status']);
            if ($item['purchase_time']) {
                $item['purchase_time'] = date('Y-m-d H:i:s', $item['purchase_time']);
            }
            if ($item['use_time']) {
                $item['use_time'] = date('Y-m-d H:i:s', $item['use_time']);
            }
        }
        unset($item);
        return $lists;
    }

    public static function purchaseCode(array $params): bool
    {
        try {
            if (!ConfigService::get('device_auth', 'is_open', 1)) {
                throw new \Exception('设备授权功能未开启');
            }
            $plan = DeviceAuthPlan::where(['id' => $params['plan_id'], 'status' => 1])->findOrEmpty();
            if ($plan->isEmpty()) {
                throw new \Exception('套餐不存在或已下架');
            }
            $quantity = (int)$params['quantity'];
            $payType = (int)$params['pay_type'];
            DeviceAuthCodeLogic::assertPoolStock((int)$plan->type, $quantity);
            $orderAmount = bcmul((string)$plan->price, (string)$quantity, 2);
            $tokensAmount = $plan->tokens_price * $quantity;

            $order = DeviceAuthOrder::create([
                'sn'             => generate_sn(DeviceAuthOrder::class, 'sn'),
                'user_id'        => $params['user_id'],
                'biz_type'       => DeviceAuthOrderEnum::BIZ_TYPE_PURCHASE,
                'plan_id'        => $plan->id,
                'auth_type'      => $plan->type,
                'quantity'       => $quantity,
                'unit_price'     => $plan->price,
                'unit_tokens'    => $plan->tokens_price,
                'order_amount'   => $payType == DeviceAuthOrderEnum::PAY_TYPE_ONLINE ? $orderAmount : 0,
                'tokens_amount'  => $payType == DeviceAuthOrderEnum::PAY_TYPE_TOKENS ? $tokensAmount : 0,
                'pay_type'       => $payType,
                // 在线支付默认微信；虚拟支付预下单时会覆盖为 MNP_VIRTUAL_PAY
                'pay_way'        => $payType == DeviceAuthOrderEnum::PAY_TYPE_ONLINE ? PayEnum::WECHAT_PAY : 0,
                'pay_status'     => DeviceAuthOrderEnum::PAY_STATUS_UNPAID,
                'order_terminal' => $params['terminal'] ?? 1,
            ]);

            if ($payType == DeviceAuthOrderEnum::PAY_TYPE_TOKENS) {
                self::handleTokensPayment($order, AccountLogEnum::TOKENS_DEC_DEVICE_AUTH_PURCHASE);
                self::$returnData = ['order_id' => $order->id, 'paid' => true];
            } else {
                self::$returnData = [
                    'order_id' => $order->id,
                    'from'     => 'device_auth',
                    'paid'     => false,
                ];
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function renewDevice(array $params): bool
    {
        try {
            if (!ConfigService::get('device_auth', 'is_open', 1)) {
                throw new \Exception('设备授权功能未开启');
            }
            $plan = DeviceAuthPlan::where(['id' => $params['plan_id'], 'status' => 1])->findOrEmpty();
            if ($plan->isEmpty()) {
                throw new \Exception('套餐不存在或已下架');
            }
            $device = SvDevice::where([
                'id'          => $params['device_id'],
                'device_code' => $params['device_code'],
                'user_id'     => $params['user_id'],
            ])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            $payType = (int)$params['pay_type'];
            if ($payType == DeviceAuthOrderEnum::PAY_TYPE_TOKENS) {
                DeviceAuthCodeLogic::assertPoolStock((int)$plan->type, 1);
            } else {
                self::assertRenewAvailable((int)$params['user_id'], $plan);
            }
            $order = DeviceAuthOrder::create([
                'sn'             => generate_sn(DeviceAuthOrder::class, 'sn'),
                'user_id'        => $params['user_id'],
                'biz_type'       => DeviceAuthOrderEnum::BIZ_TYPE_RENEW,
                'plan_id'        => $plan->id,
                'auth_type'      => $plan->type,
                'quantity'       => 1,
                'unit_price'     => $plan->price,
                'unit_tokens'    => $plan->tokens_price,
                'order_amount'   => $payType == DeviceAuthOrderEnum::PAY_TYPE_ONLINE ? $plan->price : 0,
                'tokens_amount'  => $payType == DeviceAuthOrderEnum::PAY_TYPE_TOKENS ? $plan->tokens_price : 0,
                'pay_type'       => $payType,
                // 在线支付默认微信；虚拟支付预下单时会覆盖为 MNP_VIRTUAL_PAY
                'pay_way'        => $payType == DeviceAuthOrderEnum::PAY_TYPE_ONLINE ? PayEnum::WECHAT_PAY : 0,
                'pay_status'     => DeviceAuthOrderEnum::PAY_STATUS_UNPAID,
                'device_id'      => $device->id,
                'device_code'    => $device->device_code,
                'order_terminal' => $params['terminal'] ?? 1,
            ]);

            if ($payType == DeviceAuthOrderEnum::PAY_TYPE_TOKENS) {
                self::handleTokensPayment($order, AccountLogEnum::TOKENS_DEC_DEVICE_AUTH_RENEW, $device, $plan);
                self::$returnData = ['order_id' => $order->id, 'paid' => true];
            } else {
                self::$returnData = [
                    'order_id' => $order->id,
                    'from'     => 'device_auth',
                    'paid'     => false,
                ];
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function activate(array $params): bool
    {
        Log::channel('device')->write(date('Y-m-d H:i:s').' 设备绑定通知：'.time());
        Db::startTrans();
        try {
            $code = DeviceCdkCode::where('code', $params['code'])->lock(true)->findOrEmpty();
            if ($code->isEmpty()) {
                throw new \Exception('设备CDK不存在');
            }
            if ($code->owner_user_id != $params['user_id']) {
                throw new \Exception('该设备CDK不属于当前用户');
            }
            if ((int)$code->status === DeviceAuthCodeEnum::STATUS_USED) {
                throw new \Exception('设备CDK已使用');
            }
            if ((int)$code->status === DeviceAuthCodeEnum::STATUS_DISABLED) {
                throw new \Exception('设备CDK已作废');
            }
            $device = SvDevice::where([
                'device_code' => $params['device_code'],
                'user_id'     => $params['user_id'],
            ])->lock(true)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $now = time();
            $days = DeviceAuthCodeEnum::resolveDurationDays((int)$code->type, (int)$code->duration_days);
            $deviceActive = (int)$device->auth_status === DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE
                && ((int)$device->auth_expire_time === 0 || (int)$device->auth_expire_time > $now);
            $deviceStartTime = $deviceActive && (int)$device->auth_start_time > 0
                ? (int)$device->auth_start_time
                : $now;
            if ($days === 0) {
                $expireTime = 0;
            } else {
                $baseTime = $deviceActive && (int)$device->auth_expire_time > $now
                    ? (int)$device->auth_expire_time
                    : $now;
                $expireTime = $baseTime + $days * 86400;
            }

            DeviceAuthCodeSyncService::useOnPlatform($code, $device->device_code, (int)$params['user_id']);

            $code->status = DeviceAuthCodeEnum::STATUS_USED;
            $code->user_id = $params['user_id'];
            $code->device_id = $device->id;
            $code->device_code = $device->device_code;
            $code->use_time = $now;
            $code->auth_start_time = $now;
            $code->auth_expire_time = $expireTime;
            $code->update_time = $now;
            $code->save();

            self::updateDeviceAuthState($device, $code, $deviceStartTime, $expireTime);

            if ($code->batch_id) {
                DeviceAuthBatch::where('id', $code->batch_id)->inc('used_num')->update();
            }

            Db::commit();

            self::$returnData = [
                'code'             => $code->code,
                'cdk_type_desc'    => DeviceAuthCodeEnum::getTypeDesc($code->type),
                'auth_type_desc'   => DeviceAuthCodeEnum::getTypeDesc($code->type),
                'auth_expire_time' => $expireTime ? date('Y-m-d H:i:s', $expireTime) : '永久',
            ];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function notice(array $params): bool
    {
        Log::channel('device')->write(date('Y-m-d H:i:s') . ' 旧设备激活通知：' . time() . json_encode($params));
        Db::startTrans();
        try {
            $device_code = SvDevice::where('device_code', $params['device_code'])->lock(true)->findOrEmpty();
            if ($device_code->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            if ($device_code->user_id != $params['user_id']) {
                throw new \Exception('该设备不属于当前用户');
            }
            $device = SvDevice::where([
                                          'device_code' => $params['device_code'],
                                          'user_id'     => $params['user_id'],
                                      ])->lock(true)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $now = time();

            $device->auth_status      = DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE;
            $device->auth_start_time  = $device->auth_start_time == 0 ? ($params['auth_start_time'] ?? 0) : $device->auth_start_time;
            $device->auth_expire_time = $device->auth_expire_time == 0 ? ($params['auth_expire_time'] ?? 0) : $device->auth_expire_time;
            $device->update_time      = $now;
            $device->save();

            Db::commit();

            self::$returnData = [
                'device_code' => $device->device_code,
            ];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function redeem(array $params): bool
    {
        Log::channel('device')->write(date('Y-m-d H:i:s').' 设备CDK兑换：'.time());
        Db::startTrans();
        try {
            $codeValue = trim((string)($params['cdk_code'] ?? ''));
            if ($codeValue === '') {
                throw new \Exception('设备CDK不能为空');
            }

            $code = DeviceCdkCode::where('code', $codeValue)->lock(true)->findOrEmpty();
            if ($code->isEmpty()) {
                throw new \Exception('设备CDK不存在');
            }
            $device = SvDevice::where([
                'device_code' => $params['device_code'],
                'user_id'     => $params['user_id'],
            ])->lock(true)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            self::$returnData = self::applyCdkToDevice($code, $device, (int)$params['user_id']);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage() ?: '兑换失败');
            return false;
        }
    }

    public static function addPhone(array $params): bool
    {
        try {
            $exists = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();
            if (!$exists->isEmpty()) {
                if ($exists->user_id != $params['user_id']) {
                    throw new \Exception('设备已被其他用户绑定');
                }
                SvDeviceUsed::saveRecord((int)$params['user_id'], (string)$params['device_code'], (int)$exists->id, 1);
                self::$returnData = $exists->toArray();
                return true;
            }
            $device = SvDevice::create([
                'device_code'  => $params['device_code'],
                'user_id'      => $params['user_id'],
                'device_name'  => $params['device_name'] ?? $params['device_code'],
                'device_model' => $params['device_model'] ?? '',
                'sdk_version'  => $params['sdk_version'] ?? '',
                'status'       => 0,
                'auto_type'    => 1,
                'is_first'     => 1,
            ]);
            SvDeviceUsed::saveRecord((int)$params['user_id'], (string)$params['device_code'], (int)$device->id, 1);
            self::$returnData = $device->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function planList(): array
    {
        return DeviceAuthPlan::where('status', 1)
            ->order('sort desc, id asc')
            ->select()
            ->each(function ($item) {
                $item['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['type']);
            })
            ->toArray();
    }

    protected static function handleTokensPayment(DeviceAuthOrder $order, int $changeType, ?SvDevice $device = null, ?DeviceAuthPlan $plan = null): void
    {
        Db::startTrans();
        try {
            $user = User::find($order->user_id);
            $tokens = (float)$order->tokens_amount;
            // 企业空间成员看企业钱包，勿用个人 tokens 预检
            $spendable = \app\common\service\TeamBillingService::spendableTokens((int)$order->user_id);
            if ($spendable < $tokens) {
                $msg = \app\common\service\TeamBillingService::resolveSpender((int)$order->user_id) !== null
                    ? '当前团队算力不足，请联系团队主' : '算力余额不足';
                throw new \Exception($msg);
            }
            User::userTokensChange($order->user_id, $tokens, 'dec');
            $log = AccountLogLogic::add(
                $order->user_id,
                $changeType,
                AccountLogEnum::DEC,
                $tokens,
                1,
                $order->sn,
                AccountLogEnum::getChangeTypeDesc($changeType)
            );
            $order->pay_status = DeviceAuthOrderEnum::PAY_STATUS_PAID;
            $order->pay_time = time();
            $order->account_log_id = $log ? $log->id : 0;
            $order->save();

            if ($order->biz_type == DeviceAuthOrderEnum::BIZ_TYPE_PURCHASE) {
                DeviceAuthCodeLogic::assignCodesFromPool($order);
            } elseif ($order->biz_type == DeviceAuthOrderEnum::BIZ_TYPE_RENEW && $device && $plan) {
                self::consumeRenewCdk($order, $device, $plan);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function assertRenewAvailable(int $userId, DeviceAuthPlan $plan): void
    {
        $hasUserCdk = self::buildRenewCdkQuery($userId, $plan, false)->count() > 0;
        $hasPoolCdk = self::buildRenewCdkQuery($userId, $plan, true)->count() > 0;
        if (!$hasUserCdk && !$hasPoolCdk) {
            throw new \Exception('暂无相同类型的设备CDK，无法续期');
        }
    }

    public static function consumeRenewCdk(DeviceAuthOrder $order, SvDevice $device, DeviceAuthPlan $plan): array
    {
        $code = null;
        if ((int)$order->pay_type !== DeviceAuthOrderEnum::PAY_TYPE_TOKENS) {
            $code = self::buildRenewCdkQuery((int)$order->user_id, $plan, false)
                ->order('id asc')
                ->lock(true)
                ->findOrEmpty();
        }

        if ($code === null || $code->isEmpty()) {
            DeviceAuthCodeLogic::assignCodesFromPool($order);
            $code = DeviceCdkCode::where('order_id', $order->id)
                ->where('owner_user_id', $order->user_id)
                ->lock(true)
                ->findOrEmpty();
        }

        if ($code->isEmpty()) {
            throw new \Exception('暂无相同类型的设备CDK，无法续期');
        }

        $device = SvDevice::where('id', $device->id)->lock(true)->findOrEmpty();
        if ($device->isEmpty()) {
            throw new \Exception('设备不存在');
        }

        return self::applyCdkToDevice($code, $device, (int)$order->user_id, (int)$order->id);
    }

    private static function buildRenewCdkQuery(int $userId, DeviceAuthPlan $plan, bool $poolOnly = false)
    {
        $query = DeviceCdkCode::where([
            ['owner_user_id', '=', $poolOnly ? 0 : $userId],
            ['status', '=', DeviceAuthCodeEnum::STATUS_UNUSED],
            ['type', '=', (int)$plan->type],
        ]);
        if ((int)$plan->type === DeviceAuthCodeEnum::TYPE_CUSTOM) {
            $durationDays = DeviceAuthCodeEnum::resolveDurationDays((int)$plan->type, (int)$plan->duration_days);
            $query->where('duration_days', $durationDays);
        }
        return $query;
    }

    public static function applyCdkToDevice(
        DeviceCdkCode $code,
        SvDevice $device,
        int $userId,
        ?int $orderId = null
    ): array {
        if ((int)$code->owner_user_id !== $userId) {
            throw new \Exception('该设备CDK不属于当前用户');
        }
        if ((int)$code->status === DeviceAuthCodeEnum::STATUS_USED) {
            throw new \Exception('设备CDK已使用');
        }
        if ((int)$code->status === DeviceAuthCodeEnum::STATUS_DISABLED) {
            throw new \Exception('设备CDK已作废');
        }

        $now = time();
        $days = DeviceAuthCodeEnum::resolveDurationDays((int)$code->type, (int)$code->duration_days);
        $deviceActive = (int)$device->auth_status === DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE
            && ((int)$device->auth_expire_time === 0 || (int)$device->auth_expire_time > $now);
        $deviceStartTime = $deviceActive && (int)$device->auth_start_time > 0
            ? (int)$device->auth_start_time
            : $now;
        if ($days === 0) {
            $expireTime = 0;
        } else {
            $baseTime = $deviceActive && (int)$device->auth_expire_time > $now
                ? (int)$device->auth_expire_time
                : $now;
            $expireTime = $baseTime + $days * 86400;
        }

        DeviceAuthCodeSyncService::redeemOnPlatform($code, $device->device_code, $userId);

        $code->status = DeviceAuthCodeEnum::STATUS_USED;
        $code->user_id = $userId;
        $code->device_id = $device->id;
        $code->device_code = $device->device_code;
        $code->use_time = $now;
        $code->auth_start_time = $now;
        $code->auth_expire_time = $expireTime;
        $code->update_time = $now;
        if ($orderId) {
            $code->order_id = $orderId;
        }
        $code->save();

        self::updateDeviceAuthState($device, $code, $deviceStartTime, $expireTime);

        if ($code->batch_id) {
            DeviceAuthBatch::where('id', $code->batch_id)->inc('used_num')->update();
        }

        return [
            'code'             => $code->code,
            'cdk_code'         => $code->code,
            'cdk_type_desc'    => DeviceAuthCodeEnum::getTypeDesc($code->type),
            'auth_type_desc'   => DeviceAuthCodeEnum::getTypeDesc($code->type),
            'auth_expire_time' => $expireTime ? date('Y-m-d H:i:s', $expireTime) : '永久',
        ];
    }

    private static function updateDeviceAuthState(SvDevice $device, DeviceCdkCode $code, int $startTime, int $expireTime): void
    {
        SvDevice::where('id', $device->id)->update([
            'auth_status'       => DeviceAuthCodeEnum::DEVICE_AUTH_ACTIVE,
            'auth_start_time'   => $startTime,
            'auth_expire_time'  => $expireTime,
            'last_cdk_code_id'  => (int)$code->id,
            'update_time'       => time(),
        ]);
    }

    private static function getLastDeviceCdk(int $id): ?DeviceCdkCode
    {
        if ($id <= 0) {
            return null;
        }
        $code = DeviceCdkCode::findOrEmpty($id);
        return $code->isEmpty() ? null : $code;
    }
}
