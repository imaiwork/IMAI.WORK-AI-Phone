<?php

namespace app\api\logic\storyboard;

use app\api\controller\VideoInfoController;
use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\api\logic\WechatLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\storyboard\StoryboardVideoSetting;
use app\common\model\storyboard\StoryboardVideoTask;
use app\common\model\user\User;
use app\common\model\user\UserAuth;
use app\common\model\user\UserTokensLog;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

/**
 * StoryboardVideoTaskLogic
 * 分镜视频任务逻辑处理
 */
class StoryboardVideoSettingLogic extends ApiLogic
{
    const STORYBOARD_VIDEO_CREATE = 'storyboard_video_create';
    const STORYBOARD_VIDEO_STATUS = 'storyboard_video_status';

    public static function add(array $params): bool
    {
        $default = ConfigService::get('storage', 'default', 'local');
        if ($default !== 'aliyun') {
            message('该功能仅限开启阿里云oss后使用');
        }
        $ossConfig = ConfigService::get('storage', 'aliyun');
        if (empty($ossConfig['Location']) || empty($ossConfig['bucket'])) {
            message('请先配置阿里云oss');
        }
        $ossRegion = $ossConfig['Location'];
        $pattern   = ['cn-beijing', 'cn-hangzhou', 'cn-shenzhen', 'cn-shanghai'];
        $region    = self::matchAnySubstring($ossRegion, $pattern);
        if (!$region) {
            message('仅限 华东2（上海）、华北2（北京）、华东1（杭州）、华南1（深圳）区域的oss才可使用');
        }
        $name       = $params['name'] ?? '分镜混剪任务-' . date('YmdHis') . rand(100000, 999999);
        $taskId     = generate_unique_task_id();
        $successNum = 0;
        $errorNum   = 0;
        $number     = $params['number'] ?? 1;
        $width      = 1080;
        $height     = 1920;
        $duration   = $params['duration'];
        $picStatus  = true;
        //替换oss域名
        foreach ($params['MediaGroupArray'] as $key => $value) {
            foreach ($params['MediaGroupArray'][$key]['MediaArray'] as $key1 => $value1) {
                $params['MediaGroupArray'][$key]['MediaArray'][$key1] = self::replaceOssDomain($value1, $ossRegion, $ossConfig['bucket']);
                if (str_contains($value1, 'mp4') && $picStatus) {
                    //生成缩略图
                    $videos          = [
                        'video_url' => FileService::getFileUrl($params['MediaGroupArray'][$key]['MediaArray'][$key1]),
                        'time'      => 1.0,
                        'options'   => [
                            'quality' => 2
                        ]
                    ];
                    $thumbnailResult = (new VideoInfoController())->videoThumbnail($videos);
                    if ($thumbnailResult['result']) {
                        $pic       = $thumbnailResult['url'];
                        $picStatus = false;
                    }
                }
            }
        }
        //InputConfig 参数
        $inputConfig = [
            'MediaGroupArray'      => $params['MediaGroupArray'],
            'TitleArray'           => $params['TitleArray'],
            'BackgroundMusicArray' => $params['BackgroundMusicArray'],
        ];

        //EditingConfig 参数
        $editingConfig = [
            'TitleConfig'           => [
                'EffectColorStyle' => 'CS0001-000004'
            ],
            'BackgroundMusicConfig' => [
                'Volume' => $params['BackgroundMusicVolume'] ?? 0.2,
            ],
        ];
        //背景音乐风格，默认为空。若InputConfig中已配置背景音乐，此字段不生效
        if (isset($params['BackgroundMusicStyle'])) {
            $editingConfig['BackgroundMusicConfig']['Style'] = $params['BackgroundMusicStyle'];
        }

        if (isset($params['SpeechTextArray'])) {
            $inputConfig['SpeechTextArray'] = $params['SpeechTextArray'];
            $editingConfig['SpeechConfig']  = [
                'Volume' => 1,
            ];
            $type                           = 1;
        } else {
            $type = 2;
        }
        if (isset($params['StickerArray'])) {
            $inputConfig['StickerArray'] = $params['StickerArray'];
        }

        //OutputConfig 参数
        $outputConfig = [
            'Count'    => $number,
            'Width'    => $width,
            'Height'   => $height,
            'MediaURL' => 'https://' . $ossConfig['bucket'] . '.' . $ossRegion . '.aliyuncs.com/uploads/video/' . date('Ymd') . '/' . $taskId . '_{index}.mp4',
        ];

        try {
            Db::startTrans();
            $insert  = [
                'user_id'        => self::$uid,
                'name'           => $name,
                'task_id'        => $taskId,
                'type'           => $type,
                'status'         => 0,
                'video_count'    => $number,
                'total_duration' => $duration * $number,
                'input_config'   => json_encode($inputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'output_config'  => json_encode($outputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'editing_config' => json_encode($editingConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'pic'            => $pic ?? 'static/images/creationRecord.jpg',
            ];
            $setting = StoryboardVideoSetting::create($insert);
            $scene   = self::STORYBOARD_VIDEO_CREATE;
            $request = [
                'InputConfig'   => json_encode($inputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'EditingConfig' => json_encode($editingConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'OutputConfig'  => json_encode($outputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'region'        => $region,
                'duration'      => $duration * $number,
            ];
            $result  = self::requestUrl($request, $scene, self::$uid, $taskId);
            if (isset($result['data']['body']['JobId'])) {
                if ($type == 1) {
                    $msg = $params['SpeechTextArray'][0] ?? '该任务未设置文案，自行补充';
                } else {
                    foreach ($params['MediaGroupArray'] as $value) {
                        foreach ($value['SpeechTextArray'] as $value1) {
                            if (!empty($value1)) {
                                $msg = $value1;
                                break;
                            }
                        }
                        if (!empty($msg)) {
                            break;
                        }
                    }
                }
                for ($i = 0; $i < $number; $i++) {
                    $videoTaskId         = generate_unique_task_id();
                    $insertTask          = [
                        'user_id'          => self::$uid,
                        'video_setting_id' => $setting->id,
                        'name'             => $name . '_' . ($i + 1),
                        'task_id'          => $videoTaskId,
                        'pic'              => $pic ?? 'static/images/creationRecord.jpg',
                        'status'           => 0,
                        'duration'         => $duration,
                        'msg'              => $msg ?? '该任务未设置文案，自行补充',
                        'type'             => $type,
                        'create_time'      => time(),
                        'update_time'      => time(),
                        'width'            => $width,
                        'height'           => $height,
//                        'video_result_url' => '/uploads/video/' . date('Ymd') . '/' . $taskId . '_' . $i . '.mp4'
                        'video_result_url' => ''
                    ];
                    $insertTask['extra'] = '';
                    StoryboardVideoTask::create($insertTask);

                }
                self::$returnData                = $setting->toArray();
                self::$returnData['success_num'] = $successNum;
                self::$returnData['error_num']   = $errorNum;
                self::$returnData['task_id']     = $taskId;
                self::$returnData['total_num']   = $number;
                $update                          = [
                    'extra'       => '',
                    'status'      => $errorNum == 0 ? 2 : ($errorNum == $number ? 4 : 5),
                    'success_num' => $successNum,
                    'error_num'   => $errorNum,
                    'result_id'   => $result['data']['body']['JobId']
                ];
                StoryboardVideoSetting::update($update, ['id' => $setting->id]);
                self::$returnData['result_id'] = $update['result_id'];
                $mnpMessage                    = [
                    'openid'   => UserAuth::where('user_id', self::$uid)->order('id', 'desc')->value('openid'),
                    'scene_id' => 402,
                    'name'     => $name,
                    'time'     => date('Y-m-d H:i:s', time()),
                    'status'   => '开始'
                ];
                WechatLogic::sendMnpMessage($mnpMessage);
            } else {
                throw new Exception('生成失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function status($params, $userId)
    {
        $taskId = $params['task_id'] ?? '';
        if (!$taskId) {
            message('参数错误');
        }
        $scene               = self::STORYBOARD_VIDEO_STATUS;
        $resultId            = StoryboardVideoSetting::where('task_id', $taskId)->value('result_id');
        $params['result_id'] = "c762789a766f4662aa9f141e547d1563";
        $response            = \app\common\service\ToolsService::storyboard();
        $result              = $response->status($params);
        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::storyboard();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::STORYBOARD_VIDEO_CREATE => ['storyboard_video_create', AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO],
            };
            $unit               = TokenLogService::checkToken($userId, $tokenScene);
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now']     = time();

            switch ($scene) {
                case self::STORYBOARD_VIDEO_CREATE:
                    $response = $response->create($request);
                    break;
                case self::STORYBOARD_VIDEO_STATUS:
                    $response = $response->status($request);
                    break;
                default:
            }
            Log::channel('storyboard')->write('扣费请求返回' . json_encode($response));
            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $points = ceil($request['duration'] / 60) * $unit;
                Log::channel('storyboard')->write('扣费数量' . $points);
                if ($points > 0) {
                    $extra = [];
                    switch ($scene) {
                        case self::STORYBOARD_VIDEO_CREATE:
                            $extra = ['扣费项目' => '分镜混剪', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        default:
                    }

                    //token扣除
                    User::userTokensChange($userId, $points);
                    //记录日志
                    AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
                }
                return $response ?? [];
            }
            return $response;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function updateName(array $params): bool
    {
        try {
            $find = StoryboardVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

            if ($find->isEmpty()) {
                self::setError('视频设置不存在');
                return false;
            }
            $find->name        = $params['name'];
            $find->update_time = time();
            $find->save();
            self::$returnData = $find->refresh()->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 获取分镜视频设置详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $setting = StoryboardVideoSetting::where('id', $id)
                                             ->where('user_id', self::$uid)
                                             ->find();

            if (!$setting) {
                self::setError('视频设置不存在');
                return false;
            }

            $settingData = $setting->toArray();

            // 处理JSON字段
            $jsonFields = ['anchor', 'voice', 'copywriting', 'character_design', 'material', 'clip', 'music', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($settingData[$field])) {
                    $settingData[$field] = json_decode($settingData[$field], true);
                } else {
                    $settingData[$field] = [];
                }
            }

            self::$returnData = $settingData;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除分镜视频设置
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {
            if (is_string($id)) {
                StoryboardVideoSetting::destroy(['id' => $id]);
            } else {
                StoryboardVideoSetting::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function checkStatus()
    {
        $settings = StoryboardVideoSetting::where('status', 'in', [0, 1, 2])->where('create_time', '<=', strtotime('-10 minutes'))->select()->toArray();
        foreach ($settings as $setting) {
            $num = $setting['success_num'] + $setting['error_num'];
            if ($setting['video_count'] == $num) {
                if ($setting['error_num'] > 0 && $setting['error_num'] < $num) {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 5]);
                    $status = '部分失败';
                } else if ($setting['error_num'] > 0 && $setting['error_num'] == $num) {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 4]);
                    $status = '生成失败';
                } else {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 3]);
                    $status = '生成成功';
                }
                //发送小程序消息通知
                $old = NoticeRecord::where('title', 'like', '%' . $setting['name'] . '%')->findOrEmpty();
                //回调时已通知，避免重复通知
                if (!$old->isEmpty()) {
                    continue;
                }
                $mnpMessage = [
                    'openid'   => UserAuth::where('user_id', $setting['user_id'])->order('id', 'desc')->value('openid'),
                    'scene_id' => 402,
                    'name'     => $setting['name'],
                    'time'     => date('Y-m-d H:i:s', time()),
                    'status'   => $status
                ];
                WechatLogic::sendMnpMessage($mnpMessage);
            }
        }
        return true;
    }

    public static function matchAnySubstring(string $originalStr, array $targetSubstrings)
    {
        foreach ($targetSubstrings as $sub) {
            if (str_contains($originalStr, $sub)) {
                return $sub;
            }
        }
        return false;
    }

    public static function checkTaskStatus()
    {
        $tasks = StoryboardVideoSetting::where('status', '=', 2)->select()->toArray();
        if (empty($tasks)) {
            return true;
        }
        $service   = \app\common\service\ToolsService::storyboard();
        $ossRegion = ConfigService::get('storage', 'aliyun')['Location'];
        $pattern   = ['cn-beijing', 'cn-hangzhou', 'cn-shenzhen', 'cn-shanghai'];
        $region    = self::matchAnySubstring($ossRegion, $pattern);
        if (!$region) {
            echo '请先配置阿里云OSS存储';
            return false;
        }
        foreach ($tasks as $task) {
            $params['result_id'] = $task['result_id'];
            $params['task_id']   = $task['task_id'];
            $params['region']    = $region;
            $result              = $service->status($params);
            $taskUpdate          = [];
            $successNum          = 0;
            $errorNum            = 0;
            $userId              = $task['user_id'];
            $tokenScene          = 'storyboard_video_create';
            $taskId              = $task['task_id'];
            $unit                = TokenLogService::checkToken($userId, $tokenScene);
            if (!$result) {
                continue;
            }
            $data          = $result['data'];
            $totalDuration = 0; //分钟
            $return        = false;
            if ($result['code'] == 10000 && $data['statusCode'] == 200) {
                $status = $data['body']['EditingBatchJob']['Status'] ?? '';
                if ($status == 'Finished') {
                    $jobs = $data['body']['EditingBatchJob']['SubJobList'];
                    $num  = 0;
                    foreach ($jobs as $job) {
                        $num++;
                        $videoTask = StoryboardVideoTask::where('video_setting_id', $task['id'])->where('name', $task['name'] . '_' . $num)->findOrEmpty();
                        if ($videoTask->isEmpty()) {
                            continue;
                        }
                        //视频生成成功
                        if ($job['Status'] == 'Success') {
                            if (ceil($job['Duration'] / 60) != ceil($videoTask->duration / 60)) {
                                $return = true;
                            }
                            $totalDuration               += ceil( ((int) $job['Duration']) / 60);
                            $videoTask->status           = 3;
                            $videoTask->result_id        = $job['MediaId'];
                            $videoTask->duration         = $job['Duration'];
                            $videoTask->video_result_url = self::returnFileUrl($job['MediaURL']);
                            $videoTask->video_token      = ceil($job['Duration'] / 60) * $unit;
                            $videoTask->save();
                            $successNum++;
                        } else if ($job['Status'] == 'Init'){
                            continue;
                        }else{
                            $videoTask->status = 2;
                            $videoTask->remark = '系统繁忙，视频生成失败，请稍后重试';
                            $videoTask->save();
                            $errorNum++;
                            $return = true;
                        }
                    }
                }
                if ($status == 'Failed') {
                    $videoTasks = StoryboardVideoTask::where('video_setting_id', $task['id'])->select();
                    $extend     = json_decode($data['body']['EditingBatchJob']['Extend'], true);
                    foreach ($videoTasks as $videoTask) {
                        //视频生成失败
                        $videoTask->status = 2;
                        $videoTask->remark = self::remarkMessage($extend['ErrorMessage']);
                        $videoTask->save();
                        $errorNum++;
                    }
                }
            } else {
                $videoTasks = StoryboardVideoTask::where('video_setting_id', $task['id'])->select();
                foreach ($videoTasks as $videoTask) {
                    //视频生成失败
                    $videoTask->status = 2;
                    $videoTask->remark = '系统错误，生成失败';
                    $videoTask->save();
                    $errorNum++;
                }
            }
            //更新创建任务视频成功失败数
            $taskUpdate['update_time'] = time();
            $taskUpdate['success_num'] = $successNum;
            $taskUpdate['error_num']   = $errorNum;
            StoryboardVideoSetting::update($taskUpdate, ['id' => $task['id']]);

            if (isset($status) && $status == 'Finished') {
                echo "开始计费\n" . "原消耗时长". ceil($task['total_duration'] / 60) . "分钟\n实际消耗时长" . $totalDuration . "分钟\n";
                //计费
                $typeID     = AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO;
                //查询是否需要补扣或退费
                if (ceil($task['total_duration'] / 60) != $totalDuration && $return) {
                    $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
                    if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                        $cost = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount');
                        if (ceil($task['total_duration'] / 60) < $totalDuration) {
                            $points   = ($totalDuration - ceil($task['total_duration'] / 60)) * $unit;
                            $trueCost = $cost + $points;
                            $extra    = ['扣费项目' => '分镜混剪差额补扣', '算力单价' => $unit,'原扣'=> $cost, '实际消耗算力' => $trueCost, '补扣' => $points];
                            AccountLogLogic::recordUserTokensLog(true, $userId, $typeID, $points, $taskId, $extra);
                        } else {
                            $points   = (ceil($task['total_duration'] / 60) - $totalDuration) * $unit;
                            $trueCost = $cost - $points;
                            $extra    = ['退费项目' => '分镜混剪差额返还', '算力单价' => $unit,'原扣'=> $cost, '实际消耗算力' => $trueCost, '退费' => $points];
                            AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId, $extra);
                        }
                    }
                }
            }
        }

        if (isset($status) && $status == 'Failed') {
            echo "开始退费\n";
            //计费参数
            $userId = $task['user_id'];
            $typeID = AccountLogEnum::TOKENS_DEC_STORYBOARD_VIDEO;
            $taskId = $task['task_id'];
            $count  = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {
                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }
        }
        return true;
    }

    public static function replaceOssDomain($url, $ossRegion, $ossBucket)
    {
        $fileUrl = substr($url, strpos($url, 'uploads'));
        return 'https://' . $ossBucket . '.' . $ossRegion . '.aliyuncs.com/' . $fileUrl;
    }

    public static function returnFileUrl($url)
    {
        return substr($url, strpos($url, 'uploads'));
    }

    public static function remarkMessage($message)
    {
        $result = '生成失败';
        if (str_contains($message, 'The prefix of the specified')) {
            $result = '仅支持阿里云存储的素材';
        }
        if (str_contains($message, 'reason: AccessDenied')) {
            $result = '阿里云oss拒绝访问，请检查oss设置';
        }
        return $result;
    }
}
