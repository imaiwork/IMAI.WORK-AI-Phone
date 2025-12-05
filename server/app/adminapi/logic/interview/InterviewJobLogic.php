<?php

namespace app\adminapi\logic\interview;

use app\adminapi\lists\interview\InterviewRecordLists;
use app\common\logic\BaseLogic;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewJob;
use app\common\service\ConfigService;

class InterviewJobLogic extends BaseLogic
{

    /**
     * @desc 新增岗位
     * @param array $params
     * @return true
     * @date 2025/2/13 15:45
     * @author dagouzi
     */
    public static function add(array $params)
    {
        $params['attention'] = json_encode($params['attention'], JSON_UNESCAPED_UNICODE);
        InterviewJob::create($params);
        return true;
    }


    /**
     * @notes  编辑资讯分类
     * @param array $params
     * @return bool
     * @author heshihu
     * @date 2022/2/21 17:50
     */
    public static function edit(array $params): bool
    {
        $params['attention'] = json_encode($params['attention']);
        try {
            InterviewJob::update($params, ['id' => $params['id']]);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 详情
     * @param string $id
     * @return bool
     * @author L
     * @data 2024/6/29 10:30
     */
    public static function detail(string $id): bool
    {
        try {
            $result =  InterviewJob::where('id', $id)->findOrEmpty()->toArray();

            if (empty($result)) {

                throw new \Exception('岗位不存在');
            }
            $result['attention'] = json_decode($result['attention']);

            self::$returnData = $result;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    

       /**
     * @notes 变更状态
     * @param int $jobId
     * @param int $status
     * @return bool
     */
    public static function changeStatus(int $jobId, int $status): bool
    {
        $interviewJob = InterviewJob::find($jobId);
        if (!$interviewJob) {
            throw new \Exception('面试工作记录不存在');
        }
        $interviewJob->status = $status;
        return $interviewJob->save();
    }

   /**
     * @notes 批量删除面试工作
     * @param  $jobIds
     * @return int
     */
    public static function deleteJobs($jobIds): int
    {
        try {
            // 确保传入的 ID 数组不为空
            if (empty($jobIds)) {
                throw new \Exception('没有提供要删除的面试工作 ID');
            }
            if(is_array($jobIds)){
                $exists = InterviewJob::whereIn('id',$jobIds)->value('id');
            }else{
                $exists = InterviewJob::where(['id'=>$jobIds])->value('id');
            }
            if (!$exists) {
                throw new \Exception('面试工作不存在');
            }
            // 批量删除
            $result = InterviewJob::destroy($jobIds);
            if ($result > 0) {
                return $result;// 删除成功
            } 
            throw new \Exception('删除失败');
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
