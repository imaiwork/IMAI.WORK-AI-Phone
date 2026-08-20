<?php

namespace app\adminapi\logic\recharge;

use app\adminapi\logic\setting\pay\PayConfigLogic;
use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\pay\PayConfig;
use app\common\model\recharge\GiftPackage;


/**
 * logic
 */
class GiftPackageLogic extends BaseLogic
{
    /**
     * 添加
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function add(array $postData): bool
    {
        try {
            $postData = self::normalizePackageData($postData);
            self::assertVirtualProductId($postData);
            self::$returnData = GiftPackage::create($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 删除
     * @param array $getData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function delete(array $getData): bool
    {
        try {
            GiftPackage::destroy(['id' => $getData['id']]);
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 编辑
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function edit(array $postData): bool
    {
        try {
            $info = GiftPackage::findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $postData = self::normalizePackageData($postData);
            self::assertVirtualProductId($postData);
            self::$returnData = GiftPackage::update($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 归一化礼包提交数据
     */
    private static function normalizePackageData(array $postData): array
    {
        $postData['product_id'] = trim((string)($postData['product_id'] ?? ''));
        if (mb_strlen($postData['product_id']) > 64) {
            throw new \Exception('虚拟支付产品ID不能超过64个字符');
        }

        $packageInfo = $postData['package_info'] ?? [];
        if (is_string($packageInfo)) {
            $packageInfo = json_decode($packageInfo, true) ?: [];
        }
        if (!is_array($packageInfo)) {
            $packageInfo = [];
        }
        $packageInfo['expired'] = $packageInfo['expired'] ?? 50;
        if ($packageInfo['expired'] > 70) {
            throw new \Exception('过期时间不能大于70');
        }
        $postData['package_info'] = json_encode($packageInfo, JSON_UNESCAPED_UNICODE);
        return $postData;
    }

    /**
     * 小程序虚拟支付开启时，产品ID必填
     */
    private static function assertVirtualProductId(array $postData): void
    {
        if (!self::isMnpVirtualPayEnabled()) {
            return;
        }
        if (trim((string)($postData['product_id'] ?? '')) === '') {
            throw new \Exception('当前为小程序虚拟支付，请填写虚拟支付产品ID');
        }
    }

    /**
     * 是否开启小程序虚拟支付
     */
    public static function isMnpVirtualPayEnabled(): bool
    {
        $pay = PayConfig::where(['pay_way' => PayEnum::WECHAT_PAY])->findOrEmpty();
        if ($pay->isEmpty()) {
            return false;
        }
        $config = $pay['config'] ?? [];
        if (!is_array($config)) {
            return false;
        }
        return (int)($config['mnp_pay_type'] ?? PayConfigLogic::MNP_PAY_TYPE_WECHAT)
            === PayConfigLogic::MNP_PAY_TYPE_VIRTUAL;
    }


    /**
     * 详情
     * @param array $getData
     * @return bool
     * @author L
     * @data 2024-08-15 15:04:27
     */
    public static function detail(array $getData): bool
    {
        try {
            self::$returnData = GiftPackage::json(['package_info'], true)->findOrEmpty($getData['id'])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */
    public static function changeStatus(array $params): bool
    {
        try {
            $info = GiftPackage::findOrEmpty($params['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->status = 1 - $info->status;
            $info->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        