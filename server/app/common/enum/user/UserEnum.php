<?php


namespace app\common\enum\user;

/**
 * 管理后台登录终端
 * Class terminalEnum
 * @package app\common\enum
 */
class UserEnum
{

    /**
     * 性别
     * SEX_OTHER = 未知
     * SEX_MEN =  男
     * SEX_WOMAN = 女
     */
    const SEX_OTHER = 0;
    const SEX_MEN = 1;
    const SEX_WOMAN = 2;


    /**
     * @notes 性别描述
     * @param bool $from
     * @return string|string[]
     * @author 段誉
     * @date 2022/9/7 15:05
     */
    public static function getSexDesc($from = true)
    {
        $desc = [
            self::SEX_OTHER => '未知',
            self::SEX_MEN => '男',
            self::SEX_WOMAN => '女',
        ];
        if (true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }
}
