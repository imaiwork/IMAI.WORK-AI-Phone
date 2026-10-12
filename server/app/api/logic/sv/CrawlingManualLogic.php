<?php

namespace app\api\logic\sv;


use think\facade\Db;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\sv\SvAddWechatRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;

/**
 * CrawlingManualLogic
 * 爬取任务逻辑处理
 */
class CrawlingManualLogic extends SvBaseLogic
{
    /**
     * 添加爬取任务
     */
    public static function add(array $params)
    {
        try {
            self::checkAutoDevice($params);
            Db::startTrans();

            $is_overlap = $params['task_exec_type'] ?? 0;
            if ((int)$is_overlap === 1) {
                \app\api\logic\device\TaskLogic::updateTaskStatusByIds($params['task_ids']);
                $params['time_config'] = [
                    date('H:i', time()) . '-' . date('H:i', (time() + (60 * (int)$params['minutes']))),
                ];
                $params['custom_date'] = [
                    date('Y-m-d', time())
                ];
                unset($params['task_ids']);
            }
            $devices = SvDevice::where('device_code', 'in', $params['device_codes'])->select()->toArray();
            //$wechats = AiWechat::where('wechat_id', 'in', $params['wechat_id'])->select()->toArray();
            $times = \app\api\logic\device\TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            if ((int)$is_overlap === 0) {
                foreach ($devices as $device) {
                    foreach ($times as $time) {
                        list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($device['device_code'], DeviceEnum::TASK_TYPE_FRIENDS, $time['start_time'], $time['end_time'], self::$uid);
                        if (!$isOverlap) {
                            $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                            $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                            throw new \Exception($msg);
                        }
                    }
                }
            }


            $params['name'] =  $params['name'] ??  '批量新增线索任务' . date('mdHis', time());
            $params['crawling_task_ids'] = json_encode($params['crawling_task_ids'] ?? [], JSON_UNESCAPED_UNICODE);
            $params['add_friend_type'] = (int)($params['add_friend_type'] ?? 1);
            $params['remarks'] = json_encode($params['remarks'], JSON_UNESCAPED_UNICODE);
            $params['wechat_id'] = implode(',', $params['wechat_id']);
            $params['device_codes'] = json_encode($params['device_codes'], JSON_UNESCAPED_UNICODE);
            $params['user_id'] = self::$uid;
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            $params['custom_date'] = json_encode($params['custom_date'], JSON_UNESCAPED_UNICODE);
            $params['start_time'] = min(array_column($times, 'start_time'));
            $params['end_time'] = max(array_column($times, 'end_time'));

            $task = SvCrawlingManualTask::create($params);
            $recordData = array();
            if ($params['source'] == 1) {
                $recordData = self::_getRecordByFile($params['fileurl'], $task);
            } elseif ($params['source'] == 2) {
                $recordData = self::_getRecordByAutoClue($params['crawling_task_ids'], $task);
            } elseif ((int)$params['source'] === 3) {
                $recordData = self::_getRecordByDySearchClue($params['crawling_task_ids'], $task);
            } elseif ((int)$params['source'] === 4) {
                // 客资线索动态获取：不快照号码，由 crawling_manual_cron 持续补号
                if ($task->crawling_task_ids !== '[]') {
                    $task->crawling_task_ids = '[]';
                    $task->exec_add_count = $task->add_number ?? 0;
                    $task->save();
                }
            }
            if (!empty($recordData)) {
                SvCrawlingManualTaskRecord::insertAll($recordData);
                $task->exec_add_count = count($recordData);
                $task->save();
            }
            if ((int)($params['add_friend_type'] ?? 1) === 2) {
                $task->exec_add_count = (int)$params['add_number'];
                $task->save();
            }
            $allTaskInstall = array();
            foreach ($devices as $device) {
                $account = SvAccount::where('device_code', $device['device_code'])->where('user_id', self::$uid)->where('type', 1)->find();
                foreach ($times as $time) {
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $device['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_FRIENDS,
                        'account' => $account->account ?? '',
                        'account_type' => 1,
                        'nickname' => $account->nickname ?? '',
                        'avatar' => $account->avatar ?? '', 
                        'task_name' => '设备自动加微任务',
                        'status' => 0,
                        'day' => date('Y-m-d', $time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'time_config' => $params['time_config'],
                        'sub_task_id' => $task->id,
                        'source' => DeviceEnum::TASK_SOURCE_FRIENDS, //sv_crawling_manual_task
                        'create_time' => time(),
                    ]);
                    \app\api\logic\device\TaskLogic::updateWechatRpaTaskTime($device['device_code'], $time['start_time']);
                }
            }
            \app\api\logic\device\TaskLogic::add($allTaskInstall);
            Db::commit();
            self::$returnData = $task->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function getWechatAccount(string $device_code)
    {
        if ($device_code == '') {
            return '';
        }
        $wechat = AiWechat::where('device_code', $device_code)->findOrEmpty();
        if ($wechat->isEmpty()) {
            return '';
        }
        return $wechat->wechat_id;
    }

    private static function _getRecordByFile(string $fileurl, SvCrawlingManualTask $task)
    {
        try {
            $fileContent = self::downloadExcelWithCurl($fileurl);
            // 创建临时文件
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_curl_');
            file_put_contents($tempFile, $fileContent);
            // 读取 Excel 文件
            $spreadsheet = IOFactory::load($tempFile);
            // 清理临时文件
            @unlink($tempFile);

            $worksheet = $spreadsheet->getActiveSheet();
            $items = $worksheet->toArray();
            $recordData = array();
            foreach ($items as $key => $item) {
                if ($key == 0) {
                    continue;
                }
                $tmps = array_filter(explode(',', $item[0]));
                foreach ($tmps as $tkey => $tmp) {
                    // $find = SvCrawlingManualTaskRecord::where('clue_wechat', $tmp)->where('user_id', $task->user_id)->findOrEmpty();
                    // if (!$find->isEmpty()) {
                    //     continue;
                    // }

                    if (array_key_exists($tmp, $recordData)) {
                        continue;
                    }
                    $recordData[$tmp] = [
                        'user_id' => $task->user_id,
                        'task_id' => $task->id,
                        'clue_wechat' => $tmp,
                        'status' => 4,
                        'create_time' => time()
                    ];
                }
            }
            unset($items);
            return array_values($recordData);
        } catch (\Exception $e) {
            throw new \Exception('读取失败: ' . $e->getMessage());
        }
    }


    private static function downloadExcelWithCurl(string $url)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $fileContent = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL 错误: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('HTTP 错误: ' . $httpCode);
        }

        return $fileContent;
    }

