<?php

namespace app\common\service\wechat;


use EasyWeChat\Kernel\Exceptions\Exception;
use EasyWeChat\MiniApp\Application;


/**
 * 微信功能类
 * Class WeChatMnpService
 * @package app\common\service
 */
class WeChatMnpService
{

    protected $app;

    protected $config;

    public function __construct()
    {
        $this->config = $this->getConfig();
        $this->app = new Application($this->config);
    }


    /**
     * @notes 配置
     * @return array
     * @throws \Exception
     * @author 段誉
     * @date 2023/2/27 12:03
     */
    protected function getConfig()
    {
        $config = WeChatConfigService::getMnpConfig();
        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new \Exception('请先设置小程序配置');
        }
        return $config;
    }


    /**
     * @notes 小程序-根据code获取微信信息
     * @param string $code
     * @return array
     * @throws Exception
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 11:03
     */
    public function getMnpResByCode(string $code)
    {
        $utils = $this->app->getUtils();
        $response = $utils->codeToSession($code);

        if (!isset($response['openid']) || empty($response['openid'])) {
            throw new Exception('获取openID失败');
        }

        return $response;
    }


    /**
     * @notes 获取手机号
     * @param string $code
     * @return \EasyWeChat\Kernel\HttpClient\Response|\Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author 段誉
     * @date 2023/2/27 11:46
     */
    public function getUserPhoneNumber(string $code)
    {
        return $this->app->getClient()->postJson('wxa/business/getuserphonenumber', [
            'code' => $code,
        ]);
    }


    /**
     * @notes 获取小程序码
     * @param string $scene
     * @param string $page
     * @param int $width
     * @return string
     * @throws \Exception
     */
    public function getMnpCodeUrl(string $page, int $width = 430, $savePath = '/tmp/mnp-wxacode-123.png', $params = [])
    {
        try {

            $response = $this->app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'page' => $page,
                'scene' => isset($params['auth_key']) ? '1011&auth_key=' . $params['auth_key'] : '1011',
                'width' => $width,
                'check_path' => true
            ]);

            // 获取响应头的Content-Type
            $contentType = $response->getHeaderLine('Content-Type');

            // 如果是图片类型
            if (strpos($contentType, 'image') !== false) {
                $response->saveAs($savePath);
                return $savePath;
            }

            // 如果不是图片，尝试解析JSON错误信息
            $jsonData = json_decode($response->getContent(), true);
            if (isset($jsonData['errcode']) && $jsonData['errcode'] != 0) {
                throw new \Exception('获取小程序码失败: ' . ($jsonData['errmsg'] ?? '未知错误'));
            }

            throw new \Exception('获取小程序码失败: 响应格式错误');
        } catch (\Throwable $e) {
            throw new \Exception('获取小程序码失败: ' . $e->getMessage());
        }
    }

    /**
     * @desc 获取短链
     * https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/url-link/generateUrlLink.html
     * @param string $path
     * @param string $query
     * @return array|mixed[]
     * @date 2025/2/20 11:07
     * @throws \EasyWeChat\Kernel\Exceptions\BadResponseException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public function urlLink(string $path, string $query)
    {
        return $this->app->getClient()->post('wxa/generate_urllink', [
            'json' => [
                'path' => $path,
                'query' => $query
            ],
        ])->toArray();

    }
}
