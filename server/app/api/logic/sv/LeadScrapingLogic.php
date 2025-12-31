<?php

namespace app\api\logic\sv;

use app\api\logic\device\TaskLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvLeadScrapingIndustryLog;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use think\facade\Db;

/**
 * LeadScrapingLogic
 * @desc 截流任务
 * @author Qasim
 */
class LeadScrapingLogic extends SvBaseLogic
{

    /**
     * @desc 添加截流任务
     * @param array $params
     * @return bool
     */
    public static function add(array $params)
    {

        Db::startTrans();
        try {
            TaskLogic::checkAccounts($params['accounts']);
            $insertData        = [
                'user_id'   => self::$uid,
                'task_type' => $params['task_type'] ?? 1,
                'name'      => $params['name'] ?? '',
                'status'    => 0,
            ];
            $leadScraping = SvLeadScrapingSetting::create($insertData);
            $leadScraping = $leadScraping->refresh();
            //查询任务明细是否存在
            //            $leadScrapingRecord = SvLeadScrapingRecord::where('scraping_id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            //            if (!$leadScrapingRecord->isEmpty() && $leadScrapingRecord['status'] !== 0) {
            //                self::setError('任务正在执行中，不能修改');
            //                return false;
            //            }

            if (isset($params['filter']) && is_array($params['filter'])) {
                $params['filter'] = json_encode($params['filter'], JSON_UNESCAPED_UNICODE);
            } else {
                self::setError('过滤词不能为空');
                return false;
            }

            if (isset($params['industry']) && !empty($params['industry'])) {
                $params['industry'] = json_encode(explode(';', $params['industry']), JSON_UNESCAPED_UNICODE);
            } else {
                self::setError('行业不能为空');
                return false;
            }

            if (isset($params['content']) && is_array($params['content'])) {
                $params['content'] = json_encode($params['content'], JSON_UNESCAPED_UNICODE);
            } else {
                self::setError('内容不能为空');
                return false;
            }


            if (isset($params['accounts']) && is_array($params['accounts'])) {
                foreach ($params['accounts'] as &$account) {
                    $account['device_code'] = SvAccount::where('id', $account['id'])->value('device_code');
                }
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            } else {
                self::setError('账号不能为空');
                return false;
            }

            $params['task_frequency'] = $params['task_frep'];
            unset($params['task_frep']);
            if (isset($params['task_date']) && is_array($params['task_date']) && !empty($params['task_date'])) {
                $params['task_date'] = json_encode($params['task_date'], JSON_UNESCAPED_UNICODE);
            } else {
                for ($i = 0; $i < $params['task_frequency']; $i++) {
                    $params['task_date'][$i] = date('Y-m-d', strtotime("+{$i} day"));
                }
                $params['task_date'] = json_encode($params['task_date'], JSON_UNESCAPED_UNICODE);
            }

            if (!empty($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
                $params['status']      = 1;
            } else {
                self::setError('时间不能为空');
                return false;
            }

            // 更新
            SvLeadScrapingSetting::where('id', $leadScraping->id)->update($params);
            $result = $leadScraping->refresh()->toArray();
            if ($result['status'] == 1) {
                $accountTask = SvLeadScrapingSettingAccount::where('scraping_id', $result['id'])->findOrEmpty();
                if (!$accountTask->isEmpty()) {
                    throw new \Exception('任务已创建');
                }

                $industries = json_decode($result['industry'], true);
                foreach ($industries as $industry) {
                    $log = SvLeadScrapingIndustryLog::where('user_id', self::$uid)->where('keyword', $industry)->findOrEmpty();
                    if (!$log->isEmpty()) {
                        continue;
                    }
                    SvLeadScrapingIndustryLog::create([
                        'scraping_id' => $result['id'],
                        'task_type' => $params['task_type'],
                        'keyword' => $industry,
                        'user_id' => self::$uid,
                    ]);
                }
                $times = [];
                foreach (json_decode($result['task_date'], true) as $date) {
                    foreach (json_decode($result['time_config'], true) as $time) {
                        $time      = explode('-', $time);
                        $startTime = $date . ' ' . $time[0] . ':00';
                        $endTime   = $date . ' ' . $time[1] . ':00';
                        $times[]   = [
                            'start_time' => strtotime($startTime),
                            'end_time'   => strtotime($endTime),
                        ];
                    }
                }
                //                switch ($result['task_type']) {
                //                    case 1:
                //                        $taskType = DeviceEnum::TASK_COMMENT_TO_COMMENT;
                //                        break;
                //                    case 2:
                //                        $taskType = DeviceEnum::TASK_COMMENT_TO_MSG;
                //                        break;
                //                    default:
                //                        $taskType = DeviceEnum::TASK_COMMENT_TO_MARK_CLUE;
                //                }
                foreach (json_decode($result['accounts'], true) as $account) {
                    foreach ($times as $time) {
                        //判断时间是否冲突
                        list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_TOUCH, $time['start_time'], $time['end_time'], self::$uid);
                        if (!$isOverlap) {
                            $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                            $msg     = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type']) . "】与当前所选时间冲突";
                            throw new \Exception($msg);
                        }
                        $startTime        = $time['start_time'];
                        $endTime          = $time['end_time'];
                        $subTask          = SvLeadScrapingSettingAccount::create([
                            'scraping_id'     => $result['id'],
                            'user_id'         => self::$uid,
                            'task_type'       => $result['task_type'],
                            'status'          => 0,
                            'name'            => $result['name'] . ' - ' . self::formatType((int)$account['type']),
                            'account'         => $account['account'],
                            'account_type'    => $account['type'],
                            'device_code'     => $account['device_code'],
                            'send_start_time' => $startTime,
                            'send_end_time'   => $endTime,
                            'count'           => 0,
                            'published_count' => 0,
                        ]);
                        $allTaskInstall[] = [
                            'user_id'      => self::$uid,
                            'device_code'  => $account['device_code'],
                            'task_type'    => DeviceEnum::TASK_TYPE_TOUCH,
                            'task_scene'   => $result['task_type'],
                            'account'      => $account['account'],
                            'account_type' => $account['type'],
                            'task_name'    => $result['name'] . ' - ' . self::formatType((int)$account['type']),
                            'status'       => 0,
                            'day'          => date('Y-m-d', $startTime),
                            'start_time'   => $startTime,
                            'end_time'     => $endTime,
                            'sub_task_id'  => $subTask->id,
                            'source'       => DeviceEnum::TASK_SOURCE_TOUCH,
                            'create_time'  => time(),
                        ];
                    }
                }
                TaskLogic::add($allTaskInstall);
            }
            Db::commit();
            $result['filter']      = !empty($result['filter']) ? json_decode($result['filter'], true) : [];
            $result['industry']    = !empty($result['industry']) ? implode(';', json_decode($result['industry'], true)) : [];
            $result['content']     = !empty($result['content']) ? json_decode($result['content'], true) : [];
            $result['accounts']    = !empty($result['accounts']) ? json_decode($result['accounts'], true) : [];
            $result['time_config'] = !empty($result['time_config']) ? json_decode($result['time_config'], true) : [];
            $result['task_date']   = !empty($result['task_date']) ? json_decode($result['task_date'], true) : [];
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
    public static function detail(array $params)
    {
        try {
            // 检查截流任务设置是否存在
            $leadScraping = SvLeadScrapingSetting::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($leadScraping->isEmpty()) {
                self::setError('截流任务设置不存在');
                return false;
            }

            $result              = $leadScraping->toArray();
            $result['filter']    = !empty($result['filter']) ? json_decode($result['filter'], true) : [];
            $result['content']   = !empty($result['content']) ? json_decode($result['content'], true) : [];
            $result['accounts']  = !empty($result['accounts']) ? json_decode($result['accounts'], true) : [];
            $result['task_date'] = !empty($result['task_date']) ? json_decode($result['task_date'], true) : [];
            self::$returnData    = $result;

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除截流任务
     * @param array $params
     * @return bool
     */
    public static function delete(array $params)
    {
        Db::startTrans();
        try {
            // 检查截流任务是否存在
            $leadScraping = SvLeadScrapingSettingAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($leadScraping->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            SvLeadScrapingSettingAccount::where('scraping_id', $leadScraping['id'])->where('user_id', self::$uid)->select()->delete();
            SvLeadScrapingSetting::where('id', $leadScraping['scraping_id'])->where('user_id', self::$uid)->select()->delete();
            $leadScraping->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function recordDetail(array $params)
    {
        try {
            // 检查截流任务是否存在
            $record = SvLeadScrapingRecord::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('任务记录不存在');
                return false;
            }

            self::$returnData = $record->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function recordDelete(array $params)
    {
        $record = SvLeadScrapingRecord::field('*')
            ->where('id', $params['id'])
            ->where('user_id', self::$uid)
            ->findOrEmpty();
        if ($record->isEmpty()) {
            self::setError('任务记录不存在');
            return false;
        }
        $record->delete();

        return true;
    }

    public static function getIndustryLog($params)
    {
        $page   = $params['page_no'] ?? 1;
        $limit  = $params['page_size'] ?? 10;
        $offset = max(($page - 1), 0) * $limit;
        $logs   = SvLeadScrapingIndustryLog::field('*')
            ->where('user_id', self::$uid)
            ->where('task_type', $params['task_type'])
            ->limit($offset, $limit)
            ->select();
        $count  = SvLeadScrapingIndustryLog::where('user_id', self::$uid)->where('task_type', $params['task_type'])->count();
        if ($logs->isEmpty()) {
            $logs = [];
        } else {
            $logs = $logs->toArray();
        }

        self::$returnData = [
            'lists'     => $logs,
            'count'     => $count,
            'page_no'   => $page,
            'page_size' => $limit,
        ];
        return true;
    }

    public static function deleteIndustryLog($id)
    {
        SvLeadScrapingIndustryLog::destroy($id);
        self::$returnData = [];
        return true;
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
