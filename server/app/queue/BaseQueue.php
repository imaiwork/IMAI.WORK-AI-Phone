<?php

namespace app\queue;

use think\facade\Cache;

class BaseQueue
{
    private static string $EM_JOB = 'emJob'; // 向量任务
    private static string $QA_JOB = 'qaJob'; // QA的任务

    /**
     * @notes 向量任务队列推入
     * @param array $data
     * @author kb
     */
    public static function pushEM(array $data): void
    {
        app('queue')->push('app\queue\EmQueueJob', $data, self::$EM_JOB);
    }

    /**
     * @notes QA拆分队列推入
     * @param array $data
     * @author kb
     */
    public static function pushQA(array $data): void
    {
        app('queue')->push('app\queue\QaQueueJob', $data, self::$QA_JOB);
    }

    /**
     * @notes 取向量队列长度
     * @return int
     * @author kb
     */
    public static function getEmbJobLength(): int
    {
        $redis = Cache::store('redis')->handler();
        return $redis->lLen('{queues:'.self::$EM_JOB.'}')??0;
    }

    /**
     * @notes 取QA队列长度
     * @return int
     * @author kb
     */
    public static function getQaJobLength(): int
    {
        $redis = Cache::store('redis')->handler();
        return $redis->lLen('{queues:'.self::$QA_JOB.'}')??0;
    }

    /**
     * @notes 清空向量队列
     * @return void
     * @author kb
     */
    public static function clearEmbQueue(): void
    {
        $redis = Cache::store('redis')->handler();
        $redis->del('{queues:'.self::$EM_JOB.'}');
    }

    /**
     * @notes 清空QA队列
     * @return void
     * @author kb
     */
    public static function clearQaQueue(): void
    {
        $redis = Cache::store('redis')->handler();
        $redis->del('{queues:'.self::$QA_JOB.'}');
    }
}