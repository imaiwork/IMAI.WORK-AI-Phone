<?php

namespace app\common\model\interview;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 面试记录模型 - 记录用户对特定岗位的面试情况
 * Class InterviewRecord
 * @package app\common\model\interview
 */
class InterviewRecord extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 面试记录状态
    const STATUS_ONGOING = 0;      // 进行中
    const STATUS_COMPLETED = 1;    // 已完成
    const STATUS_EXITED = 2;       // 用户主动退出
    const STATUS_RESTART = 3;      // 需要重新开始
    const STATUS_INTERRUPTED = 4;  // 意外中断
    const STATUS_ANALYZE = 5;      // 分析中
    const STATUS_ERROR = 6;      // 分析失败
    const STATUS_AI_ERROR = 7;      // Ai分析失败


  
    /**
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
     * 与Interview表的关联
     * @return \think\model\relation\HasMany
     */
    public function interviews()
    {
        return $this->hasMany(Interview::class, 'interview_record_id', 'id');
    }
    
    /**
     * 获取最后一次面试
     * @return \think\model\relation\HasOne
     */
    public function lastInterview()
    {
        return $this->hasOne(Interview::class, 'id', 'last_interview_id');
    }
    
    /**
     * 更新面试会话总数
     * @return bool
     */
    public function updateTotalSessions(): bool
    {
        $this->total_sessions = $this->interviews()->count();
        return $this->save();
    }
    
    /**
     * 更新最后一次面试信息
     * @param Interview $interview
     * @return bool
     */
    public function updateLastInterview(Interview $interview): bool
    {
        $duration = $interview->end_time - $this->first_start_time;
        $this->last_interview_id = $interview->id;
        $this->last_end_time = $interview->end_time;
        $this->duration = $duration;
        // 更新最高分数
        if ($interview->score > $this->best_score) {
            $this->best_score = $interview->score;
        }
        
        return $this->save();
    }

    public function getStatusTextAttr($value, $data)
    {
      
        $statusTexts = [
            self::STATUS_ONGOING => '进行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_EXITED => '主动退出',
            self::STATUS_RESTART => '重新开始',
            self::STATUS_INTERRUPTED => '意外中断',
            self::STATUS_ANALYZE => '分析中',
            self::STATUS_AI_ERROR => 'Ai分析失败',
            self::STATUS_ERROR => '分析失败',
        ];

        return isset($statusTexts[$data['status']]) ? $statusTexts[$data['status']] : '未知状态';
    }
}
