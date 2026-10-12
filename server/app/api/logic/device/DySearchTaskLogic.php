<?php

namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\api\logic\aiPersona\BasePersonaLogic;
use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaTrafficConfig;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDySearchRecord;
use app\common\model\sv\SvDySearchTask;
use app\common\model\sv\SvDySearchTaskDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\sv\DySearchAccountNormalizer;
use app\common\service\sv\DySearchFilterHistoryService;
use app\common\service\sv\SvDeviceTaskExistenceService;
use app\common\traits\DySearchTaskTrait;
use think\facade\Db;
use think\facade\Log;

/**
 * 抖音获客任务
 */
class DySearchTaskLogic extends ApiLogic
{
    use DySearchTaskTrait;

    public static function add(array $params)
    {
        try {
            Db::startTrans();
            self::checkAutoDevice($params);
            $params['user_id'] = self::$uid;

            $deviceCodes = $params['device_codes'] ?? [];
            $keywords = $params['keywords'] ?? [];
            if (!is_array($deviceCodes) || !is_array($keywords)) {
                throw new \Exception('设备与线索必须是数组');
            }

            $deviceCodes = array_values(array_unique(array_filter(array_map(function ($code) {
                return trim((string)$code);
            }, $deviceCodes), function ($code) {
                return $code !== '';
            })));
            $searchMode = (string)($params['search_mode'] ?? 'keyword');
            if (!in_array($searchMode, ['keyword', 'account'], true)) {
                throw new \Exception('搜寻模式仅支持关键词或指定账号');
            }

            $addRule = (string)($params['add_rule'] ?? 'all');
            if (!in_array($addRule, ['all', 'phone', 'wechat', 'phone_first'], true)) {
                throw new \Exception('加好友规则仅支持全部、仅手机、仅微信或手机优先');
            }

            if ($searchMode === 'account') {
                $keywords = DySearchAccountNormalizer::normalizeList($keywords);
            } else {
                $keywords = DySearchAccountNormalizer::normalizeKeywords($keywords);
            }
            $historyKeywords = $keywords;
            $keywords = DySearchAccountNormalizer::orderKeywordsForTask($keywords, $searchMode);

            $deviceCount = count($deviceCodes);
            $keywordCount = count($keywords);
            if ($deviceCount < 1) {
                throw new \Exception('请选择设备');
            }
            if ($keywordCount < 1) {
                throw new \Exception('请输入线索词或账号名单');
            }
            if ($deviceCount > 20) {
                throw new \Exception('单次最多选择20台设备');
            }
            if ($keywordCount > 200) {
                throw new \Exception('线索词或账号名单最多200条');
            }
            if ($keywordCount < $deviceCount) {
                throw new \Exception('线索数不得少于所选设备数量');
            }

            $scanConfig = DySearchAccountNormalizer::buildScanConfig($params['scan_config'] ?? [], $searchMode);

            $accountMap = [];
            foreach ($deviceCodes as $device) {
                $account = SvAccount::where('device_code', $device)
                    ->where('type', DeviceEnum::ACCOUNT_TYPE_DY)
                    ->where('user_id', self::$uid)
                    ->findOrEmpty();
                if ($account->isEmpty()) {
                    throw new \Exception('设备' . $device . '未绑定抖音账号');
                }
                $accountMap[$device] = $account;
            }

            if (isset($params['wechat_id']) && is_array($params['wechat_id'])) {
                $wechatId = implode(',', array_filter(array_map('trim', $params['wechat_id']), function ($id) {
                    return $id !== '';
                }));
            } else {
                $wechatId = trim((string)($params['wechat_id'] ?? ''));
            }

            $remarks = $params['remarks'] ?? [];
            if (is_string($remarks)) {
                $decoded = json_decode($remarks, true);
                $remarks = is_array($decoded) ? $decoded : ($remarks !== '' ? [$remarks] : []);
            }
            if (!is_array($remarks)) {
                $remarks = [];
            }
            $remarks = array_values(array_filter(array_map('trim', $remarks), function ($item) {
                return $item !== '';
            }));

            $addType = (int)($params['add_type'] ?? 0);
            if ($addType === 1 && $wechatId === '') {
                throw new \Exception('请配置添加微信的客服微信');
            }
            $wechatMap = [];
            if ($addType === 1) {
                foreach ($deviceCodes as $device) {
                    $wechat = self::findDeviceWechatAccount($device, $wechatId);
                    if ($wechat === null) {
                        throw new \Exception('设备' . $device . '未绑定所选加微微信');
                    }
                    $wechatMap[$device] = $wechat;
                }
            }
            $addRemarkEnable = (int)($params['add_remark_enable'] ?? 0);
            if ($addRemarkEnable === 1 && $remarks === []) {
                throw new \Exception('请至少配置一条打招呼话术');
            }

            $isOverlap = (int)($params['task_exec_type'] ?? 0);
            $minutes = (int)($params['minutes'] ?? 0);
            if ($isOverlap === 1) {
                if ($minutes < 5 || $minutes > 120) {
                    throw new \Exception('即时执行时长须在5到120分钟之间');
                }
                $startTs = time();
                $endTs = $startTs + (60 * $minutes);
                if (date('Y-m-d', $startTs) !== date('Y-m-d', $endTs)) {
                    throw new \Exception('即时任务不能跨自然日，请缩短时长或明天再创建');
                }
                TaskLogic::updateTaskStatusByIds($params['task_ids'] ?? []);
                $timeConfig = [
                    date('H:i', $startTs) . '-' . date('H:i', $endTs),
                ];
                $customDate = [date('Y-m-d', $startTs)];
            } else {
                if (!isset($params['time_config']) || empty($params['time_config'])) {
                    throw new \Exception('请配置任务执行时间区间');
                }
                $timeConfig = $params['time_config'];
                if (is_string($timeConfig)) {
                    $timeConfig = [$timeConfig];
                }
                if (!is_array($timeConfig) || $timeConfig === []) {
                    throw new \Exception('请配置任务执行时间区间');
                }
                $customDate = $params['custom_date'] ?? [];
                if (is_null($customDate) || $customDate === '') {
                    $customDate = [];
                }
                if (is_string($customDate)) {
                    $customDate = [$customDate];
                }
                if (!is_array($customDate)) {
                    $customDate = [];
                }
            }

            $taskFrep = DySearchAccountNormalizer::parseTaskFrep($params['task_frep'] ?? 0);
            if ($isOverlap === 0 && $taskFrep < 1 && $customDate === []) {
                $taskFrep = 1;
            }
            $devices = self::distributeKeywords($keywords, $deviceCodes, $keywordCount, $deviceCount);
            $times = TaskLogic::getTimes($timeConfig, date('Y-m-d', time()), $taskFrep, $customDate);

            $allTaskInstall = [];
            $createdTaskIds = [];
            $task = null;
            $addNumber = (int)($params['add_number'] ?? 15);
            $addInterval = (int)($params['add_interval_time'] ?? 10);
            $perKw = DySearchAccountNormalizer::parseCap($params['per_kw'] ?? 30, '每词上限', 30);
            $dayCap = DySearchAccountNormalizer::parseCap($params['day_cap'] ?? 50, '单日上限', 50);

            foreach ($times as $time) {
                $task = SvDySearchTask::create([
                    'user_id'            => self::$uid,
                    'name'               => $params['name'] ?? ('抖音获客任务' . date('mdHis')),
                    'device_codes'       => array_values($deviceCodes),
                    'search_mode'        => $searchMode,
                    'keywords'           => $keywords,
                    'per_kw'             => $perKw,
                    'scan_config'        => $scanConfig,
                    'exclude_default_id' => 1,
                    'add_type'           => $addType,
                    'wechat_id'          => $wechatId,
                    'add_rule'           => $addRule,
                    'add_number'         => $addNumber,
                    'add_interval_time'  => $addInterval,
                    'add_remark_enable'  => $addRemarkEnable,
                    'remarks'            => $remarks,
                    'task_exec_type'     => $isOverlap,
                    'minutes'            => $minutes,
                    'task_frep'          => (string)($params['task_frep'] ?? ''),
                    'time_config'        => $timeConfig,
                    'custom_date'        => $customDate,
                    'start_time'         => $time['start_time'],
                    'end_time'           => $time['end_time'],
                    'day_cap'            => $dayCap,
                    'status'             => 0,
                    'ocr_type'           => (int)($params['ocr_type'] ?? 1),
                    'source'             => SvDySearchTask::SOURCE_MANUAL,
                    'auto_type'          => SvDySearchTask::AUTO_TYPE_MANUAL,
                    'persona_id'         => (int)($params['persona_id'] ?? 0),
                    'remark'             => $params['remark'] ?? '',
                ]);
                $createdTaskIds[] = (int)$task->id;

                foreach ($devices as $device => $val) {
                    if ($isOverlap === 0) {
                        list($passOverlap, $lap) = TaskLogic::isTaskTimeOverlapping(
                            $device,
                            DeviceEnum::TASK_TYPE_DY_SEARCH,
                            $time['start_time'],
                            $time['end_time'],
                            self::$uid
                        );
                        if (!$passOverlap) {
                            $timeMsg = '【' . date('Y-m-d H:i', $lap['start_time']) . '-' . date('Y-m-d H:i', $lap['end_time']) . '】';
                            throw new \Exception(
                                '您在' . $timeMsg . '的【'
                                . DeviceEnum::getAccountTypeDesc($lap['account_type'])
                                . DeviceEnum::getTaskTypeDesc($lap['task_type'])
                                . '】与当前所选时间冲突'
                            );
                        }
                    }

                    SvDySearchTaskDevice::create([
                        'user_id'     => self::$uid,
                        'task_id'     => $task->id,
                        'device_code' => $device,
                        'keywords'    => $val,
                        'status'      => 1,
                    ]);

                    $account = $accountMap[$device];
                    $allTaskInstall[] = [
                        'user_id'      => self::$uid,
                        'device_code'  => $device,
                        'task_type'    => DeviceEnum::TASK_TYPE_DY_SEARCH,
                        'account'      => $account->account ?? '',
                        'account_type' => DeviceEnum::ACCOUNT_TYPE_DY,
                        'nickname'     => $account->nickname ?? '',
                        'avatar'       => $account->avatar ?? '',
                        'task_name'    => '抖音获客任务',
                        'status'       => 0,
                        'day'          => date('Y-m-d', $task->start_time),
                        'time_config'  => json_encode($timeConfig, JSON_UNESCAPED_UNICODE),
                        'start_time'   => $task->start_time,
                        'end_time'     => $task->end_time,
                        'sub_task_id'  => $task->id,
                        'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH,
                        'source'       => DeviceEnum::TASK_SOURCE_DY_SEARCH,
                        'create_time'  => time(),
                    ];

                    if ($addType === 1) {
                        $st = $time['end_time'] + 180;
                        $et = $st + ($addNumber * $addInterval * 60);
                        if (($et - $st) < (5 * 60)) {
                            $et = $st + (5 * 60);
                        }
                        if ($isOverlap === 0) {
                            list($passOverlap, $lap) = TaskLogic::isTaskTimeOverlapping(
                                $device,
                                DeviceEnum::TASK_TYPE_DY_SEARCH_WECHAT,
                                $st,
                                $et,
                                self::$uid
                            );
                            if (!$passOverlap) {
                                $timeMsg = '【' . date('Y-m-d H:i', $lap['start_time']) . '-' . date('Y-m-d H:i', $lap['end_time']) . '】';
                                throw new \Exception(
                                    '您在' . $timeMsg . '的【'
                                    . DeviceEnum::getAccountTypeDesc($lap['account_type'])
                                    . DeviceEnum::getTaskTypeDesc($lap['task_type'])
                                    . '】与当前所选时间冲突'
                                );
                            }
                        }
                        $wechat = $wechatMap[$device];
                        $allTaskInstall[] = [
                            'user_id'      => self::$uid,
                            'device_code'  => $device,
                            'task_type'    => DeviceEnum::TASK_TYPE_DY_SEARCH_WECHAT,
                            'account'      => $wechat->account ?? '',
                            'account_type' => DeviceEnum::ACCOUNT_TYPE_SPH,
                            'nickname'     => $wechat->nickname ?? '',
                            'avatar'       => $wechat->avatar ?? '',
                            'task_name'    => '抖音获客加微任务',
                            'status'       => 0,
                            'day'          => date('Y-m-d', $st),
                            'time_config'  => json_encode([
                                date('H:i', $st) . '-' . date('H:i', $et),
                            ], JSON_UNESCAPED_UNICODE),
                            'start_time'   => $st,
                            'end_time'     => $et,
                            'sub_task_id'  => $task->id,
                            'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH_WECHAT,
                            'source'       => DeviceEnum::TASK_SOURCE_DY_SEARCH_WECHAT,
                            'create_time'  => time(),
                        ];
                    }
                }
            }

            if (!$task instanceof SvDySearchTask) {
                throw new \Exception('未生成可执行时间段，无法创建任务');
            }

            TaskLogic::add($allTaskInstall);
            $closed = self::closeRunningHistoryTasks((int)self::$uid, $createdTaskIds);
            if ($closed !== null) {
                Log::channel('device')->write('抖音获客新建后收口执行中任务条数=' . $closed);
            }
            Db::commit();

            try {
                self::sendNotice([
                    'userId'    => self::$uid,
                    'content'   => $task->name ?: '抖音获客',
                    'autoType'  => 0,
                    'startTime' => date('Y-m-d H:i', (int)$task->start_time),
                    'endTime'   => date('Y-m-d H:i', (int)$task->end_time),
                    'status'    => 0,
                ], 401);
            } catch (\Throwable $ignore) {
                Log::channel('device')->write('抖音获客任务创建通知发送失败：' . $ignore->getMessage());
            }

            try {
                DySearchFilterHistoryService::saveAfterAdd(
                    (int)self::$uid,
                    $searchMode,
                    $historyKeywords,
                    $remarks,
                    $scanConfig['miss_scripts'] ?? []
                );
            } catch (\Throwable $ignore) {
                Log::channel('device')->write('抖音获客线索词与话术历史保存失败：' . $ignore->getMessage());
            }

            Log::channel('device')->write('抖音获客任务已创建 task_id=' . (int)$task->id . ' 设备数=' . $deviceCount . ' 线索数=' . $keywordCount . ' 时段数=' . count($times));
            $payload = $task->toArray();
            $payload['task_ids'] = $createdTaskIds;
            self::$returnData = $payload;
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::channel('device')->write('抖音获客任务创建失败：' . $e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function history(): bool
    {
        try {
            self::$returnData = DySearchFilterHistoryService::getHistory((int)self::$uid);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('获客任务不存在');
                return false;
            }
            $result = $task->toArray();
            $result['binds'] = SvDySearchTaskDevice::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->select()
                ->toArray();
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('获客任务不存在');
            }
            $taskId = (int)$task->id;
            try {
                self::dySearchCancel($taskId);
            } catch (\Throwable $e) {
                Log::channel('device')->write('抖音获客任务取消指令发送失败：' . $e->getMessage());
                throw new \Exception('设备指令发送失败');
            }

            Db::startTrans();
            $task = SvDySearchTask::where('id', $taskId)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('获客任务不存在');
            }
            $task->status = 4;
            $task->save();
            SvDySearchTaskDevice::where('task_id', $taskId)->where('user_id', self::$uid)->select()->delete();
            $task->delete();
            SvDeviceTask::where('user_id', self::$uid)
                ->where('sub_task_id', $taskId)
                ->where('source', 'in', [
                    DeviceEnum::TASK_SOURCE_DY_SEARCH,
                    DeviceEnum::TASK_SOURCE_DY_SEARCH_WECHAT,
                ])
                ->select()
                ->delete();
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function pause(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('获客任务不存在');
                return false;
            }
            if (in_array((int)$task->status, [3, 4], true)) {
                self::setError('任务已结束，不能暂停');
                return false;
            }

            $prevStatus = (int)$task->status;
            $bindIds = SvDySearchTaskDevice::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->where('status', 1)
                ->column('id');
            $task->status = 2;
            $task->save();
            if ($bindIds !== []) {
                SvDySearchTaskDevice::where('id', 'in', $bindIds)
                    ->update(['status' => 2, 'update_time' => time()]);
            }
            try {
                self::dySearchPause((int)$task->id);
            } catch (\Throwable $e) {
                Log::channel('device')->write('抖音获客任务暂停指令发送失败：' . $e->getMessage());
                $task->status = $prevStatus;
                $task->save();
                if ($bindIds !== []) {
                    SvDySearchTaskDevice::where('id', 'in', $bindIds)
                        ->update(['status' => 1, 'update_time' => time()]);
                }
                self::setError('设备指令发送失败');
                return false;
            }
            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function recovery(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('获客任务不存在');
                return false;
            }
            if (in_array((int)$task->status, [3, 4], true)) {
                self::setError('任务已结束，不能恢复');
                return false;
            }
            if ((int)$task->status !== 2) {
                self::setError('仅暂停中的任务可恢复');
                return false;
            }

            $bindIds = SvDySearchTaskDevice::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->where('status', 2)
                ->column('id');
            $task->status = 1;
            $task->save();
            if ($bindIds !== []) {
                SvDySearchTaskDevice::where('id', 'in', $bindIds)
                    ->update(['status' => 1, 'update_time' => time()]);
            }
            try {
                self::dySearchRecovery((int)$task->id);
            } catch (\Throwable $e) {
                Log::channel('device')->write('抖音获客任务恢复指令发送失败：' . $e->getMessage());
                $task->status = 2;
                $task->save();
                if ($bindIds !== []) {
                    SvDySearchTaskDevice::where('id', 'in', $bindIds)
                        ->update(['status' => 2, 'update_time' => time()]);
                }
                self::setError('设备指令发送失败');
                return false;
            }
            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function send(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('获客任务不存在');
                return false;
            }
            if (in_array((int)$task->status, [3, 4], true)) {
                self::setError('任务已结束，不能下发');
                return false;
            }

            $prevStatus = (int)$task->status;
            $prevBinds = SvDySearchTaskDevice::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->where('status', '<>', 3)
                ->column('status', 'id');
            $task->status = 1;
            $task->save();
            if ($prevBinds !== []) {
                SvDySearchTaskDevice::where('id', 'in', array_keys($prevBinds))
                    ->update(['status' => 1, 'update_time' => time()]);
            }
            try {
                self::dySearchSend((int)$task->id);
            } catch (\Throwable $e) {
                Log::channel('device')->write('抖音获客任务下发指令发送失败：' . $e->getMessage());
                $task->status = $prevStatus;
                $task->save();
                foreach ($prevBinds as $bindId => $status) {
                    SvDySearchTaskDevice::where('id', (int)$bindId)
                        ->update(['status' => (int)$status, 'update_time' => time()]);
                }
                self::setError('设备指令发送失败');
                return false;
            }
            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 立即执行下发前把主任务、本设备绑定、当次日历任务改回执行中。
     * 演示包不写库。不开启事务，由调用方在下发失败时回滚。
     *
     * @param array<string, mixed> $content 1007 报文
     */
    public static function reopenForImmediateExec(array $content, int $userId, string $deviceCode): void
    {
        if ((int)($content['isDemoData'] ?? 1) === 1) {
            return;
        }
        $taskId = (int)($content['task_id'] ?? 0);
        if ($taskId < 1) {
            return;
        }

        $deviceCode = trim($deviceCode);
        $startTime = (int)($content['start_time'] ?? 0);
        $endTime = (int)($content['end_time'] ?? 0);
        if ($userId < 1 || $deviceCode === '' || $startTime < 1 || $endTime <= $startTime) {
            throw new \Exception('即时执行时段无效');
        }

        $task = SvDySearchTask::where('id', $taskId)->where('user_id', $userId)->findOrEmpty();
        if ($task->isEmpty()) {
            throw new \Exception('获客任务不存在');
        }

        $bindId = (int)($content['id'] ?? 0);
        if ($bindId > 0) {
            $bind = SvDySearchTaskDevice::where('id', $bindId)
                ->where('task_id', $taskId)
                ->where('device_code', $deviceCode)
                ->where('user_id', $userId)
                ->findOrEmpty();
            if ($bind->isEmpty()) {
                throw new \Exception('设备未绑定该获客任务');
            }
        }

        $deviceTaskId = (int)($content['device_task_id'] ?? 0);
        $deviceTask = SvDeviceTask::where('id', $deviceTaskId)
            ->where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->where('sub_task_id', $taskId)
            ->where('task_type', DeviceEnum::TASK_TYPE_DY_SEARCH)
            ->findOrEmpty();
        if ($deviceTask->isEmpty()) {
            throw new \Exception('获客日历任务不存在');
        }

        $now = time();
        SvDySearchTask::where('id', $taskId)->where('user_id', $userId)->update([
            'status' => 1,
            'update_time' => $now,
        ]);
        if ($bindId > 0) {
            SvDySearchTaskDevice::where('id', $bindId)
                ->where('task_id', $taskId)
                ->where('device_code', $deviceCode)
                ->where('user_id', $userId)
                ->update([
                    'status' => 1,
                    'update_time' => $now,
                ]);
        }
        SvDeviceTask::where('id', $deviceTaskId)
            ->where('user_id', $userId)
            ->where('device_code', $deviceCode)
            ->where('sub_task_id', $taskId)
            ->where('task_type', DeviceEnum::TASK_TYPE_DY_SEARCH)
            ->update([
                'status' => DeviceEnum::TASK_STATUS_RUNNING,
                'remark' => '立即执行',
                'update_time' => $now,
            ]);
    }

    public static function keywordsCount(array $params)
    {
        try {
            $task = SvDySearchTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('获客任务不存在');
                return false;
            }
            $keywords = $task->keywords;
            if (!is_array($keywords)) {
                $keywords = [];
            }

            $visit = SvDySearchRecord::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->group('exec_keyword')
                ->column('count(*)', 'exec_keyword');
            $clue = SvDySearchRecord::where('task_id', $task->id)
                ->where('user_id', self::$uid)
                ->where('result', 1)
                ->group('exec_keyword')
                ->column('count(*)', 'exec_keyword');

            $result = [];
            foreach ($keywords as $keyword) {
                $visitCount = isset($visit[$keyword]) ? (int)$visit[$keyword] : 0;
                $clueCount = isset($clue[$keyword]) ? (int)$clue[$keyword] : 0;
                $result[$keyword] = [
                    'count'       => $visitCount,
                    'exec_count'  => $visitCount,
                    'clue_count'  => $clueCount,
                    'status'      => $visitCount > 0 ? 1 : 0,
                    'keyword'     => $keyword,
                    'device_code' => '',
                ];
            }

            $binds = SvDySearchTaskDevice::where('task_id', $task->id)->where('user_id', self::$uid)->select();
            foreach ($binds as $bind) {
                $assigned = is_array($bind['keywords']) ? $bind['keywords'] : [];
                $bindStatus = (int)$bind['status'];
                $execKeyword = trim((string)($bind['exec_keyword'] ?? ''));
                foreach ($assigned as $keyword) {
                    if (!isset($result[$keyword])) {
                        continue;
                    }
                    $result[$keyword]['device_code'] = (string)$bind['device_code'];
                    if ($bindStatus === 3) {
                        $result[$keyword]['status'] = 3;
                    } elseif ($execKeyword !== '' && $execKeyword === $keyword) {
                        $result[$keyword]['status'] = $bindStatus;
                    }
                }
            }

            $data = array_values($result);
            $keys = array_column($data, 'status');
            array_multisort($keys, SORT_DESC, $data);

            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 24h / 初次创建：按工作流场景 18 生成抖音搜寻获客任务。不收口执行中任务，不创建场景 19。
     */
    public static function autoDySearchTaskCron(SvDevice $device): array
    {
        print_r("\n{$device->device_code} 自动创建抖音搜寻获客任务\n");
        Log::channel('auto')->write($device->device_code . ' 自动创建抖音搜寻获客任务', 'create');
        try {
            if (empty($device->persona_id)) {
                Log::channel('auto')->write($device->device_code . ' 人设ID为空，跳过抖音搜寻获客', 'create');
                return SvDeviceTaskExistenceService::emptySlotResult();
            }

            $persona = AiPersona::where('id', $device->persona_id)->findOrEmpty();
            if ($persona->isEmpty()) {
                Log::channel('auto')->write($device->device_code . ' 未找到人设数据 ' . Db::getLastSql(), 'create');
                return SvDeviceTaskExistenceService::emptySlotResult();
            }

            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'auto_clues.options.dy_search')) {
                Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.dy_search=0，跳过抖音搜寻获客任务', 'create');
                return SvDeviceTaskExistenceService::emptySlotResult();
            }

            return self::createAutoDySearchTask($device, $persona);
        } catch (\Throwable $th) {
            Log::channel('auto')->write($device->device_code . ' 自动创建抖音搜寻获客任务失败 ' . $th->__toString(), 'create');
            return SvDeviceTaskExistenceService::emptySlotResult();
        }
    }

    /**
     * @return array{created:int,skipped_existing:int,skipped_no_account:int,skipped_empty_schedule:int}
     */
    private static function createAutoDySearchTask(SvDevice $device, AiPersona $persona): array
    {
        $result = SvDeviceTaskExistenceService::emptySlotResult();
        $schedules = BasePersonaLogic::getAutoSchedule($persona, DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH);
        if ($schedules->isEmpty()) {
            SvDeviceTaskExistenceService::bumpEmptySchedule($result, (string)$device->device_code, '抖音搜寻获客任务');
            return $result;
        }

        $date = date('Y-m-d');
        $keywords = self::loadAutoDySearchKeywords($device, $persona);
        if ($keywords === null) {
            return $result;
        }

        $scripts = DySearchFilterHistoryService::getHistory((int)$persona->user_id)['miss_scripts'] ?? [];
        if (!is_array($scripts)) {
            $scripts = [];
        }
        $missAction = $scripts !== [] ? 'dm' : 'skip';
        $scanConfig = DySearchAccountNormalizer::buildScanConfig([
            'miss_scripts' => $scripts,
            'miss_action'  => $missAction,
        ], 'keyword');

        Db::startTrans();
        try {
            foreach ($schedules as $schedule) {
                $platforms = $schedule->platform;
                if (empty($platforms) || !is_array($platforms)) {
                    continue;
                }

                $hasDy = false;
                foreach ($platforms as $platform) {
                    if ((int)($platform['account_type'] ?? 0) === DeviceEnum::ACCOUNT_TYPE_DY) {
                        $hasDy = true;
                        break;
                    }
                }
                if (!$hasDy) {
                    continue;
                }

                if (AutoTaskSceneConfigService::shouldSkipDailyCreate(
                    DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH,
                    DeviceEnum::ACCOUNT_TYPE_DY,
                    (string)$device->device_code,
                    '抖音搜寻获客任务'
                )) {
                    continue;
                }

                $startTime = strtotime($date . ' ' . $schedule->start_time . ':00');
                $endTime = strtotime($date . ' ' . $schedule->end_time . ':00');
                $execTime = date('H:i', (int)$startTime) . '-' . date('H:i', (int)$endTime);

                $account = SvAccount::where('type', DeviceEnum::ACCOUNT_TYPE_DY)
                    ->where('user_id', $device->user_id)
                    ->where('device_code', $device->device_code)
                    ->findOrEmpty();
                if ($account->isEmpty()) {
                    SvDeviceTaskExistenceService::bumpMissingAccount(
                        $result,
                        (string)$device->device_code,
                        DeviceEnum::ACCOUNT_TYPE_DY,
                        '抖音搜寻获客任务'
                    );
                    continue;
                }

                if (SvDeviceTaskExistenceService::shouldSkipExistingSlot(
                    (int)$device->user_id,
                    (string)$device->device_code,
                    (int)$persona->id,
                    DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH,
                    DeviceEnum::ACCOUNT_TYPE_DY,
                    (int)$startTime,
                    (int)$endTime,
                    '抖音搜寻获客任务'
                )) {
                    $result['skipped_existing']++;
                    continue;
                }

                if ($endTime < time()) {
                    Log::channel('auto')->write(
                        $device->device_code . '该账号类型[' . $account->type . ']时间[' . $execTime . ']已过期',
                        'create'
                    );
                }

                $taskName = '自动化抖音搜寻获客' . date('mdHis');
                $task = SvDySearchTask::create([
                    'user_id'            => $device->user_id,
                    'name'               => $taskName,
                    'device_codes'       => [$device->device_code],
                    'search_mode'        => 'keyword',
                    'keywords'           => $keywords,
                    'per_kw'             => 30,
                    'day_cap'            => 50,
                    'scan_config'        => $scanConfig,
                    'exclude_default_id' => 1,
                    'add_type'           => 1,
                    'wechat_id'          => '',
                    'add_rule'           => 'all',
                    'add_number'         => 15,
                    'add_interval_time'  => 10,
                    'add_remark_enable'  => 0,
                    'remarks'            => [],
                    'task_exec_type'     => 0,
                    'minutes'            => 0,
                    'task_frep'          => '1',
                    'time_config'        => [$execTime],
                    'custom_date'        => [$date],
                    'start_time'         => $startTime,
                    'end_time'           => $endTime,
                    'status'             => 0,
                    'ocr_type'           => 1,
                    'source'             => SvDySearchTask::SOURCE_AUTO,
                    'auto_type'          => SvDySearchTask::AUTO_TYPE_AUTO,
                    'persona_id'         => $persona->id,
                    'remark'             => '',
                ]);

                SvDySearchTaskDevice::create([
                    'user_id'     => $device->user_id,
                    'task_id'     => $task->id,
                    'device_code' => $device->device_code,
                    'keywords'    => $keywords,
                    'status'      => 1,
                ]);

                SvDeviceTask::create([
                    'user_id'      => $device->user_id,
                    'device_code'  => $device->device_code,
                    'task_type'    => DeviceEnum::TASK_TYPE_DY_SEARCH,
                    'account'      => $account->account ?? '',
                    'account_type' => DeviceEnum::ACCOUNT_TYPE_DY,
                    'nickname'     => $account->nickname ?? '',
                    'avatar'       => $account->avatar ?? '',
                    'auto_type'    => 1,
                    'task_name'    => $taskName,
                    'time_config'  => json_encode([$execTime], JSON_UNESCAPED_UNICODE),
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'day'          => date('Y-m-d', (int)$startTime),
                    'sub_task_id'  => $task->id,
                    'persona_id'   => $persona->id,
                    'task_scene'   => DeviceEnum::AUTO_TASK_SCENE_DY_SEARCH,
                    'source'       => DeviceEnum::TASK_SOURCE_DY_SEARCH,
                    'status'       => 0,
                    'create_time'  => time(),
                ]);
                $result['created']++;
            }

            Db::commit();
            return $result;
        } catch (\Throwable $th) {
            Db::rollback();
            Log::channel('auto')->write(
                '自动创建抖音搜寻获客任务失败 ' . $device->device_code . ' ' . $th->__toString(),
                'create'
            );
            return SvDeviceTaskExistenceService::emptySlotResult();
        }
    }

    /**
     * 每台设备只取一次线索词。空词返回 null（调用方直接 return，不记 skipped_empty_schedule）。
     *
     * @return list<string>|null
     */
    private static function loadAutoDySearchKeywords(SvDevice $device, AiPersona $persona): ?array
    {
        $config = AiPersonaTrafficConfig::where('user_id', $persona->user_id)
            ->where('persona_id', $persona->id)
            ->findOrEmpty();

        $rawKeywords = [];
        if (!$config->isEmpty()) {
            $rawKeywords = $config->clue_keywords;
            if (is_string($rawKeywords)) {
                $decoded = json_decode($rawKeywords, true);
                $rawKeywords = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($rawKeywords)) {
                $rawKeywords = [];
            }
        }

        $keywords = DySearchAccountNormalizer::normalizeKeywords($rawKeywords);
        if ($keywords === []) {
            Log::channel('auto')->write($device->device_code . ' 线索词为空，跳过', 'create');
            return null;
        }
        if (count($keywords) > 200) {
            $keywords = array_slice($keywords, 0, 200);
            Log::channel('auto')->write($device->device_code . ' 线索词超过200条已截断', 'create');
        }

        return $keywords;
    }

    /**
     * 新建后收口本用户其它执行中任务。exclude 为空则跳过。
     * 仅收口 source=1 的手动任务，自动任务不受手动新建影响。
     *
     * @param list<int> $excludeTaskIds
     * @return array{user_id: int, status: int, exclude_ids: list<int>, set_status: int, source: int}|null
     */
    public static function closeRunningHistorySpec(int $userId, array $excludeTaskIds): ?array
    {
        $exclude = [];
        foreach ($excludeTaskIds as $id) {
            $id = (int)$id;
            if ($id > 0 && !isset($exclude[$id])) {
                $exclude[$id] = true;
            }
        }
        $excludeIds = array_keys($exclude);
        if ($userId < 1 || $excludeIds === []) {
            return null;
        }
        return [
            'user_id'     => $userId,
            'status'      => 1,
            'source'      => SvDySearchTask::SOURCE_MANUAL,
            'exclude_ids' => $excludeIds,
            'set_status'  => 4,
        ];
    }

    /**
     * @param list<int> $excludeTaskIds
     * @return list<int>
     */
    public static function findRunningHistoryIds(int $userId, array $excludeTaskIds): array
    {
        $spec = self::closeRunningHistorySpec($userId, $excludeTaskIds);
        if ($spec === null) {
            return [];
        }
        $ids = SvDySearchTask::where('user_id', $spec['user_id'])
            ->where('status', $spec['status'])
            ->where('source', $spec['source'])
            ->where('id', 'not in', $spec['exclude_ids'])
            ->column('id');
        $out = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /**
     * @param list<int> $excludeTaskIds
     */
    private static function closeRunningHistoryTasks(int $userId, array $excludeTaskIds): ?int
    {
        $ids = self::findRunningHistoryIds($userId, $excludeTaskIds);
        if ($ids === []) {
            return null;
        }
        foreach ($ids as $id) {
            self::dySearchCloseAlive($id, '新建任务已收口');
        }
        return count($ids);
    }

    /**
     * 按 wechat_id 在该设备上取微信账号，不回落到第一条。
     */
    private static function findDeviceWechatAccount(string $deviceCode, string $wechatId): ?SvAccount
    {
        $tokens = DySearchAccountNormalizer::splitWechatIds($wechatId);
        if ($tokens === [] || $deviceCode === '') {
            return null;
        }

        $found = SvAccount::where('type', DeviceEnum::ACCOUNT_TYPE_SPH)
            ->where('user_id', self::$uid)
            ->where('device_code', $deviceCode)
            ->where('account', 'in', $tokens)
            ->findOrEmpty();
        if (!$found->isEmpty()) {
            return $found;
        }

        $ids = [];
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $ids[] = (int)$token;
            }
        }
        if ($ids === []) {
            return null;
        }

        $found = SvAccount::where('type', DeviceEnum::ACCOUNT_TYPE_SPH)
            ->where('user_id', self::$uid)
            ->where('device_code', $deviceCode)
            ->where('id', 'in', $ids)
            ->findOrEmpty();
        return $found->isEmpty() ? null : $found;
    }

    /**
     * 设备平均分配线索，多出来的随机分配
     */
    private static function distributeKeywords(array $keywords, array $deviceCodes, int $keywordCount, int $deviceCount)
    {
        $assigned = array_fill_keys($deviceCodes, []);
        $base = (int)($keywordCount / $deviceCount);
        $remainder = $keywordCount % $deviceCount;
        $current = 0;
        foreach ($deviceCodes as $device) {
            $assigned[$device] = array_slice($keywords, $current, $base);
            $current += $base;
        }
        if ($remainder > 0) {
            $remainingKeywords = array_slice($keywords, $current);
            $randomIndexes = [];
            while (count($randomIndexes) < $remainder) {
                $rand = mt_rand(0, $deviceCount - 1);
                if (!in_array($rand, $randomIndexes)) {
                    $randomIndexes[] = $rand;
                }
            }
            foreach ($randomIndexes as $i => $deviceIndex) {
                $device = $deviceCodes[$deviceIndex];
                $assigned[$device][] = $remainingKeywords[$i];
            }
        }
        return $assigned;
    }
}