    private static function _getRecordByAutoClue(string $crawling_task_ids, SvCrawlingManualTask $task)
    {
        $recordData = array();
        $crawling_task_ids = json_decode($crawling_task_ids, true);
        $items = SvAddWechatRecord::alias('a')
            ->join('sv_crawling_task t', 'a.crawling_task_id = t.id', 'left')
            ->where('a.crawling_task_id', 'in', $crawling_task_ids)
            ->where('t.status', 'in', [3, 4])
            ->select()
            ->toArray();
        foreach ($items as $item) {
            // $find = SvCrawlingManualTaskRecord::where('clue_wechat', $item['reg_wechat'])->where('user_id', $task->user_id)->findOrEmpty();
            // if (!$find->isEmpty()) {
            //     continue;
            // }
            if (array_key_exists($item['reg_wechat'], $recordData)) {
                continue;
            }
            $recordData[$item['reg_wechat']] = [
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'clue_wechat' => $item['reg_wechat'],
                'status' => 4,
                'create_time' => time()
            ];
        }
        unset($items);
        return array_values($recordData);
    }

    /**
     * 复刻 source=2：从加微队按抖音获客任务 id 取号。
     */
    private static function _getRecordByDySearchClue(string $crawling_task_ids, SvCrawlingManualTask $task): array
    {
        $recordData = array();
        $crawling_task_ids = json_decode($crawling_task_ids, true);
        $items = SvAddWechatRecord::alias('a')
            ->join('sv_dy_search_task t', 'a.dy_search_task_id = t.id and t.delete_time is null')
            ->where('a.dy_search_task_id', 'in', $crawling_task_ids)
            ->where('a.user_id', $task->user_id)
            ->where('a.channel', \app\common\service\display\DySearchIntentionMapper::ADD_CHANNEL)
            ->where('a.status', 'in', [4, 5])
            ->where('t.status', 'in', [3, 4])
            ->select()
            ->toArray();
        foreach ($items as $item) {
            if (array_key_exists($item['reg_wechat'], $recordData)) {
                continue;
            }
            $recordData[$item['reg_wechat']] = [
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'clue_wechat' => $item['reg_wechat'],
                'status' => 4,
                'create_time' => time()
            ];
        }
        unset($items);
        return array_values($recordData);
    }


