<?php

namespace app\common\logic;

use OpenAi\OpenAi;


/**
 * 逻辑基类
 * Class BaseLogic
 * @package app\common\logic
 */
class BaseLogic
{
    /**
     * 错误信息
     * @var string
     */
    protected static $error;

    /**
     * 返回状态码
     * @var int
     */
    protected static $returnCode = 0;

    protected static $returnData;

    /**
     * @notes 获取错误信息
     * @return string
     * @author 段誉
     * @date 2021/7/21 18:23
     */
    public static function getError(): string
    {
        if (false === self::hasError()) {
            return '系统错误';
        }
        return self::$error;
    }

    /**
     * @notes 设置错误信息
     * @param  $error
     * @author 段誉
     * @date 2021/7/21 18:20
     */
    public static function setError($error): void
    {
        !empty($error) && self::$error = $error;
    }

    /**
     * @notes 是否存在错误
     * @return bool
     * @author 段誉
     * @date 2021/7/21 18:32
     */
    public static function hasError(): bool
    {
        return !empty(self::$error);
    }

    /**
     * @notes 设置状态码
     * @param $code
     * @author 段誉
     * @date 2021/7/28 17:05
     */
    public static function setReturnCode($code): void
    {
        self::$returnCode = $code;
    }

    /**
     * @notes 特殊场景返回指定状态码,默认为0
     * @return int
     * @author 段誉
     * @date 2021/7/28 15:14
     */
    public static function getReturnCode(): int
    {
        return self::$returnCode;
    }

    /**
     * @notes 获取内容
     * @return mixed
     * @author kb
     * @date 2021/9/11 17:29
     */
    public static function getReturnData()
    {
        return self::$returnData;
    }

}
