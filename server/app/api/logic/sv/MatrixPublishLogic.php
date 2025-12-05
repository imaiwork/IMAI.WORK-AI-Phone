<?php

namespace app\api\logic\sv;

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
use app\common\enum\DeviceEnum;
use Channel\Client as ChannelClient;

/**
 * PublishLogic
 * @desc 任务发布计划
 * @author Qasim
 */
class MatrixPublishLogic extends SvBaseLogic
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

            if (isset($params['accounts']) && is_array($params['accounts'])) {
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            }

            if (isset($params['publish_json']) && is_array($params['publish_json'])) {
                $params['publish_json'] = json_encode($params['publish_json'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['video_ids']) && is_array($params['video_ids'])) {
                $params['video_ids'] = json_encode($params['video_ids'], JSON_UNESCAPED_UNICODE);
            }
            $params['video_setting_id'] = $params['video_setting_id'] ?? 0;
            $params['task_type'] = $params['task_type'] ?? 2;
            $params['publish_frep'] = $params['publish_frep'] ?? 2;
            $params['publish_start'] = date('Y-m-d', time());
            $params['type'] = 0;
            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }
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
            //print_r($e->__toString());die;
            // 回滚事务
            Db::rollback();
            //            clogger($e);
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
            if (isset($params['video_ids']) && is_array($params['video_ids'])) {
                $params['video_ids'] = json_encode($params['video_ids'], JSON_UNESCAPED_UNICODE);
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

    private static function batchPushlishAccount($publish, $params)
    {
        try {
            $insertData = [];
            $time_config = json_decode($params['time_config'], true);
            $accounts = json_decode($params['accounts'], true);
            $accountTypes = array_count_values(array_column($accounts, 'type'));
            $video_ids = json_decode($params['video_ids'], true);
            $mediaSettings = ShanjianVideoSetting::where('id', 'in', $video_ids)->where('user_id', self::$uid)->select()->toArray();
            if (empty($mediaSettings)) {
                return;
            }
            $mediaCount = array_sum(array_column($mediaSettings, 'video_count'));
            // 实现媒体分配逻辑
            $allocatedAccounts = self::allocateMediaToAccounts($accounts, $accountTypes, $mediaCount);

            $nextPublishTime =  date('Y-m-d H:i:s', strtotime($params['publish_start'] . ' ' . $time_config[0]));
            //print_r($nextPublishTime);die;
            foreach ($allocatedAccounts as $key => $account) {
                if ($account['count'] == 0) {
                    continue;
                }
                if ($account['type'] == 1) {
                    $find = AiWechat::where('wechat_id', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                } elseif ($account['type'] == 3) {
                    $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                    $account = array_merge($account, $find);
                }

                array_push($insertData, [
                    'publish_id' => $publish->id,
                    'user_id' => self::$uid,
                    'task_type' => 2,
                    'name' => $params['name'],
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'video_setting_id' => 0,
                    'video_ids' => json_encode($video_ids, JSON_UNESCAPED_UNICODE),
                    'poi' => $params['poi'] ?? '',
                    'media_type' => $params['media_type'],
                    'publish_start' => $params['publish_start'],
                    'publish_end' => $params['publish_end'] ?? null,
                    'next_publish_time' => $nextPublishTime, //视频发布时间
                    'count' => $account['count'],
                    'published_count' => 0,
                    'status' => 1,
                    'created_time' => time(),
                ]);
            }
            //print_r($insertData);die;
            $model = new SvPublishSettingAccount();
            $model->saveAll($insertData);
        } catch (\Throwable $th) {
            //print_r($th->__toString());die;
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
        //print_r($accountsByType);die;
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
            $publish['video_ids'] = is_null($publish['video_ids']) ? [] : json_decode($publish['video_ids'], true);
            self::$returnData = $publish->toArray();
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
    public static function delete(array $params)
    {
        Db::startTrans();
        try {
            $task = SvPublishSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($task->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }
            SvPublishSettingAccount::where('publish_id', $task['id'])->where('user_id', self::$uid)->select()->delete();
            SvPublishSettingDetail::where('publish_id', $task['id'])->where('user_id', self::$uid)->select()->delete();

            $task->delete();
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


    public static function recordDelete(array $params)
    {
        $record = SvPublishSettingDetail::field('*')
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

    public static function testPublish(array $params)
    {
        ini_set('max_execution_time', 0);
        Db::startTrans();
        try {
            $publish = SvPublishSetting::create([
                'user_id' => self::$uid,
                'name' => date('YmdHis') . $params['name'],
                'accounts' => json_encode($params['accounts'], JSON_UNESCAPED_UNICODE),
                'video_setting_id' => 0,
                'type' => 0,
                'media_type' => 1,
                'publish_start' => date('Y-m-d', time()),
                'time_config' => json_encode($params['time_config'], JSON_UNESCAPED_UNICODE),
                'data_type' => 1,
                'create_time' => time(),
                'update_time' => time(),
                'date_type' => 0,
                'task_type' => 2,
                'publish_frep' => $params['publish_frep'] ?? 2,
                'status' => 1
            ]);
            $video_urls = $params['video_url'];
            $pics = $params['pic'];
            $msgs = $params['msg'];
            $accounts = $params['accounts'];

            $times = [];
            for ($i = 0; $i < $params['publish_frep']; $i++) {
                $publishTime = date('Y-m-d H:i:s', time() + (($i + 1) * 1800));
                if (strtotime($publishTime) <= time()) {
                    continue;
                }
                $times[] = $publishTime;
            }
            $insertData = [];
            foreach ($accounts as $akey => $account) {
                $find = SvAccount::where('account', $account['account'])->where('user_id', self::$uid)->limit(1)->find()->toArray();
                $account = array_merge($account, $find);
                $publishAccount = SvPublishSettingAccount::create([
                    'publish_id' => $publish->id,
                    'user_id' => self::$uid,
                    'task_type' => 2,
                    'name' => $publish->name . '_' . $akey,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'video_setting_id' => 0,
                    'publish_start' => date('Y-m-d', time()),
                    'next_publish_time' => $times[0], //视频发布时间
                    'count' => count($video_urls),
                    'published_count' => 0,
                    'status' => 1,

                    'task_status' => 2,
                    'data_type' => 1,
                    'created_time' => time(),
                ]);
                foreach ($video_urls as $vkey => $video_url) {
                    $task_id = generate_unique_task_id();
                    $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                        'keywords' => $msgs[$vkey],
                        'task_id' => $task_id,
                        'user_id' => self::$uid,
                    ]);
                    $insertData[] = [
                        'publish_id' => $publish->id,
                        'publish_account_id' => $publishAccount->id,
                        'video_task_id' => 0, //视频任务id，关联sv_video_tas
                        'video_setting_id' => 0,
                        'user_id' => self::$uid,
                        'account' => $account['account'],
                        'account_type' => $account['type'],
                        'device_code' => $account['device_code'],
                        'material_id' => 0,
                        'material_type' => 1,
                        'material_url' => FileService::getFileUrl($video_url),
                        'material_title' => $response['data']['title'], // 循环匹配title
                        'material_subtitle' => $response['data']['content'], //
                        'pic' => FileService::getFileUrl($pics[$vkey]),
                        'task_id' => $task_id,
                        'sub_task_id' =>  time() . ($vkey + 100),
                        'platform' => $account['type'],
                        'status' => 0,
                        'publish_time' => $times[$vkey],
                        'create_time' => time(),
                        'task_type' => 2
                    ];
                }
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
        //print_r('执行发布记录拉取任务');
        try {
            $accounts = SvPublishSettingAccount::alias('pa')
                ->field('pa.*, ps.time_config, ps.publish_frep')
                ->join('sv_publish_setting ps', 'ps.id = pa.publish_id and ps.user_id = pa.user_id')
                ->where('pa.task_status', 1)
                ->where('pa.task_type', 2)
                ->where('ps.status', 'in', [1, 2])
                ->select()->toArray();

            //print_r(Db::getLastSql());die;
            // print_r("count: " . count($accounts));
            $insertData = [];
            $videoIds = [];
            $accountTypes = array_count_values(array_column($accounts, 'account_type'));
            $usedVideoIds = [];
            foreach ($accountTypes as $type => $accountType) {
                $usedVideoIds[$type] = [];
            }

            foreach ($accounts as $key => $account) {
                // 遍历发布账号数据
                // 1 获取已经生成的发布数量与设置的视频数量是否一致
                // 2 如果视频任务都已经完成了，并且生成的发布数量仍少于预计的数量，则将该任务状态设置为待发布状态
                // 3 发布记录都生成完成，将账号任务状态改为待发布状态，避免下次执行还会执行该任务
                $check = self::_checkPublishStatus($account, $usedVideoIds);
                if ($check === true) {
                    continue;
                }

                $medias = self::_getMedias($account, $usedVideoIds);
                if (empty($medias)) {
                    array_push($insertData, [
                        'publish_id' => $account['publish_id'],
                        'publish_account_id' => $account['id'],
                        'video_task_id' => 0, //视频任务id，关联sv_video_tas
                        'video_setting_id' => 0,
                        'user_id' => $account['user_id'],
                        'account' => $account['account'],
                        'account_type' => $account['account_type'],
                        'device_code' => $account['device_code'],
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
                        'remark' => '发布时间为空',
                        'publish_time' => date('Y-m-d H:i:s', time()),
                        'create_time' => time(),
                        'task_type' => 2
                    ]);
                    SvPublishSettingAccount::where('id', $account['id'])->update(['task_status' => 2, 'status' => 2, 'publish_end' => date('Y-m-d', time()), 'update_time' => time()]);
                    SvPublishSetting::where('id', $account['publish_id'])->update(['status' => 3, 'publish_end' => date('Y-m-d', time()), 'update_time' => time()]);
                    continue;
                }
                //$mediaCount = count($medias);
                $endTime = max(array_column($medias, 'publish_time'));
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
                            'video_task_id' => $media['media_id'], //视频任务id，关联sv_video_tas
                            'video_setting_id' => $media['video_setting_id'],
                            'user_id' => $account['user_id'],
                            'account' => $account['account'],
                            'account_type' => $account['account_type'],
                            'device_code' => $account['device_code'],
                            'material_id' => $media['media_id'],
                            'material_type' => $media['material_type'],
                            'material_url' => $media['material_url'],
                            'material_title' => $media['material_title'],
                            'material_tag' => $media['topic'],
                            'pic' => $media['pic'],
                            'poi' => $media['poi'],
                            'material_subtitle' => $media['material_subtitle'],
                            'task_id' => $media['task_id'],
                            'sub_task_id' => $media['sub_task_id'],
                            'platform' => $account['account_type'],
                            'status' => $media['material_status'],
                            'remark' => $media['material_remark'],
                            'publish_time' => $media['publish_time'],
                            'create_time' => time(),
                            'task_type' => 2
                        ]);
                        array_push($videoIds, $media['id']);
                    }
                }
                //print_r($insertData);die;
                SvPublishSettingAccount::where('id', $account['id'])->update([
                    'publish_end' => date('Y-m-d', strtotime($endTime)),
                ]);
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
        $_ids = json_decode($account['video_ids'], true);
        $settingStatus = ShanjianVideoSetting::where('id', 'in', $_ids)->group('status')->column('status');
        //print_r($settingStatus);die;
        //提取同任务同账号类型中已经生成的待发布视频id
        $video_ids = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
            //->where('publish_account_id', $account['id'])
            ->where('account_type', $account['account_type'])
            ->where('task_type', 2)
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
            ->where('task_type', 2)
            ->count();

        //如果有视频任务状态为0或1或2则返回false
        if (in_array(0, $settingStatus) || in_array(1, $settingStatus) || in_array(2, $settingStatus)) {
            return false;
        } else {
            if (count($settingStatus) == 1 && array_sum($settingStatus) == 3) {
                if ($count == $account['count']) {
                    SvPublishSettingAccount::where('id', $account['id'])->update([
                        'task_status' => 2,
                    ]);
                    return true;
                }
                if ($count < $account['count']) {
                    //判断新生成的视频是否记录到发布任务中
                    $newVideoids = ShanjianVideoTask::where('video_setting_id', 'in', $_ids)
                        ->where('id', 'not in', $usedVideoIds[$account['account_type']])
                        ->where('status', 'in', [2, 3])
                        ->column('id');
                    //为空则返回false
                    if (empty($newVideoids)) {
                        SvPublishSettingAccount::where('id', $account['id'])->update([
                            'task_status' => 2,
                            'count' => $count,
                        ]);
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
    }

    private static function _getMedias(array $account, array &$usedVideoIds)
    {
        $video_ids = json_decode($account['video_ids'], true);
        if (empty($video_ids)) {
            return [];
        }
        $medias = ShanjianVideoSetting::alias('vs')
            ->field('vt.*')
            ->join('shanjian_video_task vt', 'vs.id = vt.video_setting_id')
            ->where('vs.id', 'in', $video_ids)
            ->where('vt.id', 'NOT IN', $usedVideoIds[$account['account_type']])
            ->where('vt.status', 'in', [2, 3])
            ->limit($account['count'])
            ->select()->toArray();
        //print_r(Db::getLastSql());die;
        //print_r($medias);die;

        $times = self::getTimes($account);
        if (empty($times)) {
            return [];
        }
        //print_r($times);die;
        // 合并后的新数组
        $mergedArray = [];
        foreach ($medias as $key => $media) {
            if(!isset($times[$key])){
                continue;
            }
            //获取发布内容和标题
            $task_id = generate_unique_task_id();
            $response = [
                'code' => 10000,
            ];
            if ($media['status'] === 3) {
                $response = \app\common\service\ToolsService::Sv()->getPublishContent([
                    'keywords' => $media['msg'],
                    'task_id' => $task_id,
                    'user_id' => $account['user_id'],
                ]);
            }

            if ((int)$response['code'] === 10000) {
                $mergedArray[] = [
                    'material_url' => $media['status'] == 2 ? '' : FileService::getFileUrl($media['video_result_url']),
                    'material_title' => $media['status'] == 2 ? '' : $response['data']['title'], // 循环匹配title
                    'material_subtitle' => $media['status'] == 2 ? '' : $response['data']['content'],
                    'material_status' => $media['status'] == 2 ? 2 : 0, // 2失败 0待发布
                    'material_remark' => $media['status'] == 2 ? $media['remark'] : '',
                    'pic' => $media['pic'],
                    'topic' => '',
                    'material_type' => $account['media_type'],
                    'publish_time' => $times[$key],
                    'poi' => $account['poi'],
                    'id' => $account['id'],
                    'video_setting_id' => $media['video_setting_id'],
                    'media_id' => $media['id'],
                    'publish_id' => $account['publish_id'],
                    'publish_account_id' => $account['id'],
                    'user_id' => $account['user_id'],
                    'account' => $account['account'],
                    'account_type' => $account['account_type'],
                    'device_code' => $account['device_code'],
                    'task_id' => $task_id,
                    'sub_task_id' => time() . ($key + 100),

                ];
                array_push($usedVideoIds[$account['account_type']], $media['id']);
                if ($media['status'] === 3) {
                    self::execDeduction($account['user_id'], $task_id);
                }
            }
        }
        return $mergedArray;
    }

    private static function getTimes(array $account)
    {
        $maxTime = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
            ->where('publish_account_id', $account['id'])
            ->where('account_type', $account['account_type'])
            ->where('task_type', 2)
            ->order('publish_time desc')
            ->limit(1)
            ->value('publish_time');

        //print_r($maxTime);die;
        $times = [];
        $timeConfig = json_decode($account['time_config'], true);
        $startDate = $account['publish_start'];
        for ($i = 0; $i < ceil($account['count'] / $account['publish_frep']); $i++) {
            foreach ($timeConfig as $time) {
                $publishTime = date('Y-m-d H:i:s', strtotime("{$startDate} {$time}"));
                if (strtotime($publishTime) <= strtotime($maxTime)) {
                    continue;
                }

                if (strtotime($publishTime) <= time()) {
                    continue;
                }

                $times[] = $publishTime;
            }
            $startDate = date('Y-m-d', strtotime("{$startDate} +1 day"));
        }
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
}