    public static function detail(array $params)
    {
        try {
            //code...
            $result = SvCrawlingManualTask::where('id', $params['id'])->findOrEmpty();
            if ($result->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            $data =  $result->toArray();
            $data['wechat_id'] = explode(',', $data['wechat_id']);
            $data['crawling_task_ids'] = json_decode($data['crawling_task_ids'], true);
            $data['remarks'] = json_decode($data['remarks'], true);
            $data['wechat_count'] = count($data['wechat_id']);
            self::$returnData = $data;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function delete(array $params)
    {
        try {
            //code...
            $find = SvCrawlingManualTask::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            SvDeviceTask::where('sub_task_id', $params['id'])->where('task_type', DeviceEnum::TASK_TYPE_FRIENDS)->select()->delete();
            // 先删除记录
            SvCrawlingManualTaskRecord::where('task_id', $params['id'])->select()->delete();
            $find->delete();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function change(array $params)
    {
        try {
            //code...
            $result = SvCrawlingManualTask::where('id', $params['id'])->findOrEmpty();
            if ($result->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            $result->status = $params['status'];
            $result->save();
            self::$returnData = $result->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function recordDelete(array $params)
    {
        try {
            //code...
            $find = SvCrawlingManualTaskRecord::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('记录不存在');
                return false;
            }
            $find->delete();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function crawlingManualTaskCron()
    {
        try {
            //code...
            $records = SvCrawlingManualTaskRecord::alias('a')
                ->field('a.*')
                ->field('t.add_type, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_manual_task t', 'a.task_id = t.id')
                ->where('t.status', 'in', [0, 1])
                ->where('t.source', '<>', 4)
                ->where('t.add_friend_type', '<>', 2)
                ->where('a.status', 4)
                ->order('a.id', 'desc')
                ->limit(50)
                ->select()
                ->toArray();
            if (empty($records)) {
                self::setError('没有可处理的记录');
                return false;
            }
            foreach ($records as $record) {
                $task = SvCrawlingManualTask::where('id', $record['task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setLog('线索任务不存在');
                    continue;
                }
                if ($task->completed_add_count >= $task->exec_add_count) {
                    $task->status = 3;
                    $task->update_time = time();
                    $task->save();
                    continue;
                } else {
                    if (is_null($task->start_time)) {
                        $task->start_time = time();
                    }
                    $task->status = 1;
                    $task->update_time = time();
                    $task->save();
                }

                $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                if (preg_match($wxPattern, $record['clue_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['clue_wechat'],
                    ]);
                    if (isset($response['code']) && (int)$response['code'] === 10005) {
                        continue;
                    }
                    if (isset($response['code']) && (int)$response['code'] === 10000) {
                        if (is_null($response['data'])) {
                            self::setLog($record['clue_wechat'] . '该账号还未开始验证');
                            self::setLog($response);
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['clue_wechat']],
                            ]);
                            self::setLog($response);
                            continue;
                        }

                        if (isset($response['data']['status']) && (int)$response['data']['status'] === 0) {
                            self::setLog($record['clue_wechat'] . '该账号还未完成验证,稍后再试');
                            self::setLog($response);
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['clue_wechat']],
                            ]);
                            self::setLog($response);
                            continue;
                        }

                        if (isset($response['data']['valid']) && (bool)$response['data']['valid'] === false) {
                            self::setLog($record['clue_wechat'] . '该账号不是有效的微信号,忽略');
                            self::setLog($response);
                            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                                'status' => 0,
                                'result' => '该线索经过校验为无效线索',
                                'update_time' => time(),
                            ]);
                            continue;
                        }
                    }
                }


                // 处理加微逻辑
                $wechat_ids = explode(',', $record['wechat_id']);
                $useWechat = [];
                foreach ($wechat_ids as $wechat_id) {
                    //计算微信加微间隔
                    $interval_find = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', 0)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', '>', (time() - ((int)$record['add_interval_time'] * 60)))
                        ->order('id', 'desc')
                        ->findOrEmpty();
                    if (!$interval_find->isEmpty()) {
                        self::setLog('当前微信' . $wechat_id . '加微间隔未到');
                        continue;
                    }

                    $addCount = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', 0)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                        ->count();
                    if ($addCount >= $record['add_number']) {
                        self::setLog('当前微信' . $wechat_id . '今日加微信次数已到');
                        continue;
                    }
                    array_push($useWechat, $wechat_id);
                }

                if (empty($useWechat)) {
                    self::setLog('当前无可以使用的微信账号');
                    SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                        'status' => 4,
                        'result' => '冷却中，等待后可继续添加',
                        'update_time' => time(),
                    ]);
                    continue;
                }

                $currentTime = time(); // 获取当前时间戳
                $coolingThreshold = $currentTime - 1800; // 2小时前的时间戳（7200秒）
                $wechat = AiWechat::field('*')
                    ->where('wechat_id', 'in', $useWechat)
                    ->where(function ($query) use ($coolingThreshold) {
                        $query->where('is_cooling', 0)->whereOr('cooling_time', '<', $coolingThreshold);
                    })
                    ->where('wechat_status', 1)
                    ->order('update_time asc')->limit(1)->findOrEmpty();
                if (!$wechat->isEmpty()) {
                    self::setLog(Db::getLastSql());
                    self::setLog($wechat);
                    self::_sendChannelAddWechatMessage([
                        'WechatId' => $wechat['wechat_id'],
                        'DeviceCode' => $wechat['device_code'],
                        'Phones' => $record['clue_wechat'],
                        'message' => self::_createGreetingMessage($record), //ai生成打招呼消息
                    ], $wechat, $record);
                } else {
                    SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                        'status' => 0,
                        'result' => '当前账号存在安全风险，暂停添加',
                        'update_time' => time(),
                    ]);
                    self::setLog('冷却中，等待后可继续添加');
                    continue;
                }
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function _sendChannelAddWechatMessage(array $payload, AiWechat $wechat, array $record)
    {
        try {
            //进程通信
            $request = [
                'DeviceId' => $payload['DeviceCode'],
                'WeChatId' => $payload['WechatId'],
                'Phones' => [$payload['Phones']],
                'Message' => $payload['message'],
                'TaskId' => time() . (1000 + (int)$record['id']),
                'Remark' => $payload['Remark'] ?? '',
            ];
            self::setLog($request, 'device');
            $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($request);
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            $channel = "socket.{$payload['DeviceCode']}.message";
            self::setLog('channel: ' . $channel, 'device');

            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            ]);

