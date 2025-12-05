<?php

namespace app\common\Jobs;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use think\facade\Db;
use think\queue\Job;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewRecord;
use app\common\model\interview\InterviewDialog;
use app\common\model\user\User;
use think\facade\Log;

class EndInterviewJob
{
    protected int $interviewId;
    protected int $userId = 0;  
    protected int $interviewRecordId = 0;  

    public function __construct(int $interviewId)
    {
        $this->interviewId = $interviewId;
    }

    // 将 fire 方法重命名为 handle
    public function handle(Job $job)
    {
        Db::startTrans();

        try {
            // 获取面试记录
            $interview = Interview::where(['id' => $this->interviewId, 'status' => Interview::STATUS_ANALYZE])->findOrEmpty();
            if ($interview->isEmpty()) {
                throw new \Exception('没有分析中的面试');
            }
            $company_id = InterviewCv::where(['interview_job_id' => $interview->job_id,'user_id' => $interview->user_id])->value('company_id');
        
            if (empty($company_id)) {
                throw new \Exception('没有公司ID');
            }
            //$unit = TokenLogService::checkToken($company_id, 'interview_mark');
            $unit = 0;
            $this->userId = $interview->user_id;
            $this->interviewRecordId = $interview->interview_record_id;

            // 更新面试记录
            $interviewRecord = InterviewRecord::where(['id' => $interview->interview_record_id, 'status' => InterviewRecord::STATUS_ANALYZE])->findOrEmpty();
            if ($interviewRecord->isEmpty()) {
                throw new \Exception('没有分析中的面试记录');
            }

            // 访问通义获取评分和面试评价
            $dialogs = InterviewDialog::where('interview_id', $this->interviewId)->field(['question', 'answer'])->select()->toArray();
            $interviewResponse = $this->qwen($dialogs);

            // 获取评分和面试评价
            $interview->end_time = time();
            $interview->score = $interviewResponse['score'];
            $interview->comment = $interviewResponse['result'];
            $interview->analyze = $interviewResponse['evaluation'];
            $interview->inspection_point = $interviewResponse['appraisal'];
            $interview->status = Interview::STATUS_COMPLETED;
            $interview->save();

            $duration = $interview->end_time - $interviewRecord->first_start_time;
            $interviewRecord->best_score = $interviewResponse['score'];
            $interviewRecord->duration = $duration;
            $interviewRecord->end_time = $interview->end_time;
            $interviewRecord->status = InterviewRecord::STATUS_COMPLETED;
            $interviewRecord->last_interview_id = $interview->id;
            $interviewRecord->save();
            if($unit > 0){
                User::userTokensChange($company_id, $unit);
                $extra = [
                    '面试评分次数' => 1,
                    "算力单价" => $unit,
                    "实际消耗算力" => $unit
                ];
                AccountLogLogic::recordUserTokensLog(true, $company_id, AccountLogEnum::TOKENS_DEC_AI_MARK, $unit, $interview->id, $extra);
            }

            //记录日志
            Db::commit();
            $job->delete(); // 任务成功完成，删除任务
        } catch (\Throwable $e) {
            Log::error("面试结束任务处理失败，面试ID: {$this->interviewId}，错误信息: {$e->getMessage()}");
            Db::rollback();
            $this->handleFailure($job, $e->getMessage());
        }
    }

    private function handleFailure(Job $job, string $errorMessage)
    {
        Log::error("面试结束任务失败处理次数{$job->attempts()}");
        if ($job->attempts() >= 3) {
            // 超过重试次数，记录到失败任务表
            $this->markAsFailed($job, $errorMessage);
            $job->delete();
        } else {
            // 重试任务
            $job->release(5); // 延迟5秒后重试
        }
    }

