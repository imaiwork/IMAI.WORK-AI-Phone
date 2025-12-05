<?php

namespace app\common\model\interview;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 面试对话记录模型
 * Class InterviewDialog
 * @package app\common\model\interview
 */
class InterviewDialog extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    // 问题类型
    const TYPE_FOCUSED = 1;     // 带关注的问题
    const TYPE_INDEPTH = 2;     // 深入的问题
    const TYPE_UNFOCUSED = 3;   // 不带关注的问题
    const TYPE_OPENING = 4;     // 开场白
    const TYPE_INTERRUPT = 5;   // 中断信息
    const TYPE_EXIT = 6;         // 退出信息


    /**
     * 获取问题类型文本
     * @param int $type
     * @return string
     */
    public static function getTypeText(int $type): string
    {
        $typeTexts = [
            self::TYPE_FOCUSED => '带关注的问题',
            self::TYPE_INDEPTH => '深入的问题',
            self::TYPE_UNFOCUSED => '不带关注的问题',
            self::TYPE_OPENING => '开场白',
            self::TYPE_INTERRUPT => '中断信息',
            self::TYPE_EXIT => '退出信息'
        ];
        return $typeTexts[$type] ?? '未知类型';
    }

    /**
     * 获取所有问题类型
     * @return array
     */
    public static function getAllTypes(): array
    {
        return [
            self::TYPE_FOCUSED => '带关注的问题',
            self::TYPE_INDEPTH => '深入的问题',
            self::TYPE_UNFOCUSED => '不带关注的问题',
            self::TYPE_OPENING => '开场白'
        ];
    }

    /**
     * 与Interview表的关联
     * @return \think\model\relation\BelongsTo
     */
    public function interview()
    {
        return $this->belongsTo(Interview::class, 'interview_id', 'id');
    }
}
