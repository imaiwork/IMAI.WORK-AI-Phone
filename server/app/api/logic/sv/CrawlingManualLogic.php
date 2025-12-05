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
            Db::startTrans();
            $devices = SvDevice::where('device_code', 'in', $params['device_codes'])->select()->toArray();
            //$wechats = AiWechat::where('wechat_id', 'in', $params['wechat_id'])->select()->toArray();
            $times = \app\api\logic\device\TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            foreach($devices as $device){
                foreach($times as $time){
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($device['device_code'], DeviceEnum::TASK_TYPE_FRIENDS, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }
                }
            }

            $params['name'] =  $params['name'] ??  '批量新增线索任务' . date('mdHis', time()) ;
            $params['crawling_task_ids'] = json_encode($params['crawling_task_ids'], JSON_UNESCAPED_UNICODE);
            $params['remarks'] = json_encode($params['remarks'], JSON_UNESCAPED_UNICODE);
            $params['wechat_id'] = implode(',', $params['wechat_id']);
            $params['device_codes'] = json_encode($params['device_codes'], JSON_UNESCAPED_UNICODE);
            $params['user_id'] = self::$uid;
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            $params['custom_date'] = json_encode($params['custom_date'], JSON_UNESCAPED_UNICODE);
            $task = SvCrawlingManualTask::create($params);
            $recordData = array();
            if ($params['source'] == 1) {
                $recordData = self::_getRecordByFile($params['fileurl'], $task);
            } elseif ($params['source'] == 2) {
                $recordData = self::_getRecordByAutoClue($params['crawling_task_ids'], $task);
            }
            if (!empty($recordData)) {
                SvCrawlingManualTaskRecord::insertAll($recordData);
                $task->exec_add_count = count($recordData);
                $task->save();
            }
            $allTaskInstall = array();
            foreach($devices as $device){
                foreach($times as $time){
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $device['device_code'],
                        'task_type' => DeviceEnum::TASK_TYPE_FRIENDS,
                        'account' => is_null($device['wechat_device_code']) ? '' : self::getWechatAccount($device['wechat_device_code']),
                        'account_type' => 1,
                        'task_name' => '设备自动加微任务',
                        'status' => 0,
                        'day' => date('Y-m-d',$time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $task->id,
                        'source' => DeviceEnum::TASK_SOURCE_FRIENDS,//sv_crawling_manual_task
                        'create_time' => time(),
                    ]);
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
        if($device_code == ''){
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
                foreach ($tmps as $key => $tmp) {
                    $find = SvCrawlingManualTaskRecord::where('clue_wechat', $tmp)->where('user_id', $task->user_id)->findOrEmpty();
                    if (!$find->isEmpty()) {
                        continue;
                    }
                    $recordData[] = [
                        'user_id' => $task->user_id,
                        'task_id' => $task->id,
                        'clue_wechat' => $tmp,
                        'status' => 4,
                        'create_time' => time()
                    ];
                }
            }
            unset($items);
            return $recordData;
        } catch (\Exception $e) {
            throw new \Exception('读取失败: ' . $e->getMessage());
        }
    }


    private static function downloadExcelWithCurl($url)
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
            $find = SvCrawlingManualTaskRecord::where('clue_wechat', $item['reg_wechat'])->where('user_id', $task->user_id)->findOrEmpty();
            if (!$find->isEmpty()) {
                continue;
            }
            $recordData[] = [
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'clue_wechat' => $item['reg_wechat'],
                'status' => 4,
                'create_time' => time()
            ];
        }
        unset($items);
        return $recordData;
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
                ->where('a.status', 4)
                ->order('a.update_time', 'asc')
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
                }else{
                    if(is_null($task->start_time)){
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
            self::setLog($request);
            $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($request);
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            $channel = "socket.{$payload['DeviceCode']}.message";
            self::setLog('channel: ' . $channel);

            \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            \Channel\Client::publish($channel, [
                'data' => is_array($pushMessage) ? json_encode($pushMessage) : $pushMessage
            ]);
            //$wechat->add_num += 1;
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
            SvCrawlingManualTaskRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->wechat_id,
                'wechat_name' => $wechat->wechat_nickname,
                'remark' => $request['Message'],
                'exec_task_id' => $request['TaskId'],
                'exec_time' => date('Y-m-d H:i:s', time()),
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);

            $completed_add_count = SvCrawlingManualTask::where('id', $record['task_id'])->value('completed_add_count');
            SvCrawlingManualTask::where('id', $record['task_id'])->update([
                'completed_add_count' => $completed_add_count + 1,
                'status' => 1,
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e->__toString());
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

    private static function setLog($content, $type = 'crontab')
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
            $info= $task;
            $record= SvCrawlingManualTaskRecord::where('task_id', $params['id'])->select()->toArray();
            if (!$record) {
                self::setError('子任务不存在');
                return false;
            }
            var_dump($record);
            $where = [
                'wechat_no' => $record[0]['wechat_no']
            ];
            $info['account_info'] = AiWechat::where($where)->findOrEmpty()->toArray();

            dd($info);
            $info['task_name'] = $params['task_name'];
            $info['task_category'] = $params['task_category'];
            $bind['keywords'] = json_decode($bind['keywords'], JSON_UNESCAPED_UNICODE);
            $info['start_time'] = date('H:i',$info['start_time']);
            $info['end_time'] = date('H:i',$info['end_time']);
            $info['info'] = $bind;
            self::$returnData = $info;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}
