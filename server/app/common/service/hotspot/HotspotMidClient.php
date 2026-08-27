<?php

namespace app\common\service\hotspot;

use app\common\service\ToolsService;
use think\exception\HttpResponseException;

/**
 * 热点追踪走中台时的统一请求：捕获传输层异常、校验业务码、解包信封。
 * 调用方仍按旧契约读取（TikHub 的 data、方舟的 output/choices/usage）。
 */
class HotspotMidClient
{
    public const KIND_TIKHUB = 'tikhub';
    public const KIND_ARK = 'ark';

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public static function request(string $path, array $body = [], string $kind = self::KIND_TIKHUB): array
    {
        $timeout = max(1, (int)config('hotspot.http_timeout', 120));
        HotspotLog::write(sprintf('中台请求：路径=%s 类型=%s', $path, $kind));

        try {
            $client = app(ToolsService::class)
                ->setApiUrl($path)
                ->setMethod('POST')
                ->setTimeout(10, $timeout);
            if ($body !== []) {
                $client->setRequest($body);
            }
            $response = $client->sendWithoutThrow()->response;
        } catch (HotspotUpstreamException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $msg = self::extractExceptionMessage($e);
            HotspotLog::exception('中台请求异常：路径=' . $path, $e);
            throw new HotspotUpstreamException($msg);
        }

        if (!is_array($response)) {
            $response = [];
        }
        HotspotLog::json('中台原始返回：路径=' . $path . ' ', HotspotLog::safe($response), 3000);

        return $kind === self::KIND_ARK
            ? self::unwrapArk($response)
            : self::unwrapTikHub($response);
    }

    /**
     * 解包 TikHub 中台信封，返回仍带 data 的旧结构。
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public static function unwrapTikHub(array $response): array
    {
        $code = (int)($response['code'] ?? 0);

        if ($code === 200 && array_key_exists('data', $response)) {
            return $response;
        }

        if ($code !== 10000) {
            throw self::failByCode($code, $response);
        }

        $data = $response['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        if (array_key_exists('code', $data) && array_key_exists('data', $data)) {
            $innerCode = (int)$data['code'];
            if ($innerCode !== 200 && $innerCode !== 10000) {
                throw self::failByCode($innerCode, $data);
            }
            return $data;
        }

        return ['data' => $data];
    }

    /**
     * 解包方舟中台信封，返回 Responses/Chat 本体。
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public static function unwrapArk(array $response): array
    {
        $code = (int)($response['code'] ?? 0);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        if ($code === 10000 && self::hasArkBody($data)) {
            return $data;
        }
        if ($code === 10000 && isset($data['code']) && is_array($data['data'] ?? null) && self::hasArkBody($data['data'])) {
            return $data['data'];
        }
        if (($code === 0 || $code === 200) && self::hasArkBody($response)) {
            return $response;
        }
        if ($code === 200 && self::hasArkBody($data)) {
            return $data;
        }
        if ($code !== 10000 && $code !== 0 && $code !== 200) {
            throw self::failByCode($code, $response);
        }

        throw new HotspotUpstreamException('上游返回空结果');
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function hasArkBody(array $body): bool
    {
        $output = $body['output'] ?? null;
        $choices = $body['choices'] ?? null;
        return (is_array($output) && $output !== []) || (is_array($choices) && $choices !== []);
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function failByCode(int $code, array $response): HotspotUpstreamException
    {
        $message = trim((string)($response['message'] ?? $response['msg'] ?? $response['message_zh'] ?? ''));
        if ($code === 10001 && ($message === '' || str_contains($message, '超时'))) {
            $message = '请求超时，请稍后重试';
        }
        if ($message === '') {
            $message = '上游接口返回 ' . $code;
        }
        try {
            HotspotLog::write(sprintf('中台业务码异常：业务码=%d 说明=%s', $code, mb_substr($message, 0, 300)));
        } catch (\Throwable $e) {
            // 单测/无日志通道时不影响抛错
        }
        return new HotspotUpstreamException(mb_substr($message, 0, 300));
    }

    public static function extractExceptionMessage(\Throwable $e): string
    {
        if ($e instanceof HttpResponseException) {
            $data = $e->getResponse()->getData();
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                $data = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($data)) {
                $data = [];
            }
            $msg = trim((string)($data['msg'] ?? $data['message'] ?? ''));
            return self::normalizeTransportMessage($msg !== '' ? $msg : $e->getMessage());
        }
        return self::normalizeTransportMessage($e->getMessage());
    }

    public static function normalizeTransportMessage(string $msg): string
    {
        $msg = trim($msg);
        if ($msg !== '' && (str_contains($msg, '密钥') || stripos($msg, 'API Key') !== false)) {
            return '缺少中台密钥，请联系站长配置';
        }
        if ($msg !== '' && (str_contains($msg, '超时') || stripos($msg, 'timeout') !== false)) {
            return '请求超时，请稍后重试';
        }
        return $msg !== '' ? mb_substr($msg, 0, 300) : '服务异常，请稍后再试';
    }
}
