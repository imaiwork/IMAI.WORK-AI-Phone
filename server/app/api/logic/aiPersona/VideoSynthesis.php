<?php


namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\exception\MaterialNotReadyException;
use app\common\service\MaterialReadinessService;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvSetting;
use app\common\model\user\User;
use app\common\service\aiPersona\AiPersonaOptionService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 设备任务逻辑
 * Class VideoSynthesis    
 * @package app\api\logic\device
 */
class VideoSynthesis extends BasePersonaLogic
{
    const WECHAT_CIRCLE_SCENE = 7;

    public static function getCircleTimesByType(int $personaType)
    {
        $maps = [
            1 => [
                1 => [ //微信朋友圈
                    '07:50-08:00' => '07:51,0',
                ],
            ],
            2 => [
                1 => [
                    '18:00-18:15' => '18:02,0',
                ]
            ],
            3 => [
                1 => [
                    '13:15-13:30' => '13:16,0',
                ]
            ],
        ];

        return $maps[$personaType] ?? [];
    }

    private static function getCirclePublishTimeSlots(AiPersona $persona, SvDevice $device): array
    {
        if ((int)$persona->workflow_template_id > 0) {
            $hasConfig = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
                ->where('scene', self::WECHAT_CIRCLE_SCENE)
                ->count() > 0;

            if ($hasConfig) {
                return self::formatCircleScheduleSlots(self::getAutoSchedule($persona, self::WECHAT_CIRCLE_SCENE));
            }

            // 工作流已配置但无朋友圈发布时间段：不回退默认时段（对齐 AutoVideoSynthesis）
            return [];
        }

        return self::getDefaultCirclePublishTimeSlots((int)$persona->persona_type, $device);
    }

    private static function formatCircleScheduleSlots($schedules): array
    {
        $slots = [];
        foreach ($schedules as $schedule) {
            $startTime = trim((string)$schedule->start_time);
            $endTime = trim((string)$schedule->end_time);
            if ($startTime === '' || $endTime === '') {
                continue;
            }
            $slots[] = $startTime . '-' . $endTime;
        }

        return array_values(array_unique($slots));
    }

    private static function getDefaultCirclePublishTimeSlots(int $personaType, SvDevice $device): array
    {
        $slots = [];
        foreach (self::getCircleTimesByType($personaType) as $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                list($st, $et) = explode('-', $slot);
                if (!self::checkScheduleIsCreate([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'persona_type' => $personaType,
                    'start_time' => $st,
                    'end_time' => $et,
                    'persona_id'=>$device->persona_id,
                    'scene' => self::WECHAT_CIRCLE_SCENE
                ])) {
                    continue;
                }
                $slots[] = $slot;
            }
        }

