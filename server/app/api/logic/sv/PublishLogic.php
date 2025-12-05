<?php

namespace app\api\logic\sv;

use think\facade\Db;

use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvMediaSetting;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\service\FileService;
use app\common\model\sv\SvDeviceRpa;
use Channel\Client as ChannelClient;
use app\common\enum\DeviceEnum;

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

            if (isset($params['accounts']) && is_array($params['accounts'])) {
                $params['accounts'] = implode(',', $params['accounts']);
            }
            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['publish_json']) && is_array($params['publish_json'])) {
                $params['publish_json'] = json_encode($params['publish_json'], JSON_UNESCAPED_UNICODE);
            }
            $params['video_setting_id'] = $params['video_setting_id'] ?? 0;
            //print_r($params);die;
            $params['task_type'] = $params['task_type'] ?? 1;
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
                $params['accounts'] = implode(',', $params['accounts']);
            }
            if (is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }

            if ((int)$params['date_type'] === 1) {
                $times = array_map(function ($item) {
                    return strtotime($item);
                }, array_column($params['publish_json'], 'publish_time'));
                $params['publish_start'] = date('Y-m-d', min($times));
                $params['publish_end'] = date('Y-m-d', max($times));
            } else {
                if (empty($params['publish_start']) || empty($params['publish_end'])) {
                    self::setError('请输入发布时间');
                    return false;
                }
            }

            if (is_array($params['publish_json'])) {
                $params['publish_json'] = json_encode($params['publish_json'], JSON_UNESCAPED_UNICODE);
            }

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
            $accounts = explode(',', $params['accounts']);
            $mediaSetting = SvMediaSetting::where('id', $params['video_setting_id'])->limit(1)->findOrEmpty();
            if ($mediaSetting->isEmpty()) {
                return;
            }
            $media_url = json_decode($mediaSetting['media_url'], true);
            $mediaCount = 0;
            if (!empty($media_url)) {
                $mediaCount = count($media_url[0]['url']);
            }
            $publishJson = json_decode($params['publish_json'], true);

            $days = ceil((strtotime($params['publish_end']) - strtotime($params['publish_start'])) / 86400);
            $time_config = array_map(function ($item) {
                $tmp = $item['start_time'] . '-' . $item['end_time'];
                return $tmp;
            }, json_decode($params['time_config'], true));
            $times = \app\api\logic\device\TaskLogic::getTimes($time_config, date('Y-m-d', time()), $days);
            //print_r($times);die;

            $nextPublishTime = $params['date_type'] == 1 ? (!empty($publishJson) ? $publishJson[0]['publish_time'] : '') : '';
            //print_r($publishJson);die;
            $allTaskInstall = [];
            $deviceTaskName = $params['media_type'] === 2 ? '图文发布任务' : '视频发布任务';
            foreach ($accounts as $key => $account) {
                $account = SvAccount::where('account', $account)->where('user_id', self::$uid)->limit(1)->find();


                //print_r($account);die;
                $nextPublishTime = $nextPublishTime != '' ? $nextPublishTime : self::_getPublishTime($publish,  $mediaSetting['media_count'], $key);
                // array_push($insertData, [
                //     'publish_id' => $publish->id,
                //     'user_id' => self::$uid,
                //     'name' => $params['name'],
                //     'account' => $account['account'],
                //     'account_type' => $account['type'],
                //     'device_code' => $account['device_code'],
                //     'video_setting_id' => $params['video_setting_id'],
                //     'poi' => $params['poi'],
                //     'media_type' => $params['media_type'],
                //     'publish_start' => $params['publish_start'] ?? null,
                //     'publish_end' => $params['publish_end'] ?? null,
                //     'next_publish_time' => $nextPublishTime, //视频发布时间
                //     'count' => $mediaCount,
                //     'published_count' => 0,
                //     'status' => 1,
                //     'created_time' => time(),
                // ]);
                $pubAccount =  SvPublishSettingAccount::create([
                    'publish_id' => $publish->id,
                    'user_id' => self::$uid,
                    'name' => $params['name'],
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'video_setting_id' => $params['video_setting_id'],
                    'poi' => $params['poi'],
                    'media_type' => $params['media_type'],
                    'publish_start' => $params['publish_start'] ?? null,
                    'publish_end' => $params['publish_end'] ?? null,
                    'next_publish_time' => $nextPublishTime, //视频发布时间
                    'count' => $mediaCount,
                    'published_count' => 0,
                    'status' => 1,
                    'created_time' => time(),
                ]);
                $num = 1;
                foreach ($times as $tk => $time) {
                    if ($num > $account['count']) {
                        break;
                    }
                    $num ++;
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
                        'task_name' => '小红书' . $deviceTaskName,
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
            \app\api\logic\device\TaskLogic::add($allTaskInstall);
            //print_r($insertData);die;
            // $model = new SvPublishSettingAccount();
            // $model->saveAll($insertData);
        } catch (\Throwable $th) {
            //print_r($th->__toString());die;
            throw new \Exception($th->getMessage(), $th->getCode());
        }
    }

    public static function change(array $params)
    {
        $find = SvPublishSettingAccount::where('id', $params['id'])->findOrEmpty();
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
            $publish['accounts'] = is_null($publish['accounts']) ? [] : explode(',', $publish['accounts']);
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
     * @desc 删除任务发布计划
     * @param array $params
     * @return bool
     */
    public static function delete(array $params)
    {
        Db::startTrans();
        try {
            // 检查任务发布计划是否存在
            $publish = SvPublishSettingAccount::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();
            if ($publish->isEmpty()) {
                self::setError('任务不存在');
                return false;
            }

            SvPublishSettingDetail::where('publish_account_id', $publish['id'])->where('user_id', self::$uid)->select()->delete();
            SvPublishSetting::where('id', $publish['publish_id'])->where('user_id', self::$uid)->select()->delete();
            $publish->delete();
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

            self::$returnData = $record->toArray();
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

    public static function setPublishDetail()
    {
        //print_r('执行发布记录拉取任务');
        try {
            $accounts = SvPublishSettingAccount::alias('pa')
                ->field('pa.*, ps.publish_start, ps.publish_end, ps.time_config, a.device_code as devicecode')
                ->field('vs.id as media_id, vs.media_count, vs.media_url, vs.media_type, vs.title as media_title, vs.subtitle as media_subtitle, ps.date_type, ps.publish_json')
                ->join('sv_media_setting vs', 'vs.id = pa.video_setting_id and vs.user_id = pa.user_id and vs.media_type = pa.media_type')
                ->join('sv_publish_setting ps', 'ps.id = pa.publish_id and ps.user_id = pa.user_id')
                ->join('sv_account a', 'a.account = pa.account and a.user_id = pa.user_id')
                ->where('pa.status', 1)
                ->where('pa.task_type', 1)
                //->where('pa.id', 150)
                ->where('pa.id', 'NOT IN', function ($query) {
                    $query->name('sv_publish_setting_detail')
                        ->field('publish_account_id')
                        ->where('delete_time is null')
                        ->where('publish_account_id', '>', 0)
                        ->where('task_type', 1)
                        ->group('publish_account_id')->select();
                })
                //->order('pa.id desc')
                //->where('vs.status', 'in', [3, 5])
                ->select()->toArray();

            //print_r(Db::getLastSql());die;
            // print_r("count: " . count($accounts));
            $insertData = [];
            $videoIds = [];
            foreach ($accounts as $key => $account) {
                $medias = self::_getMedias($account);
                //print_r($medias);die;
                $mediaCount = count($medias);
                foreach ($medias as $key => $media) {
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
                            'user_id' => $account['user_id'],
                            'account' => $account['account'],
                            'account_type' => $account['account_type'],
                            'device_code' => $account['devicecode'],
                            'material_id' => $media['media_id'],
                            'material_type' => $media['material_type'],
                            'material_url' => self::_getMaterialUrl($media),
                            'material_title' => $media['material_title'],
                            'material_tag' => $media['topic'],
                            'poi' => $media['poi'],
                            'material_subtitle' => $media['material_subtitle'],
                            'task_id' => generate_unique_task_id(),
                            'platform' => $account['account_type'],
                            'status' => 0,
                            'publish_time' => $media['publish_time'] != '' ? $media['publish_time'] : self::_getPublishTime($account, $mediaCount, $key),
                            'create_time' => time()
                        ]);
                        array_push($videoIds, $media['id']);
                    }
                }
                //print_r($insertData);die;
            }
            //print_r($insertData);die;
            if (!empty($insertData)) {
                $model = new SvPublishSettingDetail();
                $model->saveAll($insertData);
            }

            sleep(2);
            self::_checkPublishTime(array_column($accounts, 'id'));

            self::$returnData = $insertData;
            return true;
        } catch (\Exception $e) {
            print_r($e->__toString());
            die;
            return false;
        }
    }

    private static function _checkPublishTime(array $accountIds)
    {
        //根据生成的发布记录删除主任务多余的时间区间
        $items = SvPublishSettingDetail::where('publish_account_id', 'in', $accountIds)->select()->toArray();
        $ids = [];
        foreach ($items as $item) {
            $task = \app\common\model\sv\SvDeviceTask::field('*')
                ->where('sub_task_id', $item['publish_account_id'])
                ->where('account', $item['account'])
                ->where('account_type', $item['account_type'])
                ->where('device_code', $item['device_code'])
                ->where('task_type', 1)
                ->where('start_time', '<=', strtotime($item['publish_time']))
                ->where('end_time', '>', strtotime($item['publish_time']))
                ->findOrEmpty();
            if (!$task->isEmpty()) {
                $ids[] = $task['id'];
            }
        }
        \app\common\model\sv\SvDeviceTask::where('id', 'not in', $ids)->where('sub_task_id', 'in', $accountIds)->select()->delete();
    }

    private static function _getMaterialUrl($media)
    {
        if (!is_array($media['material_url'])) {
            return FileService::getFileUrl($media['material_url']);
        } else {
            $urls = $media['material_url'];
            return implode(',', array_map(function ($url) {
                return FileService::getFileUrl($url);
            }, $urls));
        }
    }

    private static function _getMedias(array $account)
    {
        //print_r($account);die;

        // 合并后的新数组
        $mergedArray = [];
        $media_url = json_decode($account['media_url'], true);
        //print_r($media_url);die;
        $media_url = $media_url[0]['url'];

        $media_title = json_decode($account['media_title'], true);
        $media_subtitle = json_decode($account['media_subtitle'], true);
        $topics = array_column($media_subtitle, 'topic');

        $date_type = $account['date_type'];
        $publish_json = $date_type == 1 ? json_decode($account['publish_json'], true) : [];
        //print_r($media_url);die;



        // 获取各数组长度
        $mediaUrlCount = count($media_url);
        $titleCount = count($media_title);
        $subtitleCount = count($media_subtitle);
        if ($mediaUrlCount == 0) {
            return [];
        }

        // 循环匹配（以media_url的长度为基准）
        for ($i = 0; $i < $mediaUrlCount; $i++) {
            $topic = isset($topics[$i]) ? $topics[$i] : [];
            $topic = implode(',', $topic);

            $mergedArray[] = [
                'material_url' => isset($media_url[$i]['url']) ? $media_url[$i]['url'] :  $media_url[$i],
                'material_title' => $titleCount == 0 ? ' ' : ($media_title[$i % $titleCount]['content'] ?? ' '), // 循环匹配title
                'material_subtitle' => $subtitleCount == 0 ? ' ' : ($media_subtitle[$i % $subtitleCount]['content'] ?? ' '), //
                'topic' => $topic,
                'material_type' => $account['media_type'],
                'publish_time' => $publish_json[$i]['publish_time'] ?? '',
                'poi' => $account['poi'],
                'id' => $account['id'],
                'media_id' => $account['media_id'],
                'publish_id' => $account['publish_id'],
                'publish_account_id' => $account['id'],
                'user_id' => $account['user_id'],
                'account' => $account['account'],
                'account_type' => $account['account_type'],
                'device_code' => $account['devicecode'],

            ];
        }

        return $mergedArray;
    }

    private static function _getPublishTime($publish,  int $videoCount, int $num)
    {
        try {
            $publish['time_config'] = json_decode($publish['time_config'], true);
            if (empty($publish['time_config'])) {
                $publish['time_config'] = [
                    [
                        'start_time' => date('H:i', time() + 600), // 开始时间
                        'end_time' => '23:59' // 结束时间
                    ]
                ];
            }
            $timeConfig = $publish['time_config'];
            // 初始化结果数组
            $periods = [];
            // 解析开始和结束日期
            $startDate = new \DateTime($publish['publish_start']);
            $endDate = new \DateTime($publish['publish_end']);
            // 遍历日期范围（包含结束日期）
            while ($startDate <= $endDate) {
                $currentDate = $startDate->format('Y-m-d');
                // 遍历每个时间段配置
                foreach ($timeConfig as $timeSlot) {
                    // if (strtotime("{$currentDate} {$timeSlot['end_time']}") < time()) {
                    //     continue;
                    // }
                    $periods[] = [
                        date('Y-m-d H:i:s', strtotime("{$currentDate} {$timeSlot['start_time']}")),
                        date('Y-m-d H:i:s', strtotime("{$currentDate} {$timeSlot['end_time']}"))
                    ];
                }
                // 日期递增1天
                $startDate->modify('+1 day');
            }
            //print_r($periods);die;
            $periodCount = count($periods) > $videoCount ? count($periods) : $videoCount;
            $index = ceil($num % $periodCount);
            return isset($periods[$index]) ? $periods[$index][0] : date('Y-m-d H:i:s', strtotime($periods[0][0]) + (120 * $index)); // 默认值
        } catch (\Exception $e) {
            //print_r($e->__toString());die;
            throw new \Exception($e->getMessage(), $e->getCode());
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
