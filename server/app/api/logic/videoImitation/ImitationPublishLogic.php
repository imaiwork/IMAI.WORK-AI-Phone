<?php

namespace app\api\logic\videoImitation;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvAccount;
use app\common\enum\DeviceEnum;
use app\common\model\videoImitation\VideoImitationTask;
use app\common\service\FileService;
use think\facade\Db;
use think\facade\Log;

class ImitationPublishLogic extends ApiLogic
{
    /**
     * @desc 添加复刻视频发布计划
     * @param array $params
     * @return bool
     */
    public static function add(array $params)
    {
        Db::startTrans();
        try {
            $params['user_id'] = self::$uid;
            
            if (isset($params['accounts']) && is_array($params['accounts'])) {
                $params['accounts'] = json_encode($params['accounts'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['video_ids']) && is_array($params['video_ids'])) {
                $videoIdsArr = $params['video_ids'];
                $params['video_ids'] = json_encode($params['video_ids'], JSON_UNESCAPED_UNICODE);
            } else {
                $videoIdsArr = json_decode($params['video_ids'], true) ?? [];
            }
            
            if (isset($params['time_config']) && is_array($params['time_config'])) {
                $params['time_config'] = json_encode($params['time_config'], JSON_UNESCAPED_UNICODE);
            }

            $params['task_type'] = 6;
            $params['publish_start'] = date('Y-m-d', time());
            $params['type'] = 0;
            $params['status'] = 1;

            // 1. 验证设备时间冲突
            self::checkPublishTime($params, count($videoIdsArr));

            // 2. 插入发布主表
            $publish = SvPublishSetting::create($params);
            
            if (!$publish->isEmpty() && isset($params['accounts'])) {
                // 3. 构建并投递账号及设备任务池
                self::batchPushlishAccount($publish, $params, count($videoIdsArr));
            }

            Db::commit();
            self::$returnData = $publish->toArray();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 验证发布时间
     * @param array $params
     * @param int $mediaCount
     * @throws \Exception
     * @return void
     */
    private static function checkPublishTime($params, $mediaCount)
    {
        $accounts = $params['accounts'] ?? [];
        if (is_string($accounts)) {
            $accounts = json_decode($accounts, true);
        }
        $time_config = $params['time_config'] ?? [];
        if (is_string($time_config)) {
            $time_config = json_decode($time_config, true);
        }

        if (empty($accounts) || empty($time_config)) {
            return;
        }

        $allocatedAccounts = self::allocateMediaToAccounts($accounts, $mediaCount);
        foreach ($allocatedAccounts as $account) {
            if ($account['count'] == 0) continue;
            $days = ceil($account['count'] / $params['publish_frep']);
            $times = \app\api\logic\device\TaskLogic::getTimes($time_config, date('Y-m-d', time()), $days);

            $find = SvAccount::where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
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
    }

    private static function batchPushlishAccount($publish, $params, $mediaCount)
    {
        $time_config = json_decode($params['time_config'], true);
        $accounts = json_decode($params['accounts'], true);
        
        $allocatedAccounts = self::allocateMediaToAccounts($accounts, $mediaCount);
        $tmpTime = strpos($time_config[0], '-') !== false ? explode('-', $time_config[0])[0] : $time_config[0];
        $nextPublishTime = date('Y-m-d H:i:s', strtotime($params['publish_start'] . ' ' . $tmpTime));

        $allTaskInstall = [];
        $insertDetails = [];
        $slotMaxTimes = [];

        foreach ($allocatedAccounts as $account) {
            if ($account['count'] == 0) continue;

            $days = ceil($account['count'] / $params['publish_frep']);
            $times = \app\api\logic\device\TaskLogic::getTimes($time_config, date('Y-m-d', time()), $days);

            $find = SvAccount::where('account', $account['account'])->where('type', $account['type'])->where('user_id', self::$uid)->findOrEmpty()->toArray();
            $account = array_merge($account, $find);

            $pubAccount = SvPublishSettingAccount::create([
                'publish_id' => $publish->id,
                'user_id' => self::$uid,
                'task_type' => 6,
                'name' => $params['name'],
                'account' => $account['account'],
                'account_type' => $account['type'],
                'device_code' => $account['device_code'],
                'video_setting_id' => 0, 
                // 核心：在创建发布号清单时，将请求中传来的目标仿写视频ID群体记录在此（绑定源头任务）
                // 后续定时任务会严格按照存在这里的 video_ids 列表去拉取成品数据
                'video_ids' => $params['video_ids'],
                'poi' => $params['poi'] ?? '',
                'media_type' => $params['media_type'],
                'publish_start' => $params['publish_start'],
                'publish_end' => $params['publish_end'] ?? null,
                'next_publish_time' => $nextPublishTime,
                'count' => $account['count'],
                'published_count' => 0,
                'status' => 1,
                'scene' => $params['scene'] ?? 1,
                'created_time' => time(),
            ]);

            $num = 1;
            foreach ($times as $tk => $time) {
                if ($num > $account['count']) {
                    break;
                }
                $num++;
                
                // --- 计算该时段在该计划下的排期时间 (仿7分钟错峰) ---
                $slotKey = $time['start_time'];
                if (isset($slotMaxTimes[$slotKey])) {
                    $pTime = strtotime($slotMaxTimes[$slotKey]) + rand(420, 600);
                    if ($pTime > $time['end_time']) {
                        $pTime = $time['end_time'] - rand(30, 90);
                    }
                } else {
                    $pTime = $time['start_time'] + rand(0, 180);
                }
                $publishTimeStr = date('Y-m-d H:i:s', $pTime);
                $slotMaxTimes[$slotKey] = $publishTimeStr;

                array_push($insertDetails, [
                    'publish_id' => $publish->id,
                    'publish_account_id' => $pubAccount->id,
                    // 占位期此值为0，意味着“坑位准备就绪，等待最终内容”
                    // 之后 Cron 定时扫描时，会提取上一层绑定的 video_ids 将成品取出实装于此
                    'video_task_id' => 0, 
                    'video_setting_id' => 0,
                    'task_type' => 6,
                    'user_id' => self::$uid,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'device_code' => $account['device_code'],
                    'material_id' => 0,
                    'material_type' => ((int)($params['media_type'] ?? 1) === 2) ? 2 : 1,
                    'material_url' => '',
                    'material_title' => '',
                    'material_tag' => '',
                    'pic' => '',
                    'poi' => $account['poi'] ?? '',
                    'scene' => 1,
                    'material_subtitle' => '',
                    'task_id' => generate_unique_task_id(),
                    'sub_task_id' => $pubAccount->id,
                    'platform' => $account['type'],
                    'status' => 2, // 占位待回填
                    'remark' => '',
                    'publish_time' => $publishTimeStr,
                    'create_time' => time(),
                ]);

                array_push($allTaskInstall, [
                    'user_id' => self::$uid,
                    'device_code' => $pubAccount->device_code,
                    'task_type' => DeviceEnum::TASK_TYPE_PUBLISH,
                    'account' => $account['account'],
                    'account_type' => $account['type'],
                    'task_name' => ((int)($params['media_type'] ?? 1) === 2)
                        ? '设备图文复刻发布任务'
                        : '设备视频复刻发布任务',
                    'status' => 0,
                    'day' => date('Y-m-d', $time['start_time']),
                    'start_time' => $time['start_time'],
                    'end_time' => $time['end_time'],
                    'sub_task_id' => $pubAccount->id,
                    'source' => DeviceEnum::TASK_SOURCE_PUBLISH, 
                    'create_time' => time(),
                ]);
            }
        }
        
        if (!empty($insertDetails)) {
            $model = new SvPublishSettingDetail();
            $model->saveAll($insertDetails);
        }

        if (!empty($allTaskInstall)) {
            \app\api\logic\device\TaskLogic::add($allTaskInstall);
        }
    }

    /**
     * @desc 均分视频到账号
     * @param array $accounts
     * @param int $mediaCount
     * @return array
     */
    private static function allocateMediaToAccounts($accounts, $mediaCount)
    {
        $allocatedAccounts = [];
        $accountsByType = [];
        foreach ($accounts as $account) {
            $type = $account['type'];
            if (!isset($accountsByType[$type])) {
                $accountsByType[$type] = [];
            }
            $accountsByType[$type][] = $account;
        }
        
        foreach ($accountsByType as $type => $typeAccounts) {
            $typeAccountCount = count($typeAccounts);
            if ($mediaCount < $typeAccountCount) {
                foreach ($typeAccounts as $index => $account) {
                    $account['count'] = ($index < $mediaCount) ? 1 : 0;
                    $allocatedAccounts[] = $account;
                }
            } else {
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

    /**
     * 定时生成待发布详情（Cron服务调用）
     * 仅将生成好的成片回填到 add 时提前创建的予发布占位记录中
     */
    public static function setImitationPublishDetail()
    {
        try {
            // 全局扫描：所有已生成完成（status=3）未确认，并且时间超过30分钟的离群视频统一自动变为已确认发布
            Db::name('video_imitation_task')->where([
                ['status', '=', 3],
                ['publish_confirm', '=', 0],
                ['update_time', '<', time() - 1800]
            ])->update([
                'publish_confirm' => 1
            ]);

            // 获取还没完成构建明细的分配账号
            $accounts = SvPublishSettingAccount::alias('pa')
                ->field('pa.*, ps.accounts as ps_accounts, ps.time_config as ps_time_config, ps.publish_frep as publish_frep')
                ->join('sv_publish_setting ps', 'pa.publish_id = ps.id')
                ->where('pa.task_status', 1)
                ->where('pa.task_type', 6)
                ->where('pa.status', 'in', [1, 2])
                ->where('ps.status', 'in', [1, 2])
                ->select()->toArray();

            if (empty($accounts)) {
                return;
            }

            foreach ($accounts as $account) {
                // 读取当前账号下仍然是占位状态的明细详情记录
                $placeholders = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
                    ->where('publish_account_id', $account['id'])
                    ->where('task_type', 6)
                    ->where('status', 2)
                    ->where('video_task_id', 0)
                    ->order('publish_time', 'asc')
                    ->select()->toArray();

                if (empty($placeholders)) {
                    // 没有占位任务，说明当前账号已经全部分配满，标记已完成并跳过
                    SvPublishSettingAccount::where('id', $account['id'])->update(['task_status' => 2]);
                    continue;
                }

                $placeholderCount = count($placeholders);

                // 根据上面 add 时绑定的 video_ids 提取视频范围，这里保证了“只拉取属于该计划自身的指定视频”
                $video_ids = json_decode($account['video_ids'], true);
                if (empty($video_ids)) continue;

                // 查出该号已被分配了哪些视频资源 (防止同一账号类型内部发重复)
                $typeUsedList = SvPublishSettingDetail::where('publish_id', $account['publish_id'])
                    ->where('account_type', $account['account_type'])
                    ->where('task_type', 6)
                    ->where('video_task_id', '>', 0)
                    ->column('video_task_id');
                
                $query = VideoImitationTask::where('id', 'in', $video_ids)
                    ->where('status', 3)
                    ->where(function ($q) {
                        $q->where('publish_confirm', 1)
                          ->whereOr('update_time', '<', time() - 1800);
                    });

                $accountMediaType = (int)($account['media_type'] ?? 1);
                if ($accountMediaType === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT) {
                    $query->where('media_type', VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT);
                } else {
                    // 兼容旧数据未写 media_type
                    $query->where(function ($q) {
                        $q->whereNull('media_type')
                            ->whereOr('media_type', 'in', [0, VideoImitationTask::MEDIA_TYPE_VIDEO]);
                    });
                }
                
                if (!empty($typeUsedList)) {
                    $query->where('id', 'not in', $typeUsedList);
                }
                
                // 拿需要的数量
                $availableVideos = $query->limit($placeholderCount)->select()->toArray();
                
                if (empty($availableVideos)) {
                    continue; // 还没生成出来，等下一次轮转
                }

                $statusArr = [];
                foreach ($availableVideos as $key => $media) {
                    if (!isset($placeholders[$key])) break;
                    $ph = $placeholders[$key];

                    $isImageText = (int)($media['media_type'] ?? 1) === VideoImitationTask::MEDIA_TYPE_IMAGE_TEXT
                        || (int)($account['media_type'] ?? 1) === 2;

                    if ($isImageText) {
                        $rewritten = $media['rewritten_images'] ?? [];
                        if (is_string($rewritten)) {
                            $rewritten = json_decode($rewritten, true) ?: [];
                        }
                        if (!is_array($rewritten) || empty($rewritten)) {
                            continue;
                        }
                        $urls = [];
                        foreach ($rewritten as $img) {
                            if (is_array($img)) {
                                $img = $img['url'] ?? '';
                            }
                            $img = trim((string)$img);
                            if ($img !== '') {
                                $urls[] = FileService::getFileUrl($img);
                            }
                        }
                        if (empty($urls)) {
                            continue;
                        }
                        $materialUrl = json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $pic = $urls[0];
                        $materialType = 2;
                    } else {
                        if (empty($media['video_url'])) {
                            continue;
                        }
                        $materialUrl = FileService::getFileUrl($media['video_url']);
                        $pic = $media['thumbnail'] ?? '';
                        $materialType = 1;
                    }

                    $topic = $media['publish_topic'] ?? '';
                    if (is_string($topic) && $topic !== '' && ($topic[0] ?? '') === '[') {
                        $topic = implode(',', json_decode($topic, true) ?? []);
                    }
                    
                    // 回填成片数据
                    SvPublishSettingDetail::where('id', $ph['id'])->update([
                        'video_task_id' => $media['id'],
                        'material_id' => $media['id'],
                        'material_url' => $materialUrl,
                        'material_type' => $materialType,
                        'material_title' => $media['publish_title'] ?: ($media['title'] ?: '默认短标题'),
                        'material_tag' => is_string($topic) ? $topic : '',
                        'pic' => $pic,
                        'material_subtitle' => $media['publish_text'] ?: ($media['rewritten_text'] ?? ''),
                        'status' => 0, // 修改为待发布 (机器调度将读取 status=0 的单子)
                        'update_time' => time(),
                    ]);
                    $statusArr[] = 1;

                    if ($media['publish_confirm'] == 0) {
                        VideoImitationTask::where('id', $media['id'])->update([
                            'publish_confirm' => 1,
                        ]);
                    }
                }
                
                if (!empty($statusArr)) {
                    SvPublishSettingAccount::where('id', $account['id'])->update([
                        'status' => 1,
                        'update_time' => time()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::channel('shanjian')->write("复刻发布明细补填失败：" . $e->getMessage());
        }
    }
}
