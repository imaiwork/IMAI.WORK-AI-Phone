<?php

namespace app\common\enum;
/**
 * 卡密枚举类
 * Class CardCodeEnum
 * @package app\common\enum
 */
class CardCodeEnum
{

    const TYPE_TOKENS = 3;
    const TYPE_DISTRIBUTION_TOKENS = 5;

    /**
     * @notes 获取卡密类型
     * @param bool $from
     * @return bool|mixed|string
     * @author kb
     * @date 2023/7/10 12:22
     */
    public static function getTypeDesc($from = true)
    {
        $desc = [
            self::TYPE_TOKENS => '算力值',
            self::TYPE_DISTRIBUTION_TOKENS => '代理算力卡',
        ];
        if (true === $from) {
            return $desc;
        }
        return $desc[$from] ?? '';
    }
}