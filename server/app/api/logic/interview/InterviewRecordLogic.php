<?php

namespace app\api\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\InterviewRecord;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewJob;
use think\facade\Db;
use Exception;
use think\facade\Queue;

/**
 * 面试记录逻辑层
 * Class InterviewRecordLogic
 * @package app\api\logic\interview
 */
class InterviewRecordLogic extends BaseLogic
{
    /**
     * 获取面试记录列表
     * @param array $params 查询参数
     * @return bool
     */
    public static function getList(array $params): bool
    {
        try {
            $userId = $params['user_id'] ?? 0;
            $page = $params['page'] ?? 1;
            $pageSize = $params['page_size'] ?? 10;
            
            $query = InterviewRecord::where('user_id', $userId);
            
            // 按岗位ID筛选
            if (!empty($params['job_id'])) {
                $query->where('job_id', $params['job_id']);
            }
            
            // 按状态筛选
            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', $params['status']);
            }
            
            // 按创建时间排序
            $query->order('create_time', 'desc');
            
            // 分页查询
            $list = $query->page($page, $pageSize)->select()->toArray();
            $total = $query->count();
            
            // 处理状态文本
            foreach ($list as &$item) {
                $item['status_text'] = InterviewRecord::getStatusText($item['status']);
                $item['work_years_text'] = InterviewRecord::getWorkYearsText($item['work_years']);
                
                // 转换时间戳为日期
                $item['first_start_time_text'] = !empty($item['first_start_time']) ? 
                    date('Y-m-d H:i:s', $item['first_start_time']) : '';
                    
                $item['last_end_time_text'] = !empty($item['last_end_time']) ? 
                    date('Y-m-d H:i:s', $item['last_end_time']) : '';
            }
            
            self::$returnData = [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ];
            
            return true;
        } catch (Exception $e) {
            throw new \Exception('获取面试记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取面试记录详情
     * @param array $params 查询参数
     * @return bool
     */
    public static function detail(array $params): bool
    {
        try {
            $id = $params['id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            
            // 查询记录
            $record = InterviewRecord::where(['id' => $id])->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('面试记录不存在');
            }
            
            // 权限检查
            if ($record->user_id != $userId) {
                throw new \Exception('没有权限');
            }
            
            // 获取关联的面试会话
            $interviews = Interview::where('interview_record_id', $id)
                ->order('id', 'desc')
                ->select()
                ->toArray();
            
            // 处理数据
            $data = $record->toArray();
            $data['status_text'] = InterviewRecord::getStatusText($data['status']);
            $data['work_years_text'] = InterviewRecord::getWorkYearsText($data['work_years']);
            
            // 转换时间戳为日期
            $data['first_start_time_text'] = !empty($data['first_start_time']) ? 
                date('Y-m-d H:i:s', $data['first_start_time']) : '';
                
            $data['last_end_time_text'] = !empty($data['last_end_time']) ? 
                date('Y-m-d H:i:s', $data['last_end_time']) : '';
                
            $data['interviews'] = $interviews;
            
            self::$returnData = $data;
            return true;
        } catch (Exception $e) {
            throw new \Exception('获取面试记录详情失败：' . $e->getMessage());
        }
    }
    
    /**
     * 添加面试记录
     * @param array $params 记录数据
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $userId = $params['user_id'] ?? 0;
            $jobId = $params['job_id'] ?? 0;
            
            // 检查参数
            if (empty($userId) || empty($jobId)) {
                throw new \Exception('用户ID和岗位ID不能为空');
                return false;
            }
            
            // 检查是否已存在记录
            $exists = InterviewRecord::where(['user_id' => $userId, 'job_id' => $jobId])->findOrEmpty();
            if (!$exists->isEmpty()) {
                throw new \Exception('该用户已有此岗位的面试记录');
            }
            
            // 创建记录
            $record = new InterviewRecord;
            $record->user_id = $userId;
            $record->job_id = $jobId;
            $record->first_start_time = $params['first_start_time'] ?? time();
            $record->degree = $params['degree'] ?? '';
            $record->work_years = $params['work_years'] ?? 0;
            $record->status = InterviewRecord::STATUS_ONGOING;
            $record->save();
            
            Db::commit();
            self::$returnData = $record->toArray();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new \Exception('添加面试记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 更新面试记录
     * @param array $params 更新数据
     * @return bool
     */
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $id = $params['id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            
            // 查询记录
            $record = InterviewRecord::where(['id' => $id])->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('面试记录不存在');
            }
            
            // 权限检查
            if ($record->user_id != $userId) {
                throw new \Exception('没有权限');
            }
            
            // 更新记录
            $allowFields = ['degree', 'work_years', 'status'];
            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $record->$field = $params[$field];
                }
            }
            
            $record->save();
            
            Db::commit();
            self::$returnData = $record->toArray();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new \Exception('更新面试记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 删除面试记录
     * @param array $params 删除参数
     * @return bool
     */
    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $id = $params['id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            
            // 查询记录
            $record = InterviewRecord::where(['id' => $id])->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('面试记录不存在');
            }
            
            // 权限检查
            if ($record->user_id != $userId) {
                throw new \Exception('没有权限');
            }
            
            // 删除关联的面试会话
            Interview::where('interview_record_id', $id)->delete();
            
            // 删除记录
            $record->delete();
            
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new \Exception('删除面试记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 更新面试记录状态
     * @param array $params 更新参数
     * @return bool
     */
    public static function updateStatus(array $params): bool
    {
        Db::startTrans();
        try {
            $id = $params['id'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            
            // 查询记录
            $record = InterviewRecord::where(['id' => $id,'status'=>InterviewRecord::STATUS_AI_ERROR])->findOrEmpty();
            if ($record->isEmpty()) {
                throw new \Exception('面试记录不存在');
            }
            
             // 查询记录
             $interview = Interview::where(['id' => $record->last_interview_id,'status'=>InterviewRecord::STATUS_AI_ERROR])->findOrEmpty();
             if ($interview->isEmpty()) {
                 throw new \Exception('面试不存在');
             }

            // 权限检查
            $job = InterviewJob::where(['id'=>$interview->job_id])->findOrEmpty();
            if ($job->isEmpty()) {
                throw new \Exception('岗位不存在');
            }

            if($job->user_id != $userId){
                throw new \Exception('非法操作');
            }
            Queue::push('app\common\Jobs\EndInterviewJob@handle',  $interview->id);
            
         
            // 更新状态
            $record->status = InterviewRecord::STATUS_ANALYZE;    
            $record->save();

            $interview->status = InterviewRecord::STATUS_ANALYZE;    
            $interview->save();

            Db::commit();
            self::$returnData = $record->toArray();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new \Exception('更新面试记录状态失败：' . $e->getMessage());
        }
    }

    /**
     * 批量删除面试记录
     * @param array $ids 要删除的记录ID数组
     * @param int $userId 用户ID
     * @return bool
     */
    public static function batchDelete(array $ids, int $userId): bool
    {

        $jobIds = InterviewJob::where('user_id', $userId)->column('id');
        if(empty($jobIds)){
            throw new \Exception('没有面试岗位');
        }

        Db::startTrans();
        try {
            // 查询要删除的记录
            $records = InterviewRecord::whereIn('id', $ids)
                ->where('job_id', 'in', $jobIds)
                ->column('id');

            if (empty($records)) {
                throw new \Exception('没有面试记录');
            }

            // 批量删除记录
            InterviewRecord::destroy($records);

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new \Exception('批量删除面试记录失败：' . $e->getMessage());
        }
    }
} 