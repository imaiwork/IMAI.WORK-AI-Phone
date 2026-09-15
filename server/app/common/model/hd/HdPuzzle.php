<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdPuzzle extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    // 状态常量
    const STATUS_PENDING = 0; // 待处理
    const STATUS_SUCCESS = 1; // 成功
    const STATUS_FAILED = 2; // 失败
    
    // 类型常量
    const TYPE_THREE = 1; // 三张图
    const TYPE_FOUR = 2; // 四张图
    const TYPE_FIVE = 3; // 五张图
    const TYPE_SIX = 4; // 六张图
    const TYPE_NINE = 5; // 九张图
    
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
        ];
        
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取类型文本
     * @param int $type
     * @return string
     */
    public static function getTypeText(int $type): string
    {
        $typeMap = [
            self::TYPE_THREE => '三张图',
            self::TYPE_FOUR => '四张图',
            self::TYPE_FIVE => '五张图',
            self::TYPE_SIX => '六张图',
            self::TYPE_NINE => '九张图',
        ];
        
        return $typeMap[$type] ?? '未知';
    }
    

    
    /**
     * 与拼图设置的关联关系
     * @return \think\model\relation\HasOne
     */
    public function setting()
    {
        return $this->hasOne(HdPuzzleSetting::class, 'id', 'puzzle_setting_id');
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
}

