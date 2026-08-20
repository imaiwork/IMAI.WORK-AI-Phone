<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;
use app\common\service\FileService;
use app\common\service\ShanjianQueueService;
use think\model\concern\SoftDelete;

/**
 * 闪剪视频任务模型
 * Class ShanjianVideoTask
 * @package app\common\model\shanjian
 */
class ShanjianVideoTask extends BaseModel
{
    use SoftDelete;
    
    protected $deleteTime = 'delete_time';
    
    // 状态常量
    const STATUS_PENDING = 0;        // 待处理
    const STATUS_PROCESSING = 1;     // 视频查询中
    const STATUS_FAILED = 2;         // 视频合成失败
    const STATUS_SUCCESS = 3;        // 视频合成成功

    // 成片下载状态
    const DOWNLOAD_PENDING = 0;      // 待下载
    const DOWNLOAD_DOWNLOADING = 1;  // 下载中
    const DOWNLOAD_SUCCESS = 2;      // 下载成功
    const DOWNLOAD_FAILED = 3;       // 下载失败
    
    // 音频类型常量
    const AUDIO_TYPE_SCRIPT = 1;     // 文案驱动
    const AUDIO_TYPE_AUDIO = 2;      // 音频驱动
    
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_FAILED => '失败',
            self::STATUS_SUCCESS => '成功',
        ];
        
        return $statusMap[$status] ?? '未知';
    }

    /**
     * 获取成片下载状态文本
     */
    public static function getDownloadStatusText(int $status): string
    {
        $statusMap = [
            self::DOWNLOAD_PENDING => '待下载',
            self::DOWNLOAD_DOWNLOADING => '下载中',
            self::DOWNLOAD_SUCCESS => '下载成功',
            self::DOWNLOAD_FAILED => '下载失败',
        ];

        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取音频类型文本
     * @param int $audioType
     * @return string
     */
    public static function getAudioTypeText(int $audioType): string
    {
        $typeMap = [
            self::AUDIO_TYPE_SCRIPT => '文案驱动',
            self::AUDIO_TYPE_AUDIO => '音频驱动',
        ];
        
        return $typeMap[$audioType] ?? '未知';
    }
    
    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\user\User', 'user_id', 'id');
    }
    
    /**
     * 获取器：处理material字段
     * @param string $value
     * @return array
     */
    public function getMaterialAttr($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * 修改器：处理material字段
     * @param mixed $value
     * @return string
     */
    public function setMaterialAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string)$value;
    }
    
    /**
     * 获取器：处理extra字段
     * @param string $value
     * @return array
     */
    public function getExtraAttr($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * 修改器：处理extra字段
     * @param mixed $value
     * @return string
     */
    public function setExtraAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($value == ""){
            return $value;
        }
        return (string)$value;
    }
    
    /**
     * 获取器：处理状态文本
     * @param int $value
     * @return string
     */
    public function getStatusTextAttr(int $value): string
    {
        return self::getStatusText($value);
    }
    
    /**
     * 获取器：处理音频类型文本
     * @param int $value
     * @return string
     */
    public function getAudioTypeTextAttr(int $value): string
    {
        return self::getAudioTypeText($value);
    }

    public function getQueueStatusTextAttr($value, array $data): string
    {
        return ShanjianQueueService::statusText(
            (string)($data['queue_status'] ?? ''),
            (int)($data['queue_position'] ?? 0)
        );
    }

    public function getDownloadStatusTextAttr($value, array $data): string
    {
        return self::getDownloadStatusText((int)($data['download_status'] ?? 0));
    }

    public function getVideoSourceUrlAttr($value)
    {
        return $value ? FileService::getFileUrl((string)$value) : '';
    }

    public function setVideoSourceUrlAttr($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        // 远端原链接原样保存，本地路径走统一去域处理
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return FileService::setFileUrl($value);
    }
    
    /**
     * 获取器：处理创建时间
     * @param int $value
     * @return string
     */
    public function getCreateTimeAttr(int $value): string
    {
        return date('Y-m-d H:i:s', $value);
    }
    
    /**
     * 获取器：处理更新时间
     * @param int $value
     * @return string
     */
    public function getUpdateTimeAttr(int $value): string
    {
        return date('Y-m-d H:i:s', $value);
    }



    public function getMusicUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setMusicUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }


    public function getVideoResultUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setVideoResultUrlAttr($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return FileService::setFileUrl($value);
    }
}
