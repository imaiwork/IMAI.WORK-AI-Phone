<?php

declare(strict_types=1);

namespace app\common\service\sv;

use app\common\service\ToolsService;

/**
 * 中台微信号存在性校验：查询 / 提交 / 判定。
 * 手机号等非微信号形态不走这里。
 */
class WechatExistVerifyService
{
    public const PATTERN = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';

    public const OUTCOME_VALID = 'valid';
    public const OUTCOME_INVALID = 'invalid';
    public const OUTCOME_PENDING = 'pending';
    public const OUTCOME_RETRY = 'retry';

    /** @var (callable(string, string): array)|null */
    private static $testHandler = null;

    public static function setTestHandler(?callable $handler): void
    {
        self::$testHandler = $handler;
    }

    public static function isWechatFormat(string $value): bool
    {
        $value = trim($value);
        return $value !== '' && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * @return array{outcome: string, log: string, response: array<string, mixed>}
     */
    public static function check(
        string $wechat,
        int $connectTimeout = 5,
        int $requestTimeout = 8,
        bool $allowSubmit = true,
        int $deadline = 0
    ): array {
        $wechat = trim($wechat);
        try {
            $response = self::query($wechat, $connectTimeout, $requestTimeout);
        } catch (\Throwable $e) {
            return [
                'outcome' => self::OUTCOME_RETRY,
                'log' => $wechat . '查询校验结果异常:' . $e->getMessage(),
                'response' => [],
            ];
        }

        $outcome = self::interpret($response);
        $log = self::outcomeLog($wechat, $outcome, $response);
        $needSubmit = $outcome === self::OUTCOME_PENDING
            && $allowSubmit
            && (!array_key_exists('data', $response) || $response['data'] === null);
        if (!$needSubmit) {
            return ['outcome' => $outcome, 'log' => $log, 'response' => $response];
        }
        if ($deadline > 0 && time() >= $deadline) {
            return [
                'outcome' => self::OUTCOME_RETRY,
                'log' => $log . '；达到单轮耗时预算，跳过提交校验',
                'response' => $response,
            ];
        }

        try {
            $submit = self::submit($wechat, $connectTimeout, $requestTimeout);
            return [
                'outcome' => self::OUTCOME_PENDING,
                'log' => $log,
                'response' => $submit,
            ];
        } catch (\Throwable $e) {
            return [
                'outcome' => self::OUTCOME_RETRY,
                'log' => $wechat . '提交校验异常:' . $e->getMessage(),
                'response' => $response,
            ];
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function interpret(array $response): string
    {
        if (isset($response['code']) && (int)$response['code'] === 10005) {
            return self::OUTCOME_RETRY;
        }
        if (!isset($response['code']) || (int)$response['code'] !== 10000) {
            return self::OUTCOME_RETRY;
        }
        if (!array_key_exists('data', $response) || $response['data'] === null) {
            return self::OUTCOME_PENDING;
        }
        $data = is_array($response['data']) ? $response['data'] : [];
        if (isset($data['status']) && (int)$data['status'] === 0) {
            return self::OUTCOME_PENDING;
        }
        if (!array_key_exists('valid', $data)) {
            return self::OUTCOME_RETRY;
        }
        return (bool)$data['valid'] ? self::OUTCOME_VALID : self::OUTCOME_INVALID;
    }

    /**
     * @return array<string, mixed>
     */
    public static function query(string $wechat, int $connectTimeout = 5, int $requestTimeout = 8): array
    {
        if (self::$testHandler !== null) {
            $response = (self::$testHandler)('query', $wechat);
            return is_array($response) ? $response : [];
        }
        $response = ToolsService::Sv()->queryResult(
            ['string' => $wechat],
            $connectTimeout,
            $requestTimeout
        );
        return is_array($response) ? $response : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function submit(string $wechat, int $connectTimeout = 5, int $requestTimeout = 8): array
    {
        if (self::$testHandler !== null) {
            $response = (self::$testHandler)('submit', $wechat);
            return is_array($response) ? $response : [];
        }
        $response = ToolsService::Sv()->validateStrings(
            ['strings' => [$wechat]],
            $connectTimeout,
            $requestTimeout
        );
        return is_array($response) ? $response : [];
    }

    /**
     * @param array<string, mixed> $response
     */
    private static function outcomeLog(string $wechat, string $outcome, array $response): string
    {
        return match ($outcome) {
            self::OUTCOME_VALID => $wechat . '有效',
            self::OUTCOME_INVALID => $wechat . '无效',
            self::OUTCOME_PENDING => $wechat . (array_key_exists('data', $response) && $response['data'] === null
                ? '该账号还未开始验证'
                : '该账号还未完成验证,稍后再试'),
            default => $wechat . (isset($response['code']) && (int)$response['code'] === 10005
                ? '校验返回10005(算力不足或限流)，稍后重试'
                : '校验查询返回异常code，稍后重试'),
        };
    }
}
