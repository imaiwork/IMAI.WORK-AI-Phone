<?php

namespace app\api\logic\sv;

use app\api\logic\device\TaskLogic;
use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvGroupBuyFilterHistory;
use app\common\model\sv\SvGroupBuyTask;
use app\common\model\sv\SvGroupBuyTaskAccount;
use think\facade\Db;

/**
 * GroupBuyLogic
 * @desc 团购截流任务逻辑层
 */
class GroupBuyLogic extends SvBaseLogic
{
    /**
     * @desc 添加团购截流任务
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            TaskLogic::checkAccounts($params['accounts']);

            $taskType = in_array('4',$params['marker_method']) ? 2 : 1;
            // 初始化主任务（草稿状态）
            $insertData = [
                'user_id'   => self::$uid,
                'task_type' => $taskType,
                'group_buy_type' => $params['group_buy_type'] ? 1 : 2,
                'name'      => $params['name'] ?? '',
                'status'    => 0,
            ];

            // 立即执行模式
            $isOverlap = (int)($params['task_exec_type'] ?? 0);
            if ($isOverlap === 1) {
                TaskLogic::updateTaskStatusByIds($params['task_ids']);
                $params['time_config'] = [
                    date('H:i', time()) . '-' . date('H:i', time() + 60 * (int)$params['minutes']),
                ];
                $params['task_date'] = [date('Y-m-d', time())];
                $params['task_frep'] = 1;
            }
            unset($params['task_ids']);

            // 创建主任务（草稿）
            $groupBuy = SvGroupBuyTask::create($insertData);
            $groupBuy = $groupBuy->refresh();

            // -------- 参数校验 & 序列化 --------

            // 评论词筛选
            if (isset($params['filter']) && is_array($params['filter'])) {
                $params['filter'] = json_encode($params['filter'], JSON_UNESCAPED_UNICODE);
            }

            // 昵称过滤词
            if (isset($params['nickname_filter']) && is_array($params['nickname_filter'])) {
                $params['nickname_filter'] = json_encode($params['nickname_filter'], JSON_UNESCAPED_UNICODE);
            }

            // 人设
            if (isset($params['persona_id'])) {
                $persona = AiPersona::where('id',$params['persona_id'])->where('user_id', self::$uid)->findOrEmpty();
                if ($persona->isEmpty()) {
                    self::setError('不存在该人设');
                    return false;
                }
            } else {
                self::setError('请选择人设');
                return false;
            }

            // 评论关键词
            if (isset($params['comment_keyword']) && is_array($params['comment_keyword'])) {
                $params['comment_keyword'] = json_encode($params['comment_keyword'], JSON_UNESCAPED_UNICODE);
            }

            // 账号列表
            if (isset($params['accounts']) && is_array($params['accounts'])) {
                foreach ($params['accounts'] as &$account) {
                    $account['device_code'] = SvAccount::where('id', $account['id'])->value('device_code');
                }
                unset($account);
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            } else {
                self::setError('账号不能为空');
                return false;
            }

            // 留痕方式
            if (isset($params['marker_method']) && is_array($params['marker_method'])) {
                $params['marker_method'] = json_encode($params['marker_method'], JSON_UNESCAPED_UNICODE);
            }

            // 团购专属字段校验
            if (isset($params['radius']) && $params['radius'] < 0) {
                self::setError('距离范围不合法');
                return false;
            }
            if (isset($params['watch_time']) && empty($params['watch_time'])) {
                self::setError('观看视频时长不能为空');
                return false;
            }
            if (isset($params['interval_time']) && empty($params['interval_time'])) {
                self::setError('触达间隔时间不能为空');
                return false;
            }
            if (isset($params['comment_offset']) && (int)$params['comment_offset'] < 0) {
                self::setError('评论起始位置不能为负数');
                return false;
            }

            // 任务频率
            $params['task_frequency'] = $params['task_frep'] ?? 1;
            unset($params['task_frep']);

            // 任务执行日期
            if (isset($params['task_date']) && is_array($params['task_date']) && !empty($params['task_date'])) {
                $params['task_date'] = json_encode($params['task_date'], JSON_UNESCAPED_UNICODE);
            } else {
                $taskDates = [];
                for ($i = 0; $i < $params['task_frequency']; $i++) {
                    $taskDates[] = date('Y-m-d', strtotime("+{$i} day"));
                }
                $params['task_date'] = json_encode($taskDates, JSON_UNESCAPED_UNICODE);
            }

            // 每日执行时间段
            if (!empty($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
                $params['status']      = 1; // 草稿 -> 待执行
            } else {
                self::setError('执行时间不能为空');
                return false;
            }
            if (isset($params['old']) && !empty($params['old'])) {
                $params['old'] = is_array($params['old']) ? json_encode($params['old'], JSON_UNESCAPED_UNICODE) : $params['old'];
            }else{
                $params['old'] = json_encode([
                    'min' => 18,
                    'max' => 30,
                ], JSON_UNESCAPED_UNICODE);
            }
            //print_r($params);die;
            // 更新主任务完整数据
            SvGroupBuyTask::where('id', $groupBuy->id)->update($params);
            $result = $groupBuy->refresh()->toArray();

            // -------- 创建子任务（账号 × 时间段） --------
            if ($result['status'] == 1) {
                $accountTask = SvGroupBuyTaskAccount::where('group_buy_id', $result['id'])->findOrEmpty();
                if (!$accountTask->isEmpty()) {
                    throw new \Exception('任务已创建');
                }

                // 展开所有执行时间段
                $times = [];
                foreach ($result['task_date'] as $date) {
                    $date = date('Y-m-d', strtotime($date));
                    foreach (json_decode($result['time_config'], true) as $time) {
                        [$startHi, $endHi] = explode('-', $time);
                        $times[] = [
                            'start_time' => strtotime($date . ' ' . $startHi . ':00'),
                            'end_time'   => strtotime($date . ' ' . $endHi . ':00'),
                        ];
                    }
                }

                $allTaskInstall = [];
                foreach (json_decode($result['accounts'], true) as $account) {
                    $find    = SvAccount::where('account', $account['account'])
                                        ->where('type', $account['type'])
                                        ->where('user_id', self::$uid)
                                        ->limit(1)
                                        ->find()
                                        ->toArray();
                    $account = array_merge($account, $find);

                    foreach ($times as $time) {
                        // 非立即执行模式检查时间冲突
                        if ($isOverlap === 0) {
                            [$isTimeOk, $lap] = TaskLogic::isTaskTimeOverlapping(
                                $account['device_code'],
                                DeviceEnum::TASK_TYPE_GROUP_BUY,
                                $time['start_time'],
                                $time['end_time'],
                                self::$uid
                            );
                            if (!$isTimeOk) {
                                $timeMsg = '【' . date('Y-m-d H:i', $lap['start_time'])
                                           . '-' . date('Y-m-d H:i', $lap['end_time']) . '】';
                                $msg     = "您在{$timeMsg}的【"
                                           . DeviceEnum::getAccountTypeDesc($lap['account_type'])
                                           . DeviceEnum::getTaskTypeDesc($lap['task_type'])
                                           . '】与当前所选时间冲突';
                                throw new \Exception($msg);
                            }
                        }

                        $startTime = $time['start_time'];
                        $endTime   = $time['end_time'];
                        $taskName = $result['name'] . ' - ' . self::formatType((int)$account['type']);

                        $subTask = SvGroupBuyTaskAccount::create([
                                                      'group_buy_id'    => $result['id'],
                                                      'user_id'         => self::$uid,
                                                      'task_type'       => $result['task_type'],
                                                      'status'          => 0,
                                                      'name'            => $taskName,
                                                      'account'         => $account['account'],
                                                      'account_type'    => $account['type'],
                                                      'nickname'        => $account['nickname'] ?? '',
                                                      'avatar'          => $account['avatar'] ?? '',
                                                      'device_code'     => $account['device_code'],
                                                      'send_start_time' => $time['start_time'],
                                                      'send_end_time'   => $time['end_time'],
                                                      'persona_id'      => $result['persona_id'] ?? 0,
                                                  ]);

                        // 汇总到设备任务调度
                        $allTaskInstall[] = [
                            'user_id'      => self::$uid,
                            'device_code'  => $account['device_code'],
                            'task_type'    => DeviceEnum::TASK_TYPE_GROUP_BUY,
                            'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_GROUP_BUY,
                            'account'      => $account['account'],
                            'account_type' => $account['type'],
                            'nickname'     => $account['nickname'],
                            'avatar'       => $account['avatar'],
                            'task_name'    => $taskName,
                            'status'       => 0,
                            'day'          => date('Y-m-d', $startTime),
                            'time_config'  => $params['time_config'],
                            'start_time'   => $startTime,
                            'end_time'     => $endTime,
                            'sub_task_id'  => $subTask->id,
                            'source'       => DeviceEnum::TASK_SOURCE_GROUP_BUY,
                            'create_time'  => time(),
                        ];

                        TaskLogic::updateWechatRpaTaskTime($account['device_code'], $startTime);
                    }
                }

                TaskLogic::add($allTaskInstall);
            }

            // 保存筛选词历史
            self::saveFilterHistory(self::$uid, $params);

            Db::commit();
            // 格式化返回数据
            $result['nickname_filter'] = !empty($result['nickname_filter']) ? $result['nickname_filter'] : [];
            $result['comment_keyword'] = !empty($result['comment_keyword']) ? $result['comment_keyword'] : [];
            $result['marker_method']   = !empty($result['marker_method']) ? (is_array($result['marker_method']) ? $result['marker_method'] : json_decode($result['marker_method'], true)) : [];
            $result['accounts']        = !empty($result['accounts']) ? json_decode($result['accounts'], true) : [];
            $result['time_config']     = !empty($result['time_config']) ? json_decode($result['time_config'], true) : [];
            $result['task_date']       = !empty($result['task_date']) ? $result['task_date'] : [];

            self::$returnData = $result;
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑团购截流任务
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $task = SvGroupBuyTask::where('id', $params['id'])
                                  ->where('user_id', self::$uid)
                                  ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            if (in_array($task->status, [2, 3])) {
                throw new \Exception('执行中或已完成的任务不可编辑');
            }

            if (isset($params['filter']) && is_array($params['filter'])) {
                $params['filter'] = json_encode($params['filter'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['nickname_filter']) && is_array($params['nickname_filter'])) {
                $params['nickname_filter'] = json_encode($params['nickname_filter'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['comment_keyword']) && is_array($params['comment_keyword'])) {
                $params['comment_keyword'] = json_encode($params['comment_keyword'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['marker_method']) && is_array($params['marker_method'])) {
                $params['marker_method'] = json_encode($params['marker_method'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['task_date']) && is_array($params['task_date'])) {
                $params['task_date'] = json_encode($params['task_date'], JSON_UNESCAPED_UNICODE);
            }

            SvGroupBuyTask::where('id', $params['id'])->update($params);

            Db::commit();
            self::$returnData = ['id' => $params['id']];
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除任务（软删除）
     */
    public static function delete(array $params): bool
    {
        try {
            $task = SvGroupBuyTask::where('id', $params['id'])
                                  ->where('user_id', self::$uid)
                                  ->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            SvGroupBuyTask::destroy($params['id']);
            SvGroupBuyTaskAccount::where('group_buy_id', $params['id'])
                             ->useSoftDelete('delete_time', time())
                             ->delete();
            return true;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新子任务状态（暂停 / 恢复）
     */
    public static function updateStatus(array $params): bool
    {
        try {
            $account = SvGroupBuyTaskAccount::where('id', $params['id'])
                                        ->where('user_id', self::$uid)
                                        ->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('子任务不存在');
                return false;
            }
            // 4=暂停中 -> 恢复为1=运行中，其他状态 -> 暂停为4
            $newStatus = $account->status == 4 ? 1 : 4;
            SvGroupBuyTaskAccount::where('id', $params['id'])->update(['status' => $newStatus]);
            return true;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取任务详情
     */
    public static function detail(array $params): array|false
    {
        try {
            $task = SvGroupBuyTask::where('id', $params['id'])
                                  ->where('user_id', self::$uid)
                                  ->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            $data             = $task->toArray();
            $data['accounts'] = json_decode($data['accounts'] ?? '[]', true);
            $data['marker_method']   = !empty($data['marker_method']) ? (is_array($data['marker_method']) ? $data['marker_method'] : json_decode($data['marker_method'], true)) : [];
            $data['comment_keyword'] = !empty($data['comment_keyword']) ? json_decode($data['comment_keyword'], true) : [];
            $data['nickname_filter'] = json_decode($data['nickname_filter'] ?? '[]', true);
            $data['filter'] = json_decode($data['filter'] ?? '[]', true);
            $data['time_config']   = json_decode($data['time_config'] ?? '[]', true);
            $data['task_date']     = json_decode($data['task_date'] ?? '[]', true);

            // 子任务列表
            $data['sub_tasks'] = SvGroupBuyTaskAccount::where('group_buy_id', $params['id'])
                                                  ->where('user_id', self::$uid)
                                                  ->select()
                                                  ->toArray();
            return $data;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取筛选词历史
     */
    public static function getFilterHistory(): bool
    {
        try {
            $result = SvGroupBuyFilterHistory::where('user_id', self::$uid)
                ->order('id', 'desc')
                ->limit(1)
                ->findOrEmpty();

            if ($result->isEmpty()) {
                self::$returnData = [
                    'filter'          => [],
                    'nickname_filter' => [],
                    'number'          => 1,
                ];
            } else {
                self::$returnData                    = $result->toArray();
                self::$returnData['filter']          = !empty($result->filter)
                    ? (is_string($result->filter) ? json_decode($result->filter, true) : $result->filter)
                    : [];
                self::$returnData['nickname_filter'] = !empty($result->nickname_filter)
                    ? (is_string($result->filter) ? json_decode($result->nickname_filter, true) : $result->nickname_filter)
                    : [];
            }

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 保存筛选词历史（去重累加）
     */
    protected static function saveFilterHistory(int $userId, array $params): void
    {
        $filter         = $params['filter'] ?? '[]';
        $nicknameFilter = $params['nickname_filter'] ?? '[]';
        $commentKeyword = $params['comment_keyword'] ?? '[]';

        $existing = SvGroupBuyFilterHistory::where('user_id', $userId)->findOrEmpty();
        if ($existing->isEmpty()) {
            SvGroupBuyFilterHistory::create([
                                                'user_id'         => $userId,
                                                'filter'          => $filter,
                                                'nickname_filter' => $nicknameFilter,
                                                'comment_keyword' => $commentKeyword,
                                                'number'          => 1,
                                            ]);
        } else {
            // 合并去重
            $mergedFilter   = array_unique(array_merge(
                                               json_decode($existing->filter ?? '[]', true),
                                               json_decode($filter, true)
                                           ));
            $mergedNickname = array_unique(array_merge(
                                               json_decode($existing->nickname_filter ?? '[]', true),
                                               json_decode($nicknameFilter, true)
                                           ));
            $mergedKeyword  = array_unique(array_merge(
                                               json_decode($existing->comment_keyword ?? '[]', true),
                                               json_decode($commentKeyword, true)
                                           ));
            $existing->filter          = json_encode(array_values($mergedFilter), JSON_UNESCAPED_UNICODE);
            $existing->nickname_filter = json_encode(array_values($mergedNickname), JSON_UNESCAPED_UNICODE);
            $existing->comment_keyword = json_encode(array_values($mergedKeyword), JSON_UNESCAPED_UNICODE);
            $existing->number          += 1;
            $existing->save();
        }
    }

    private static function formatType($type): string
    {
        return match ($type) {
            3       => '小红书',
            4       => '抖音',
            5       => '快手',
            default => '未知',
        };
    }
}