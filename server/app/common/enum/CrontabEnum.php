<?php


namespace app\common\enum;

/**
 * 定时任务枚举
 * Class CrontabEnum
 * @package app\common\enum
 */
class CrontabEnum
{
    /**
     * 类型
     * CRONTAB 定时任务
     */
    const CRONTAB = 1;
    const DAEMON = 2;

    /**
     * 定时任务状态
     */
    const START = 1;
    const STOP = 2;
    const ERROR = 3;
}
