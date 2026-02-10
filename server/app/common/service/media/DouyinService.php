<?php

namespace app\common\service\media;

use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use think\facade\Config;
use think\facade\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use think\facade\Log;

/**
 * 抖音H5场景发布内容服务类
 * 
 * 提供抖音H5场景下发布内容所需的参数生成功能
 * 包括：nonce_str、ticket、timestamp等参数的生成和获取
 */
class DouyinService
{
    // 抖音开放平台配置
    private $clientKey;
    private $clientSecret;
    private $accessToken;
    private $tokenConfig;
    private $ticket;
    private $apiBaseUrl = 'https://open.douyin.com';
    
    // HTTP客户端
    private $httpClient;
    
    public function __construct()
    {
        // 从配置文件获取抖音相关配置
        $this->clientKey = Config::get('douyin.client_key','');
        $this->clientSecret = Config::get('douyin.client_secret','');
        $this->accessToken = '';
        $this->apiBaseUrl = Config::get('douyin.api_base_url', 'https://open.douyin.com');

        // 初始化HTTP客户端
        $this->httpClient = new Client([
            'timeout' => 10.0,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Douyin-Service/1.0'
            ]
        ]);
    }
    
    /**
     * 生成随机字符串
     * 
     * @param int $length 字符串长度，默认32位
     * @return string 随机字符串
     */
    public function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        
        return $str;
    }
    
    /**
     * 生成时间戳
     * 
     * @return int 当前时间戳（秒）
     */
    public function generateTimestamp(): int
    {
        return time();
    }
    

    /**
     * 生成抖音H5发布内容所需的签名参数
     * 
     * @param array $params 业务参数数组
     * @return array 返回包含nonce_str、timestamp、ticket、sign的签名参数
     */
    public function generateSignParams(array $params = []): array
    {
        // 生成基本参数
        $nonceStr = $this->generateNonceStr();
        $timestamp = $this->generateTimestamp();
        $result =  $this->tokenConfig();
        if (!$result) {
            return [];
        }
        
        // 合并参数
        $signParams = [
            'timestamp' => (string)$timestamp,
            'nonce_str' => $nonceStr,
            'ticket' => $this->ticket,
            // 'client_token' =>  $client_token,
//            'client_key' => $this->clientKey,
        ];
        ksort($signParams);
        // 4. 生成签名字符串（注意：不要urlencode）
        Log::channel('douyin')->write('签名参数：' . json_encode($signParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signString = $this->buildSignString($signParams);
        Log::channel('douyin')->write('签名参数排序：' . $signString);
        // 5. MD5签名
        $signature = md5($signString);
        Log::channel('douyin')->write('signature：' . $signature);
        // 生成签名
        $params['signature'] = $signature;
        $params['client_key'] = $this->clientKey;
        return array_merge($signParams, $params);
    }
    
    /**
     * 生成签名
     * 
     * @param array $params 参数数组
     * @return string 签名
     */
    private function generateSign(array $params): string
    {
        // 参数按key排序
        ksort($params);
        // 拼接参数字符串
        $paramString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $key !== 'sign') {
                $paramString .= $key . '=' . $value . '&';
            }
        }
        $paramString = rtrim($paramString, '&');

        return md5($paramString);
    }
    /**
     * 构建签名字符串（关键！）
     */
    private function buildSignString(array $params): string
    {
        $pairs = [];

        foreach ($params as $key => $value) {
            // 关键：值必须是字符串，且不能URL编码
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }
   

    /**
     * 获取访问令牌
     * 
     * @return string|null 返回access_token，如果获取失败返回null
     */
    public function tokenConfig()
    {
        // 调用抖音API获取access_token
        try {
            $response = \app\common\service\ToolsService::Sv()->douyin(
                [
                    'app_id' => $this->clientKey,
                    'app_secret' => $this->clientSecret,
                ]
            );
        
           if (isset($response['code']) && $response['code'] != 10000) {
               return false;
            }
            $data = $response['data'] ?? [];
            if ($data && isset($data['access_token'])) {
                $this->accessToken = $data['access_token'];
                $return = true;
            }
            if ($data && isset($data['ticket'])) {
                $this->ticket = $data['ticket'];
                $return = $return == true ? true : false;
            }
    
           return $return;
        } catch (GuzzleException $e) {
            // 记录日志
            Log::channel('douyin')->write('Douyin getAccessToken error: ' . $e->getMessage());
        }
        
        return false;
    }
}