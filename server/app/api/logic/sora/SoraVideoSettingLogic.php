<?php

namespace app\api\logic\sora;

use app\api\logic\ApiLogic;
use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sora\SoraVideoSetting;
use app\common\model\sora\SoraVideoTask;
use app\common\model\user\User;
use think\facade\Db;
use think\facade\Log;

/**
 * SoraVideoTaskLogic
 * sora视频任务逻辑处理
 */
class SoraVideoSettingLogic extends ApiLogic
{
    const SORA_VIDEO_CREATE = 'sora_video_create';
    const SORA_PRO_VIDEO_CREATE = 'sora_pro_video_create';
    const SORA_COPYWRITING_CREATE = 'sora_copywriting_create';
    const SORA_VIDEO_STATUS = 'sora_video_status';

    public static function add(array $params): bool
    {
        $successNum   = 0;
        $errorNum     = 0;
        $name         = $params['name'] ?? '';
        $theme        = $params['theme'] ?? '';
        $content      = $params['content'] ?? '无';
        $gender       = $params['gender'] ?? '无';
        $image_urls   = $params['image_urls'] ?? [];
        $frequency    = $params['frequency'] ?? '';                         //镜头切换频率
        $aspect_ratio = $params['aspect_ratio'] == '9:16' ? '9:16' : '16:9';//输出比例
        $duration     = !empty($params['duration']) ? $params['duration'] : 10; //输出时长
        $style        = $params['style'] ?? '';                             //视频风格
        $number       = $params['number'] ?? 1;                             //生成视频数量
        $taskId       = generate_unique_task_id();
        $ai_type      = $params['ai_type'] ?? 0;
        $model        = $params['model'] == 'sora-2-pro' ? 2 : 1;

        $keywords = '视频类型：【' . $theme . '】
        视频细节：【' . $content . '】
        人物性别：【' . $gender . '】
        视频风格：【' . $style . '】
        镜头切换频率：【' . $frequency . '】
        输出比例：【' . $aspect_ratio . '】
        输出时长：【' . $duration . 's】';
        if (empty($name) || empty($number) || empty($theme) || empty($style)) {
            message('参数错误');
        }

        // AI 优化文案
        if ($ai_type == 1){
            $message = self::copywriting(['keywords' => $keywords, 'number' => 1]);
            $keywords = !empty($message) ? $message : $keywords;
        }

        try {
            Db::startTrans();
            $insert = [
                'user_id'       => self::$uid,
                'name'          => $name,
                'task_id'       => $taskId,
                'status'        => 0,
                'video_count'   => $number,
                'copywriting'   => $keywords,
                'ai_type'       => $ai_type,
                'pic'           => 'static/images/creationRecord.jpg',
                'model_version' => $model,
            ];
            $setting = SoraVideoSetting::create($insert);

            for ($i = 0; $i < $number; $i++) {
                $request = [
                    'prompt'       => $keywords,
                    'aspect_ratio' => $aspect_ratio,
                    'duration'     => $duration,
                    'model'        => $model,
                ];
                // 素材图片不为空
                if (!empty($image_urls)) {
                    // 生成的视频选择素材图片，按顺序只可选择一张
                    if (count($image_urls) == 1) {
                        $key = 0;
                    } else {
                        if ($i > count($image_urls)) {
                            $key = $i % count($image_urls);
                        } else {
                            $key = $i;
                        }
                    }
                    $request['image_urls'][] = $image_urls[$key];
                }

                $scene       = $model == 2 ? self::SORA_PRO_VIDEO_CREATE : self::SORA_VIDEO_CREATE;
                $videoTaskId = generate_unique_task_id();
                $insertTask  = [
                    'user_id'          => self::$uid,
                    'video_setting_id' => $setting->id,
                    'name'             => $name . '_' . ($i + 1),
                    'task_id'          => $videoTaskId,
                    'pic'              => 'static/images/creationRecord.jpg',
                    'status'           => 0,
                    'gender'           => $gender,
                    'ai_type'          => $ai_type,
                    'duration'         => $duration,
                    'msg'              => $keywords,
                    'create_time'      => time(),
                    'update_time'      => time(),
                    'model_version'    => $model,
                ];
                $result = self::requestUrl($request, $scene, self::$uid, $videoTaskId);
                if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
                    self::$returnData['id'][] = $result['data']['id'] ?? '';
                    $insertTask['extra'] = json_encode([
                                                           'copywriting' => $keywords,
                                                           'image_urls'  => $image_urls,
                                                           'video_id'    => $result['data']['id'] ?? ''
                                                       ], JSON_UNESCAPED_UNICODE);
                    SoraVideoTask::create($insertTask);
                } else {
                    $errorNum += 1;
                }
                usleep(100000);
            }
            self::$returnData = $setting->toArray();
            self::$returnData['success_num'] = $successNum;
            self::$returnData['error_num']   = $errorNum;
            self::$returnData['task_id']     = $taskId;
            self::$returnData['total_num']   = $number;
            $update                          = [
                'extra'       => json_encode([
                                                'image_urls' => $image_urls,
                                                'image_counts' => count($image_urls)
                                            ], JSON_UNESCAPED_UNICODE),
                'status'      => $errorNum == 0 ? 2 : ($errorNum == $number ? 4 : 5),
                'success_num' => $successNum,
                'error_num'   => $errorNum
            ];
            SoraVideoSetting::update($update, ['id' => $setting->id]);
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

        $scene = self::SORA_VIDEO_STATUS;

        if (!empty($result) && isset($result['code']) && $result['code'] == 10000) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }

