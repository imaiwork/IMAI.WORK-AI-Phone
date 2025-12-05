<?php

namespace app\common\service\wechat;

use EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient;
use Symfony\Component\HttpClient\CurlHttpClient;

class WeChatUrllinkService
{
    protected ?AccessTokenAwareClient $client = null;
    protected $config;


    public function getClient(): AccessTokenAwareClient
    {

        if (!$this->client) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    protected function getConfig()
    {
        $config = WeChatConfigService::getMnpConfig();
        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new \Exception('请先设置小程序配置');
        }
        return $config;
    }

    protected function createClient(): AccessTokenAwareClient
    {
        $curlClient = new CurlHttpClient();
        $accessTokenAwareClient = new AccessTokenAwareClient();

        // 这里不需要设置 HTTP 客户端
        return $accessTokenAwareClient;
    }

    public function urlLink(string $path, string $query): array
    {
        $client = $this->getClient();
        $access_token = $this->getStableAccessToken();
        $url = "https://api.weixin.qq.com/wxa/generate_urllink?access_token={$access_token}";

        $response = $client->postJson($url, [
            'path'  => $path,
            'query' => $query,
        ]);
        return $response->toArray();
    }

    public function getStableAccessToken(): string
    {
        $this->config = $this->getConfig();
        $url = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        $params = [
            'grant_type' => 'client_credential',
            'appid'      => $this->config['app_id'],
            'secret'     => $this->config['secret'],
        ];

        $client = new CurlHttpClient();
        $response = $client->request('POST', $url, [
            'json' => $params,
        ]);

        $data = $response->toArray();
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }

        throw new \Exception('Failed to get stable access token: ' . json_encode($data));
    }

    public function getVersionList(): array
    {
        $client = $this->getClient();
        $access_token = $this->getStableAccessToken();
        $url = "https://api.weixin.qq.com/wxaapi/log/get_client_version?access_token={$access_token}";
        $response = $client->getJson($url);
        $data = $response->toArray();
        if (isset($data['errcode']) && $data['errcode'] === 0) {
            return $data['cvlist'];
        }
        throw new \Exception('Failed to get version list: ' . json_encode($data));
    }
}