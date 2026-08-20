<?php


namespace app\adminapi\validate\setting;


use app\common\enum\PayEnum;
use app\adminapi\logic\setting\pay\PayConfigLogic;
use app\common\model\pay\PayConfig;
use app\common\validate\BaseValidate;


class PayConfigValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'name' => 'require|checkName',
        'icon' => 'require',
        'sort' => 'require|number|max:5',
        'config' => 'require|checkConfig',
    ];

    protected $message = [
        'id.require' => 'id不能为空',
        'name.require' => '支付名称不能为空',
        'icon.require' => '支付图标不能为空',
        'sort.require' => '排序不能为空',
        'sort,number' => '排序必须是纯数字',
        'sort.max' => '排序最大不能超过五位数',
        'config.require' => '支付参数缺失',
    ];

    public function sceneGet()
    {
        return $this->only(['id']);
    }


    /**
     * @notes 校验支付配置记录
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 16:19
     */
    public function checkConfig($config, $rule, $data)
    {
        $result = PayConfig::where('id', $data['id'])->find();
        if (empty($result)) {
            return '支付方式不存在';
        }

        if ($result['pay_way'] == PayEnum::WECHAT_PAY) {
            return $this->checkWechatConfig((array)$config, $result['config'] ?? []);
        }
        if ($result['pay_way'] == PayEnum::ALI_PAY) {
            if (empty($config['mode'])) {
                return '模式不能为空';
            }
            if (empty($config['merchant_type'])) {
                return '商户类型不能为空';
            }
            if (empty($config['app_id'])) {
                return '应用ID不能为空';
            }
            if (empty($config['private_key'])) {
                return '应用私钥不能为空';
            }
            if (empty($config['ali_public_key'])) {
                return '支付宝公钥不能为空';
            }
        }
        return true;
    }


    /**
     * @notes 校验微信支付配置，支持小程序普通微信支付/虚拟支付切换
     * @param array $config
     * @param array|string|null $oldConfig
     * @return bool|string
     */
    private function checkWechatConfig(array $config, $oldConfig = [])
    {
        $formatConfig = PayConfigLogic::formatWechatConfig($config, $oldConfig);
        $mnpPayType = (int)$formatConfig['mnp_pay_type'];

        if (!in_array($mnpPayType, [PayConfigLogic::MNP_PAY_TYPE_WECHAT, PayConfigLogic::MNP_PAY_TYPE_VIRTUAL], true)) {
            return '小程序支付方式参数错误';
        }

        if ($mnpPayType == PayConfigLogic::MNP_PAY_TYPE_WECHAT) {
            if (empty($formatConfig['interface_version'])) {
                return '微信支付接口版本不能为空';
            }
            if (empty($formatConfig['merchant_type'])) {
                return '商户类型不能为空';
            }
            if (empty($formatConfig['mch_id'])) {
                return '微信支付商户号不能为空';
            }
            if (empty($formatConfig['pay_sign_key'])) {
                return '商户API密钥不能为空';
            }
            if (empty($formatConfig['apiclient_cert'])) {
                return '微信支付证书不能为空';
            }
            if (empty($formatConfig['apiclient_key'])) {
                return '微信支付证书密钥不能为空';
            }
        }

        if ($mnpPayType == PayConfigLogic::MNP_PAY_TYPE_VIRTUAL) {
            $virtualConfig = $formatConfig['mnp_virtual_pay'] ?? [];
            if (empty($virtualConfig['offer_id'])) {
                return '小程序虚拟支付offer_id不能为空';
            }
            if (empty($virtualConfig['app_key'])) {
                return '小程序虚拟支付app_key不能为空';
            }
            if (!in_array((int)$virtualConfig['env'], [0, 1], true)) {
                return '小程序虚拟支付环境参数错误';
            }
            if (empty($virtualConfig['currency_type'])) {
                return '小程序虚拟支付币种不能为空';
            }
            if (empty($virtualConfig['mode'])) {
                return '小程序虚拟支付mode不能为空';
            }
            if (empty($virtualConfig['method'])) {
                return '小程序虚拟支付签名方法不能为空';
            }
        }

        return true;
    }


    /**
     * @notes 校验支付名
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     * @author 段誉
     * @date 2023/2/23 16:19
     */
    public function checkName($value, $rule, $data)
    {
        $result = PayConfig::where('name', $value)
            ->where('id', '<>', $data['id'])
            ->findOrEmpty();
        if (!$result->isEmpty()) {
            return '支付名称已存在';
        }
        return true;
    }
}