        return array_values(array_unique($slots));
    }

    private static function buildCircleVideoTypes(int $slotCount): array
    {
        if ($slotCount <= 0) {
            return [];
        }

        $types = [3, 4];
        if ($slotCount === 1) {
            return [$types[array_rand($types)]];
        }

        $videoTypes = [];
        for ($i = 0; $i < $slotCount; $i++) {
            $videoTypes[] = $types[$i % count($types)];
        }

        return $videoTypes;
    }

    private static function getExecTime(array $times, SvDevice $device, $personaType)
    {
        $res = [];
        foreach ($times as $key => $timeSlots) {
            foreach ($timeSlots as $slot => $value) {
                list($st, $et) = explode('-', $slot);
                if (!self::checkScheduleIsCreate([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'persona_type' => $personaType,
                    'start_time' => $st,
                    'end_time' => $et,
                    'persona_id'=>$device->persona_id,
                    'scene' => self::WECHAT_CIRCLE_SCENE
                ])) {
                    continue;
                }

                // 解析 "时间,标志"
                list($time, $flag) = explode(',', $value);
                $res[$flag][$slot][$key] = $time;
            }
        }
        return $res;
    }
    /**
     * 微信企业视频合成任务
     * @param string $deviceCode 设备编码
     * @return bool
     */
    public static function wechatVideoSynthesis($deviceCode)
    {

        try {
            // 添加缓存锁，10分钟内不能重复执行
            $cacheKey = 'wechat_video_synthesis_' . $deviceCode;
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
            if ($device->isEmpty()) {
                $msg = '-不存在';
                throw new \Exception($msg);
            }
            if (Cache::store('material_redis')->get($cacheKey)) {
                throw new \Exception('微信企业视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('material_redis')->set($cacheKey, 1, 600); // 600秒 = 10分钟
            if ($device->persona_id <= 0) {
                $msg = '-未绑定人设';
                throw new \Exception($msg);
            }
             if ($device->synthesis_w == 1) {
                $msg = '-朋友圈视频合成任务已执行';
                throw new \Exception($msg);
            }
            $persona = AiPersona::where('id', $device->persona_id)
                ->whereIn('wechat_publish_mode', [1, 3] )
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                $msg = '-绑定的人设' . AiPersona::formatLabel(null, (int)$device->persona_id) . '不存在或发布模式不符合要求';
                throw new \Exception($msg);
            }
            if (!AiPersonaOptionService::isEnabledForPersonaId((int)$persona->id, 'video_clip')) {
                Log::channel('wechatVideoSynthesis')->write('设备号:' . $deviceCode . ' global_option.video_clip=0，跳过微信视频合成任务');
                return true;
            }
            // 对齐 AutoVideoSynthesis：无朋友圈发布时间段则跳过，不锁 synthesis_w
            $publishCount = MarketingTemplateSchedule::getTodayPublishTaskCount(
                (int)$persona->id,
                self::WECHAT_CIRCLE_SCENE
            );
            $publishTimeSlots = self::getCirclePublishTimeSlots($persona, $device);
            if ($publishCount <= 0 || empty($publishTimeSlots)) {
                Log::channel('wechatVideoSynthesis')->write('朋友圈发布时段为空，跳过微信视频合成：' . json_encode([
                    'device_code' => $deviceCode,
                    'persona_id' => (int)$persona->id,
                    'publish_count' => $publishCount,
                    'slot_count' => count($publishTimeSlots),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return true;
            }

            if ($persona->wechat_publish_mode == 3) {
                $allMaterials = MaterialModel::where('persona_id', $device->persona_id)
                ->where('use_status', 1)
                ->where('is_wechat', 0)
                ->where('publish_mode', 1)
                ->whereIn('material_type', [MaterialModel::MATERIAL_TYPE_VIDEO, MaterialModel::MATERIAL_TYPE_IMAGE])
                ->select()->toArray();
            } else {
                $allMaterials = MaterialModel::where('persona_id', $device->persona_id)
                ->where('use_status', 1)
                ->where('is_wechat', 1)
                ->where('publish_mode', 1)
                ->whereIn('material_type', [MaterialModel::MATERIAL_TYPE_VIDEO, MaterialModel::MATERIAL_TYPE_IMAGE])
                ->select()->toArray();
            }
           

            if (empty($allMaterials)) {
                $msg = '-绑定的人设' . AiPersona::formatLabel($persona) . '下没有可用的素材';
                throw new \Exception($msg);
            }

            // 素材转码就绪门禁:任一视频素材未完成转码则本轮跳过,等下一轮cron
            $check = MaterialReadinessService::checkPersonaMaterials((int)$device->persona_id, (int)$device->user_id);
            if (!$check['ready']) {
                throw MaterialNotReadyException::fromCheck($check, "朋友圈合成 device={$device->device_code} 本轮跳过");
            }
            $groupedData = [
                'videos' => [], // 视频组 (material_type = 1)
                'images' => []  // 图片组 (material_type = 2)
            ];
            foreach ($allMaterials as $item) {
                $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                if (Cache::store('redis')->has($rediskey)) {
                    Cache::store('redis')->delete($rediskey);
                }
                $device_bind_num = Cache::store('material_redis')->get($rediskey);
                if (empty($device_bind_num)) {
                    $device_bind_num = 0;
                }
                // if ($device_bind_num > 2) {
                //     continue;
                // }

                if ($item['material_type'] == 1) {
                    $groupedData['videos'][] = $item;
                } elseif ($item['material_type'] == 2) {
                    $groupedData['images'][] = $item;
                }
            }

            switch ($persona->persona_type) {
                case 1:
                    $videoTypes = self::buildCircleVideoTypes(count($publishTimeSlots));
                    return self::processIndividualIp($device, $persona, $groupedData, $videoTypes, $publishTimeSlots);
                case 2:
                    $videoTypes = self::buildCircleVideoTypes(count($publishTimeSlots));
                    return self::processLocalLife($device, $persona, $groupedData, $videoTypes, $publishTimeSlots);
                case 3:
                    $videoTypes = self::buildCircleVideoTypes(count($publishTimeSlots));
                    return self::processEnterpriseService($device, $persona, $groupedData, $videoTypes, $publishTimeSlots);
                default:
                    $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel($persona) . '类型不存在';
                    throw new \Exception($msg);
            }
        } catch (MaterialNotReadyException $e) {
            // 素材转码未就绪:不标记设备失败,异常透传给 cron 调用方,由它清防重缓存等下一轮
            Log::channel('wechatVideoSynthesis')->write('设备号' . $deviceCode . ' 素材转码未就绪,本轮跳过待下一轮：' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
             $errorMsg = $e->getMessage();
            if ($errorMsg !== '-不存在') {
                $device->markSynthesisDone(SvDevice::SYNTHESIS_SCENE_WECHAT);
                $device->save();
            }
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $e->getMessage();
            Log::channel('wechatVideoSynthesis')->write($msg);
            self::setError($msg);
            return false;
        }
    }

    private static function processIndividualIp($device, $persona, $groupedData, array $videoTypes = [1, 3], array $publishTimeSlots = [])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '个人IP', $publishTimeSlots);
    }

    private static function processLocalLife($device, $persona, $groupedData, array $videoTypes = [1, 4], array $publishTimeSlots = [])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '本地生活', $publishTimeSlots);
    }

    private static function processEnterpriseService($device, $persona, $groupedData, array $videoTypes = [3, 4], array $publishTimeSlots = [])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '企业服务', $publishTimeSlots);
    }

    private static function createVideoSynthesisTasks($device, $persona, $groupedData, array $videoTypes, string $prefix, array $publishTimeSlots = [])
    {
        $videos = $groupedData['videos'];
        $images = $groupedData['images'];
        $deviceCode = $device->device_code;
        $userId = $device->user_id;
        $personaId = $persona->id;
        $persona_type = $persona->persona_type ?? 0;
        $newsMixcutDuration = self::getNewsMixcutDuration($personaId, $userId);
        $synthesisConfig = AiPersonaSynthesisConfig::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->findOrEmpty();

        try {
            $coze['keywords'] = self::buildCozeKeywords($deviceCode, $userId, $personaId, $persona_type);
            $publishTimeSlots = array_values($publishTimeSlots);
            if (empty($publishTimeSlots)) {
                throw new \Exception('没有可用的朋友圈发布时间段');
            }
        } catch (\Exception $e) {
            $msg = '设备号' . $deviceCode . '视频合成任务失败：' . $e->getMessage();
            Log::channel('wechatVideoSynthesis')->write($msg);
            throw new \Exception($msg);
        }

        $key = -1;
        $shanjianType = 0;
        Db::startTrans();
        try {
            $auto_type = 1;
            $voice_id = '';
            $createdSettings = [];
            foreach ($videoTypes as $key => $shanjianType) {
                
                $execTime = $publishTimeSlots[$key] ?? $publishTimeSlots[count($publishTimeSlots) - 1];
                $copywritingResult2 = [];
                $copywritingResult4 = [];

                $typeName = self::getShanjianTypeName($shanjianType);
                $uniqueId = generate_unique_task_id();
                $shanjianVideoSettingData = [
                    'auto_type' => 1,
                    'wechat_type' => 1,
                    'device_code' => $deviceCode,
                    'user_id' => $userId,
                    'task_id' => $uniqueId,
                    'status' => 1, // 1待处理
                    'video_count' => 1,
                    'shanjian_type' => $shanjianType, // 设置类型
                    'create_time' => time(),
                    'update_time' => time()
                ];
                $extradata = [
                    'setting_index' => 1,
                    'create_type' => 'batch',
                    'exec_time' => json_encode([$execTime], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                try {
                    $anchorId = '';
                    if ($shanjianType == 1) {
                        $avatarInfoList = AiPersonaDigitalAvatar::availableQuery()
                            ->field('ad.third_avatar_id,ad.third_voice_id,ad.cover_url')
                            ->where('ad.user_id', $userId)
                            ->where('ad.persona_id', $persona->id)
                            ->select()
                            ->toArray();
                        if (empty($avatarInfoList)) {
                            $shanjianType = 3;
                            $shanjianVideoSettingData['shanjian_type'] = 3;
                        } else {
                            $avatar_total = count($avatarInfoList) - 1;
                            $avatarInfo = random_int(0, $avatar_total);
                            $voice_id =  $avatarInfoList[$avatarInfo]['third_voice_id'] ?? '';
                            $anchorId =  $avatarInfoList[$avatarInfo]['third_avatar_id'] ?? '';
                            $pic = $avatarInfoList[$avatarInfo]['cover_url'] ?? '';
                        }
                    } else {
                        $anchorId = '';
                        $voice_id = '';
                        // 新闻体(4)不需要音色；其余非数字人类型才选音色
                        if ((int)$shanjianType !== 4) {
                            $voice_id_list = AiPersonaDigitalVoice::availableQuery()
                                ->where('ad.user_id', $userId)
                                ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                                ->where('ad.persona_id', $persona->id)
                                ->column('ad.third_voice_id');
                            if (empty($voice_id_list)) {
                                $msg =  '绑定的人设' . AiPersona::formatLabel($persona) . '下人设没有绑定音色';
                                throw new \Exception($msg);
                            }
                            $voice_total = count($voice_id_list) - 1;
                            $avatarInfo = random_int(0, $voice_total);
                            $voice_id =  $voice_id_list[$avatarInfo] ?? '';
                        }
                    }


                    switch ($shanjianType) {
                        case 1:
                            $coze['sn'] = 5;
                            $coze['number'] = 1;
                            $coze['length'] = 100;
                            $scene = 'virtualman';
                            break;
                        case 2:
                            $coze['sn'] = 0;
                            $coze['number'] = 5;
                            $coze['length'] = 80;
                            $scene = 'realMan';
                            break;
                        case 3:
                            if ($key == 0) {
                                $coze['sn'] = 3;
                            } else {
                                $coze['sn'] = 4;
                            }
                            $coze['number'] = 1;
                            $coze['length'] = 80;
                            $scene = 'oralMixCutting';
                            $voice_id_list = AiPersonaDigitalVoice::availableQuery()
                                ->where('ad.user_id', $userId)
                                ->where('ad.persona_id', $persona->id)
                                ->whereIn('ad.provider', AiPersonaDigitalVoice::synthesisProviders())
                                ->column('ad.third_voice_id');
                            if (empty($voice_id_list)) {
                                $msg =  '绑定的人设' . AiPersona::formatLabel($persona) . '下人设没有绑定音色';
                                throw new \Exception($msg);
                            }
                            $voice_total = count($voice_id_list) - 1;
                            $voice = random_int(0, $voice_total);
                            $voice_id =  $voice_id_list[$voice];
                            break;
                        case 4:
                            $coze['sn'] = 2;
                            $coze['number'] = 1;
                            $coze['length'] = 80;
                            $scene = 'newsMixCutting';
                            $voice_id = '';
                            break;
                        default:
                            $msg = '视频类型不存在';
                            throw new \Exception($msg);
                            break;
                    }

                    $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $userId, 6);
                    switch ($shanjianType) {
                        case 1:
                        case 2:
                            $copywritingResult2['0']['content'] = $copywritingResult['content']['0'] ?? '';
                            break;
                        case 3:
                            break;
                        case 4:
                            $copywritingResult4['0']['title'] = $copywritingResult['content']['0'] ?? '';
                            $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult4, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $extradata['videoDuration'] = $newsMixcutDuration;
                            break;

                        default:
                            $msg = '视频类型不存在';
                            throw new \Exception($msg);
                    }
                } catch (\Exception $e) {
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，文案错误: ' . $e->getMessage();
                    Log::channel('wechatVideoSynthesis')->write($msg);
                    $failed = CopywritingImitationLogic::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                        'wechat_type' => 1,
                        'exec_time' => $execTime,
                    ]);
                    if ($failed) {
                        $createdSettings[] = $failed;
                    }
                    continue;
                }

                $selectedMaterials = self::selectAndValidateMaterials($videos, $images, $shanjianType, $deviceCode);

                if (empty($selectedMaterials)) {
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，素材为空';
                    Log::channel('wechatVideoSynthesis')->write($msg);
                    $failed = CopywritingImitationLogic::createFailedSynthesisTask($device, $persona, $shanjianType, $msg, [
                        'wechat_type' => 1,
                        'exec_time' => $execTime,
                    ]);
                    if ($failed) {
                        $createdSettings[] = $failed;
                    }
                    continue;
                }


                try {
                    $taskMsg = $copywritingResult['content']['0'] ?? '';
                    $titlecoze['sn'] = 8;
                    $titlecoze['number'] = 1;
                    $titlecoze['length'] = 10;
                    $titlecoze['keywords'] = $taskMsg;
                    $titleResult = AutoDeviceSettingLogic::copywriting($titlecoze, $userId, 6);
                    $taskTitle = $titleResult['content']['0'] ?? '';

                    $material_use_log = [];
                    foreach ($selectedMaterials as &$item) {
                        if ($shanjianType != 1) {
                            $pic = $item['thumbnail_url'];
                        }
                        $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                        if (Cache::store('redis')->has($rediskey)) {
                            Cache::store('redis')->delete($rediskey);
                        }
                        Cache::store('material_redis')->inc($rediskey);
                        $material_use_log[] = [
                            'material_id' => $item['id'],
                            'user_id' => $userId,
                            'persona_id' => $persona->id,
                            'use_scene' => 1,
                            'use_status' => 0,
                            'create_time' => time(),
                            'update_time' => time(),
                        ];
                        $extradata['device_code'] = $deviceCode;
                        unset($item['id']);
                        unset($item['thumbnail_url']);
                    }
                    $extradata['volume'] = AiPersonaSynthesisConfig::normalizeMusicVolume(
                        $synthesisConfig->isEmpty() ? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT : ($synthesisConfig->music_volume ?? AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT)
                    );
                    $extradata['speed_ratio'] = AiPersonaSynthesisConfig::normalizeSpeechRate(
                        $synthesisConfig->isEmpty() ? AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT : ($synthesisConfig->speech_rate ?? AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT)
                    );
                    $shanjianVideoSettingData['name'] = '朋友圈-' . mb_substr($taskTitle, 0, 6) . '-' . date('YmdHis') . '-' . $typeName;
                    if ($shanjianType == 4) {
                        $taskTitle = $copywritingResult4['0']['title'];
                    }
                    if ($shanjianType == 1) {
                        $copywritingResult2['0']['title'] = $taskTitle;
                        $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    $clip_template_id = ShanjianClipTemplate::where('scene', $scene)->where('auto_type', $auto_type)->column('id');
                    if (empty($clip_template_id)) {
                        throw new \Exception('未找到剪辑模板');
                    }
                    $clip_template_total = count($clip_template_id) - 1;
                    $clip = random_int(0, $clip_template_total);
                    $clip_id = $clip_template_id[$clip];
                    $music_url = $synthesisConfig->isEmpty()
                        ? CopywritingImitationLogic::resolveSystemMusicUrl()
                        : CopywritingImitationLogic::resolveMusicUrlByConfig($synthesisConfig, (int)$persona->id);
                    $shanjianVideoSettingData['material'] = json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $shanjianVideoSettingData['voice'] = $voice_id;

                    // 新闻体(4)不需要音色，不走 MiniMax TTS；仅数字人口播(1)/素材混剪(3)
                    $isMinimaxVoice = in_array((int)$shanjianType, [1, 3], true)
                        && $voice_id !== ''
                        && ShanjianVideoSettingLogic::isMinimaxVoiceId((string)$voice_id, (int)$userId);
                    $contentForTts = trim((string)$taskMsg);
                    if ($isMinimaxVoice && $contentForTts === '') {
                        $fallbackVoices = AiPersonaDigitalVoice::availableQuery()
                            ->where('ad.user_id', $userId)
                            ->where('ad.persona_id', $persona->id)
                            ->where('ad.provider', 'shanjian')
                            ->whereRaw('(hv.model_version IS NULL OR hv.model_version NOT IN (10, 11))')
                            ->column('ad.third_voice_id');
                        $fallbackVoices = array_values(array_filter(array_map('strval', $fallbackVoices)));
                        if (empty($fallbackVoices)) {
                            throw new \Exception('MiniMax音色需要文案，当前任务无文案且无人设可用非MiniMax音色');
                        }
                        $voice_id = $fallbackVoices[random_int(0, count($fallbackVoices) - 1)];
                        $shanjianVideoSettingData['voice'] = $voice_id;
                        $isMinimaxVoice = false;
                    }

                    $taskId = generate_unique_task_id();
                    $pendingTask = [
                        'shanjian_type' => $shanjianType,
                        'device_code' => $deviceCode,
                        'name' => $shanjianVideoSettingData['name'],
                        'pic' => $pic,
                        'task_id' => $taskId,
                        'persona_id' => $persona->id,
                        'status' => 0,
                        'audio_type' => 1,
                        'auto_type' => 1,
                        'user_id' => $userId,
                        'anchor_id' => $anchorId,
                        'voice_id' => $voice_id,
                        'wechat_type' => 1,
                        'card_name' => '',
                        'card_introduced' => '',
                        'title' => $taskTitle,
                        'msg' => $taskMsg,
                        'material' => json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'music_url' => $music_url,
                        'clip_id' => $clip_id,
                        'extra' => json_encode($extradata, JSON_UNESCAPED_UNICODE),
                    ];

                    if ($isMinimaxVoice) {
                        // 对齐手动 addType3：同步创建 status=-1 占位视频任务，TTS/ASR 完成后回填
                        $pendingTask['status'] = -1;
                        $requestJson = [
                            'user_id' => $userId,
                            'auto_pending_task' => $pendingTask,
                            'copywriting' => [['title' => $taskTitle, 'content' => $contentForTts]],
                            'voice' => $voice_id,
                        ];
                        $shanjianVideoSettingData['request_json'] = json_encode($requestJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $setting = new ShanjianVideoSetting();
                        $setting->save($shanjianVideoSettingData);
                        $settingId = $setting->id;
                        $minimaxTask = ShanjianVideoSettingLogic::createAudioTask(
                            $settingId,
                            $voice_id,
                            [['content' => $contentForTts]],
                            (int)$userId
                        );
                        $minimaxTaskId = (int)($minimaxTask->id ?? 0);
                        $pendingTask['video_setting_id'] = $settingId;
                        $pendingTask['minimax_task_id'] = $minimaxTaskId;
                        $pendingTask['audio_url'] = '';
                        $pendingTask['create_time'] = time();
                        $pendingTask['update_time'] = time();
                        $shanjiantask = new ShanjianVideoTask();
                        $shanjiantask->save($pendingTask);
                        $task_id = $shanjiantask->id;
                        foreach ($material_use_log as &$log) {
                            $log['task_id'] = $task_id;
                        }
                        unset($log);
                        MaterialUseLog::insertAll($material_use_log);
                        $createdSettings[] = [
                            'setting_id' => $settingId,
                            'task_id' => $task_id,
                            'type' => $typeName,
                            'shanjian_type' => $shanjianType,
                            'minimax_pending' => 1,
                        ];
                        Log::channel('wechatVideoSynthesis')->write('朋友圈命中MiniMax音色，已建占位任务等待TTS' . json_encode([
                            'setting_id' => $settingId,
                            'task_id' => $task_id,
                            'minimax_task_id' => $minimaxTaskId,
                            'voice_id' => $voice_id,
                            'device_code' => $deviceCode,
                            'shanjian_type' => $shanjianType,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        continue;
                    }

                    $setting = new ShanjianVideoSetting();
                    $setting->save($shanjianVideoSettingData);
                    $settingId = $setting->id;
                    $pendingTask['video_setting_id'] = $settingId;
                    $pendingTask['create_time'] = time();
                    $pendingTask['update_time'] = time();
                    $shanjiantask = new ShanjianVideoTask();
                    $shanjiantask->save($pendingTask);
                    $task_id = $shanjiantask->id;
                    foreach ($material_use_log as &$log) {
                        $log['task_id'] = $task_id;
                    }
                    $createdSettings[] = [
                        'setting_id' => $settingId,
                        'type' => $typeName,
                        'shanjian_type' => $shanjianType
                    ];
                    MaterialUseLog::insertAll($material_use_log);
                } catch (\Exception $e) {
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，混剪任务创建失败: ' . $e->getMessage();
                    Log::channel('wechatVideoSynthesis')->write($msg);
                    $failed = CopywritingImitationLogic::createFailedSynthesisTask($device, $persona, $shanjianType, $e->getMessage(), [
                        'wechat_type' => 1,
                        'exec_time' => $execTime,
                        'title' => $taskTitle ?? '',
                        'msg' => $taskMsg ?? '',
                    ]);
                    if ($failed) {
                        $createdSettings[] = $failed;
                    }
                    continue;
                }
            }
            if (empty($createdSettings)) {
                throw new \Exception('未成功创建任何朋友圈视频合成任务');
            }
            $device->markSynthesisDone(SvDevice::SYNTHESIS_SCENE_WECHAT);
            $device->save();
            Db::commit();
            return [
                'device' => $device->toArray(),
                'persona' => $persona->toArray(),
                'settings' => $createdSettings,
                'count' => count($createdSettings)
            ];
        } catch (\Exception $e) {
            Db::rollback();
            $msg = '设备号: ' . $deviceCode . '视频合成任务失败：' . $e->getMessage();
            Log::channel('wechatVideoSynthesis')->write($msg);
            throw $e;
        }
    }

    private static function selectAndValidateMaterials(array $videos, array $images, int $shanjianType, string $deviceCode): array
    {
        try {
            $videoCount = 0;
            $imageCount = 0;

            switch ($shanjianType) {
                case 1:
                    $videoCount = rand(2, 3);
                    $imageCount = rand(2, 3);
                    break;
                case 2:
                    $videoCount = rand(1, 2);
                    $imageCount = rand(2, 3);
                    break;
                case 3:
                    $videoCount = 8;
                    $imageCount = rand(3, 4);
                    break;
                case 4:
                    $videoCount = 5;
                    $imageCount = rand(2, 3);
                    break;
                default:
                    $videoCount = 1;
                    $imageCount = 2;
            }

            // 记录原始需求的视频和图片数量
            $originalVideoCount = $videoCount;
            $originalImageCount = $imageCount;
            // $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，原始需求图片数量: ' . $originalImageCount;
            // Log::channel('wechatVideoSynthesis')->write($msg);
            // foreach ($videos as $key => $video) {
            //     $rediskey = 'material_' . $video['id'] . '_device_' . $deviceCode;
            //     $device_bind_num = Cache::store('material_redis')->get($rediskey);
            //     // Log::channel('wechatVideoSynthesis')->write('设备号: ' . $deviceCode . '，视频' . $video['id'] . '绑定次数: ' . $device_bind_num);
            //     if ($device_bind_num > 2) {
            //         unset($videos[$key]);
            //         continue;
            //     }
            // }
            // foreach ($images as $key => $image) {
            //     $rediskey = 'material_' . $image['id'] . '_device_' . $deviceCode;
            //     $device_bind_num = Cache::store('material_redis')->get($rediskey);
            //     // Log::channel('wechatVideoSynthesis')->write('设备号: ' . $deviceCode . '，图片' . $image['id'] . '绑定次数: ' . $device_bind_num);
            //     if ($device_bind_num > 2) {
            //         unset($images[$key]);
            //         continue;
            //     }
            // }

            $hasVideos = !empty($videos);
            $hasImages = !empty($images);

            if (!$hasVideos && !$hasImages) {
                $msg = '设备号: ' . $deviceCode . '没有可用素材';
                Log::channel('wechatVideoSynthesis')->write($msg);
                return [];
            }

            if (!$hasVideos && $hasImages) {
                $imageCount = $videoCount + $imageCount;
                $msg = '设备号: ' . $deviceCode . '，视频数量为0，补充图片数量: ' . $videoCount;
                $videoCount = 0;
                Log::channel('wechatVideoSynthesis')->write($msg);
            } elseif (!$hasImages && $hasVideos) {
                $videoCount = $videoCount + $imageCount;
                $msg = '设备号: ' . $deviceCode . '，图片数量为0，补充视频数量: ' . $imageCount;
                $imageCount = 0;
                Log::channel('wechatVideoSynthesis')->write($msg);
            }
            // 两种素材都有，但数量不足时，用另一种素材补充
            elseif ($hasVideos && $hasImages) {
                // 视频不足：用图片补充缺失的视频数量
                if (count($videos) < $originalVideoCount) {
                    $videoCount = count($videos);
                    $cjvideoCount = $originalVideoCount - $videoCount;
                    $imageCount = $cjvideoCount + $imageCount;
                    $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，视频数量: ' . count($videos) . '，补充图片数量: ' . $cjvideoCount;
                    Log::channel('wechatVideoSynthesis')->write($msg);
                }
                // 图片不足：用视频补充缺失的图片数量
                if (count($images) < $originalImageCount) {
                    $imageCount = count($images);
                    $cjimageCount = $originalImageCount - $imageCount;
                    $videoCount = $cjimageCount + $videoCount;
                    $msg = '设备号: ' . $deviceCode . '，原始需求图片数量: ' . $originalImageCount . '，图片数量: ' . count($images) . '，补充视频数量: ' . $cjimageCount;
                    Log::channel('wechatVideoSynthesis')->write($msg);
                }
            }

            // 打乱素材顺序实现随机抽取
            shuffle($videos);
            shuffle($images);
            $selectedVideos = [];
            if ($videoCount > 0) {
                $selectedVideos = array_slice($videos, 0, $videoCount);
                // $msg = '设备号: ' . $deviceCode . '，原始需求视频数量: ' . $originalVideoCount . '，视频数量: ' . $videoCount;
                // Log::channel('wechatVideoSynthesis')->write($msg);
            }
            $selectedImages = [];
            if ($imageCount > 0) {
                $selectedImages = array_slice($images, 0, $imageCount);
                // $msg = '设备号: ' . $deviceCode . '，原始需求图片数量: ' . $originalImageCount . '，图片数量: ' . $imageCount;
                // Log::channel('wechatVideoSynthesis')->write($msg);
            }
            $mergedMaterials = [];
            if ($videoCount > 0 || $imageCount > 0) {
                $mergedMaterials = array_merge($selectedVideos, $selectedImages);
            }

            if (empty($mergedMaterials)) {
                return [];
            }
            foreach ($mergedMaterials as $key => &$value) {
                if ($value['material_type'] == 1) {
                    $value['type'] = 'video';
                } else {
                    $value['type'] = 'image';
                    $value['duration'] = 2;
                }
                $value['fileUrl'] = $value['file_url'];
                unset($value['user_id']);
                unset($value['persona_id']);
                unset($value['material_name']);
                unset($value['material_type']);
                unset($value['height']);
                unset($value['width']);
                unset($value['publish_mode']);
                unset($value['create_time']);
                unset($value['update_time']);
                unset($value['file_url']);
                unset($value['use_status']);
            }
            unset($value);

            return \app\api\logic\shanjian\ShanjianVideoSettingLogic::trimMaterialsByDuration(array_values($mergedMaterials));
        } catch (\Exception $e) {
            $msg = '设备号: ' . $deviceCode . '，素材选择异常: ' . $e->getMessage();
            Log::channel('wechatVideoSynthesis')->write($msg);
            return [];
        }
    }

    public static function buildCozeKeywords(string $deviceCode, int $userId, int $personaId, int $personaType): string
    {
        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
        if ($persona->isEmpty()) {
            $msg = '设备号' . $deviceCode . '绑定的人设' . AiPersona::formatLabel(null, $personaId) . '不存在';
            throw new \Exception($msg);
        }

        $personaLabel = AiPersona::formatLabel($persona);
        switch ($personaType) {
            case 1:
                $personaInfo = AiPersonaIndividual::where('user_id', $userId)->where('persona_id', $personaId)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    $msg = '设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下个人IP人设不存在';
                    throw new \Exception($msg);
                }
                break;
            case 2:
                $personaInfo = AiPersonaEnterprise::where('user_id', $userId)->where('persona_id', $personaId)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    $msg = '设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下企业服务人设不存在';
                    throw new \Exception($msg);
                }
                break;
            case 3:
                $personaInfo = AiPersonaLocal::where('user_id', $userId)->where('persona_id', $personaId)->findOrEmpty();
                if ($personaInfo->isEmpty()) {
                    $msg = '设备号' . $deviceCode . '绑定的人设' . $personaLabel . '下本地门店人设不存在';
                    throw new \Exception($msg);
                }
                break;
            default:
                $msg = '设备号' . $deviceCode . '绑定的人设' . $personaLabel . '类型不存在';
                throw new \Exception($msg);
        }

        return self::buildIpCopywritingKeywords($persona, $personaInfo, $personaType);
    }

    private static function buildIpCopywritingKeywords($persona, $personaInfo, int $personaType): string
    {
        $personaName = $persona->persona_name ?? '';
        $personaDesc = $persona->persona_desc ?? '';
        $mainBusiness = $persona->main_business ?? '';
        $targetPainPoints = $persona->target_pain_points ?? '';
        $conversionHook = $persona->conversion_hook ?? '';
        $locationCity = $persona->store_position ?? '';

        switch ($personaType) {
            case 1:
                $ipName = $personaName ?: ($personaInfo->nickname ?? '');
                $accountType = '个人IP';
                $whatYouDo = $personaInfo->identity ?? '';
                $mainShare = $personaInfo->core_value ?? '';
                $targetViewers = $personaInfo->target_audience ?? '';
                $tone = $personaInfo->personality_tags ?? '';
                $desiredAction = $personaInfo->monetize_paths ?? '';
                $whatYouSell = $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_audience ?? '');
                $advantage = $conversionHook ?: ($personaInfo->highlight_story ?? '');
                $productContent = $mainBusiness;
                break;
            case 2:
                $ipName = $personaName ?: ($personaInfo->brand_name ?? '');
                $accountType = '企业服务';
                $whatYouDo = $personaInfo->main_product ?? '';
                $mainShare = $personaInfo->main_product ?? '';
                $targetViewers = $personaInfo->target_customer ?? '';
                $tone = $personaInfo->brand_tone ?? '';
                $desiredAction = $personaInfo->account_goal ?? '';
                $whatYouSell = ($personaInfo->main_product ?? '') ?: $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_customer ?? '');
                $advantage = $conversionHook ?: ($personaInfo->industry_case ?? '');
                $productContent = $mainBusiness ?: ($personaInfo->main_product ?? '');
                break;
            case 3:
                $ipName = $personaName ?: ($personaInfo->store_name ?? '');
                $accountType = '本地商家';
                $whatYouDo = $personaInfo->store_name ?? '';
                $mainShare = $personaInfo->content_preference ?? '';
                $targetViewers = $personaInfo->target_customer ?? '';
                $tone = $personaInfo->store_atmosphere ?? '';
                $locationCity = $locationCity ?: ($personaInfo->store_name ?? '');
                $desiredAction = $conversionHook ?: ($personaInfo->content_preference ?? '');
                $whatYouSell = ($personaInfo->signature_feature ?? '') ?: $mainBusiness;
                $targetBuyers = $targetPainPoints ?: ($personaInfo->target_customer ?? '');
                $advantage = ($personaInfo->open_story ?? '') ?: $conversionHook;
                $productContent = $mainBusiness ?: ($personaInfo->signature_feature ?? '');
                break;
            default:
                $ipName = $personaName;
                $accountType = '';
                $whatYouDo = '';
                $mainShare = '';
                $targetViewers = '';
                $tone = '';
                $desiredAction = '';
                $whatYouSell = '';
                $targetBuyers = '';
                $advantage = '';
                $productContent = '';
        }

        return sprintf(
            "我的IP名称是%s。\n\nIP介绍如下：\n%s\n\n账号类型是%s。\n\n我的职业/业务是：\n%s\n\n我主要分享的内容是：\n%s\n\n我想给谁看的是：\n%s\n\n这个账号整体想呈现的感觉是：\n%s\n\n我所在的城市/地点是：\n%s\n\n我希望用户看完内容之后的行为是：\n%s\n\n我正在销售的产品/服务是：\n%s\n\n我想卖给的人群是：\n%s\n\n相比同行，我的优势是：\n%s\n\n以下是我的产品内容：\n%s",
            self::stringifyCopywritingField($ipName),
            self::stringifyCopywritingField($personaDesc),
            self::stringifyCopywritingField($accountType),
            self::stringifyCopywritingField($whatYouDo),
            self::stringifyCopywritingField($mainShare),
            self::stringifyCopywritingField($targetViewers),
            self::stringifyCopywritingField($tone),
            self::stringifyCopywritingField($locationCity),
            self::stringifyCopywritingField($desiredAction),
            self::stringifyCopywritingField($whatYouSell),
            self::stringifyCopywritingField($targetBuyers),
            self::stringifyCopywritingField($advantage),
            self::stringifyCopywritingField($productContent)
        );
    }

    private static function stringifyCopywritingField($value): string
    {
        if (is_array($value)) {
            $items = array_map([self::class, 'stringifyCopywritingField'], $value);
            $items = array_filter($items, static fn($item) => $item !== '');
            return implode('、', $items);
        }

        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    private static function getShanjianTypeName(int $type): string
    {
        $typeNames = [
            1 => '数字人口播',
            2 => '真人口播',
            3 => '素材混剪',
            4 => '新闻体',
        ];
        return $typeNames[$type] ?? '未知类型';
    }

    private static function getNewsMixcutDuration(int $personaId, int $userId): int
    {
        $duration = AiPersonaSynthesisConfig::where('persona_id', $personaId)
            ->where('user_id', $userId)
            ->value('news_mixcut_duration');

        return AiPersonaSynthesisConfig::normalizeNewsMixcutDuration($duration);
    }
}