            // 下发成功，先落记录字段，避免后续微信表/日志写失败导致记录漏更新
            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->wechat_id,
                'wechat_name' => $wechat->wechat_nickname,
                'wechat_avatar' => $wechat->wechat_avatar,
                'remark' => $request['Message'],
                'exec_task_id' => $request['TaskId'],
                'exec_time' => date('Y-m-d H:i:s', time()),
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);
            self::_syncClaimedAddWechatTaskId($record, (string)$request['TaskId'], $wechat);

            $taskId = (int)$record['task_id'];
            $isRetry = trim((string)($record['exec_task_id'] ?? '')) !== '';
            if (!$isRetry) {
                SvCrawlingManualTask::where('id', $taskId)->inc('completed_add_count')->update([
                    'status' => 1,
                    'update_time' => time(),
                ]);
            } else {
                SvCrawlingManualTask::where('id', $taskId)->update([
                    'status' => 1,
                    'update_time' => time(),
                ]);
                self::setLog("任务 {$taskId} 记录 {$record['id']} 为重试下发，不累加 completed_add_count", 'device');
            }
            $taskRow = SvCrawlingManualTask::where('id', $taskId)
                ->field('add_friend_type,add_number,completed_add_count')
                ->find();
            if ($taskRow && (int)$taskRow['add_friend_type'] === 2
                && (int)$taskRow['add_number'] > 0
                && (int)$taskRow['completed_add_count'] >= (int)$taskRow['add_number']) {
                SvCrawlingManualTask::where('id', $taskId)->update([
                    'status' => 3,
                    'update_time' => time(),
                ]);
                self::setLog("任务 {$taskId} 已下发 {$taskRow['completed_add_count']}/{$taskRow['add_number']} 条，置为已完成", 'device');
            }

            $wechat->is_cooling = 0;
            $wechat->cooling_time = 0;
            $wechat->update_time = time();
            $wechat->save();

            AiWechatLog::create([
                'user_id' => $wechat->user_id,
                'wechat_id' => $wechat->wechat_id,
                'log_type' => 0,
                'friend_id' => $payload['Phones'],
                'create_time' => time()
            ]);
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e->__toString(), 'device');
        }
    }

    private static function _createGreetingMessage(array $task)
    {
        if (isset($task['add_remark_enable']) && (int)$task['add_remark_enable'] === 1) {
            $remarks = json_decode($task['remarks'], true) ?? [];
            $remark = $remarks[array_rand($remarks)] ?? '您好！';
            return $remark;
        }
        return '';
    }

    private static function setLog(mixed $content, $type = 'crontab')
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        \think\facade\Log::channel($type)->write($content);
    }


    public static function subtasks(array $params)
    {
        try {
            $task = SvCrawlingManualTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$task) {
                self::setError('加好友任务不存在');
                return false;
            }
            $info = $task;
            $record = SvCrawlingManualTaskRecord::where('task_id', $params['id'])->select()->toArray();
            if (!$record) {
                self::setError('子任务不存在');
                return false;
            }
            $where = [
                'wechat_no' => $record[0]['wechat_no']
            ];
            $info['account_info'] = AiWechat::where($where)->findOrEmpty()->toArray();
            $info['task_name'] = $params['task_name'];
            $info['task_category'] = $params['task_category'];
            //$bind['keywords'] = json_decode($bind['keywords'], JSON_UNESCAPED_UNICODE);
            $info['start_time'] = date('H:i', $info['start_time']);
            $info['end_time'] = date('H:i', $info['end_time']);
            //$info['info'] = $bind;
            self::$returnData = $info;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 检查当前用户在 iw_ai_wechat 中是否存在账号数据
     */
    public static function checkWechat()
    {
        try {
            $exists = AiWechat::where('user_id', self::$uid)->findOrEmpty()->isEmpty() === false;
            self::$returnData = ['exists' => $exists];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 待加微信线索数量
     */
    public static function clues()
    {
        try {
            $pending = SvAddWechatRecord::where('user_id', self::$uid)
                ->where('status', 4)
                ->count();
            $added = SvAddWechatRecord::where('user_id', self::$uid)
                ->where('status', 1)
                ->count();
            self::$returnData = [
                'added_count' => $added,
                'clue_count' => $pending,
                'clue_total' => $pending + $added,
            ];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    /**
     * 把 AiWechatLog.create_time 转成 unix。ThinkPHP datetime_format 取出是
     * "Y-m-d H:i:s"，直接 (int) 会变成年份 2026，间隔永远判定为已到。
     */
    public static function parseSaleLogTime(mixed $value): int
    {
        if ($value === null || $value === false || $value === '') {
            return 0;
        }
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $n = (int)$value;
            return $n > 1000000000 ? $n : 0;
        }
        if (is_string($value)) {
            $t = strtotime($value);
            return $t !== false ? $t : 0;
        }
        return 0;
    }

    /**
     * 加微间隔是否已到。按「整分钟」算：间隔 1 分钟则下一分钟即可再发，
     * 避免秒级比较在每分钟 cron 上差几秒变成隔 2 分钟才发。
     */
    public static function isSaleWechatIntervalReady(int $lastLogTime, int $intervalMinutes, int $now): bool
    {
        if ($intervalMinutes <= 0 || $lastLogTime <= 0) {
            return true;
        }
        return intdiv($lastLogTime, 60) + $intervalMinutes <= intdiv($now, 60);
    }

    /**
     * 是否允许从客资池领号。仅 AI销售 + source=4（客资线索动态获取）。
     * source=1/2/3 只下发建任务时写入的 SvCrawlingManualTaskRecord。
     */
    public static function shouldClaimSaleFromAddWechat(array $task): bool
    {
        return (int)($task['add_friend_type'] ?? 0) === 2
            && (int)($task['source'] ?? 0) === 4;
    }

    /**
     * ai销售加微任务：时间窗口内从任务记录下发；source=4 记录空时再从客资池领号
     */
    public static function crawlingSaleManualTaskCron()
    {
        try {
            self::setLog('====== ai销售加微任务 cron 开始 ======', 'device');
            $tasks = SvCrawlingManualTask::where('status', 'in', [0, 1])
                ->where('add_friend_type', 2)
                ->order('id', 'asc')
                ->select()
                ->toArray();
            if (empty($tasks)) {
                self::setLog('无待处理的 ai销售加微任务', 'device');
                return true;
            }
            self::setLog('共捞取待处理 ai销售任务 ' . count($tasks) . ' 条', 'device');
            $now = time();
            foreach ($tasks as $task) {
                $taskId = (int)$task['id'];
                self::setLog("---- 处理任务 {$taskId} [{$task['name']}] source={$task['source']} ----", 'device');
                if ((int)$task['start_time'] > 0 && $now < (int)$task['start_time']) {
                    self::setLog("任务 {$taskId} 未到开始时间，跳过（start_time=" . date('Y-m-d H:i:s', $task['start_time']) . "）", 'device');
                    continue;
                }
                if ((int)$task['end_time'] > 0 && $now > (int)$task['end_time']) {
                    self::setLog("任务 {$taskId} 已过结束时间，跳过（end_time=" . date('Y-m-d H:i:s', $task['end_time']) . "）", 'device');
                    continue;
                }

                // AI销售任务加微次数以 add_number 为准（exec_add_count 建任务时会写成快照条数）
                $execCount = (int)$task['add_number'] > 0 ? (int)$task['add_number'] : (int)$task['exec_add_count'];
                if ($execCount < 1) {
                    self::setLog("任务 {$taskId} 未配置执行数量，跳过", 'device');
                    continue;
                }
                self::setLog("任务 {$taskId} 目标执行 {$execCount} 条，已完成 " . (int)$task['completed_add_count'] . " 条", 'device');
                if ((int)$task['completed_add_count'] >= $execCount) {
                    self::setLog("任务 {$taskId} 已满足执行数量，置为已完成(status=3)", 'device');
                    SvCrawlingManualTask::where('id', $taskId)->update([
                        'status' => 3,
                        'update_time' => time(),
                    ]);
                    continue;
                }

                self::_dispatchSaleRecords($task, $execCount);
            }
            self::setLog('====== ai销售加微任务 cron 结束 ======', 'device');
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            self::setLog('ai销售加微任务 cron 异常：' . $th->getMessage(), 'device');
            self::setLog($th->getTraceAsString(), 'device');
            return false;
        }
    }

    /**
     * 从客资池领取一条待加号码进任务记录表，源记录置执行中防重复领取。
     * 返回新建记录数组；无可领号码返回 null。
     */
    private static function _claimSaleRecordFromAddWechat(array $task): ?array
    {
        if (!self::shouldClaimSaleFromAddWechat($task)) {
            return null;
        }
        $taskId = (int)$task['id'];
        $existing = SvCrawlingManualTaskRecord::where('task_id', $taskId)->column('clue_wechat');
        $query = SvAddWechatRecord::where('user_id', $task['user_id'])
            ->where('status', 4)
            ->where('reg_wechat', '<>', '');
        if (!empty($existing)) {
            $query->where('reg_wechat', 'not in', $existing);
        }
        $item = $query->order('id', 'desc')->findOrEmpty();
        if ($item->isEmpty()) {
            self::setLog("任务 {$taskId} 客资池暂无待加号码(status=4)，等待后续入库", 'device');
            return null;
        }
        $claimed = SvAddWechatRecord::where('id', $item['id'])
            ->where('status', 4)
            ->update([
                'status' => 2,
                'result' => '已领取，等待执行加微',
                'update_time' => time(),
            ]);
        if ((int)$claimed < 1) {
            self::setLog("客资记录 {$item['id']} 领取失败(可能已被其他任务抢占)，跳过", 'device');
            return null;
        }
        $record = SvCrawlingManualTaskRecord::create([
            'user_id' => $task['user_id'],
            'task_id' => $taskId,
            'clue_wechat' => $item['reg_wechat'],
            'status' => 4,
            'create_time' => time(),
        ]);
        self::setLog("任务 {$taskId} 已领取客资 {$item['id']} → 微信号 {$item['reg_wechat']}，生成记录 {$record->id}", 'device');
        $row = $record->toArray();
        $row['add_wechat_record_id'] = (int)$item['id'];
        return $row;
    }

    /**
     * 客资池领取的号码：把下发 TaskId 写回 sv_add_wechat_record.task_id，
     * 与手动记录 exec_task_id 一致，供 AddFriendsTaskOpt 按 TaskId 回写，避免回池重复加。
     */
    private static function _syncClaimedAddWechatTaskId(array $record, string $execTaskId, AiWechat $wechat): void
    {
        $query = SvAddWechatRecord::where('status', 2);
        if (!empty($record['add_wechat_record_id'])) {
            $query->where('id', (int)$record['add_wechat_record_id']);
        } else {
            $clue = (string)($record['clue_wechat'] ?? '');
            if ($clue === '' || empty($record['user_id'])) {
                return;
            }
            $query->where('user_id', $record['user_id'])->where('reg_wechat', $clue);
        }
        $updated = $query->update([
            'task_id' => $execTaskId,
            'wechat_no' => $wechat->wechat_id,
            'wechat_name' => $wechat->wechat_nickname,
            'wechat_avatar' => $wechat->wechat_avatar,
            'result' => '执行中',
            'update_time' => time(),
        ]);
        if ((int)$updated > 0) {
            self::setLog("已同步客资记录 task_id={$execTaskId}（与手动记录 exec_task_id 一致）", 'device');
        }
    }

    private static function _dispatchSaleRecords(array $task, int $execCount): void
    {
        $taskId = (int)$task['id'];

        // source=1/2/3/4 都先从任务记录表取待下发号码
        $records = SvCrawlingManualTaskRecord::where('task_id', $taskId)
            ->where('status', 4)
            ->where(function ($query) {
                $query->whereNull('exec_task_id')
                    ->whereOr('exec_task_id', '')
                    ->whereOr('exec_task_id', '0');
            })
            ->order('id', 'asc')
            ->limit(10)
            ->select()
            ->toArray();
        if ($records !== []) {
            self::_dispatchSaleRecordRows($task, $records, $execCount);
            return;
        }

        // 只有 source=4：记录表没有待下发数据时，才从客资池领号
        if (!self::shouldClaimSaleFromAddWechat($task)) {
            self::setLog("任务 {$taskId} 记录表无待下发号码且非客资线索来源，本轮结束", 'device');
            return;
        }
        $max = 10;
        $usedWechatIds = [];
        while ($max-- > 0) {
            $completed = (int)SvCrawlingManualTask::where('id', $taskId)->value('completed_add_count');
            if ($completed >= $execCount) {
                self::setLog("任务 {$taskId} 已满足执行数量 {$execCount} 条，置为已完成(status=3)", 'device');
                SvCrawlingManualTask::where('id', $taskId)->update([
                    'status' => 3,
                    'update_time' => time(),
                ]);
                return;
            }
            $wechat = self::_pickSaleWechat($task, $usedWechatIds);
            if ($wechat === null) {
                self::setLog("任务 {$taskId} 无可用微信账号(间隔未到)，停止领号", 'device');
                return;
            }
            $record = self::_claimSaleRecordFromAddWechat($task);
            if ($record === null) {
                return;
            }
            self::setLog("任务 {$taskId} 记录 {$record['id']} 下发加微：微信号 {$record['clue_wechat']} → 执行微信 {$wechat['wechat_id']}(设备 {$wechat['device_code']})", 'device');
            self::_sendChannelAddWechatMessage([
                'WechatId' => $wechat['wechat_id'],
                'DeviceCode' => $wechat['device_code'],
                'Phones' => $record['clue_wechat'],
                'message' => self::_createGreetingMessage(array_merge($record, $task)),
            ], $wechat, $record);
            $usedWechatIds[] = (string)$wechat['wechat_id'];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    private static function _dispatchSaleRecordRows(array $task, array $records, int $execCount): void
    {
        $taskId = (int)$task['id'];
        $usedWechatIds = [];
        foreach ($records as $record) {
            $completed = (int)SvCrawlingManualTask::where('id', $taskId)->value('completed_add_count');
            if ($completed >= $execCount) {
                self::setLog("任务 {$taskId} 已满足执行数量 {$execCount} 条，置为已完成(status=3)", 'device');
                SvCrawlingManualTask::where('id', $taskId)->update([
                    'status' => 3,
                    'update_time' => time(),
                ]);
                return;
            }
            $wechat = self::_pickSaleWechat($task, $usedWechatIds);
            if ($wechat === null) {
                self::setLog("任务 {$taskId} 当前无可用微信账号(间隔未到)，等待下轮，不领新号", 'device');
                return;
            }
            self::setLog("任务 {$taskId} 记录 {$record['id']} 下发加微：微信号 {$record['clue_wechat']} → 执行微信 {$wechat['wechat_id']}(设备 {$wechat['device_code']})", 'device');
            self::_sendChannelAddWechatMessage([
                'WechatId' => $wechat['wechat_id'],
                'DeviceCode' => $wechat['device_code'],
                'Phones' => $record['clue_wechat'],
                'message' => self::_createGreetingMessage(array_merge($record, $task)),
            ], $wechat, $record);
            $usedWechatIds[] = (string)$wechat['wechat_id'];
        }
    }

    /**
     * 按任务设置的多个微信筛选可用号：加微间隔未到的跳过，
     * 本轮已下发过的跳过，再排除冷却中与状态异常的，取最久未使用的一个。
     *
     * @param array<int, string> $usedWechatIds 本轮 cron 已向其下发过的微信号
     */
    private static function _pickSaleWechat(array $task, array $usedWechatIds = []): ?AiWechat
    {
        $taskId = (int)$task['id'];
        $wechatIds = array_filter(array_map('trim', explode(',', (string)$task['wechat_id'])));
        if (empty($wechatIds)) {
            self::setLog("任务 {$taskId} 未配置加微微信(wechat_id为空)，无法选号", 'device');
            return null;
        }
        $useWechat = [];
        $intervalMinutes = (int)$task['add_interval_time'];
        foreach ($wechatIds as $wechatId) {
            if (in_array((string)$wechatId, $usedWechatIds, true)) {
                self::setLog("任务 {$taskId} 微信 {$wechatId} 本轮已下发，跳过（同一秒不连发）", 'device');
                continue;
            }
            $lastLog = AiWechatLog::where('user_id', $task['user_id'])
                ->where('log_type', 0)
                ->where('wechat_id', $wechatId)
                ->order('id', 'desc')
                ->findOrEmpty();
            $lastLogTime = $lastLog->isEmpty() ? 0 : self::parseSaleLogTime($lastLog['create_time']);
            if (!self::isSaleWechatIntervalReady($lastLogTime, $intervalMinutes, time())) {
                self::setLog("任务 {$taskId} 微信 {$wechatId} 加微间隔未到，跳过", 'device');
                continue;
            }
            $useWechat[] = $wechatId;
        }
        if (empty($useWechat)) {
            self::setLog("任务 {$taskId} 全部微信均不可用(间隔未到)", 'device');
            return null;
        }
        $coolingThreshold = time() - 1800;
        $wechat = AiWechat::where('user_id', $task['user_id'])
            ->where('wechat_id', 'in', $useWechat)
            ->where(function ($query) use ($coolingThreshold) {
                $query->where('is_cooling', 0)->whereOr('cooling_time', '<', $coolingThreshold);
            })
            ->where('wechat_status', 1)
            ->order('update_time', 'asc')
            ->limit(1)
            ->findOrEmpty();
        if ($wechat->isEmpty()) {
            self::setLog("任务 {$taskId} 可用候选微信均处于冷却或离线状态，无法选号", 'device');
            return null;
        }
        self::setLog("任务 {$taskId} 选中执行微信 {$wechat['wechat_id']}", 'device');
        return $wechat;
    }
}