    public static function copywriting(array $params)
    {
        $message = '帮我创作一段适合SORA生成视频的文案，视频参数如下：'.$params['keywords'];
        $number   = $params['number'] ?? 1;
        if (empty($message) || empty($number)) {
            message('参数错误');
        }

        $taskId  = generate_unique_task_id();
        $request = [
            'keywords' => $message,
            'number'   => $number,
        ];
        $scene   = self::SORA_COPYWRITING_CREATE;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);
        Log::channel('sora')->write('扣费请求返回'.json_encode($result));
        if (!empty($result) && isset($result['data']['message'])) {
            return $result['data']['message'];
        }
        return '';
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId)
    {

        try {
            $response = \app\common\service\ToolsService::sora();
            [$tokenScene, $tokenCode] = match ($scene) {
                self::SORA_COPYWRITING_CREATE => ['sora_copywriting_create', AccountLogEnum::TOKENS_DEC_SORA_COPYWRITING],
                self::SORA_VIDEO_CREATE       => ['sora_video_create', AccountLogEnum::TOKENS_DEC_SORA_VIDEO],
                self::SORA_PRO_VIDEO_CREATE   => ['sora_pro_video_create', AccountLogEnum::TOKENS_DEC_SORA_PRO_VIDEO],
            };
            $unit               = TokenLogService::checkToken($userId, $tokenScene);
            $request['task_id'] = $taskId;
            $request['user_id'] = $userId;
            $request['now']     = time();

            switch ($scene) {
                case self::SORA_COPYWRITING_CREATE:
                    $response = $response->text($request);
                    break;
                case self::SORA_VIDEO_CREATE:
                    $response = $response->create($request);
                    break;
                case self::SORA_PRO_VIDEO_CREATE:
                    $response = $response->proCreate($request);
                    break;
                default:
            }
            Log::channel('sora')->write('扣费请求返回'.json_encode($response));
            //成功响应，需要扣费
            if (isset($response['code']) && $response['code'] == 10000) {
                $points = $unit;
                Log::channel('sora')->write('扣费数量'.$points);
                if ($points > 0) {
                    $extra = [];
                    switch ($scene) {
                        case self::SORA_COPYWRITING_CREATE:
                            $extra = ['扣费项目' => '一句话生成视频AI优化文案', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SORA_VIDEO_CREATE:
                            $extra = ['扣费项目' => '一句话生成视频', '算力单价' => $unit, '实际消耗算力' => $points];
                            break;
                        case self::SORA_PRO_VIDEO_CREATE:
                            $extra = ['扣费项目' => '一句话生成视频(pro)', '算力单价' => $unit, '实际消耗算力' => $points];
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
            $find = SoraVideoSetting::where('id', $params['id'])->where('user_id', self::$uid)->findOrEmpty();

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
     * 获取sora视频设置详情
     * @param int $id
     * @return bool
     */
    public static function detail(int $id): bool
    {
        try {
            $setting = SoraVideoSetting::where('id', $id)
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
     * 删除sora视频设置
     * @param int $id
     * @return bool
     */
    public static function delete($id): bool
    {
        try {
            if (is_string($id)) {
                SoraVideoSetting::destroy(['id' => $id]);
            } else {
                SoraVideoSetting::whereIn('id', $id)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function checkStatus(){
        $settings = SoraVideoSetting::where('status', 'in',[2,5])->where('create_time', '<=', strtotime('-40 minutes'))->select()->toArray();
        foreach ($settings as $setting){
            $num = $setting['success_num'] + $setting['error_num'];
            if ($setting['video_count'] == $num){
                if ($setting['error_num'] > 0 && $setting['error_num'] < $num){
                    SoraVideoSetting::where('id', $setting['id'])->update(['status' => 5]);
                }else if ($setting['error_num'] > 0 && $setting['error_num'] == $num){
                    SoraVideoSetting::where('id', $setting['id'])->update(['status' => 4]);
                }else{
                    SoraVideoSetting::where('id', $setting['id'])->update(['status' => 3]);
                }
            }
        }
        return true;
    }
}
