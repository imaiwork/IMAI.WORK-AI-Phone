<?php

namespace app\common\model\interview;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 面试模型
 * Class Interview
 * @package app\common\model\interview
 */
class Interview extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 面试状态常量
    const STATUS_ONGOING = 0;      // 进行中
    const STATUS_COMPLETED = 1;    // 已完成
    const STATUS_EXITED = 2;       // 用户主动退出
    const STATUS_RESTART = 3;      // 需要重新开始
    const STATUS_INTERRUPTED = 4;  // 意外中断
    const STATUS_ANALYZE = 5;      // 分析中
    const STATUS_ERROR = 6;      // 分析失败
    const STATUS_AI_ERROR = 7;      // Ai分析失败
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        $statusTexts = [
            self::STATUS_ONGOING => '进行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_EXITED => '用户主动退出',
            self::STATUS_RESTART => '需要重新开始',
            self::STATUS_INTERRUPTED => '意外中断',
            self::STATUS_ANALYZE => '分析中',
            self::STATUS_AI_ERROR => 'Ai分析失败',
            self::STATUS_ERROR => '分析失败',

        ];
        return $statusTexts[$status] ?? '未知状态';
    }
    
    /**
     * 与InterviewRecord表的关联
     * @return \think\model\relation\BelongsTo
     */
    public function record()
    {
        return $this->belongsTo(InterviewRecord::class, 'interview_record_id', 'id');
    }
    
    /**
     * 与InterviewDialog表的关联
     * @return \think\model\relation\HasMany
     */
    public function dialogs()
    {
        return $this->hasMany(InterviewDialog::class, 'interview_id', 'id');
    }
}
