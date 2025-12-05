<?php


namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\service\wechat\WeChatOaService;
use EasyWeChat\Kernel\Exceptions\Exception;
use app\common\service\wechat\WeChatMnpService;
use app\common\service\FileService;
use think\helper\Str;

/**
 * 微信
 * Class WechatLogic
 * @package app\api\logic
 */
class WechatLogic extends BaseLogic
{

    /**
     * @notes 微信JSSDK授权接口
     * @param $params
     * @return false|mixed[]
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/3/1 11:49
     */
    public static function jsConfig($params)
    {
        try {
            $url = urldecode($params['url']);
            return (new WeChatOaService())->getJsConfig($url, [
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone',
                'openLocation',
                'getLocation',
                'chooseWXPay',
                'updateAppMessageShareData',
                'updateTimelineShareData',
                'openAddress',
                'scanQRCode'
            ]);
        } catch (Exception $e) {
            self::setError('获取jssdk失败:' . $e->getMessage());
            return false;
        }
    }


    /**
     * 获取小程序码
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024/7/1 15:30
     */
    public static function getMnpCodeUrl(array $postData)
    {
        try {

            $wechatMnpService = new WeChatMnpService();

            $path = public_path() . 'uploads/images/' . md5($postData['path']) . '.png';
            $params = [];
            $authKey = '';

            if (isset($postData['mnp_auth'])){
                $authKey = Str::random(16);
                $params = ['auth_key' => $authKey];
                $path = public_path() . 'uploads/images/mnpqrcode/' . md5(time().$authKey) . '.png';
            }

            if (!is_dir(dirname($path))) {
                umask(0);
                mkdir(dirname($path), 0777, true);
            }

            if (!file_exists($path)) {
                $wechatMnpService->getMnpCodeUrl($postData['path'], 430, $path, $params);
            }

            self::$returnData = ['url' => FileService::getFileUrl(str_replace(public_path(), '', $path)),'auth_key' => $authKey];
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
