<?php

namespace app\api\logic\sv;

use app\api\logic\device\TaskLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCityTouchFilterHistory;
use app\common\model\sv\SvCityTouchRecord;
use app\common\model\sv\SvCityTouchTask;
use app\common\model\sv\SvCityTouchTaskAccount;
use think\facade\Db;

/**
 * CityTouchLogic
 * @desc 同城视频截流任务
 * @author Qasim
 */
class CityTouchLogic extends SvBaseLogic
{
    /**
     * @desc 添加同城视频截流任务
     * @param array $params
     * @return bool
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            self::checkAutoDevice($params);
            TaskLogic::checkAccounts($params['accounts']);

            $taskType = in_array('4', $params['marker_method']) ? 2 : 1;
            // 初始化基础插入数据（草稿状态）
            $insertData = [
                'user_id'   => self::$uid,
                'task_type' => $taskType,
                'name'      => $params['name'] ?? '',
                'status'    => 0,
            ];

            // 立即执行模式：覆盖时间配置
            $isOverlap = (int)($params['task_exec_type'] ?? 0);
            if ($isOverlap === 1) {
                TaskLogic::updateTaskStatusByIds($params['task_ids']);
                $params['time_config'] = [
                    date('H:i', time()) . '-' . date('H:i', time() + 60 * (int)$params['minutes']),
                ];
                $params['task_date']   = [date('Y-m-d', time())];
                $params['task_frep']   = 1;
            }
            unset($params['task_ids']);

            // 创建主任务记录（草稿）
            $cityTouch = SvCityTouchTask::create($insertData);
            $cityTouch = $cityTouch->refresh();

            // -------- 参数校验 & 序列化 --------

            // 评论词筛选
            if (isset($params['filter']) && is_array($params['filter'])) {
                $params['filter'] = json_encode($params['filter'], JSON_UNESCAPED_UNICODE);
            }

            // 过滤词（对方昵称不包含筛选）
            if (isset($params['nickname_filter']) && is_array($params['nickname_filter'])) {
                $params['nickname_filter'] = json_encode($params['nickname_filter'], JSON_UNESCAPED_UNICODE);
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

            // 留痕方式（1点赞 2关注 3私信 4评论）
            if (isset($params['marker_method']) && is_array($params['marker_method'])) {
                $params['marker_method'] = json_encode($params['marker_method'], JSON_UNESCAPED_UNICODE);
            }

            // 同城专属字段校验
            if (isset($params['radius']) && $params['radius'] < 0) {
                self::setError('距离不合法');
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

            $params['old'] = is_array($params['old']) ? json_encode($params['old'], JSON_UNESCAPED_UNICODE) : $params['old'];
            $params['comment_fans_num'] = is_array($params['comment_fans_num']) ? json_encode($params['comment_fans_num'], JSON_UNESCAPED_UNICODE) : $params['comment_fans_num'];
            $params['comment_follow_num'] = is_array($params['comment_follow_num']) ? json_encode($params['comment_follow_num'], JSON_UNESCAPED_UNICODE) : $params['comment_follow_num'];
            //print_r($params);die;
            // 更新主任务完整数据
            SvCityTouchTask::where('id', $cityTouch->id)->update($params);
            $result = $cityTouch->refresh()->toArray();

            // -------- 创建子任务（账号 × 时间段维度） --------
            if ($result['status'] == 1) {
                // 防止重复创建
                $accountTask = SvCityTouchTaskAccount::where('city_touch_id', $result['id'])->findOrEmpty();
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
                    // 补全账号完整信息
                    $find    = SvAccount::where('account', $account['account'])
                        ->where('type', $account['type'])
                        ->where('user_id', self::$uid)
                        ->limit(1)
                        ->find()
                        ->toArray();
                    $account = array_merge($account, $find);

                    foreach ($times as $time) {
                        // 非立即执行模式下检查时间冲突
                        if ($isOverlap === 0) {
                            [$isTimeOk, $lap] = TaskLogic::isTaskTimeOverlapping(
                                $account['device_code'],
                                DeviceEnum::TASK_TYPE_SAME_CITY_CUTOFF,
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
                        $taskName  = $result['name'] . ' - ' . self::formatType((int)$account['type']);

                        // 创建账号子任务（注意关联字段为 city_touch_id）
                        $subTask = SvCityTouchTaskAccount::create([
                            'city_touch_id'   => $result['id'],
                            'user_id'         => self::$uid,
                            'task_type'       => $result['task_type'],
                            'status'          => 0,
                            'name'            => $taskName,
                            'account'         => $account['account'],
                            'account_type'    => $account['type'],
                            'nickname'        => $account['nickname'],
                            'avatar'          => $account['avatar'],
                            'device_code'     => $account['device_code'],
                            'send_start_time' => $startTime,
                            'send_end_time'   => $endTime,
                            'count'           => 0,
                            'published_count' => 0,
                        ]);

                        // 汇总到设备任务调度
                        $allTaskInstall[] = [
                            'user_id'      => self::$uid,
                            'device_code'  => $account['device_code'],
                            'task_type'    => DeviceEnum::TASK_TYPE_SAME_CITY_CUTOFF,
                            'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_SAME_CITY_CUTOFF,
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
                            'source'       => DeviceEnum::TASK_SOURCE_SAME_CITY_CUTOFF,
                            'create_time'  => time(),
                        ];

                        TaskLogic::updateWechatRpaTaskTime($account['device_code'], $startTime);
                    }
                }

                TaskLogic::add($allTaskInstall);
            }

            // 保存过滤词历史
            self::updateFilterHistory($params);

            Db::commit();

            // 格式化返回数据
            $result['nickname_filter'] = !empty($result['nickname_filter']) ? $result['nickname_filter'] : [];
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
     * @desc 获取任务详情
     * @param array $params
     * @return bool
     */
    public static function detail(array $params): bool
    {
        try {
            $cityTouch = SvCityTouchTask::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($cityTouch->isEmpty()) {
                self::setError('同城视频截流任务不存在');
                return false;
            }

            $result                    = $cityTouch->toArray();
            $result['nickname_filter'] = !empty($result['nickname_filter']) ? json_decode($result['nickname_filter'], true) : [];
            $result['marker_method']   = !empty($result['marker_method']) ? (is_array($result['marker_method']) ? $result['marker_method'] : json_decode($result['marker_method'], true)) : [];
            $result['accounts']        = !empty($result['accounts']) ? json_decode($result['accounts'], true) : [];
            $result['time_config']     = !empty($result['time_config']) ? json_decode($result['time_config'], true) : [];
            $result['task_date']       = !empty($result['task_date']) ? json_decode($result['task_date'], true) : [];

            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除任务（含关联子任务软删除）
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $cityTouch = SvCityTouchTask::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($cityTouch->isEmpty()) {
                self::setError('同城视频截流任务不存在');
                return false;
            }

            // 软删除主任务
            $cityTouch->delete();

            // 软删除关联子任务
            SvCityTouchTaskAccount::where('city_touch_id', $params['id'])
                ->where('user_id', self::$uid)
                ->update(['delete_time' => time()]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取记录详情
     * @param array $params
     * @return bool
     */
    public static function recordDetail(array $params): bool
    {
        try {
            $record = SvCityTouchRecord::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($record->isEmpty()) {
                self::setError('记录不存在');
                return false;
            }

            self::$returnData = $record->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除记录
     * @param array $params
     * @return bool
     */
    public static function recordDelete(array $params): bool
    {
        try {
            $record = SvCityTouchRecord::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($record->isEmpty()) {
                self::setError('记录不存在');
                return false;
            }

            $record->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取过滤词历史
     * @param array $params
     * @return bool
     */
    public static function getFilterHistory(array $params): bool
    {
        try {
            $result = SvCityTouchFilterHistory::where('user_id', self::$uid)
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
     * @desc 保存过滤词历史
     * @param array $params
     */
    private static function updateFilterHistory(array $params): void
    {
        try {
            $history        = SvCityTouchFilterHistory::where('user_id', self::$uid)->findOrEmpty();
            $filter         = $params['filter'] ?? '[]';
            $nicknameFilter = $params['nickname_filter'] ?? '[]';

            if ($history->isEmpty()) {
                SvCityTouchFilterHistory::create([
                    'user_id'         => self::$uid,
                    'filter'          => $filter,
                    'nickname_filter' => $nicknameFilter,
                    'create_time'     => time(),
                ]);
            } else {
                $history->filter          = $filter;
                $history->nickname_filter = $nicknameFilter;
                $history->update_time     = time();
                $history->save();
            }
        } catch (\Exception $e) {
            // 历史记录写入失败不影响主流程，仅记录日志
            \think\facade\Log::warning('CityTouch updateFilterHistory error: ' . $e->getMessage());
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
