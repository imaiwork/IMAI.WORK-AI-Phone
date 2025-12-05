<?php

namespace app\api\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewCv;
use app\common\model\interview\InterviewDialog;
use app\common\model\interview\InterviewJob;
use app\common\model\interview\InterviewRecord;
use app\common\service\wechat\WeChatUrllinkService;

class InterviewLogic extends BaseLogic
{
    /**
     * @desc 统计
     * @return void
     * @date 2025/2/25 14:55
     * @author dagouzi
     */
    public static function stat(array $params)
    {
        $jobCount = InterviewJob::where('user_id', $params['user_id'])->count();

        $jobIds = InterviewJob::where('user_id', $params['user_id'])->column('id');
        $duration = InterviewRecord::where('job_id', 'in', $jobIds)->column('duration');
        $interviewCount = count($duration);
        if ($interviewCount == 0){
            $avgTime = 0;
        } else {
            $duration = array_sum($duration);
            $minutes = floor( $duration / 60); // 计算分钟
            $avgTime = floor($minutes / $interviewCount);
        }

        self::$returnData = [
            'job_count' => $jobCount,
            'avg_time' => $avgTime,
            'interview_count' => $interviewCount,
        ];
        return true;
    }
    public static function detail($id)
    {
        try {

            $interviewRecord = InterviewRecord::where('id', $id)->findOrEmpty()->toArray();
            if (empty($interviewRecord)) {
                throw new \Exception('面试记录不存在');
            }

            $InterviewIds = Interview::where('interview_record_id', $interviewRecord['id'])->column('id');
            if (empty($InterviewIds)) {
                throw new \Exception('面试会话不存在');
            }

            $cv = InterviewCv::where('user_id', $interviewRecord['user_id'])->where('interview_job_id',$interviewRecord['job_id'])->findOrEmpty()->toArray();
            if (empty($cv)) {
                throw new \Exception('简历不存在');
            }
            $result['cv'] = $cv;

            $Interview = Interview::where(['interview_record_id' => $interviewRecord['id'],'status' => 1])
            ->findOrEmpty()->toArray();
            $comment = '';
            $analyze = '';
            $inspection_point = '';
            if (!empty($Interview)) {
                $comment = $Interview['comment'];
                $analyze = $Interview['analyze'];
                $inspection_point = $Interview['inspection_point'];
            }

            $alltime = $interviewRecord['duration'];
            $hours = floor($alltime / 3600); // 计算小时
            $minutes = floor(($alltime % 3600) / 60); // 计算分钟
            $seconds = $alltime % 60; // 计算秒数
            // 格式化为 "时:分:秒"
            $formattedTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
            $result['ai'] = [
                'job_name' => $interviewRecord['job_name'],
                'duration' => $formattedTime,
                'times' =>Interview::where(['interview_record_id' =>$id,'status' => Interview::STATUS_RESTART])->count(),
                'first_start_time' => $interviewRecord['first_start_time'],
                'score' => $interviewRecord['best_score'],
                'comment' => $comment,
                'analyze' => $analyze,
                'inspection_point' => $inspection_point,
            ];
            $dialogs = InterviewDialog::whereIn('interview_id', $InterviewIds)->select()->toArray();
          
            // 初始化结果数组
            $newArr = [];

            // 按 interview_id 分组并构建结果
            foreach ($dialogs as $dialog) {
                $interviewId = $dialog['interview_id'];

                // 初始化分组
                if (!isset($newArr[$interviewId])) {
                    $newArr[$interviewId] = [
                        'interview_dialog' => null,
                        'interview_id' => $interviewId,
                        'type' => null,
                        'out_reason' => null,
                        'list' => []
                    ];
                }
                // 将对话记录添加到对应的组
                $newArr[$interviewId]['list'][] = $dialog;
                // 更新最后一个对话信息
                $newArr[$interviewId]['interview_dialog'] = $dialog['id'];
                $newArr[$interviewId]['type'] = $dialog['type'];
                $newArr[$interviewId]['out_reason'] = $dialog['out_reason'];
            
                if(!empty($dialog['restart_reason'])){
                    $newArr[$interviewId]['out_reason'] = $dialog['restart_reason'];
                }
            }
           
            // 处理结果，提取最后一个对话
            foreach ($newArr as &$group) {
                // 提取最后一个对话
                $lastDialog = end($group['list']); // 获取最后一个对话
                $group['interview_dialog'] = $lastDialog['id'];
                $group['type'] = $lastDialog['type'];
                $group['out_reason'] = $lastDialog['out_reason'];
                if(!empty($lastDialog['restart_reason'])){
                    $group['out_reason'] = $lastDialog['restart_reason'];
                }
            }
           
            // 重新索引数组
            $newArr = array_values($newArr);
            $result['dialogs'] = array_reverse($newArr);
            self::$returnData = $result;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
    
    /**
     * @desc 岗位链接
     * @param array $params
     * @return array
     * @date 2025/2/14 10:42
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public static function jobLink(array $params): array
    {
        $path = '/ai_modules/interview/pages/index/index';
       // $path = '/pages/index/index';
        $query = 'user_id=' . $params['user_id'] . '&job_id=' . $params['job_id'];
         $wechatService = new WeChatUrllinkService();
         $result = $wechatService->urlLink($path, $query);
         return ['url' => $result['url_link'] ?? ''];
    }

    /**
     * @desc 我的岗位链接
     * @param array $params
     * @return array
     * @date 2025/2/14 10:58
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @author dagouzi
     */
    public static function myJobLink(array $params): array
    {
        $path = '/ai_modules/interview/pages/index/index';
        //$path = '/pages/index/index';
        $query = 'user_id=' . $params['user_id'];

        $wechatService = new WeChatUrllinkService();
        $result = $wechatService->urlLink($path, $query);

        return ['url' => $result['url_link'] ?? ''];
    }
}