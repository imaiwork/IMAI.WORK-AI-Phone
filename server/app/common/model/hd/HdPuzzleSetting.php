<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdPuzzleSetting extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    // 状态常量
    const STATUS_PENDING = 1; // 待处理
    const STATUS_PROCESSING = 2; // 生成中
    const STATUS_COMPLETED = 3; // 已完成
    const STATUS_PARTIAL_COMPLETE = 4; // 部分完成
    const STATUS_FAILED = 5; // 失败
    
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '生成中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_PARTIAL_COMPLETE => '部分完成',
            self::STATUS_FAILED => '失败',
        ];
        
        return $statusMap[$status] ?? '未知';
    }
    
   
    
    /**
     * 创建时间获取器
     * @param $value
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
    
    /**
     * 更新时间获取器
     * @param $value
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
    
    /**
     * 与拼图任务的关联关系
     * @return \think\model\relation\HasMany
     */
    public function puzzles()
    {
        return $this->hasMany(HdPuzzle::class, 'puzzle_setting_id', 'id');
    }
}

