<?php

namespace app\api\logic\storyboard;

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
use app\common\service\ConfigService;
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
        $width      = 1920;
        $height     = 1080;
        $duration   = $params['duration'];

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
            'MediaURL' => 'http://' . $ossConfig['bucket'] . '.' . $ossRegion . '.aliyuncs.com/uploads/video/' . date('Ymd') . '/' . $taskId . '_{index}.mp4',
            "Video"    => ['Crf' => $duration]
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
                'input_config'   => json_encode($inputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'output_config'  => json_encode($outputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'editing_config' => json_encode($editingConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'pic'            => $params['pic'] ?? 'static/images/creationRecord.jpg',
            ];
            $setting = StoryboardVideoSetting::create($insert);
            $scene   = self::STORYBOARD_VIDEO_CREATE;
            $request = [
                'InputConfig'   => json_encode($inputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'EditingConfig' => json_encode($editingConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'OutputConfig'  => json_encode($outputConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'region'        => $region,
            ];
            $result  = self::requestUrl($request, $scene, self::$uid, $taskId);
            if (isset($result['data']['body']['JobId'])) {
                for ($i = 0; $i < $number; $i++) {
                    $videoTaskId = generate_unique_task_id();
                    $insertTask  = [
                        'user_id'          => self::$uid,
                        'video_setting_id' => $setting->id,
                        'name'             => $name . '_' . ($i + 1),
                        'task_id'          => $videoTaskId,
                        'pic'              => 'static/images/creationRecord.jpg',
                        'status'           => 0,
                        'duration'         => $duration,
                        'msg'              => '',
                        'type'             => $type,
                        'create_time'      => time(),
                        'update_time'      => time(),
                        'width'            => $width,
                        'height'           => $height,
                        'video_result_url' => '/uploads/video/' . date('Ymd') . '/' . $taskId . '_' . $i . '.mp4'
                    ];

                    if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                        self::$returnData['id'][] = $result['data']['id'] ?? '';
                        $insertTask['extra']      = '';
                        StoryboardVideoTask::create($insertTask);
                    } else {
                        $errorNum += 1;
                    }
                    usleep(100000);
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
                    'error_num'   => $errorNum
                ];
                StoryboardVideoSetting::update($update, ['id' => $setting->id]);
                $mnpMessage = [
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

    public static function status($params)
    {
        $taskId = $params['task_id'] ?? '';
        if (!$taskId) {
            message('参数错误');
        }

        $scene = self::STORYBOARD_VIDEO_STATUS;

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
                default:
            }
            Log::channel('storyboard')->write('扣费请求返回' . json_encode($response));
            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $points = $unit;
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
        $settings = StoryboardVideoSetting::where('status', 'in', [2, 5])->where('create_time', '<=', strtotime('-40 minutes'))->select()->toArray();
        foreach ($settings as $setting) {
            $num = $setting['success_num'] + $setting['error_num'];
            if ($setting['video_count'] == $num) {
                $send = false;
                if ($setting['error_num'] > 0 && $setting['error_num'] < $num) {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 5]);
                } else if ($setting['error_num'] > 0 && $setting['error_num'] == $num) {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 4]);
                    $send   = true;
                    $status = '生成失败';
                } else {
                    StoryboardVideoSetting::where('id', $setting['id'])->update(['status' => 3]);
                    $send   = true;
                    $status = '生成成功';
                }
                //发送小程序消息通知
                if ($send) {
                    $old = NoticeRecord::where('title', 'like', '%' . $setting['name'] . '%')->findOrEmpty();
                    //回调时已通知，避免重复通知
                    if (!$old->isEmpty()) {
                        return true;
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
}
