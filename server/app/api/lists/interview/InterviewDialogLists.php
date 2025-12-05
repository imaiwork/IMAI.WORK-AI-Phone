<?php

namespace app\api\lists\interview;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\interview\InterviewDialog;

/**
 * 面试对话记录列表
 * Class InterviewDialogLists
 * @package app\api\lists\interview
 */
class InterviewDialogLists extends BaseApiDataLists implements ListsSearchInterface
{
    // 默认和最大限制
    const DEFAULT_LIMIT = 10;
    const MAX_LIMIT = 50;
    
    // 定义分页相关属性
    protected $page = 1;
    protected $limit = 10;
    protected $offset = 0;
    
    // 面试ID
    protected $interviewId = 0;
    
    /**
     * 设置面试ID
     * @param int $interviewId
     */
    public function setInterviewId(int $interviewId)
    {
        $this->interviewId = $interviewId;
    }
    
    /**
     * 获取面试ID
     * @return int
     */
    public function getInterviewId(): int
    {
        return $this->interviewId;
    }
    
    /**
     * 添加搜索条件
     * @param string $field
     * @param string $op
     * @param mixed $value
     */
    public function addSearchWhere(string $field, string $op, $value)
    {
        $this->searchWhere[] = [$field, $op, $value];
    }

    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type'],
            '%like%' => ['question', 'answer'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        // 获取分页参数
        $this->page = (int)$this->request->param('page', 1);
        if ($this->page < 1) {
            $this->page = 1;
        }

        // 添加未删除条件
        $this->searchWhere[] = ['delete_time', 'null', null];
        
        // 确保添加了面试ID条件
        if ($this->interviewId > 0) {
            // 检查是否已经添加了interview_id条件
            $hasInterviewIdCondition = false;
            foreach ($this->searchWhere as $condition) {
                if ($condition[0] === 'interview_id') {
                    $hasInterviewIdCondition = true;
                    break;
                }
            }
            
            // 如果还没有添加，则添加
            if (!$hasInterviewIdCondition) {
                $this->searchWhere[] = ['interview_id', '=', $this->interviewId];
            }
        }
        
        // 调试输出
        // dump($this->searchWhere);
        
        return InterviewDialog::field('id, interview_id, type, question, answer, question_url, answer_url, 
                                    question_duration, create_time, update_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                // 使用模型方法获取类型文本
                $item['type_text'] = InterviewDialog::getTypeText($item['type']);
                
                // 处理时间显示
                $item['create_time_text'] = $item['create_time'];
                
                // 处理内容截取
                $item['question_short'] = mb_strlen($item['question']) > 30 ? 
                    mb_substr($item['question'], 0, 30) . '...' : $item['question'];
                $item['answer_short'] = mb_strlen($item['answer']) > 30 ? 
                    mb_substr($item['answer'], 0, 30) . '...' : $item['answer'];
            })
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     */
    public function count(): int
    {
        // 确保添加了面试ID条件
        if ($this->interviewId > 0) {
            // 检查是否已经添加了interview_id条件
            $hasInterviewIdCondition = false;
            foreach ($this->searchWhere as $condition) {
                if ($condition[0] === 'interview_id') {
                    $hasInterviewIdCondition = true;
                    break;
                }
            }
            
            // 如果还没有添加，则添加
            if (!$hasInterviewIdCondition) {
                $this->searchWhere[] = ['interview_id', '=', $this->interviewId];
            }
        }
        
        $this->searchWhere[] = ['delete_time', 'null', null];
        return InterviewDialog::where($this->searchWhere)->count();
    }
} 