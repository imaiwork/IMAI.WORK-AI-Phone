<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvAccount;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;
use app\common\model\sv\SvAddWechatRecord;
use think\facade\Db;
use app\common\traits\SphTaskTrait;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\sv\SvDevice;

use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatLog;
use app\common\model\ChatPrompt;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDeviceTask;

/**
 * CrawlingTaskLogic
 * 爬取任务逻辑处理
 */
class CrawlingTaskLogic extends SvBaseLogic
{
    use SphTaskTrait;
    /**
     * 添加爬取任务
     */
    public static function add(array $params)
    {
        try {
            Db::startTrans();
            $params['user_id'] = self::$uid;
            $device_codes = $params['device_codes'];
            $keywords = $params['keywords'];
            if (!is_array($device_codes) || !is_array($keywords)) {
                throw new \Exception('参数错误');
            }

            $deviceCount = count($device_codes);
            $keywordCount = count($keywords);
            if ($keywordCount < $deviceCount) {
                throw new \Exception('关键词数量不得少于所选设备数量');
            }
            $params['implementation_keywords_number'] = $keywordCount;
            $params['keywords'] = json_encode($params['keywords'], JSON_UNESCAPED_UNICODE);
            $params['device_codes'] = json_encode(array_values($device_codes), JSON_UNESCAPED_UNICODE);
            $params['name'] =  $params['name'] ?? '视频号获客任务' . date('mdHis', time()) ;
            $params['status'] = 0;
            $params['type'] = 1;
            $params['ocr_type'] = $params['ocr_type'] ?? 1;

            if (isset($params['wechat_id']) && is_string($params['wechat_id'])) {
                $params['wechat_id'] = explode(',', $params['wechat_id']);
            }
            $params['exec_add_count'] = count($params['wechat_id'])  * ((int)$params['add_number'] ?? 0);

            $params['wechat_id'] = implode(',', $params['wechat_id']);
            $params['wechat_reg_type'] = $params['wechat_reg_type'] ?? 0;

            if ((int)$params['add_type'] === 1 && empty($params['wechat_id'])) {
                throw new \Exception('请配置添加微信的客服微信');
            }
            if ((int)$params['add_remark_enable'] === 1 && empty($params['remarks'])) {
                throw new \Exception('请至少配置一条打招呼话术');
            }
            $params['remarks'] = json_encode($params['remarks'], JSON_UNESCAPED_UNICODE);

            if (!isset($params['time_config']) || empty($params['time_config'])) {
                throw new \Exception('请配置任务执行时间区间');
            }

            $devices = CrawlingTaskLogic::distributeKeywords($keywords, $device_codes, $keywordCount, $deviceCount);
            $times = \app\api\logic\device\TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $params['task_frep'], $params['custom_date']);
            $arrDeviceData = [];
            $allTaskInstall = [];
            $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            foreach ($times as $key => $time) {
                $params['start_time'] = $time['start_time'];
                $params['end_time'] = $time['end_time'];
                $task = SvCrawlingTask::create($params);
                //将关键词平均分配给设备
                foreach ($devices as $device => $val) {
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($device, DeviceEnum::TASK_TYPE_ACTIVE, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }
                    $arrDeviceData[] = [
                        'user_id'     => self::$uid,
                        'task_id'     => $task->id,
                        'device_code' => $device,
                        'keywords'    => json_encode($val, JSON_UNESCAPED_UNICODE),
                        'create_time' => time(),
                        'update_time' => time(),
                        'status'      => 1,
                    ];
                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $device,
                        'task_type' => DeviceEnum::TASK_TYPE_CLUES,
                        'account' => self::getSphAccount($device),
                        'account_type' => 1,
                        'task_name' => '视频号自动获客任务',
                        'status' => 0,
                        'day' => date('Y-m-d',$task->start_time),
                        'start_time' => $task->start_time,
                        'end_time' => $task->end_time,
                        'sub_task_id' => $task->id,
                        'source' => DeviceEnum::TASK_SOURCE_CLUES,//sv_crawling_task
                        'create_time' => time(),
                    ]);
                }
            }
            if (!empty($arrDeviceData)) {
                $bindResult = SvCrawlingTaskDeviceBind::insertAll($arrDeviceData);
                if (!$bindResult) {
                    throw new \Exception('设备任务绑定失败');
                }
            }

            $result = $task->toArray();
            $result['device_codes'] = json_decode($result['device_codes'], JSON_UNESCAPED_UNICODE);
            $result['keywords'] = json_decode($result['keywords'], JSON_UNESCAPED_UNICODE);

            //self::setOldTaskStatus($task->id);

            \app\api\logic\device\TaskLogic::add($allTaskInstall);

            //self::sphSend($task->id);
            Db::commit();
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function setOldTaskStatus(int $task_id)
    {
        SvCrawlingTask::where('id', '<', $task_id)->where('status', 'in', [0, 1, 2])->where('user_id', self::$uid)->update(['status' => 3, 'update_time' => time()]);
        SvCrawlingTaskDeviceBind::where('task_id', '<', $task_id)->where('status', 'in', [0, 1, 2])->where('user_id', self::$uid)->update(['status' => 3, 'update_time' => time()]);
        SvDeviceTask::where('sub_task_id', '<', $task_id)
            ->where('task_type', DeviceEnum::TASK_TYPE_CLUES)
            ->where('status', 'in', [0, 1])
            ->where('user_id', self::$uid)
            ->update(['status' => 4, 'update_time' => time()]);
    }

    private static function getSphAccount(string $device_code)
    {
        $find = AiWechat::where('device_code', '=', function($query) use ($device_code){
            $query->name('sv_device')->where('device_code', $device_code)->field('wechat_device_code');
        })->where('user_id', self::$uid)->findOrEmpty();
        if( !$find->isEmpty()){
            return $find->wechat_id ;
        }

         $find = SvAccount::where('device_code',  $device_code)->where('type',1)->where('user_id', self::$uid)->findOrEmpty();
        return $find->isEmpty() ? '' : $find->account;
    }
    /**
     * 获取爬取任务详情
     */
    public static function detail(array $params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('爬取任务不存在');
                return false;
            }
            $result = $task->toArray();
            $result['device_codes'] = json_decode($result['device_codes'], JSON_UNESCAPED_UNICODE);
            $result['keywords'] = json_decode($result['keywords'], JSON_UNESCAPED_UNICODE);

            $reg_content = SvCrawlingRecord::where('task_id', $result['id'])->where('reg_content', '<>', '')->group('reg_content')->column('reg_content');
            $result['crawl_number'] = $reg_content ? count(explode(",", implode(",", $reg_content))) : 0;
            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
    public static function subtasks(array $params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            if (!$task) {
                self::setError('爬取任务不存在');
                return false;
            }
             $info= $task;
            $bind = SvCrawlingTaskDeviceBind::where('task_id', $params['id'])->where('device_code', $params['device_code'])->findOrEmpty()->toArray();
            if (!$bind) {
                self::setError('子任务不存在');
                return false;
            }
            if ($info['type'] == 1){
                $where = [
                    'sd.device_code'=> $params['device_code'],
                ];
                $info['account_info'] = SvDevice::alias('sd')->field('ai.*,sd.wechat_device_code,sd.device_name,sd.device_model')->where($where)
                    ->join('ai_wechat ai', 'ai.device_code = sd.wechat_device_code', 'LEFT')
                    ->findOrEmpty()->toArray();
                $info['device_info'] = [
                    'device_name' => $info['account_info']['device_name'] ?? '',
                    'device_model' => $info['account_info']['device_model'] ?? '',
                ];
            }else{
                $where = [
                    'device_code'=> $info['device_code'],
                    'account' => $info['account']
                ];
                $info['account_info'] = SvAccount::where($where)->findOrEmpty()->toArray();
                $info['device_info'] =  SvDevice::where('device_code', $params['device_code'])->field('sd.device_name,sd.device_model')->findOrEmpty()->toArray();

            }
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


    /**
     * 更新爬取任务
     */
    public static function update(array $params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('爬取任务不存在');
                return false;
            }
            //            if ($task->status !== 0) {
            //                self::setError('爬取任务已执行，无法编辑');
            //                return false;
            //            }
            if (isset($params['device_codes'])) {
                $exec_device = SvCrawlingTaskDeviceBind::whereIn('device_code', $params['device_codes'])
                    ->where('status', '<', 3)
                    ->where('user_id', self::$uid)
                    ->select();
                if (!$exec_device->isEmpty()) {
                    throw new \Exception('该设备已存在爬取任务，请重新选择');
                }
                $params['device_codes'] = json_encode($params['device_codes'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['keywords'])) {
                $params['keywords'] = json_encode($params['keywords'], JSON_UNESCAPED_UNICODE);
            }
            SvCrawlingTask::where('id', $params['id'])->update($params);
            self::$returnData = SvCrawlingTask::find($params['id'])->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function changeStatus(array $params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('爬取任务不存在');
                return false;
            }
            if ((int)$params['status'] === 2) {
                self::sphPause($task['id']);
                $task->status = 2;
                SvCrawlingTaskDeviceBind::where('task_id', $task['id'])->where('user_id', self::$uid)->where('status', 1)->update(['status' => 2, 'update_time' => time()]);
            } else {
                self::sphRecovery($task['id']);
                $task->status = 1;
                SvCrawlingTaskDeviceBind::where('task_id', $task['id'])->where('user_id', self::$uid)->where('status', 2)->update(['status' => 1, 'update_time' => time()]);
            }
            $task->update_time = time();
            $task->save();



            self::$returnData = $task->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function keywordsCount(array $params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('爬取任务不存在');
                return false;
            }
            $keywords = json_decode($task->keywords, true);
            $record = SvCrawlingRecord::field('exec_keyword, count(DISTINCT reg_content) as count')
                ->where('task_id', $task['id'])
                ->where('reg_content', 'not in', ['', null])
                ->group('exec_keyword')
                ->column('count(DISTINCT reg_content) as count', 'exec_keyword');
            $result = array();
            foreach ($keywords as $key => $keyword) {
                $tmp = array(
                    'count' => isset($record[$keyword]) ? $record[$keyword] : 0,
                    'status' => isset($record[$keyword]) ? 3 : 0,
                    'keyword' =>  $keyword,
                    'device_code' => '',

                );
                $result[$keyword] = $tmp;
            }

            $binds = SvCrawlingTaskDeviceBind::where('task_id', $task['id'])->where('user_id', self::$uid)->select();
            foreach ($binds as $bind) {
                if (!empty($bind['exec_keyword'])) {
                    $result[$bind['exec_keyword']]['status'] = $bind['status'];
                    $result[$bind['exec_keyword']]['device_code'] = $bind['device_code'];
                }
            }
            $data = array_values($result);
            array_multisort(array_column($data, 'status'), SORT_DESC, $data);

            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }



    /**
     * 删除爬取任务
     */
    //    public static function delete($id)
    //    {
    //        try {
    //            if (is_string($id)) {
    //                SvCrawlingTask::destroy(['id' => $id, 'user_id' => self::$uid]);
    //                SvCrawlingTaskDeviceBind::destroy(['task_id' => $id, 'user_id' => self::$uid]);
    //            } else {
    //                SvCrawlingTask::whereIn('id', $id)->where('user_id', self::$uid)->select()->each(function ($item){
    //                    SvCrawlingTaskDeviceBind::destroy(['task_id' => $item['id'], 'user_id' => self::$uid]);
    //                })->delete();
    //            }
    //            return true;
    //        } catch (\Exception $e) {
    //            self::setError($e->getMessage());
    //            return false;
    //        }
    //    }

    /**
     * 删除未开启的爬取任务绑定设备
     */
    public static function deleteDevice($params)
    {
        try {
            $task = SvCrawlingTask::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('爬取任务不存在');
                return false;
            }
            if ($task->status !== 0) {
                self::setError('爬取任务已执行，无法删除');
                return false;
            }
            $devices = SvCrawlingTaskDeviceBind::where('device_code', $params['device_code'])->where('task_id', $params['id'])->where('status', 0)->select();
            if ($devices->isEmpty()) {
                self::setError('设备未绑定该任务');
                return false;
            }
            $devices->delete();
            //删除任务中的设备
            $device_codes = json_decode($task->device_codes, true);
            $key = array_search($params['device_code'], $device_codes);
            // 存在则删除
            if ($key !== false) {
                unset($device_codes[$key]);
                $device_codes = array_values($device_codes);
            }
            $task->device_codes = json_encode($device_codes, JSON_UNESCAPED_UNICODE);
            $task->save();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function ocr(array $params = [])
    {
        try {
            \think\facade\Log::channel('device')->write("ocr " . json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $find = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            $response = \app\common\service\ToolsService::Sv()->ocr($params);
            if (isset($response['code']) && $response['code'] == 10000) {
                $task = SvCrawlingTask::where('id', $params['task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setError('爬取任务不存在');
                    return false;
                }
                $device_codes = json_decode($task->device_codes, true);
                if (!in_array($params['device_code'], $device_codes)) {
                    self::setError("{$params['device_code']}设备不在该任务{$params['task_id']}中");
                    return false;
                }

                //检查扣费
                $unit = TokenLogService::checkToken($task['user_id'], 'sph_ocr');
                //计算消耗tokens
                $points = $unit;
                $task_id = generate_unique_task_id();
                //token扣除
                User::userTokensChange($task['user_id'], $points);
                $extra = ['算力单价' => $unit . '算力/次', '实际消耗算力' => $points, '场景' => 'ocr'];
                //扣费记录
                AccountLogLogic::recordUserTokensLog(true, $task['user_id'], AccountLogEnum::TOKENS_DEC_SPH_OCR, $points, $task_id, $extra);

                self::$returnData = $response;
                return true;
            } else {
                self::setError($response['msg'] ?? '请求失败');
                return false;
            }
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function localOcr(array $params = [])
    {
        try {
            \think\facade\Log::channel('device')->write("localOcr " . json_encode($params, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            $find = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            $response = \app\common\service\ToolsService::Sv()->localOcr($params);
            if (isset($response['code']) && $response['code'] == 10000) {
                $task = SvCrawlingTask::where('id', $params['task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setError('爬取任务不存在');
                    return false;
                }
                $device_codes = json_decode($task->device_codes, true);
                if (!in_array($params['device_code'], $device_codes)) {
                    self::setError("{$params['device_code']}设备不在该任务{$params['task_id']}中");
                    return false;
                }

                //检查扣费
                $unit = TokenLogService::checkToken($task['user_id'], 'sph_local_ocr');
                //计算消耗tokens
                $points = $unit;
                $task_id = generate_unique_task_id();
                //token扣除
                User::userTokensChange($task['user_id'], $points);
                $extra = ['算力单价' => $unit . '算力/次', '实际消耗算力' => $points, '场景' => '本地识别'];
                //扣费记录
                AccountLogLogic::recordUserTokensLog(true, $task['user_id'], AccountLogEnum::TOKENS_DEC_SPH_LOCAL_OCR, $points, $task_id, $extra);

                self::$returnData = $response;
                return true;
            } else {
                self::setError($response['msg'] ?? '请求失败');
                return false;
            }
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设备平均分配关键词，多出来的随机分配
     */
    private static function distributeKeywords(array $keywords, array $device_codes, int $keywordCount, int $deviceCount)
    {
        // 初始化分配结果：每个设备对应空数组
        $assigned = array_fill_keys($device_codes, []);
        // 1. 计算基础分配数量和剩余数量
        $base = (int)($keywordCount / $deviceCount); // 每个设备至少分配的数量
        $remainder = $keywordCount % $deviceCount;  // 剩余需要随机分配的数量
        // 2. 平均分配基础数量的关键词
        $current = 0; // 关键词数组的当前索引
        foreach ($device_codes as $device) {
            // 每个设备先分配base个关键词（从当前索引开始截取）
            $assigned[$device] = array_slice($keywords, $current, $base);
            $current += $base;
        }
        // 3. 处理剩余的关键词（随机分配给不同设备，每个设备最多多1个）
        if ($remainder > 0) {
            // 获取剩余的关键词（从current索引到末尾）
            $remainingKeywords = array_slice($keywords, $current);

            // 随机选择$remainder个不重复的设备索引（避免同一设备重复分配剩余关键词）
            $randomIndexes = [];
            while (count($randomIndexes) < $remainder) {
                $rand = mt_rand(0, $deviceCount - 1);
                if (!in_array($rand, $randomIndexes)) {
                    $randomIndexes[] = $rand;
                }
            }

            // 为选中的设备各分配1个剩余关键词
            foreach ($randomIndexes as $i => $deviceIndex) {
                $device = $device_codes[$deviceIndex];
                $assigned[$device][] = $remainingKeywords[$i];
            }
        }

        return $assigned;
    }



    public static function sphCluesAddWechat()
    {
        print_r("\n获客加微\n");
        try {
            $records = SvAddWechatRecord::alias('r')
                ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
                ->join('sv_crawling_task t', 'r.crawling_task_id = t.id and t.delete_time is null')
                ->where('t.add_type', 1)
                ->where('r.channel', 1)
                ->where('r.status', 'in', [3, 4, 5])
                ->where('t.wechat_id', 'not in', ['', null]) // 过滤掉wechat_id为空的记录
                //->where('t.status', 'in', [1, 2]) // 过滤掉已完成、已暂停、已删除的任务
                ->whereRaw('t.exec_add_count > t.completed_add_count') // 过滤掉已执行加微次数大于等于注册类型的记录
                ->limit(100)
                ->order('r.id', 'desc')
                ->select()
                ->toArray();

            // $records = SvCrawlingTask::alias('t')
            //     ->field('r.*, t.add_number, t.add_interval_time, t.add_friends_prompt, t.add_remark_enable, t.remarks, t.wechat_id, t.wechat_reg_type')
            //     ->join('sv_add_wechat_record r', 'r.crawling_task_id = t.id')
            //     ->where('t.add_type', 1)
            //     ->where('r.channel', 4)
            //     ->where('r.status', 'in', [3, 4, 5])
            //     ->where('t.wechat_id', 'not in', ['', null]) // 过滤掉wechat_id为空的记录
            //     ->where('t.status', 'in', [1, 2]) // 过滤掉已完成、已暂停、已删除的任务
            //     ->whereRaw('t.exec_add_count > t.completed_add_count') // 过滤掉已执行加微次数大于等于注册类型的记录
            //     ->limit(100)
            //     ->order('r.create_time', 'asc')
            //     ->select()
            //     ->toArray();
            if (empty($records)) {
                self::setLog(Db::getLastSql(), 'add_wechat');
                return false;
            }
            //print_r(Db::getLastSql());die;
            foreach ($records as $record) {
                $task = SvCrawlingTask::where('id', $record['crawling_task_id'])->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setLog(Db::getLastSql(), 'add_wechat');
                    self::setLog('线索任务不存在', 'add_wechat');
                    continue;
                }
                if ($task->completed_add_count >= $task->exec_add_count) {
                    SvAddWechatRecord::where('crawling_task_id', $record['crawling_task_id'])
                        ->where('status', 'in', [3, 4, 5])
                        ->update([
                            'status' => 0,
                            'result' => '已完成当前添加任务',
                            'update_time' => time(),
                        ]);
                    self::setLog('已完成当前添加任务', 'add_wechat');
                    continue;
                }
                $wxPattern = '/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/';
                if (preg_match($wxPattern, $record['reg_wechat'])) {
                    $response = \app\common\service\ToolsService::Sv()->queryResult([
                        "string" => $record['reg_wechat'],
                    ]);

                    if (isset($response['code']) && (int)$response['code'] === 10000) {
                        if (is_null($response['data'])) {
                            self::setLog($record['reg_wechat'] . '该账号还未开始验证', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['reg_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['status']) && (int)$response['data']['status'] === 0) {
                            self::setLog($record['reg_wechat'] . '该账号还未完成验证,稍后再试', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            $response = \app\common\service\ToolsService::Sv()->validateStrings([
                                "strings" => [$record['reg_wechat']],
                            ]);
                            self::setLog($response, 'add_wechat');
                            continue;
                        }

                        if (isset($response['data']['valid']) && (bool)$response['data']['valid'] === false) {
                            self::setLog($record['reg_wechat'] . '该账号不是有效的微信号,忽略', 'add_wechat');
                            self::setLog($response, 'add_wechat');
                            SvAddWechatRecord::where('id', $record['id'])->update([
                                'status' => 0,
                                'result' => '该线索经过校验为无效线索',
                                'update_time' => time(),
                            ]);
                            $find = SvCrawlingRecord::where('user_id', $record['user_id'])
                                ->where('task_id', $record['crawling_task_id'])
                                ->where('reg_content', 'like', "%{$record['reg_wechat']}%")
                                ->limit(1)->findOrEmpty();
                            if (!$find->isEmpty()) {
                                $find->status = 2; //无效
                                $find->update_time = time();
                                $find->save();
                            }
                            continue;
                        } else {
                            $find = SvCrawlingRecord::where('user_id', $record['user_id'])
                                ->where('task_id', $record['crawling_task_id'])
                                ->where('reg_content', 'like', "%{$record['reg_wechat']}%")
                                ->limit(1)->findOrEmpty();
                            if (!$find->isEmpty()) {
                                $wx = explode(',', $find->reg_content);
                                $find->status = count($wx) > 1 ? 3 : 1; //3既有无效又有有效 1有效
                                $find->update_time = time();
                                $find->save();
                            }
                        }
                    }
                }


                // 处理加微逻辑
                $wechat_ids = explode(',', $record['wechat_id']);
                $useWechat = [];
                foreach ($wechat_ids as $wechat_id) {
                    //计算微信加微间隔
                    $interval_find = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', '>', (time() - ((int)$record['add_interval_time'] * 60)))
                        ->order('id', 'desc')
                        ->findOrEmpty();
                    if (!$interval_find->isEmpty()) {
                        //self::setLog('当前微信' . $wechat_id . '加微间隔未到', 'add_wechat');
                        continue;
                    }

                    $addCount = AiWechatLog::where('user_id', $record['user_id'])
                        ->where('log_type', AiWechatLog::TYPE_ACCEPT_FRIEND)
                        ->where('wechat_id', $wechat_id)
                        ->where('create_time', 'between', [strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59'))])
                        ->count();
                    if ($addCount >= $record['add_number']) {
                        //self::setLog('当前微信' . $wechat_id . '今日加微信次数已到', 'add_wechat');
                        continue;
                    }
                    array_push($useWechat, $wechat_id);
                }

                if (empty($useWechat)) {
                    //self::setLog('当前无可以使用的微信账号', 'add_wechat');
                    SvAddWechatRecord::where('id', $record['id'])->update([
                        'status' => 5,
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
                    self::setLog(Db::getLastSql(), 'add_wechat');
                    self::setLog($wechat, 'add_wechat');
                    self::sendChannelAddWechatMessage([
                        'WechatId' => $wechat['wechat_id'],
                        'DeviceCode' => $wechat['device_code'],
                        'Phones' => $record['reg_wechat'],
                        'message' => self::createGreetingMessage($record, $record['user_id']), //ai生成打招呼消息
                    ], $wechat, $record);
                } else {
                    SvAddWechatRecord::where('id', $record['id'])->update([
                        'status' => 3,
                        'result' => '当前账号存在安全风险，暂停添加',
                        'update_time' => time(),
                    ]);
                    //self::setLog('冷却中，等待后可继续添加', 'add_wechat');
                    continue;
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setLog('异常信息' . $e, 'add_wechat');
            return false;
        }
    }

    private static function createGreetingMessage(array $task, int $user_id)
    {

        try {
            if (isset($task['add_remark_enable']) && (int)$task['add_remark_enable'] === 1) {
                $remarks = json_decode($task['remarks'], true) ?? [];
                $remark = $remarks[array_rand($remarks)] ?? '您好！';
                return $remark;
            }
            return '';

            $returnContent = '';
            //获取提示词
            $keyword = $task['add_friends_prompt'] != '' ? $task['add_friends_prompt'] : (ChatPrompt::where('prompt_name', '加好友内容')->value('prompt_text') ?? '');
            $request = [
                'stream' => false,
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $keyword],
                    [
                        'role' => 'user',
                        'content' => empty($task['remark']) ? '您好!' : $task['remark'],
                    ],
                ],
                'user_id' => $user_id,
                'task_id' => generate_unique_task_id(),
                'chat_type' => AccountLogEnum::TOKENS_DEC_OPENAI_CHAT,
                'now'       => time(),
            ];
            $response = \app\common\service\ToolsService::Sv()->openaiChat($request);
            if (isset($response['code']) && $response['code'] == 10000) {
                // 处理响应
                $returnContent = self::_handleResponse($response, $request['model'], $request['task_id'], $user_id);
            } else {
                self::setLog($request['task_id'] . '队列请求失败' . json_encode($response), 'add_wechat');
            }
            return $returnContent;
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e, 'add_wechat');
            return '';
        }
    }

    /**
     * 处理响应
     * @param array $response
     * @return string
     */
    private static function _handleResponse(array $response, string $model, string $task_id, int $user_id)

    {
        try {
            $scene = 'openai_chat';
            //检查扣费
            $unit = TokenLogService::checkToken($user_id, $scene);
            // 获取回复内容
            $reply = $response['data']['message'] ?? '';
            //计费
            $tokens = $response['data']['usage']['total_tokens'] ?? 0;
            if (!$reply || $tokens == 0) {
                throw new \Exception('获取内容失败');
            }

            $response = [
                'reply' => $reply,
                'usage_tokens' => $response['data']['usage'] ?? [],
            ];
            //计算消耗tokens
            $points = $unit > 0 ? round($tokens / $unit, 2) : 0;
            //token扣除
            User::userTokensChange($user_id, $points);

            $extra = ['总消耗tokens数' => $tokens, '算力单价' => $unit, '实际消耗算力' => $points, '场景' => '视频号获客加好友内容'];
            $desc = AccountLogEnum::TOKENS_DEC_OPENAI_CHAT;
            //扣费记录
            AccountLogLogic::recordUserTokensLog(true, $user_id, $desc, $points, $task_id, $extra);

            return $reply;
        } catch (\Exception $e) {
            self::setLog('异常信息' . $e, 'add_wechat');
            return '';
        }
    }

    private static function sendChannelAddWechatMessage(array $payload, AiWechat $wechat, array $record)
    {
        try {
            //进程通信
            $message = [
                'DeviceId' => $payload['DeviceCode'],
                'WeChatId' => $payload['WechatId'],
                'Phones' => [$payload['Phones']],
                'Message' => $payload['message'],
                'TaskId' => $record['task_id'],
                'Remark' => $payload['Remark'] ?? '',
            ];
            self::setLog($message, 'add_wechat');
            $content = \app\common\workerman\wechat\handlers\client\AddFriendsTaskHandler::handle($message);
            $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
            $message->setMsgType($content['MsgType']);
            $any = new \Google\Protobuf\Any();
            $any->pack($content['Content']);
            $message->setContent($any);
            $pushMessage = $message->serializeToString();

            $channel = "socket.{$payload['DeviceCode']}.message";
            self::setLog('channel: ' . $channel, 'add_wechat');

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
                'log_type' => AiWechatLog::TYPE_ACCEPT_FRIEND,
                'friend_id' => $payload['Phones'],
                'create_time' => time()
            ]);
            SvAddWechatRecord::where('id', $record['id'])->update([
                'wechat_no' => $wechat->wechat_id,
                'wechat_name' => $wechat->wechat_nickname,
                'status' => 2,
                'result' => '执行中',
                'update_time' => time(),
            ]);

            $completed_add_count = SvCrawlingTask::where('id', $record['crawling_task_id'])->value('completed_add_count');
            SvCrawlingTask::where('id', $record['crawling_task_id'])->update([
                'completed_add_count' => $completed_add_count + 1,
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            self::setLog('异常信息' . $e, 'add_wechat');
        }
    }
}
