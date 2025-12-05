<?php


namespace app\common\enum;


class DefaultEnum
{
    //默认排序
    const SORT = 50;

    //显示隐藏
    const HIDE = 0; //隐藏
    const SHOW = 1; //显示

    //性别
    const UNKNOWN = 0; //未知
    const MAN = 1; //男
    const WOMAN = 2; //女

    //属性
    const SYSTEM = 1; //系统默认
    const CUSTOM = 2; //自定义


    /**
     * @notes 获取显示状态
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/8 3:56 下午
     */
    public static function getShowDesc($value = true)
    {
        $data = [
            self::HIDE => '隐藏',
            self::SHOW => '显示'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }

    /**
     * @notes 启用状态
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/14 4:02 下午
     */
    public static function getEnableDesc($value = true)
    {
        $data = [
            self::HIDE => '停用',
            self::SHOW => '启用'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }

    /**
     * @notes 性别
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/10 11:40 上午
     */
    public static function getSexDesc($value = true)
    {
        $data = [
            self::UNKNOWN => '未知',
            self::MAN => '男',
            self::WOMAN => '女'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }


    /**
     * @notes 属性
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/14 4:41 下午
     */
    public static function getAttrDesc($value = true)
    {
        $data = [
            self::SYSTEM => '系统默认',
            self::CUSTOM => '自定义'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }


    /**
     * @notes 是否推荐
     * @param bool $value
     * @return string|string[]
     * @author ljj
     * @date 2022/2/23 3:30 下午
     */
    public static function getRecommendDesc($value = true)
    {
        $data = [
            self::HIDE => '不推荐',
            self::SHOW => '推荐'
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value];
    }
}
