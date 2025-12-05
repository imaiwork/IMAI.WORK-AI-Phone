<?php

namespace app\common\enum\notice;

/**
 * 短信枚举
 * Class SmsEnum
 * @package app\common\enum
 */
class SmsEnum
{
    /**
     * 发送状态
     */
    const SEND_ING = 0;
    const SEND_SUCCESS = 1;
    const SEND_FAIL = 2;

    /**
     * 短信平台
     */
    const ALI = 1;
    const TENCENT = 2;


    /**
     * @notes 获取短信平台名称
     * @param $value
     * @return string
     * @author 段誉
     * @date 2022/8/5 11:10
     */
    public static function getNameDesc($value)
    {
        $desc = [
            'ALI' => '阿里云短信',
            'TENCENT' => '腾讯云短信',
        ];
        return $desc[$value] ?? '';
    }
}
