<?php

namespace app\common\model\material;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * FFmpeg文件处理模型
 * Class FfmpegFile
 * @package app\common\model\material
 */
class FfmpegFile extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ffmpeg_file';

    // 类型常量
    const TYPE_IMAGE = 10;        // 图片
    const TYPE_VIDEO = 20;        // 视频

    // 状态常量
    const STATUS_PENDING = 0;     // 待处理
    const STATUS_PROCESSING = 1;  // 处理中
    const STATUS_SUCCESS = 2;     // 成功
    const STATUS_FAILED = 3;      // 失败

    /**
     * 获取类型文本
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
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statusTexts = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
        ];
        return $statusTexts[$status] ?? '未知状态';
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

    /**
     * 补全URL
     * @param $value
     * @return string
     */
    public function getUriAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

}