<?php

namespace app\api\logic\interview;

use app\common\logic\BaseLogic;
use app\common\model\interview\InterviewDialog;
use app\common\model\interview\Interview;
use app\common\model\interview\InterviewRecord;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use Exception;

/**
 * 面试对话记录逻辑层
 * Class InterviewDialogLogic
 * @package app\api\logic\interview
 */
class InterviewDialogLogic extends BaseLogic
{
    // 用户ID
    protected static $userId = 0;

    /**
     * 设置用户ID
     * @param int $userId
     */
    public static function setUserId(int $userId)
    {
        self::$userId = $userId;
    }

    /**
     * 验证面试权限
     * @param int $interviewId
     * @return bool
     */
    protected static function verifyPermission(int $interviewId): bool
    {
        $interview = Interview::where('id', $interviewId)->find();
        if (!$interview) {
            self::setError('面试不存在');
            return false;
        }

        if ($interview->user_id != self::$userId) {
            self::setError('无权限操作该面试的对话记录');
            return false;
        }

        return true;
    }

    /**
     * @desc 获取详情
     * @param int $id
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function detail(int $id): bool
    {
        try {
            $dialog = InterviewDialog::where('id', $id)->findOrEmpty();
            if ($dialog->isEmpty()) {
                self::setError('对话记录不存在');
                return false;
            }

            // 验证权限
            if (!self::verifyPermission($dialog->interview_id)) {
                return false;
            }

            $data = $dialog->toArray();
            $data['type_text'] = InterviewDialog::getTypeText($data['type']);

            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError('获取失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @desc 根据面试ID获取对话记录
     * @param int $interviewId
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getByInterviewId(int $interviewId): bool
    {
        if (empty($interviewId)) {
            self::setError('参数错误');
            return false;
        }

        $list = InterviewDialog::where('interview_id', $interviewId)
            ->order('create_time', 'asc')
            ->select()
            ->toArray();

        // 处理数据
        foreach ($list as &$item) {
            $item['type_text'] = self::getTypeText($item['type']);
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        self::$returnData = $list;
        return true;
    }

    /**
     * @desc 统计数据
     * @param array $params
     * @return bool
     * @throws DbException
     */
    public static function stat(array $params): bool
    {
        if (empty($params['interview_id'])) {
            self::setError('参数错误');
            return false;
        }

        $interviewId = $params['interview_id'];

        // 统计各类型问题数量
        $typeCounts = InterviewDialog::where('interview_id', $interviewId)
            ->group('type')
            ->column('count(id)', 'type');

        // 获取最后一次对话时间
        $lastDialog = InterviewDialog::where('interview_id', $interviewId)
            ->order('create_time', 'desc')
            ->find();

        $totalCount = InterviewDialog::where('interview_id', $interviewId)->count();

        $data = [
            'total_count' => $totalCount,
            'focus_question_count' => $typeCounts[1] ?? 0,
            'deep_question_count' => $typeCounts[2] ?? 0,
            'normal_question_count' => $typeCounts[3] ?? 0,
            'opening_count' => $typeCounts[4] ?? 0,
            'last_dialog_time' => $lastDialog ? date('Y-m-d H:i:s', $lastDialog['create_time']) : '',
        ];

        self::$returnData = $data;
        return true;
    }

    /**
     * @desc 获取类型文本
     * @param int $type
     * @return string
     */
    private static function getTypeText(int $type): string
    {
        $typeMap = [
            1 => '带关注的问题',
            2 => '深入的问题',
            3 => '不带关注的问题',
            4 => '开场白'
        ];

        return $typeMap[$type] ?? '未知类型';
    }

