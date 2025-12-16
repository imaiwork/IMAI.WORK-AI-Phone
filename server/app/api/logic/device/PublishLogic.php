<?php

namespace app\api\logic\device;

use app\common\model\sv\SvDeviceTask;
use think\facade\Db;
use app\api\logic\sv\SvBaseLogic;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\service\FileService;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\wechat\AiWechat;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvMatrixMediaSetting;
use app\common\enum\DeviceEnum;
use Channel\Client as ChannelClient;

/**
 * PublishLogic
 * @desc 任务发布计划
 * @author Qasim
 */
class PublishLogic extends SvBaseLogic
{

    protected static $interval = 3600; //视频发布间隔时间（秒）
    /**
     * @desc 添加任务发布计划
     * @param array $params
     * @return bool
     */
    public static function add(array $params)
    {

        // 启动事务
        Db::startTrans();
        try {
            $params['user_id'] = self::$uid;
            self::checkPublishTime($params);
            if (isset($params['accounts']) && is_array($params['accounts'])) {
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            }

            if (isset($params['publish_json']) && is_array($params['publish_json'])) {
                $params['publish_json'] = json_encode($params['publish_json'], JSON_UNESCAPED_UNICODE);
            }

            $params['video_setting_id'] = $params['video_setting_id'] ?? 0;
            $params['matrix_media_setting_id'] = $params['matrix_media_setting_id'] ?? 0;
            $params['task_type'] = 3;
            $params['publish_frep'] = $params['publish_frep'] ?? 2;
            $params['publish_start'] = date('Y-m-d', time());
            $params['type'] = 0;

            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }
            //print_r($params);die;
            // 添加
            $publish = SvPublishSetting::create($params);
            if (!$publish->isEmpty()) {
                if (!isset($params['accounts'])) {
                    //创建空任务
                    SvPublishSettingAccount::create([
                        'publish_id' => $publish->id,
                        'user_id' => self::$uid,
                        'name' => $params['name'],
                        'published_count' => 0,
                        'video_setting_id' => $params['video_setting_id'],
                        'matrix_media_setting_id' => $params['matrix_media_setting_id'],
                        'media_type' => $params['media_type'],
                        'poi' => $params['poi'] ?? '',
                        'publish_start' => $params['publish_start'] ?? date('Y-m-d', time()),
                        'publish_end' => $params['publish_end'] ?? date('Y-m-d', time()),
                        'status' => 0,
                        'created_time' => time(),
                    ]);
                } else {
                    self::batchPushlishAccount($publish, $params);
                }
            }
            // 提交事务
            Db::commit();
            self::$returnData = $publish->toArray();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 更新任务发布计划
     * @param array $params
     * @return bool
     */
    public static function update(array $params)
    {

        Db::startTrans();
        try {
            // 检查任务发布计划是否存在
            $publish = SvPublishSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            //查询任务明细是否存在
            $publishDetial = SvPublishSettingDetail::where('publish_id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if (!$publishDetial->isEmpty() && $publishDetial['status'] !== 0) {
                self::setError('任务正在执行中，不能修改');
                return false;
            }

            if (is_array($params['accounts'])) {
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            }
            if (is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }

            $params['publish_start'] = date('Y-m-d', strtotime('+1 day'));
            $params['update_time'] = time();
            // 更新
            SvPublishSetting::where('id', $publish->id)->update($params);
            SvPublishSettingAccount::where('publish_id', $publish->id)->select()->delete();
            SvPublishSettingDetail::where('publish_id', $publish->id)->select()->delete();
            self::batchPushlishAccount($publish, $params);
            Db::commit();
            self::$returnData = $publish->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function checkPublishTime($params)
    {
        try {
            $accounts = $params['accounts'] ?? [];
            $mediaSettings = SvMatrixMediaSetting::where('id',  $params['matrix_media_setting_id'])->where('user_id', self::$uid)->select()->toArray();
            if (empty($mediaSettings)) {
                throw new \Exception('视频任务不存在');
            }
            $mediaCount = array_sum(array_column($mediaSettings, 'media_count'));
            // 实现媒体分配逻辑
            $allocatedAccounts = self::allocateMediaToAccounts($accounts, null, $mediaCount);
            //            print_r($allocatedAccounts);die;
            foreach ($allocatedAccounts as $account) {
                if ($account['count'] == 0) {
                    continue;
                }
                $days = ceil($account['count'] / $params['publish_frep']);
                $times = \app\api\logic\device\TaskLogic::getTimes($params['time_config'], date('Y-m-d', time()), $days);
                $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                $account = array_merge($account, $find);
                foreach ($times as $time) {
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_PUBLISH, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }
                }
            }
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
    private static function batchPushlishAccount($publish, $params)
    {
        try {
            $time_config = json_decode($params['time_config'], true);
            $accounts = json_decode($params['accounts'], true);
            $accountTypes = array_count_values(array_column($accounts, 'type'));
            $mediaSettings = SvMatrixMediaSetting::where('id',  $params['matrix_media_setting_id'])->where('user_id', self::$uid)->select()->toArray();
            if (empty($mediaSettings)) {
                return;
            }
            $mediaCount = array_sum(array_column($mediaSettings, 'media_count'));
            // 实现媒体分配逻辑
            $allocatedAccounts = self::allocateMediaToAccounts($accounts, $accountTypes, $mediaCount);

            $tmpTime = strpos($time_config[0], '-') !== false ? explode('-', $time_config[0])[0] : $time_config[0];
            $nextPublishTime = date('Y-m-d H:i:s', strtotime($params['publish_start'] . ' ' . $tmpTime));
            $allTaskInstall = [];
            foreach ($allocatedAccounts as $key => $account) {
                if ($account['count'] == 0) {
                    continue;
                }

                $days = ceil($account['count'] / $params['publish_frep']);
                $times = \app\api\logic\device\TaskLogic::getTimes($time_config, date('Y-m-d', time()), $days);

                $find = SvAccount::where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                if ($find->isEmpty()) {
                    throw new \Exception('帐号不存在');
                }
                $find = $find->toArray();
                $account = array_merge($account, $find);

                $pubAccount =  SvPublishSettingAccount::create([
                    'publish_id' => $publish->id,
                    'user_id' => self::$uid,
                    'task_type' => 3,
                    'name' => $params['name'],
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'matrix_media_setting_id' => $params['matrix_media_setting_id'],
                    'video_setting_id' => 0,
                    'poi' => $params['poi'] ?? '',
                    'media_type' => $params['media_type'],
                    'publish_start' => $params['publish_start'],
                    'publish_end' => $params['publish_end'] ?? null,
                    'next_publish_time' => $nextPublishTime, //视频发布时间
                    'count' => $account['count'],
                    'published_count' => 0,
                    'status' => 1,
                    'scene' => $params['scene'],
                    'created_time' => time(),
                ]);
                $task_name = $params['media_type'] == 1 ? '矩阵自动发视频' : '矩阵自动发图文';
                $num = 1;
                foreach ($times as $time) {
                    if ($num > $account['count']) {
                        break;
                    }
                    $num++;
                    list($isOverlap, $lap) = \app\api\logic\device\TaskLogic::isTaskTimeOverlapping($account['device_code'], DeviceEnum::TASK_TYPE_PUBLISH, $time['start_time'], $time['end_time'], self::$uid);
                    if (!$isOverlap) {
                        $timeMsg = "【" . date('Y-m-d H:i', $lap['start_time']) . "-" . date('Y-m-d H:i', $lap['end_time']) . "】";
                        $msg = "您在{$timeMsg}的【" . DeviceEnum::getAccountTypeDesc($lap['account_type']) . DeviceEnum::getTaskTypeDesc($lap['task_type'])  . "】与当前所选时间冲突";
                        throw new \Exception($msg);
                    }

                    array_push($allTaskInstall, [
                        'user_id' => self::$uid,
                        'device_code' => $pubAccount->device_code,
                        'task_type' => DeviceEnum::TASK_TYPE_PUBLISH,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'task_name' => $task_name,
                        'status' => 0,
                        'day' => date('Y-m-d', $time['start_time']),
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'sub_task_id' => $pubAccount->id,
                        'source' => DeviceEnum::TASK_SOURCE_PUBLISH, //sv_publish_setting_account
                        'create_time' => time(),
                    ]);
                }
            }
            //print_r($allTaskInstall);die;
            \app\api\logic\device\TaskLogic::add($allTaskInstall);
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    // 添加分配媒体的静态方法
    private static function allocateMediaToAccounts($accounts, $accountTypes, $mediaCount)
    {
        $allocatedAccounts = [];
        // 按账号类型分组
        $accountsByType = [];
        foreach ($accounts as $account) {
            $type = $account['type'];
            if (!isset($accountsByType[$type])) {
                $accountsByType[$type] = [];
            }
            $accountsByType[$type][] = $account;
        }
        // 为每个账号类型分配媒体
        foreach ($accountsByType as $type => $typeAccounts) {
            $typeAccountCount = count($typeAccounts);

            // 如果媒体数量远少于账号数量（1条视频多个同类型账号），随机选一个账号分配
            if ($mediaCount < $typeAccountCount) {
                //$randomIndex = array_rand($typeAccounts);
                foreach ($typeAccounts as $index => $account) {
                    $account['count'] = ($index < $mediaCount) ? 1 : 0;
                    $allocatedAccounts[] = $account;
                }
            } else {
                // 媒体数量充足，同类型账号平均分配
                $baseCount = floor($mediaCount / $typeAccountCount);
                $remainder = $mediaCount % $typeAccountCount;

                foreach ($typeAccounts as $index => $account) {
                    $account['count'] = $baseCount + ($index < $remainder ? 1 : 0);
                    $allocatedAccounts[] = $account;
                }
            }
        }

        return $allocatedAccounts;
    }

    public static function change(array $params)
    {
        $find = SvPublishSetting::where('id', $params['id'])->findOrEmpty();
        if ($find->isEmpty()) {
            self::setError('任务不存在');
            return false;
        }
        $find->status = $params['status'];
        $find->updated_time = time();
        $find->save();
        self::$returnData = $find->refresh()->toArray();
        return true;
    }

    /**
     * @desc 获取任务详情
     * @param array $params
     * @return bool
     */
    public static function detail(array $params)
    {
        try {
            // 检查机器人是否存在
            $publish = SvPublishSetting::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            $publish['accounts'] = is_null($publish['accounts']) ? [] : json_decode($publish['accounts'], true);
            $publish['time_config'] = is_null($publish['time_config']) ? [] : json_decode($publish['time_config'], true);
            $publish['publish_json'] = is_null($publish['publish_json']) ? [] : json_decode($publish['publish_json'], true);
            self::$returnData = $publish->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取账号详情
     * @param array $params
     * @return bool
     */
    public static function accountDetail(array $params)
    {
        try {
            // 检查机器人是否存在
            $publishAccount = SvPublishSettingAccount::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($publishAccount->isEmpty()) {
                self::setError('账号不存在');
                return false;
            }
            $device = SvDevice::where('device_code', $publishAccount['device_code'])->findOrEmpty();
            $publishAccount->device_name = $device->isEmpty() ? '' : (is_null($device['device_name']) ? $device['device_model'] : $device['device_name']);
            $publishAccount->task_category = \app\common\enum\DeviceEnum::getAccountTypeDesc($publishAccount['account_type']) . ($publishAccount->media_type == 1 ? '视频' : '图文') . '发布';
            $publishAccount->account_name = SvAccount::where('account', $publishAccount->account)->where('type', $publishAccount->account_type)->value('nickname');
            self::$returnData = $publishAccount->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 删除任务发布计划
     * @param array $params
     * @return bool
     */
    public static function accountDelete($id)
    {
        Db::startTrans();
        try {

            if (is_string($id)) {
                $task = SvPublishSettingAccount::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setError('任务不存在');
                    return false;
                }

                SvPublishSetting::where('id', $task['publish_id'])->where('user_id', self::$uid)->select()->delete();
                SvPublishSettingDetail::where('publish_id', $task['publish_id'])->where('user_id', self::$uid)->select()->delete();
                SvDeviceTask::where('sub_task_id', $task['id'])->where('device_code', $task['device_code'])->where('task_type', DeviceEnum::TASK_TYPE_PUBLISH)->select()->delete();
                $task->delete();
            } else {

                foreach ($id as $_id) {
                    $task = SvPublishSettingAccount::where('id', $_id)->where('user_id', self::$uid)->findOrEmpty();
                    if ($task->isEmpty()) {
                        self::setError('账号不存在');
                        return false;
                    }
                    SvPublishSetting::where('id', $task['publish_id'])->where('user_id', self::$uid)->select()->delete();
                    SvPublishSettingDetail::where('publish_id', $task['publish_id'])->where('user_id', self::$uid)->select()->delete();
                    SvDeviceTask::where('sub_task_id', $task['id'])->where('device_code', $task['device_code'])->where('task_type', DeviceEnum::TASK_TYPE_PUBLISH)->select()->delete();
                    $task->delete();
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function accountUpdate(array $params)
    {
        try {
            // 检查机器人是否存在
            $publishAccount = SvPublishSettingAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($publishAccount->isEmpty()) {
                self::setError('账号任务不存在');
                return false;
            }
            $publishAccount->save($params);
            self::$returnData = $publishAccount->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @desc 删除任务发布计划
     * @param array $params
     * @return bool
     */
    public static function delete($id)
    {
        Db::startTrans();
        try {

            if (is_string($id)) {
                $task = SvPublishSetting::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
                if ($task->isEmpty()) {
                    self::setError('任务不存在');
                    return false;
                }
                $task->delete();
                SvPublishSettingAccount::where('publish_id', $task['id'])->where('user_id', self::$uid)->select()->delete();
                SvPublishSettingDetail::where('publish_id', $task['id'])->where('user_id', self::$uid)->select()->delete();
            } else {
                $task = SvPublishSetting::whereIn('id', $id)->where(['user_id' => self::$uid])
                    ->column('id');
                if (!$task) {
                    self::setError('视频任务不存在或状态不允许删除');
                    return false;
                }
                SvPublishSetting::whereIn('id', $id)->select()->delete();
                SvPublishSettingAccount::whereIn('publish_id', $id)->where('user_id', self::$uid)->select()->delete();
                SvPublishSettingDetail::whereIn('publish_id', $id)->where('user_id', self::$uid)->select()->delete();
            }
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
            // 检查任务发布计划是否存在
            $record = SvPublishSettingDetail::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('任务记录不存在');
                return false;
            }
            $result = $record->toArray();
            if ($result['account_type'] === 1) {
                $account = AiWechat::alias('a')
                    ->join('ai_wechat_device d', 'a.device_code = d.device_code and a.user_id = d.user_id')
                    ->where('a.user_id', $result['user_id'])
                    ->where('a.wechat_id', $result['account'])
                    ->where('a.device_code', $result['device_code'])
                    ->field('a.wechat_nickname as nickname, a.wechat_avatar as avatar, d.device_model, d.sdk_version')
                    ->findOrEmpty();
            } elseif ($result['account_type'] === 3) {
                $account = SvAccount::alias('a')
                    ->join('sv_device d', 'a.device_code = d.device_code and a.user_id = d.user_id')
                    ->where('a.user_id', $result['user_id'])
                    ->where('a.account', $result['account'])
                    ->where('a.device_code', $result['device_code'])
                    ->field('a.nickname, a.avatar, d.device_model, d.sdk_version')
                    ->findOrEmpty();
            }
            if ($account->isEmpty()) {
                $result['nickname'] = '';
                $result['avatar'] = '';
                $result['device_model'] = '';
                $result['sdk_version'] = '';
            } else {
                $result['nickname'] = $account['nickname'];
                $result['avatar'] = $account['avatar'];
                $result['device_model'] = $account['device_model'];
                $result['sdk_version'] = $account['sdk_version'];
            }

            self::$returnData = $result;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function recordDelete($id)
    {

        if (is_string($id)) {
            $task = SvPublishSettingDetail::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            $account  = SvPublishSettingAccount::where('publish_id', $task['publish_id'])->where('account', $task['account'])->where('user_id', self::$uid)->findOrEmpty();

            if ($account->isEmpty()) {
                self::setError('账号发布信息');
                return false;
            }
            SvDeviceTask::where('sub_task_id', $account['id'])->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)->where('account', $task['account'])->where('user_id', self::$uid)->select()->delete();
            $account->delete();
            $task->delete();
        } else {
            self::setError('不可以批量删除');
            return false;
            $task = SvPublishSettingDetail::whereIn('id', $id)->where(['user_id' => self::$uid])
                ->column('id');
            if (!$task) {
                self::setError('视频任务不存在或状态不允许删除');
                return false;
            }
            SvPublishSettingDetail::whereIn('id', $id)->select()->delete();
        }

        return true;
    }
    public static function recordRetry(array $params)
    {
        try {
            if (time() > strtotime($params['retry_time'])) {
                self::setError('重试时间不能小于当前时间');
                return false;
            }
            // 检查任务发布计划是否存在
            $record = SvPublishSettingDetail::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('任务记录不存在');
                return false;
            }

            $setting = SvPublishSetting::where('id', $record['publish_id'])->limit(1)->find();
            if (empty($setting)) {
                self::setError('任务配置不存在');
                return false;
            }
            $time_config = json_decode($setting['time_config'], true);
            if (empty($time_config)) {
                $time_config = [
                    [
                        'start_time' => date('H:i', time() + 600), // 开始时间
                        'end_time' => '23:59' // 结束时间
                    ]
                ];
            }
            $periods = array_map(function ($item) use ($setting) {
                return [
                    'start' => strtotime("{$setting['publish_start']} {$item['start_time']}:00"),
                    'end' => strtotime("{$setting['publish_end']} {$item['end_time']}:00")
                ];
            }, $time_config);
            //print_r($periods);die;
            if (strtotime($params['retry_time']) > $periods[0]['end']) {
                self::setError('重试时间不在任务时间段内');
                return false;
            }
            $record->status = 0;
            $record->publish_time = $params['retry_time'];
            $record->save();

            self::$returnData = $record->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function recordRepublish(array $params)
    {
        try {
            $record = SvPublishSettingDetail::field('*')
                ->where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($record->isEmpty()) {
                self::setError('任务记录不存在');
                return false;
            }

            if (isset($params['time']) && !empty($params['time']) && strtotime($params['time']) < time()) {
                self::setError('重新发布时间不能小于当前时间');
                return false;
            }

            $record->status = 5; //重新发布
            $record->publish_time = $params['time'] != '' ? $params['time'] : date('Y-m-d H:i:s', time() + 90);
            $record->save();

            self::$returnData = $record->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function testAdd(array $params)
    {
        Db::startTrans();
        try {
            $device = SvDevice::where('status', 1)->where('user_id', self::$uid)->order('id asc')->limit(1)->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('没有在线设备');
                return false;
            }

            if (mb_strlen($params['title'], 'utf-8') > 150) {
                self::setError('标题不能超过150个字');
                return false;
            }

            if (mb_strlen($params['subtitle'], 'utf-8') > 150) {
                self::setError('正文不能超过150个字');
                return false;
            }


            $publish = SvPublishSetting::create([
                'user_id' => self::$uid,
                'name' => empty($params['title']) ? '模拟发布' : $params['title'],
                'accounts' => implode(',', $params['accounts']),
                'video_setting_id' => 0,
                'type' => 3,
                'publish_start' => date('Y-m-d', time()),
                'publish_end'  => date('Y-m-d', time()),
                'time_config' => '[]',
                'data_type' => 1,
                'create_time' => time(),
                'update_time' => time(),
                'date_type' => 1,
                'poi' => $params['poi']
            ]);

            $url = $params['url'] ?? config('app.app_host') . '/uploads/video/20250517/7b300711-d826-4b46-8b1a-c6eaaa58cbce.mp4';
            $insertData = array();
            $count = count($params['accounts']);
            foreach ($params['accounts'] as $key => $account) {
                $account = SvAccount::where('account', $account)->where('user_id', self::$uid)->limit(1)->findOrEmpty();
                if ($account->isEmpty()) {
                    self::setError("{$account}该账号信息不存在");
                    return false;
                }
                $publishAccount = SvPublishSettingAccount::create([
                    'publish_id' => $publish->id,
                    'user_id' => self::$uid,
                    'name' => empty($params['title']) ? '模拟发布' : $params['title'],
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'video_setting_id' => 0,
                    'publish_start' => date('Y-m-d', time()),
                    'publish_end' => date('Y-m-d', time()),
                    'next_publish_time' => date('Y-m-d H:i:s', time()), //视频发布时间
                    'count' => $count,
                    'published_count' => 0,
                    'status' => 1,
                    'data_type' => 1,
                    'created_time' => time(),
                    'poi' => $params['poi']
                ]);

                array_push($insertData, [
                    'publish_id' => $publish->id,
                    'publish_account_id' => $publishAccount->id,
                    'video_task_id' => 0, //视频任务id，关联sv_video_tas
                    'user_id' => self::$uid,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'matrix_media_setting_id' => $account['matrix_media_setting_id'],
                    'material_id' => 0,
                    'material_type' => $params['material_type'],
                    'material_url' => $url,
                    'material_title' => empty($params['title']) ? ' ' : $params['title'],
                    'material_tag' => $params['topic'],
                    'poi' => $params['poi'],
                    'material_subtitle' => empty($params['subtitle']) ? ' ' : $params['subtitle'],
                    'task_id' => generate_unique_task_id(),
                    'platform' => $account['type'],
                    'status' => 0,
                    'publish_time' => date('Y-m-d H:i:s', time()), //视频发布时间
                    'create_time' => time(),
                    'data_type' => 1
                ]);
            }
            //print_r($insertData);die;
            if (!empty($insertData)) {
                $model = new SvPublishSettingDetail();
                $model->saveAll($insertData);
            }
            Db::commit();
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function setPublishDetail()
    {
        print_r('执行发布记录拉取任务');
        ini_set('max_execution_time', 0);
        try {

            $accounts = SvPublishSettingAccount::alias('pa')
                ->field('pa.*, ps.time_config, ps.publish_frep,pa.device_code as devicecode')
                ->field('vs.id as matrix_media_setting_id, vs.media_count, vs.media_url, vs.media_type, vs.copywriting as media_copywriting')
                ->join('sv_matrix_media_setting vs', 'vs.id = pa.matrix_media_setting_id and vs.user_id = pa.user_id and vs.media_type = pa.media_type')
                ->join('sv_publish_setting ps', 'ps.id = pa.publish_id and ps.user_id = pa.user_id')
                ->where('pa.task_type', 3)
                ->where('ps.status', 'in', [1, 2])
                ->where('pa.id', 'NOT IN', function ($query) {
                    $query->name('sv_publish_setting_detail')
                        ->field('publish_account_id')
                        ->where('delete_time is null')
                        ->where('publish_account_id', '>', 0)
                        ->where('task_type', 3)
                        ->group('publish_account_id')->select();
                })
                ->select()->toArray();
            //print_r(Db::getLastSql());die;
            // print_r("count: " . count($accounts));
            $insertData = [];
            $videoIds = [];
            foreach ($accounts as $key => $account) {

                $medias = self::_getMedias($account);
                if (empty($medias)) {
                    array_push($insertData, [
                        'publish_id' => $account['publish_id'],
                        'publish_account_id' => $account['id'],
                        'video_task_id' => 0, //视频任务id，关联sv_video_tas
                        'video_setting_id' => 0,
                        'matrix_media_setting_id' => $account['matrix_media_setting_id'],
                        'user_id' => $account['user_id'],
                        'account' => $account['account'],
                        'account_type' => $account['account_type'],
                        'device_code' => $account['device_code'],
                        'scene' => $account['scene'],
                        'material_id' => 0,
                        'material_type' => 1,
                        'material_url' => '',
                        'material_title' => '-',
                        'material_tag' => '',
                        'pic' => '',
                        'poi' => '',
                        'material_subtitle' => '-',
                        'task_id' => generate_unique_task_id(),
                        'sub_task_id' => '',
                        'platform' => $account['account_type'],
                        'status' => 2,
                        'remark' => '发布记录生成失败',
                        'publish_time' => date('Y-m-d H:i:s', time()),
                        'create_time' => time(),
                        'task_type' => 3
                    ]);
                    SvPublishSettingAccount::where('id', $account['id'])->update(['status' => 2, 'publish_end' => date('Y-m-d', time()), 'update_time' => time()]);
                    SvPublishSetting::where('id', $account['publish_id'])->update(['status' => 3, 'publish_end' => date('Y-m-d', time()), 'update_time' => time()]);
                    continue;
                }
                $endTime = max(array_column($medias, 'publish_time'));
                $status = 2;
                foreach ($medias as $media) {
                    $detail = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
                        ->where('publish_account_id', $account['id'])
                        ->where('video_task_id',  $media['id'])
                        ->where('user_id', $account['user_id'])
                        ->where('account', $account['account'])
                        ->find();
                    if (empty($detail)) {
                        array_push($insertData, [
                            'publish_id' => $account['publish_id'],
                            'publish_account_id' => $account['id'],
                            'video_task_id' => 0, //视频任务id，关联sv_video_tas
                            'matrix_media_setting_id' => $account['matrix_media_setting_id'],
                            'user_id' => $account['user_id'],
                            'account' => $account['account'],
                            'account_type' => $account['account_type'],
                            'device_code' => $account['device_code'],
                            'material_id' => 0,
                            'material_type' => $media['material_type'],
                            'material_url' => self::_getMaterialUrl($media),
                            'material_title' => $media['material_title'],
                            'material_tag' => $media['topic'],
                            'pic' => $media['pic'] ?? '',
                            'poi' => $media['poi'],
                            'scene' => $account['scene'],
                            'material_subtitle' => $media['material_subtitle'],
                            'task_id' => $media['task_id'],
                            'sub_task_id' => $media['sub_task_id'] ?? '',
                            'platform' => $account['account_type'],
                            'status' => 0,
                            'publish_time' => $media['publish_time'],
                            'create_time' => time(),
                            'task_type' => 3
                        ]);
                        array_push($videoIds, $media['id']);
                        $status = 1;
                    }
                }
                //print_r($insertData);die;
                SvPublishSettingAccount::where('id', $account['id'])->update([
                    'publish_end' => date('Y-m-d', strtotime($endTime)),
                    'status' => $status,
                    'update_time' => time()
                ]);
                SvPublishSetting::where('id', $account['publish_id'])->update(['status' => 2, 'publish_end' => date('Y-m-d', time()), 'update_time' => time()]);
            }
            //print_r($insertData);die;
            if (!empty($insertData)) {
                $model = new SvPublishSettingDetail();
                $model->saveAll($insertData);
            }

            self::$returnData = $insertData;
            return true;
        } catch (\Exception $e) {
            print_r($e->__toString());
            die;
            return false;
        }
    }

    /**
     * 检查发布状态
     * @param array $account
     * @return bool
     */
    private static function _checkPublishStatus(array $account, array &$usedVideoIds)
    {
        //原视频任务状态
        //        $settingStatus = SvMatrixMediaSetting::where('id',  $account['matrix_media_setting_id'])->group('status')->column('status');
        //print_r($settingStatus);die;
        //提取同任务同账号类型中已经生成的待发布视频id
        $video_ids = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
            //->where('publish_account_id', $account['id'])
            ->where('account_type', $account['account_type'])
            ->where('task_type', 3)
            ->column('video_task_id');
        //为空则返回false
        if (empty($video_ids)) {
            return false;
        }
        $usedVideoIds[$account['account_type']] = array_values(array_unique(array_merge($usedVideoIds[$account['account_type']] ?? [], $video_ids)));

        //查询当前账号已经生成的数量
        $count = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
            ->where('publish_account_id', $account['id'])
            ->where('account_type', $account['account_type'])
            ->where('task_type', 3)
            ->count();

        if ($count == $account['count']) {
            SvPublishSettingAccount::where('id', $account['id'])->update([
                'task_status' => 2,
            ]);
            return true;
        }
        if ($count < $account['count']) {
            SvPublishSettingAccount::where('id', $account['id'])->update([
                'task_status' => 2,
                'count' => $count,
            ]);
            return true;
        }
    }


    private static function getTimes(array $account)
    {
        $maxTime = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
            ->where('publish_account_id', $account['id'])
            ->where('account_type', $account['account_type'])
            ->where('task_type', 3)
            ->order('publish_time desc')
            ->limit(1)
            ->value('publish_time');

        //print_r($maxTime);die;
        $times = array(
            1 => [], //视频号
            3 => [], //小红书
            4 => [], //抖音
            5 => [], //快手
        );
        $timeConfig = json_decode($account['time_config'], true);

        $startDate = $account['publish_start'];
        for ($i = 0; $i <= ceil($account['count'] / $account['publish_frep']); $i++) {
            foreach ($timeConfig as $time) {
                $startDate = $account['publish_start'];
                $startDate = date('Y-m-d', strtotime("{$startDate} +" . $i . " day"));

                $tmps = explode('-', $time);
                $st = strtotime(date('Y-m-d H:i:s', strtotime("{$startDate} {$tmps[0]}")));
                $et = strtotime(date('Y-m-d H:i:s', strtotime("{$startDate} {$tmps[1]}")));

                if ($st < time() || $et < time()) {
                    $startDate = date('Y-m-d', strtotime("{$startDate} +" . ($i + 1) . " day"));
                    $st = strtotime(date('Y-m-d H:i:s', strtotime("{$startDate} {$tmps[0]}")));
                    $et = strtotime(date('Y-m-d H:i:s', strtotime("{$startDate} {$tmps[1]}")));
                }


                $interval = floor(($et - $st) / 4);
                $xhsPublishTime = date('Y-m-d H:i:s', $st + ($interval * 0));
                $dyPublishTime = date('Y-m-d H:i:s', $st + ($interval * 1));
                $ksPublishTime = date('Y-m-d H:i:s', $st + ($interval * 2));
                $vtPublishTime = date('Y-m-d H:i:s', $st + ($interval * 3));
                if ($maxTime) {
                    if (strtotime($xhsPublishTime) <= strtotime($maxTime) || strtotime($xhsPublishTime) <= time()) {
                        continue;
                    }
                    if (strtotime($dyPublishTime) <= strtotime($maxTime) || strtotime($dyPublishTime) <= time()) {
                        continue;
                    }
                    if (strtotime($ksPublishTime) <= strtotime($maxTime) || strtotime($ksPublishTime) <= time()) {
                        continue;
                    }
                    if (strtotime($vtPublishTime) <= strtotime($maxTime) || strtotime($vtPublishTime) <= time()) {
                        continue;
                    }
                }

                $times[3][] = !in_array($xhsPublishTime, $times[3]) ? $xhsPublishTime : date('Y-m-d H:i:s', strtotime($xhsPublishTime) + (($i + 1)  * 86400));
                $times[4][] = !in_array($dyPublishTime, $times[4]) ? $dyPublishTime :  date('Y-m-d H:i:s', strtotime($dyPublishTime) + (($i + 1)  * 86400));
                $times[5][] = !in_array($ksPublishTime, $times[5]) ? $ksPublishTime :  date('Y-m-d H:i:s', strtotime($ksPublishTime) + (($i + 1)  * 86400));
                $times[1][] = !in_array($vtPublishTime, $times[1]) ? $vtPublishTime :  date('Y-m-d H:i:s', strtotime($vtPublishTime) + (($i + 1)  * 86400));
            }
            sort($times[3]);
            sort($times[4]);
            sort($times[5]);
            sort($times[1]);

            //$startDate = date('Y-m-d', strtotime("{$startDate} +1 day"));
        }
        //print_r($times);die;
        return $times;
    }

    private static function execDeduction(int $userId, string $task_id)
    {
        $tokenScene = "coze_publish_content_generated";
        $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_COZE_PUBLISH_CONTENT_GENERATED;
        $unit = \app\api\logic\service\TokenLogService::checkToken($userId, $tokenScene);
        $points = $unit;
        $extra = ['算力单价' => $unit . '算力/条', '实际消耗算力' => $points];
        \app\common\model\user\User::userTokensChange($userId, $points);
        \app\common\logic\AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $task_id, $extra);
    }




    public static function SphPublishCron()
    {
        try {

            $publishes = SvPublishSettingDetail::alias('ps')
                ->field('ps.*')
                ->join('sv_publish_setting ss', 'ps.publish_id = ss.id')
                ->where('ps.device_code', 'in', function ($query) {
                    $query->name('ai_wechat_device')->where('device_status', 1)->field('device_code');
                })
                ->where('ps.status', 'in', [0, 5])
                ->where('ss.status', 'in', [1, 2])
                ->where('ps.account_type', 1)
                ->where('ps.publish_time', '<=', date('Y-m-d H:i:s', time()))
                ->order('ps.publish_time asc')
                ->limit(10)
                ->select()->toArray();
            print_r('视频号发布：' . count($publishes));
            foreach ($publishes as $publish) {
                sleep(1);
                $interval_find = \app\common\model\wechat\AiWechatLog::where('user_id', $publish['user_id'])
                    ->where('log_type', \app\common\model\wechat\AiWechatLog::TYPE_SPH_POST)
                    ->where('wechat_id', $publish['account'])
                    ->order('id', 'desc')
                    ->limit(1)
                    //->fetchSql(true)
                    ->findOrEmpty();
                if (!$interval_find->isEmpty() && ((time() - strtotime($interval_find->create_time)) < 150)) {
                    print_r('间隔时间未到');
                    continue;
                }
                $payload = [
                    'WeChatId' => $publish['account'],
                    'Content' => $publish['material_subtitle'],
                    'MediaType' => 1,
                    'Medias' => [
                        FileService::getFileUrl($publish['material_url']),
                    ],
                    'Cover' => FileService::getFileUrl($publish['pic']),
                    'TaskId' => $publish['sub_task_id'],
                    'Poi' => [],
                ];
                //print_r($payload);
                // 3. 构建消息发送请求
                $content = \app\common\workerman\wechat\handlers\client\SphPostTaskHandler::handle($payload);
                //print_r($content);
                // 4. 构建protobuf消息
                $message = new \Jubo\JuLiao\IM\Wx\Proto\TransportMessage();
                $message->setMsgType($content['MsgType']);
                $any = new \Google\Protobuf\Any();
                $any->pack($content['Content']);
                $message->setContent($any);
                $data = $message->serializeToString();
                //print_r($data);
                // 5. 发送到设备端
                $channel = "socket.{$publish['device_code']}.message";
                \Channel\Client::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
                \Channel\Client::publish($channel, [
                    'data' => $data
                ]);
                // 6. 更新发布记录
                SvPublishSettingDetail::where('id', $publish['id'])->update([
                    'status' => 3,
                    'exec_time' => time(),
                    'update_time' => time(),
                ]);
                \app\common\model\wechat\AiWechatLog::create([
                    'user_id' => $publish['user_id'],
                    'wechat_id' => $publish['account'],
                    'log_type' => \app\common\model\wechat\AiWechatLog::TYPE_SPH_POST,
                    'friend_id' => $payload['WeChatId'],
                    'create_time' => time()
                ]);
            }
        } catch (\Exception $e) {
            print_r($e->__toString());
            die;
        }
    }



    public static function aiNotePushCron(int $dataType = 0)
    {
        try {
            $deviceids = SvDevice::where('status', 1)->column('device_code');
            if (empty($deviceids)) {
                return;
            }
            //print_r($deviceids);die;

            foreach ($deviceids as $deviceid) {
                $publishes = SvPublishSettingDetail::alias('ps')
                    ->field('ps.*')
                    // ->join('sv_video_task v', 'v.id = ps.video_task_id')
                    ->join('sv_publish_setting_account s', 's.id = ps.publish_account_id')
                    ->where('ps.device_code', 'in', $deviceid)
                    ->where('ps.status', 'in', [0, 5])
                    ->where('s.status', 'in', [1])
                    ->where('ps.data_type', $dataType)
                    ->where('ps.publish_time', '<', date('Y-m-d H:i:s', time()))
                    ->order('ps.publish_time asc')
                    ->limit(1)
                    ->select()->toArray();

                foreach ($publishes as $publish) {
                    //判断当前rpa是否在操作小红书
                    $app = SvDeviceRpa::where('device_code', $publish['device_code'])
                        ->where('app_type', 3)
                        ->where('status', 1)
                        ->findOrEmpty();
                    if ($app->isEmpty()) {
                        //将执行app直接改为小红书
                        self::sendAppExec($publish['device_code'], 3);
                        sleep(30);
                    }

                    $material_url = explode(',', $publish['material_url']);
                    if (count($material_url) > 12) {
                        $material_url = array_slice($material_url, 0, 12);
                    }
                    $payload = array(
                        'appType' => 3,
                        'messageId' => 0,
                        'type' => 5,
                        'deviceId' => $publish['device_code'],
                        'appVersion' => '2.1.2',
                        'code' => 200,
                        'action' => 'send',
                        'content' => json_encode(array(
                            'title' => $publish['material_title'],
                            'type' => $publish['material_type'] ?? 1,
                            'list' => $material_url,
                            'isLocation' => !empty($publish['poi']) ? 1 : 0,
                            'location' => $publish['poi'],
                            'isScheduledTime' => true,
                            'scheduledTime' => $publish['publish_time'],
                            'taskId' => $publish['task_id'],
                            'body' => $publish['material_subtitle'],
                            'tag' => $publish['material_tag'] ?? '',
                            'material_id' => $publish['id']
                        ),  JSON_UNESCAPED_UNICODE)
                    );
                    print_r($payload);
                    $channel = "device.{$publish['device_code']}.message";
                    ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
                    ChannelClient::publish($channel, [
                        'data' => json_encode($payload)
                    ]);

                    self::_setPublishStatus($publish);
                }
            }
        } catch (\Exception $e) {
            print_r($e->__toString());
        }
    }

    private function sendAppExec($deviceid, $appType)
    {
        try {
            $app = SvDeviceRpa::where('device_code', $deviceid)->where('app_type', $appType)->findOrEmpty();
            if ($app->isEmpty()) {
                throw new \Exception('当前设备未绑定app');
            }
            $payload = [
                "messageId" => 2,
                "type" => 90, //执行那个app指令
                "appType" => $appType,
                "content" => json_encode([
                    "deviceId" => $deviceid,
                    "appType" => $appType,
                    'msg' => '小红书',
                    'task_id' => $app->id
                ], JSON_UNESCAPED_UNICODE),
                "deviceId" => $deviceid,
                "appVersion" => "2.1.2"
            ];

            $channel = "device.{$deviceid}.message";
            ChannelClient::connect('127.0.0.1', env('WORKERMAN.CHANNEL_PROT', 2206));
            ChannelClient::publish($channel, [
                'data' => json_encode($payload)
            ]);

            SvDeviceRpa::where('device_code', $deviceid)->where('app_type', '<>', $appType)->update(['status' => 0, 'update_time' => time()]);
            SvDeviceRpa::where('device_code', $deviceid)->where('app_type', $appType)->update(['status' => 1, 'update_time' => time()]);
        } catch (\Throwable $e) {
            print_r($e->__toString());
        }
    }

    private static function _setPublishStatus($publish)
    {
        try {
            $detail = SvPublishSettingDetail::where('id', $publish['id'])->findOrEmpty();
            if (!$detail->isEmpty()) {
                $detail->save([
                    'status' => 3,
                    'update_time' => time(),
                    'exec_time' => time()
                ]);
            } else {
                $publish['message'] = '待发布数据丢失:';
            }
            $account = SvPublishSettingAccount::where('id', $publish['publish_account_id'])->findOrEmpty();
            if (!$account->isEmpty()) {
                $account->save([
                    'update_time' => time(),
                    'published_count' => Db::raw('published_count+1'),
                ]);
            } else {

                $account['message'] = '待发布账号数据丢失:';
            }
        } catch (\Exception $e) {
            print_r($e->__toString());
        }
    }


    private static function _getMedias(array $account)
    {
        try {
            // 合并后的新数组
            $mergedArray = [];
            $media_url = json_decode($account['media_url'], true);
            $media_copywriting = json_decode($account['media_copywriting'], true);

            $timeDict = self::getTimes($account);
            //print_r($timeDict);die;
            $times = $timeDict[$account['account_type']] ?? [];
            if (empty($timeDict)) {
                return [];
            }
            $timesCount = count($times);
            // 获取各数组长度
            $mediaUrlCount = count($media_url);
            $copywritingCount = count($media_copywriting);
            if ($mediaUrlCount == 0) {
                return [];
            }
            // 循环匹配（以media_url的长度为基准）
            for ($i = 0; $i < $mediaUrlCount; $i++) {
                if (count($mergedArray) == $account['count']) {
                    continue;
                }
                $topic =  $copywritingCount == 0 ? [] : ($media_copywriting[$i % $copywritingCount]['topic'] ?? []);
                $topic = count($topic) == 0  ? ' ' : implode(',', $topic);
                $pic = $media_url[$i]['url'][0] ?? '';
                if ($account['media_type'] == 1) {
                    $mediaUrl = $media_url[$i]['url'][1];
                } else {
                    $mediaUrl = isset($media_url[$i]['url']) ? $media_url[$i]['url'] : $media_url[$i];
                }
                if ($pic) {
                    $pic = FileService::getFileUrl($pic);
                }
                $mergedArray[] = [
                    'material_url' => $mediaUrl,
                    'material_title' => $copywritingCount == 0 ? ' ' : ($media_copywriting[$i % $copywritingCount]['title'] ?? ' '), // 循环匹配title
                    'material_subtitle' => $copywritingCount == 0 ? ' ' : ($media_copywriting[$i % $copywritingCount]['content'] ?? ' '), //
                    'topic' => $topic,
                    'pic' => $pic,
                    'material_type' => $account['media_type'],
                    'publish_time' => $timesCount == 0 ? ' ' : ($times[$i % $timesCount] ?? ' '), //,
                    'poi' => $account['poi'],
                    'id' => $account['id'],
                    'matrix_media_setting_id' => $account['matrix_media_setting_id'],
                    'publish_id' => $account['publish_id'],
                    'publish_account_id' => $account['id'],
                    'user_id' => $account['user_id'],
                    'account' => $account['account'],
                    'account_type' => $account['account_type'],
                    'device_code' => $account['devicecode'],
                    'task_id' => generate_unique_task_id(),
                    'sub_task_id' => time() . rand(100, 999),

                ];
            }
            return $mergedArray;
        } catch (\Throwable $th) {
            //throw $th;
            \think\facade\Log::channel('publish')->write("_getMedias " . $th->__toString());
        }
        return [];
    }


    private static function _getMaterialUrl($media)
    {
        try {
            if (!is_array($media['material_url'])) {
                return FileService::getFileUrl($media['material_url']);
            } else {
                $urls = $media['material_url'];
                return implode(',', array_map(function ($url) {
                    return FileService::getFileUrl($url);
                }, $urls));
            }
        } catch (\Throwable $th) {
            \think\facade\Log::channel('publish')->write("_getMaterialUrl" . $th->__toString());
        }
    }
}
