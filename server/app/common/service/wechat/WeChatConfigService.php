<?php

namespace app\common\service\wechat;

use app\common\enum\PayEnum;
use app\common\enum\user\UserTerminalEnum;
use app\common\model\pay\PayConfig;
use app\common\service\ConfigService;

/**
 * 微信配置类
 * Class WeChatConfigService
 * @package app\common\service
 */
class WeChatConfigService
{

    /**
     * @notes 获取小程序配置
     * @return array
     * @author 段誉
     * @date 2022/9/6 19:49
     */
    public static function getMnpConfig(int $teamId = 0)
    {
        return [
            // team_id=0 主站小程序;>0 为团队(OEM)独立小程序,按租户隔离凭证
            // 直接读库内原文:ConfigService::get 会 json_decode,纯数字 AppSecret 会被变成浮点丢掉
            'app_id' => self::mnpRaw('app_id', $teamId),
            'secret' => self::mnpRaw('app_secret', $teamId),
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => app()->getRootPath() . 'runtime/wechat/' . date('Ym') . '/' . date('d') . '.log'
            ],
        ];
    }


    /**
     * 小程序凭证按原文读取并去掉首尾空白(粘贴常带换行,微信会判密钥无效)
     */
    private static function mnpRaw(string $name, int $teamId): string
    {
        $value = \app\common\model\Config::where([
            'type' => 'mnp_setting',
            'name' => $name,
            'team_id' => $teamId,
        ])->value('value');

        return trim((string)$value);
    }


    /**
     * @notes 获取微信公众号配置
     * @return array
     * @author 段誉
     * @date 2022/9/6 19:49
     */
    public static function getOaConfig()
    {
        return [
            'app_id' => ConfigService::get('oa_setting', 'app_id'),
            'secret' => ConfigService::get('oa_setting', 'app_secret'),
            'token' => ConfigService::get('oa_setting', 'token'),
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => app()->getRootPath() . 'runtime/wechat/' . date('Ym') . '/' . date('d') . '.log'
            ],
        ];
    }


    /**
     * @notes 获取微信开放平台配置
     * @return array
     * @author 段誉
     * @date 2022/10/20 15:51
     */
    public static function getOpConfig()
    {
        return [
            'app_id' => ConfigService::get('open_platform', 'app_id'),
            'secret' => ConfigService::get('open_platform', 'app_secret'),
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => app()->getRootPath() . 'runtime/wechat/' . date('Ym') . '/' . date('d') . '.log'
            ],
        ];
    }


    /**
     * @notes 根据终端获取本次微信下单使用的 appid
     * @param $terminal
     * @return string
     * @remark 下单 appid 与取 openid 必须同源,否则微信报「appid和openid不匹配」。
     *         小程序固定用主站 appid:商户号只绑了主站小程序,且 OEM 站不支持在线购买
     *         (前端 oem-recharge-panel 走卡密兑换),不按 OEM 租户取 appid。
     */
    public static function getPayAppIdByTerminal($terminal): string
    {
        switch ($terminal) {
            case UserTerminalEnum::WECHAT_MMP:
                return (string)(self::getMnpConfig()['app_id'] ?? '');
            case UserTerminalEnum::IOS:
            case UserTerminalEnum::ANDROID:
                return (string)(self::getOpConfig()['app_id'] ?? '');
            case UserTerminalEnum::WECHAT_OA:
            case UserTerminalEnum::H5:
            case UserTerminalEnum::PC:
                return (string)(self::getOaConfig()['app_id'] ?? '');
            default:
                return '';
        }
    }


    /**
     * @notes 根据终端获取支付配置
     * @param $terminal
     * @return array
     * @author 段誉
     * @date 2023/2/27 15:45
     */
    public static function getPayConfigByTerminal($terminal)
    {
        switch ($terminal) {
            case UserTerminalEnum::WECHAT_MMP:
                $notifyUrl = (string)url('pay/notifyMnp', [], false, true);
                break;
            case UserTerminalEnum::WECHAT_OA:
            case UserTerminalEnum::PC:
            case UserTerminalEnum::H5:
                $notifyUrl = (string)url('pay/notifyOa', [], false, true);
                break;
            case UserTerminalEnum::ANDROID:
            case UserTerminalEnum::IOS:
                $notifyUrl = (string)url('pay/notifyApp', [], false, true);
                break;
        }

        $pay = PayConfig::where(['pay_way' => PayEnum::WECHAT_PAY])->findOrEmpty()->toArray();
        //判断是否已经存在证书文件夹，不存在则新建
        if (!file_exists(app()->getRootPath() . 'runtime/cert')) {
            mkdir(app()->getRootPath() . 'runtime/cert', 0775, true);
        }
        //写入文件
        $apiclientCert = $pay['config']['apiclient_cert'] ?? '';
        $apiclientKey = $pay['config']['apiclient_key'] ?? '';

        $certPath = app()->getRootPath() . 'runtime/cert/' . md5($apiclientCert) . '.pem';
        $keyPath = app()->getRootPath() . 'runtime/cert/' . md5($apiclientKey) . '.pem';

        if (!empty($apiclientCert) && !file_exists($certPath)) {
            static::setCert($certPath, trim($apiclientCert));
        }
        if (!empty($apiclientKey) && !file_exists($keyPath)) {
            static::setCert($keyPath, trim($apiclientKey));
        }

        return [
            // 商户号
            'mch_id' => $pay['config']['mch_id'] ?? '',
            // 商户证书
            'private_key' => $keyPath,
            'certificate' => $certPath,
            // v3 API 秘钥
            'secret_key' => $pay['config']['pay_sign_key'] ?? '',
            'notify_url' => $notifyUrl,
            'http' => [
                'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
            ]
        ];
    }


    /**
     * @notes 临时写入证书
     * @param $path
     * @param $cert
     * @author 段誉
     * @date 2023/2/27 15:48
     */
    public static function setCert($path, $cert)
    {
        $fopenPath = fopen($path, 'w');
        fwrite($fopenPath, $cert);
        fclose($fopenPath);
    }
}
