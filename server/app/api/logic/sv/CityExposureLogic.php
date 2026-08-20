<?php

namespace app\api\logic\sv;

use app\api\logic\device\TaskLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCityExposureTask;
use app\common\model\sv\SvCityExposureTaskAccount;
use think\facade\Db;

/**
 * CityExposureLogic
 * @desc 同城曝光任务逻辑层
 */
class CityExposureLogic extends SvBaseLogic
{
    /**
     * @desc 添加同城曝光任务
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            TaskLogic::checkAccounts($params['accounts']);

            // 立即执行模式处理
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
            $task = SvCityExposureTask::create([
                                                   'user_id'   => self::$uid,
                                                   'task_type' => $params['task_type'] ?? 3,
                                                   'name'      => $params['name'] ?? '',
                                                   'status'    => 0,
                                               ]);
            $task = $task->refresh();

            // -------- 参数序列化 & 校验 --------

            // 账号列表
            if (isset($params['accounts']) && is_array($params['accounts'])) {
                foreach ($params['accounts'] as &$account) {
                    $account['device_code'] = SvAccount::where('id', $account['id'])->value('device_code');
                }
                unset($account);
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('账号不能为空');
            }

            // 业务参数校验
            if (isset($params['radius']) && (int)$params['radius'] < 0) {
                throw new \Exception('距离范围不合法');
            }
            if (isset($params['interval_time']) && (int)$params['interval_time'] < 1) {
                throw new \Exception('触达间隔时间不能小于1秒');
            }
            if (isset($params['visit_num']) && (int)$params['visit_num'] < 1) {
                throw new \Exception('访问数不能小于1');
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
                throw new \Exception('执行时间不能为空');
            }

            // 更新主任务完整数据
            SvCityExposureTask::where('id', $task->id)->update($params);
            $result = $task->refresh()->toArray();

            // -------- 创建子任务（账号 × 时间段） --------
            if ($result['status'] == 1) {
                $exists = SvCityExposureTaskAccount::where('city_exposure_id', $result['id'])->findOrEmpty();
                if (!$exists->isEmpty()) {
                    throw new \Exception('任务已创建');
                }

                // 展开所有执行时间段
                $times = [];
                foreach ($result['task_date'] as $date) {
                    $date = date('Y-m-d', strtotime($date));
                    foreach (json_decode($result['time_config'], true) as $timeRange) {
                        [$startHi, $endHi] = explode('-', $timeRange);
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
                                DeviceEnum::TASK_TYPE_SAME_CITY_EXPOSURE,
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

                        $subTask = SvCityExposureTaskAccount::create([
                                                              'city_exposure_id' => $result['id'],
                                                              'user_id'          => self::$uid,
                                                              'task_type'        => $result['task_type'],
                                                              'status'           => 0,
                                                              'name'             => $taskName,
                                                              'account'          => $account['account'],
                                                              'account_type'     => $account['type'],
                                                              'nickname'         => $account['nickname'] ?? '',
                                                              'avatar'           => $account['avatar'] ?? '',
                                                              'device_code'      => $account['device_code'],
                                                              'send_start_time'  => $time['start_time'],
                                                              'send_end_time'    => $time['end_time'],
                                                              'persona_id'       => $result['persona_id'] ?? 0,
                                                          ]);

                        // 汇总到设备任务调度
                        $allTaskInstall[] = [
                            'user_id'      => self::$uid,
                            'device_code'  => $account['device_code'],
                            'task_type'    => DeviceEnum::TASK_TYPE_SAME_CITY_EXPOSURE,
                            'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_EXPOSURE,
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
                            'source'       => DeviceEnum::TASK_SOURCE_SAME_CITY_EXPOSURE,
                            'create_time'  => time(),
                        ];

                        TaskLogic::updateWechatRpaTaskTime($account['device_code'], $startTime);
                    }
                }

                TaskLogic::add($allTaskInstall);
            }

            Db::commit();

            $result['accounts']        = !empty($result['accounts']) ? json_decode($result['accounts'], true) : [];
            $result['time_config']     = !empty($result['time_config']) ? json_decode($result['time_config'], true) : [];
            $result['task_date']       = !empty($result['task_date']) ? $result['task_date']: [];
            self::$returnData = $result;
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 编辑同城曝光任务
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            $task = SvCityExposureTask::where('id', $params['id'])
                                      ->where('user_id', self::$uid)
                                      ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在');
            }
            if (in_array((int)$task->status, [2, 3])) {
                throw new \Exception('执行中或已完成的任务不可编辑');
            }

            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['task_date']) && is_array($params['task_date'])) {
                $params['task_date'] = json_encode($params['task_date'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['accounts']) && is_array($params['accounts'])) {
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            }

            SvCityExposureTask::where('id', $params['id'])->update($params);

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
            $task = SvCityExposureTask::where('id', $params['id'])
                                      ->where('user_id', self::$uid)
                                      ->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            SvCityExposureTask::destroy($params['id']);
            SvCityExposureTaskAccount::where('city_exposure_id', $params['id'])
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
            $account = SvCityExposureTaskAccount::where('id', $params['id'])
                                                ->where('user_id', self::$uid)
                                                ->findOrEmpty();
            if ($account->isEmpty()) {
                self::setError('子任务不存在');
                return false;
            }
            // 4=暂停中 -> 恢复为1=运行中，其他 -> 暂停为4
            $newStatus = (int)$account->status === 4 ? 1 : 4;
            SvCityExposureTaskAccount::where('id', $params['id'])->update(['status' => $newStatus]);
            return true;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 任务详情
     */
    public static function detail(array $params): array|false
    {
        try {
            $task = SvCityExposureTask::where('id', $params['id'])
                                      ->where('user_id', self::$uid)
                                      ->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            $data                = $task->toArray();
            $data['accounts']    = json_decode($data['accounts'] ?? '[]', true);
            $data['time_config'] = json_decode($data['time_config'] ?? '[]', true);
            $data['task_date']   = json_decode($data['task_date'] ?? '[]', true);

            // 子任务列表
            $data['sub_tasks'] = SvCityExposureTaskAccount::where('city_exposure_id', $params['id'])
                                                          ->where('user_id', self::$uid)
                                                          ->select()
                                                          ->toArray();
            return $data;

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
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