    /**
     * 添加对话记录
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            // 验证权限
            if (!self::verifyPermission($params['interview_id'])) {
                return false;
            }

            $params['create_time'] = $params['update_time'] = time();
            $dialog = InterviewDialog::create($params);

            Db::commit();
            self::$returnData = $dialog->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('添加失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新对话记录
     * @param array $params
     * @return bool
     */
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $dialog = InterviewDialog::where('id', $params['id'])->findOrEmpty();
            if ($dialog->isEmpty()) {
                self::setError('对话记录不存在');
                return false;
            }

            // 验证权限
            if (!self::verifyPermission($dialog->interview_id)) {
                return false;
            }

            $params['update_time'] = time();
            $dialog->save($params);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('更新失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除对话记录
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $dialog = InterviewDialog::where('id', $params['id'])->findOrEmpty();
            if ($dialog->isEmpty()) {
                self::setError('对话记录不存在');
                return false;
            }

            // 验证权限
            if (!self::verifyPermission($dialog->interview_id)) {
                return false;
            }

            $dialog->delete_time = time();
            $dialog->save();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError('删除失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 处理面试结束（退出或中断）
     * @param array $params
     * @param int $endType 结束类型：1-主动退出，2-意外中断，3-重新开始
     * @return bool
     */
    public static function endInterview(array $params, int $endType = 1): bool
    {
        Db::startTrans();
        try {
            $userId = $params['user_id'];
            $interviewId = $params['interview_id'];

            // 根据结束类型确定原因字段和消息
            if ($endType == 1) {
                // 主动退出
                $reason = $params['reason'] ?? '用户主动退出面试';
                $statusCode = Interview::STATUS_EXITED;
                $dialogType = InterviewDialog::TYPE_OPENING;
                $reasonField = 'out_reason';
                $endMessage = '好的，大致情况我已经了解，本轮面试已结束，感谢您的配合，请提交面试过程并耐心等待通知。';
            } elseif ($endType == 2) {
                // 意外中断
                $reason = $params['reason'] ?? '聊天意外中断';
                $endMessage = '很抱歉，本次面试由于意外中断。您可以选择重新开始面试或稍后再试。';
                $statusCode = Interview::STATUS_INTERRUPTED;
                $dialogType = InterviewDialog::TYPE_OPENING;
                $reasonField = 'out_reason'; // 使用相同字段存储
            } else {
                // 重新开始
                $reason = $params['reason'] ?? '用户重新开始面试';
                $statusCode = Interview::STATUS_RESTART;
                $dialogType = InterviewDialog::TYPE_OPENING;
                $reasonField = 'restart_reason';
                $endMessage = '好的，大致情况我已经了解，本轮面试已结束，感谢您的配合，请提交面试过程并耐心等待通知。'; // 确保定义了 $endMessage
            }

            // 检查面试是否存在
            $interview = Interview::where(['id' => $interviewId, 'user_id' => $userId])
                ->findOrEmpty();

            $returnData = [
                'id' => '',
                'status' => '',
                'end_message' => $endMessage
            ];

            if ($interview->isEmpty()) {
                self::$returnData = $returnData;
                return true;
            }
           
            $returnData = [
                'id' => $interviewId,
                'status' => $interview->status,
                'end_message' => $endMessage
            ];

            if ($interview->status != 0) {
                self::$returnData = $returnData;
                return true;
            }

            // 检查面试记录
            $interviewRecord = InterviewRecord::where(['id' => $interview->interview_record_id, 'user_id' => $userId])
                ->findOrEmpty();

            if ($interviewRecord->isEmpty()) {
                self::$returnData = $returnData;
                return true;
            }

            if ($interviewRecord->status == 1) {
                self::$returnData = $returnData;
                return true;
            }

            // 更新面试状态
            $interview->status = $statusCode;
            $interview->reason = $reason;
            $interview->end_time = time();
            $interview->save();

            $time = time();
            $duration = $time - $interviewRecord->first_start_time;
            // 更新面试记录
            $interviewRecord->reason = $reason;
            $interviewRecord->status = $statusCode;
            $interviewRecord->last_interview_id = $interviewId;
            $interviewRecord->last_end_time =  $time;
            $interviewRecord->duration = $duration;
            $interviewRecord->save();

            $curDialog = InterviewDialog::where(['interview_id' => $interviewId])
                ->order('id', 'desc')
                ->findOrEmpty();
           
            if (!$curDialog->isEmpty()) {
                $curDialog->out_reason = $reason;

                if ($endType == 3) {
                    $curDialog->restart_reason = $reason;
                }
                $curDialog->save();
            } else {
                InterviewDialog::create([
                    'interview_id' => $interviewId,
                    'type' => $dialogType,
                    'question' => $endMessage,
                    $reasonField => $reason,
                    'create_time' => time(),
                    'update_time' => time()
                ]);
            }

            Db::commit();
            // 准备返回数据
           
            // 如果是意外中断，添加可重新开始标记
            if ($endType == 2) {
                $returnData['can_restart'] = true;
            }
            self::$returnData = $returnData;

            return true;
        } catch (Exception $e) {
            Db::rollback();

            // 根据结束类型设置不同的错误码
            if ($endType == 1) {
                self::setError('退出失败：' . $e->getMessage());
            } elseif ($endType == 2) {
                self::setError('处理中断失败：' . $e->getMessage());
            } else {
                self::setError('重新开始失败：' . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * 创建新面试会话
     * @param array $params ['user_id', 'job_id']
     * @return bool
     */
    public static function createNewInterview(array $params): bool
    {
        Db::startTrans();
        try {
            $userId = $params['user_id'];
            $jobId = $params['job_id'];

            // 查找该用户该岗位的面试记录
            $interviewRecord = InterviewRecord::where(['user_id' => $userId, 'job_id' => $jobId])
                ->findOrEmpty();

            // 如果没有记录，创建新记录
            if ($interviewRecord->isEmpty()) {
                $interviewRecord = InterviewRecord::create([
                    'user_id' => $userId,
                    'job_id' => $jobId,
                    'first_start_time' => time(),
                    'status' => InterviewRecord::STATUS_ONGOING,
                    'total_sessions' => 0
                ]);
            }

            // 计算当前会话序号
            $sessionNumber = $interviewRecord->total_sessions + 1;

            // 创建新的面试会话
            $interview = Interview::create([
                'user_id' => $userId,
                'interview_record_id' => $interviewRecord->id,
                'job_id' => $jobId,
                'start_time' => time(),
                'status' => Interview::STATUS_ONGOING,
                'session_number' => $sessionNumber
            ]);

            // 更新面试记录的总会话数
            $interviewRecord->total_sessions = $sessionNumber;
            $interviewRecord->last_interview_id = $interview->id;
            $interviewRecord->save();

            // 创建开场白对话
            $openingMessage = "欢迎参加面试，这是您第 {$sessionNumber} 次面试会话。请准备好开始回答问题。";
            InterviewDialog::create([
                'interview_id' => $interview->id,
                'type' => InterviewDialog::TYPE_OPENING,
                'question' => $openingMessage,
                'sequence' => 1
            ]);

            Db::commit();

            self::$returnData = [
                'interview_id' => $interview->id,
                'record_id' => $interviewRecord->id,
                'session_number' => $sessionNumber,
                'opening_message' => $openingMessage
            ];

            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError('创建面试失败：' . $e->getMessage());
            return false;
        }
    }

    public static function getDialogByInterviewId(int $interviewId, int $userId)
    {

        $id = Interview::where(['id' => $interviewId, 'user_id' => $userId])->findOrEmpty();
        if ($id->isEmpty()) {
            self::setError('面试不存在');
            return false;
        }
        try {
            // 查询对话记录
            $dialogs = InterviewDialog::where('interview_id', $interviewId)
                ->order('create_time', 'asc') // 按时间升序排列
                ->select()
                ->toArray();
            self::$returnData = $dialogs;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
