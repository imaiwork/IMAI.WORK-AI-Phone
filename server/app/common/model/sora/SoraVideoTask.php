<?php

namespace app\common\model\sora;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 闪剪视频任务模型
 * Class ShanjianVideoTask
 * @package app\common\model\shanjian
 */
class SoraVideoTask extends BaseModel
{
    use SoftDelete;
    
    protected $deleteTime = 'delete_time';
    
    // 状态常量
    const STATUS_PENDING = 0;        // 待处理
    const STATUS_PROCESSING = 1;     // 视频查询中
    const STATUS_FAILED = 2;         // 视频合成失败
    const STATUS_SUCCESS = 3;        // 视频合成成功

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
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('app\common\model\user\User', 'user_id', 'id');
    }
    
    /**
     * 获取器：处理extra字段
     * @param string $value
     * @return array
     */
    public function getExtraAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true) ?: [];
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

    public function getVideoResultUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setVideoResultUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}