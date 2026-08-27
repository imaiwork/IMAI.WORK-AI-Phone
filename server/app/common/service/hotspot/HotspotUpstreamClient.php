<?php

namespace app\common\service\hotspot;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class HotspotUpstreamClient
{
    public static function request(string $method, string $url, array $options = []): array
    {
        $timeout = (int)config('hotspot.http_timeout', 120);
        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => min(15, $timeout),
            'http_errors' => false,
            'verify' => false,
        ]);

        $started = microtime(true);
        $query = is_array($options['query'] ?? null) ? $options['query'] : [];
        HotspotLog::write(sprintf(
            '上游请求开始：方法=%s 地址=%s 超时=%d秒 SSL校验=关闭',
            $method,
            $url,
            $timeout
        ));
        if ($query !== []) {
            HotspotLog::json('上游请求参数：', HotspotLog::safe($query), 2500);
        }
        if (isset($options['json']) && is_array($options['json'])) {
            HotspotLog::json('上游请求参数：', HotspotLog::safe($options['json']), 2500);
        }

        try {
            $response = $client->request($method, $url, $options);
        } catch (ConnectException $e) {
            HotspotLog::exception('热点追踪上游超时', $e);
            throw new HotspotUpstreamException('上游接口超时，请重试');
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : '';
            HotspotLog::exception('热点追踪上游请求异常', $e);
            HotspotLog::write(sprintf(
                '上游请求异常详情：方法=%s 地址=%s 状态=%d 正文=%s',
                $method,
                $url,
                $status,
                HotspotLog::clip($body, 500)
            ));
            throw new HotspotUpstreamException('服务异常，请稍后再试');
        }

        $status = $response->getStatusCode();
        $raw = (string)$response->getBody();
        $ms = (int)round((microtime(true) - $started) * 1000);
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        HotspotLog::write(sprintf(
            '上游请求完成：方法=%s 地址=%s 状态=%d 耗时=%d毫秒 正文长度=%d',
            $method,
            $url,
            $status,
            $ms,
            strlen($raw)
        ));
        HotspotLog::json('上游原始返回：', $payload !== [] ? HotspotLog::safe($payload) : $raw, 3000);

        if ($status === 401 || $status === 403) {
            HotspotLog::write('上游鉴权失败：HTTP ' . $status . '，请检查 API Key 配置（日志不记录密钥）');
            throw new HotspotUpstreamException('上游接口鉴权失败，请检查 API Key');
        }
        if ($status === 429) {
            HotspotLog::write('上游限流：HTTP 429');
            throw new HotspotUpstreamException('上游接口限流，请稍后再试');
        }
        if ($status >= 400) {
            $msg = self::extractMessage($payload);
            HotspotLog::json('上游错误正文：', $raw, 800);
            throw new HotspotUpstreamException('上游接口返回 ' . $status . '：' . mb_substr($msg, 0, 300));
        }

        return $payload;
    }

    public static function extractMessage(array $payload): string
    {
        $error = $payload['error'] ?? null;
        if (is_array($error)) {
            foreach (['message', 'code'] as $key) {
                if (!empty($error[$key])) {
                    return (string)$error[$key];
                }
            }
        }
        foreach (['message', 'detail', 'msg', 'error'] as $key) {
            if (!empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string)$payload[$key];
            }
        }
        return '上游接口异常';
    }
}