    protected function markAsFailed(Job $job, string $errorMessage): void
    {
        Db::name('failed_jobs')->insert([
            'user_id' => $this->userId,
            'job_id' => $this->interviewRecordId,
            'job_class' => get_class(),
            'job_data' => json_encode(['user_id' => $this->userId, 'interview_id' => $this->interviewId]),
            'error_message' => $errorMessage,
            'attempts' => $job->attempts(),
            'failed_at' => date('Y-m-d H:i:s')
        ]);
        Db::startTrans();
        try {

            if (stripos($errorMessage, 'AI评分失败') !== false) {

                $status = Interview::STATUS_AI_ERROR;
                $msg = 'AI评分失败';
            } else {
                
                $status = Interview::STATUS_ERROR;
            $msg = $errorMessage;

                echo "错误信息中不包含 'AI评分失败'（忽略大小写）";
            }

            $interview = Interview::where(['id' => $this->interviewId, 'status' => Interview::STATUS_ANALYZE])->findOrEmpty();
            if ($interview->isEmpty()) {
                throw new \Exception('数据有误');
            }
            $interviewRecord = InterviewRecord::where(['id' => $interview->interview_record_id, 'status' => InterviewRecord::STATUS_ANALYZE])->findOrEmpty();
            if ($interviewRecord->isEmpty()) {
                throw new \Exception('数据有误2');
            }
            // 获取评分和面试评价
            $interview->end_time = time();
            $interview->status =  $status;
            $interview->reason =  $msg;
            $interview->save();

            $duration = $interview->end_time - $interviewRecord->first_start_time;
            $interviewRecord->duration = $duration;
            $interviewRecord->end_time = $interview->end_time;
            $interviewRecord->status = $status;
            $interviewRecord->last_interview_id = $interview->id;
            $interviewRecord->save();
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error("面试结束任务，状态变更失败{ $e->getMessage()}");
        }

    }

    public function qwen($dialogs)
    {
        $qwenData = [];
        foreach ($dialogs as $item) {
            $qwenData[] = [
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
        }


        
        $messages = [
            [
                'role' => 'system',
                'content' => '{
                        "role": “面试评估助手",
                        "description": "你是一位专业的面试评估助手，专注于分析面试对话历史，并给出一个综合评分（1-100分）和侧重考察点评价与总体评价。评价需基于对话内容的具体分析，保持公正客观且详细具体。",
                        "interaction": {
                            "instruction": "请根据提供的面试对话文本，综合分析以下方面后给出一个综合评分（1-100分）和总体评价：
                            专业知识与技能
                            沟通表达能力
                            应变与问题解决能力
                            职业素养与态度
                            团队协作与价值观匹配度
                            【HR关注点1】
                            【HR关注点2】
                            【HR关注点…】

                            评价需整合以上维度，具体指出优缺点及改进建议，并仅返回JSON格式的结果。",

                            "scene_name": "【面试场景名称】",
                            "resume": “【面试者简历信息】”,
                            "dialogue_text": "【面试对话内容】",
                            "Points_text": “【侧重考察点】”,
                            "response_format": "JSON",
                            "response_format_example": {
                            "score": 0,
                            "appraisal": "【侧重考察点评价】（需包含：1. 总体表现优缺点分析 2. 具体对话内容中的表现举例 3. 针对性改进建议） ",
                            "evaluation": "【综合评价】（需包含：1. 总体表现优缺点分析 2. 具体对话内容中的表现举例 3. 针对性改进建议）",
                            "result": “【录用推荐】（是否建议录用或进行二面）”
                            }
                        }
                    }'
                 ],
            [
                'role' => 'user',
                'content' => "对话记录:" . json_encode($qwenData, JSON_UNESCAPED_UNICODE)
            ]
        ];

        $response = \app\common\service\ToolsService::Interview()->chat([
            'action'    => 'qwen',
            'messages'  => $messages
        ]);
        if (empty($response['data']['message'])) {
            throw new \Exception('AI评分失败'.json_encode($response));
        }
        $result = format_json($response['data']['message']);
        if (empty($result['score']) || empty($result['evaluation'])) {
            throw new \Exception('AI评分失败~');
        }
        return $result;
    }
}