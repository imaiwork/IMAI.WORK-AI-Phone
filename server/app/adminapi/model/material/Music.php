<?php

namespace app\adminapi\model\material;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 数字人背景音乐模型
 * Class Music
 * @package app\adminapi\model\material
 */
class Music extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 来源类型常量
    const SOURCE_ADMIN = 0;      // 后台
    const SOURCE_USER = 1;       // 用户

    // 风格类型常量
    const STYLE_MY = 0;          // 我的
    const STYLE_TECH = 1;        // 科技
    const STYLE_SUSPENSE = 2;    // 悬疑
    const STYLE_LYRIC = 3;       // 抒情
    const STYLE_HAPPY = 4;       // 欢快
    const STYLE_CLASSICAL = 5;   // 古典
    const STYLE_JUMP = 6;        // 跳跃

    /**
     * 获取来源类型文本
     * @param int $source
     * @return string
     */
    public static function getSourceText(int $source): string
    {
        $sourceTexts = [
            self::SOURCE_ADMIN => '后台',
            self::SOURCE_USER => '用户',
        ];
        return $sourceTexts[$source] ?? '未知来源';
    }

    /**
     * 获取风格类型文本
     * @param int $style
     * @return string
     */
    public static function getStyleText(int $style): string
    {
        $styleTexts = [
            self::STYLE_MY => '我的',
            self::STYLE_TECH => '科技',
            self::STYLE_SUSPENSE => '悬疑',
            self::STYLE_LYRIC => '抒情',
            self::STYLE_HAPPY => '欢快',
            self::STYLE_CLASSICAL => '古典',
            self::STYLE_JUMP => '跳跃',
        ];
        return $styleTexts[$style] ?? '未知风格';
    }

    /**
     * 获取创建时间的格式化
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取更新时间的格式化
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
