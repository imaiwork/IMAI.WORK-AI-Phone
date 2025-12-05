<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 素材模型
 * Class SvMediaMaterial
 * @package app\common\model\sv
 */
class SvMediaMaterial extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 素材类型常量
    const TYPE_IMAGE = 1;     // 图片
    const TYPE_VIDEO = 2;     // 视频

    /**
     * 获取素材类型文本
     * @param int $type
     * @return string
     */
    public static function getTypeText(int $type): string
    {
        $typeTexts = [
            self::TYPE_IMAGE => '图片',
            self::TYPE_VIDEO => '视频',
        ];
        return $typeTexts[$type] ?? '未知类型';
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