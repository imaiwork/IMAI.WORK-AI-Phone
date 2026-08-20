<?php


namespace app\adminapi\logic\setting\pay;


use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\pay\PayConfig;
use app\common\service\FileService;

/**
 * 支付配置
 * Class PayConfigLogic
 * @package app\adminapi\logic\setting\pay
 */
class PayConfigLogic extends BaseLogic
{
    public const MNP_PAY_TYPE_WECHAT = 1;
    public const MNP_PAY_TYPE_VIRTUAL = 2;

    /**
     * @notes 设置配置
     * @param $params
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 16:16
     */
    public static function setConfig($params)
    {
        $payConfig = PayConfig::find($params['id']);

        $config = '';
        if ($payConfig['pay_way'] == PayEnum::WECHAT_PAY) {
            $config = self::formatWechatConfig($params['config'], $payConfig['config'] ?? []);
        }
        if ($payConfig['pay_way'] == PayEnum::ALI_PAY) {
            $config = [
                'mode' => $params['config']['mode'],
                'merchant_type' => $params['config']['merchant_type'],
                'app_id' => $params['config']['app_id'],
                'private_key' => $params['config']['private_key'],
                'ali_public_key' => $params['config']['mode'] == 'normal_mode' ? $params['config']['ali_public_key'] : '',
                'public_cert' => $params['config']['mode'] == 'certificate' ? $params['config']['public_cert'] : '',
                'ali_public_cert' => $params['config']['mode'] == 'certificate' ? $params['config']['ali_public_cert'] : '',
                'ali_root_cert' => $params['config']['mode'] == 'certificate' ? $params['config']['ali_root_cert'] : '',
            ];
        }

        $payConfig->name = $params['name'];
        $payConfig->icon = FileService::setFileUrl($params['icon']);
        $payConfig->sort = $params['sort'];
        $payConfig->config = $config;
        $payConfig->remark = $params['remark'] ?? '';
        return $payConfig->save();
    }


    /**
     * @notes 格式化微信支付配置，兼容原 PC 微信支付字段并追加小程序虚拟支付字段
     * @param array $config
     * @param array|string|null $oldConfig
     * @return array
     */
    public static function formatWechatConfig(array $config, $oldConfig = []): array
    {
        if (!is_array($oldConfig)) {
            $oldConfig = [];
        }
        $wechatConfig = $config['wechat_pay'] ?? $config;
        $oldWechatConfig = $oldConfig['wechat_pay'] ?? $oldConfig;

        $virtualConfig = $config['mnp_virtual_pay'] ?? [];
        $oldVirtualConfig = $oldConfig['mnp_virtual_pay'] ?? [];

        $mnpPayType = (int)($config['mnp_pay_type'] ?? ($oldConfig['mnp_pay_type'] ?? self::MNP_PAY_TYPE_WECHAT));
        if (!in_array($mnpPayType, [self::MNP_PAY_TYPE_WECHAT, self::MNP_PAY_TYPE_VIRTUAL], true)) {
            $mnpPayType = self::MNP_PAY_TYPE_WECHAT;
        }

        return [
            // 原微信支付配置，PC/公众号/H5/APP/普通小程序微信支付仍读取这些字段
            'interface_version' => self::getConfigValue($wechatConfig, $oldWechatConfig, 'interface_version'),
            'merchant_type'     => self::getConfigValue($wechatConfig, $oldWechatConfig, 'merchant_type'),
            'mch_id'            => self::getConfigValue($wechatConfig, $oldWechatConfig, 'mch_id'),
            'pay_sign_key'      => self::getConfigValue($wechatConfig, $oldWechatConfig, 'pay_sign_key'),
            'apiclient_cert'    => self::getConfigValue($wechatConfig, $oldWechatConfig, 'apiclient_cert'),
            'apiclient_key'     => self::getConfigValue($wechatConfig, $oldWechatConfig, 'apiclient_key'),
            // 小程序算力礼包支付方式：1-普通微信支付，2-微信小程序虚拟支付
            'mnp_pay_type'      => $mnpPayType,
            'mnp_virtual_pay'   => [
                'offer_id'      => self::getConfigValue($virtualConfig, $oldVirtualConfig, 'offer_id'),
                'app_key'       => self::getConfigValue($virtualConfig, $oldVirtualConfig, 'app_key'),
                // 后台未再暴露沙箱开关：未显式传 env 时固定现网(0)，避免旧配置 env=1 与现网 AppKey 混用导致签名失败
                'env'           => array_key_exists('env', $virtualConfig)
                    ? (int)$virtualConfig['env']
                    : 0,
                'currency_type' => self::getConfigValue($virtualConfig, $oldVirtualConfig, 'currency_type', 'CNY'),
                'mode'          => self::getConfigValue($virtualConfig, $oldVirtualConfig, 'mode', 'short_series_goods'),
                'method'        => self::getConfigValue($virtualConfig, $oldVirtualConfig, 'method', 'requestVirtualPayment'),
            ],
        ];
    }


    /**
     * @notes 新值为空时保留旧配置，避免切换支付方式时误清空另一套配置
     * @param array $config
     * @param array $oldConfig
     * @param string $name
     * @param mixed $default
     * @return mixed|string
     */
    private static function getConfigValue(array $config, array $oldConfig, string $name, $default = '')
    {
        if (array_key_exists($name, $config) && $config[$name] !== '' && $config[$name] !== null) {
            return $config[$name];
        }
        return $oldConfig[$name] ?? $default;
    }


    /**
     * @notes 获取配置
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 16:16
     */
    public static function getConfig($params)
    {
        $payConfig = PayConfig::find($params['id'])->toArray();
        if ($payConfig['pay_way'] == PayEnum::WECHAT_PAY) {
            $payConfig['config'] = self::formatWechatConfig($payConfig['config'] ?? []);
        }
        $payConfig['icon'] = FileService::getFileUrl($payConfig['icon']);
        $payConfig['domain'] = request()->domain();
        return $payConfig;
    }
}
