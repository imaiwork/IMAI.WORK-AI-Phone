<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\AiPersonaEnterprise;
use app\common\model\aiPersona\AiPersonaIndividual;
use app\common\model\aiPersona\AiPersonaLocal;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvAccountContact;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvSetting;
use app\common\model\user\User;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 设备任务逻辑
 * Class DeviceLogic    
 * @package app\api\logic\device
 */
class DeviceLogic extends ApiLogic
{
    public static function detail($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $find['accounts'] = SvAccount::alias('w')
                ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
                ->leftJoin('sv_setting s', 's.account = w.account')
                ->where('w.device_code', '=', $find['device_code'])
                // ->when($params['type'], function ($query) use ($params) {
                //     $query->where('w.type', $params['type']);
                // })
                ->group('w.type')
                ->order('w.id', 'desc')
                ->select()
                ->each(function ($item) {
                    if (empty($item['takeover_mode'])) {
                        $item['takeover_mode'] = 0;
                    }

                    if (empty($item['robot_id'])) {
                        $item['robot_id'] = 0;
                    }

                    $item['robot_name'] = \app\common\model\kb\KbRobot::where('id', $item['robot_id'])->where('user_id', self::$uid)->value('name', '');

                    if (!empty($item['extra'])) {
                        $extraArray = json_decode($item['extra'], true);
                    } else {
                        $extraArray = [];
                    }
                    foreach ($extraArray  as $key => $v) {
                        $item[$key] = $v;
                    }

                    return $item;
                })
                ->toArray();
            $find['device_name'] = is_null($find['device_name']) ? $find['device_model'] : $find['device_name'];

            $find['is_auto_setting'] = 0;
            if ($find['auto_type'] === 1) {
                $config = \app\common\model\auto\AutoDeviceConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty();
                list($setting, $task_status, $is_config) = self::getAutoConfigStatus($config);
                $find['is_auto_setting'] = $is_config;
            }
            $find['persona_info'] = AiPersona::where('id', $find->persona_id)->findOrEmpty();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function update($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            if (isset($params['persona_id'])) {
                $persona = AiPersona::where('id', $params['persona_id'])->findOrEmpty();
                if ($persona->isEmpty()) {
                    self::setError('IP人设不存在');
                    return false;
                }
                //$oldPersonaType = AiPersona::where('id', $find->persona_id)->value('persona_type');
                if ((int)$params['persona_id'] !== (int)$find->persona_id) {
                    $find->persona_id = (int)$params['persona_id'];
                    $find->is_first = 1;
                    $find->save();
                    self::deleteOldPersonaTask($find, '设备人设解绑，任务取消');
                    $materialIds = MaterialModel::where('user_id', $find->user_id)->where('persona_id', $find->persona_id)->column('id');
                    if ($materialIds) {
                        foreach ($materialIds as $materialId) {
                            $rediskey = 'material_' . $materialId . '_device_' . $find->device_code;
                            Cache::store('redis')->delete($rediskey);
                        }
                    }
                }
            } else {
                $find->save($params);
            }


            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }



    public static function remove($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }

            // 删除关联的账号
            SvAccount::where('device_code', $find->device_code)->where('user_id', self::$uid)->select()->each(function ($account) {
                // 删除AI设置
                SvSetting::where('account', $account->account)->select()->delete();
                // 删除好友
                SvAccountContact::where('account', $account->account)->select()->delete();

                $account->delete();
            });
            //删除设备rpa配置
            SvDeviceRpa::where('device_code', $find->device_code)->select()->delete();

            // 删除设备任务
            SvDeviceTask::where('device_code', $find->device_code)->select()->delete();
            // 删除设备接管任务
            // SvDeviceTakeOverTask::where('id', 'in', function ($query) use ($find) {
            //     $query->name('sv_device_take_over_task_account')->field('take_over_id')->where('device_code', $find->device_code);
            // })->select()->delete();
            // // 删除设备接管任务账号
            // SvDeviceTakeOverTaskAccount::where('device_code', $find->device_code)->select()->delete();

            // 删除设备激活任务
            // SvDeviceActive::where('id', 'in', function ($query) use ($find) {
            //     $query->name('sv_device_active_account')->field('active_id')->where('device_code', $find->device_code);
            // })->select()->delete();
            // // 删除设备激活任务账号
            // SvDeviceActiveAccount::where('device_code', $find->device_code)->select()->delete();


            \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备线索词配置
            \app\common\model\auto\AutoDeviceClueConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            \app\common\model\auto\AutoDeviceSetting::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备接管任务配置
            \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备截流获客任务配置
            \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备点赞评论任务配置
            \app\common\model\auto\AutoDeviceCircleLikeReplyConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();
            // 删除设备点赞评论任务账号
            \app\common\model\auto\AutoDeviceWechatCircleConfig::where('user_id', self::$uid)->where('device_code', $find->device_code)->select()->delete();

            \app\common\model\sv\SvDeviceTaskLog::where('device_code', $find->device_code)->select()->delete();

            // \app\common\model\sv\SvPublishSettingAccount::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();
            // \app\common\model\sv\SvPublishSettingDetail::where('user_id', self::$uid)->where('device_code', $find->device_code)->where('auto_type', 1)->select()->delete();

            $find->delete();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function removePersona($params)
    {
        try {
            // 检查设备是否存在
            $find = SvDevice::field('*')
                ->where('device_code', $params['device_code'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            $materialIds = MaterialModel::where('user_id', $find->user_id)->where('persona_id', $find->persona_id)->column('id');
            if ($materialIds) {
                foreach ($materialIds as $materialId) {
                    $rediskey = 'material_' . $materialId . '_device_' . $find->device_code;
                    Cache::store('redis')->delete($rediskey);
                }
            }
            // 移除绑定人设
            $find->persona_id = 0;
            $find->save();
            self::deleteOldPersonaTask($find, '人设解绑设备，任务取消');
            // 删除设备绑定的素材

            // 删除设备待执行的24小时任务
            //SvDeviceTask::where('device_code', $find->device_code)->where('auto_type', 1)->where('day', date('Y-m-d'))->select()->delete();

            self::$returnData = $find->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function bind($params): bool
    {
        try {
            $device = SvDevice::where([
                'device_code' => $params['device_code'],
            ])->findOrEmpty();
            if (!$device->isEmpty()) {
                throw new \Exception('设备已被其他用户绑定');
            }
            $personalDevice = SvDevice::where([
                'device_code' => $params['device_code'],
                'user_id'     => $params['user_id'],
            ])->findOrEmpty();
            if (!$personalDevice->isEmpty()) {
                self::$returnData = ['message' => '设备已绑定此用户'];
            } else {
                $insert = [
                    'device_code'  => $params['device_code'],
                    'user_id'      => $params['user_id'],
                    'device_name'  => $params['device_code'],
                    'device_model' => $params['device_model'],
                    'sdk_version'  => $params['sdk_version'],
                    'status'       => 0,
                    'auto_type'    => 1,
                    'is_first'     => 1,
                    'create_time'  => time(),
                ];
                SvDevice::create($insert);
                self::$returnData = ['message' => '绑定成功'];
            }

            $device_bind_num = SvDevice::where('user_id', $params['user_id'])->count();
            User::update(
                [
                    'device_bind_num'  => $device_bind_num,
                    'device_bind_time' => time(),
                    'last_bind_device_code' => $params['device_code']
                ],
                ['id' => $params['user_id']]
            );
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function videoSynthesis($deviceCode)
    {

        try {
            // 添加缓存锁，10分钟内不能重复执行
            $cacheKey = 'video_synthesis_' . $deviceCode;
            if (Cache::store('redis')->get($cacheKey)) {
                throw new \Exception('视频合成任务正在执行中，请10分钟后再试');
            }
            Cache::store('redis')->set($cacheKey, 1, 600); // 600秒 = 10分钟
            $device = SvDevice::where('device_code', $deviceCode)->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }
            if ($device->persona_id <= 0) {
                throw new \Exception('该设备未绑定人设');
            }
            $persona = AiPersona::where('id', $device->persona_id)
                ->where('publish_mode', 1)
                ->where('status', 1)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('该设备绑定的人设不存在或发布模式不符合要求');
            }
            $allMaterials = MaterialModel::where('persona_id', $device->persona_id)
                ->where('use_status', 1)
                ->where('publish_mode', $persona->publish_mode)
                ->select()->toArray();

            if (empty($allMaterials)) {
                throw new \Exception('该设备绑定的人设下没有可用的素材');
            }
            $groupedData = [
                'videos' => [], // 视频组 (material_type = 1)
                'images' => []  // 图片组 (material_type = 2)
            ];
            foreach ($allMaterials as $item) {
                $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                $device_bind_num = Cache::store('redis')->get($rediskey);
                if (empty($device_bind_num)) {
                    $device_bind_num = 0;
                }
                if ($device_bind_num > 2) {
                    continue;
                }

                if ($item['material_type'] == 1) {
                    $groupedData['videos'][] = $item;
                } elseif ($item['material_type'] == 2) {
                    $groupedData['images'][] = $item;
                }
            }

            switch ($persona->persona_type) {
                case 1:
                    $videoTypes = [1, 3];
                    return self::processIndividualIp($device, $persona, $groupedData, $videoTypes);
                case 2:
                    $videoTypes = [1, 4];
                    return self::processLocalLife($device, $persona, $groupedData, $videoTypes);
                case 3:
                    $videoTypes = [3, 4];
                    return self::processEnterpriseService($device, $persona, $groupedData, $videoTypes);
                default:
                    throw new \Exception('该设备绑定的人设类型不存在');
            }
        } catch (\Exception $e) {
            Log::channel('ipVideoSynthesis')->write($e->getMessage());
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function processIndividualIp($device, $persona, $groupedData, array $videoTypes = [1, 3])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '个人IP');
    }

    private static function processLocalLife($device, $persona, $groupedData, array $videoTypes = [1, 4])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '本地生活');
    }

    private static function processEnterpriseService($device, $persona, $groupedData, array $videoTypes = [3, 4])
    {
        return self::createVideoSynthesisTasks($device, $persona, $groupedData, $videoTypes, '企业服务');
    }

    private static function createVideoSynthesisTasks($device, $persona, $groupedData, array $videoTypes, string $prefix)
    {
        $videos = $groupedData['videos'];
        $images = $groupedData['images'];
        $deviceCode = $device->device_code;
        $userId = $device->user_id;
        $createdSettings = [];
        $copywritingData = [];
        $persona_type = $persona->persona_type ?? 0;
        try {
            switch ($persona_type) {
                case 1:
                    $personaInfo = AiPersonaIndividual::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        throw new \Exception('个人IP人设不存在');
                    }

                    $nickname = $personaInfo->nickname ?? '';
                    $identity = $personaInfo->identity ?? '';
                    if (is_array($identity)) {
                        $identity = implode(',', $identity);
                    }
                    $personality_tags = $personaInfo->personality_tags ?? '';
                    if (is_array($personality_tags)) {
                        $personality_tags = implode(',', $personality_tags);
                    }

                    $core_value = $personaInfo->core_value ?? '';
                    if (is_array($core_value)) {
                        $core_value = implode(',', $core_value);
                    }
                    $target_audience = $personaInfo->target_audience ?? '';
                    if (is_array($target_audience)) {
                        $target_audience = implode(',', $target_audience);
                    }
                    $monetize_paths = $personaInfo->monetize_paths ?? '';
                    if (is_array($monetize_paths)) {
                        $monetize_paths = implode(',', $monetize_paths);
                    }
                    $highlight_story = $personaInfo->highlight_story ?? '';
                    if (is_array($highlight_story)) {
                        $highlight_story = implode(',', $highlight_story);
                    }
                    $coze['keywords'] = "我的昵称/网名是{$nickname}，真实身份/职业是{$identity}，希望以{$personality_tags}的性格标签语气生成内容。
                                        我能提供的核心价值如下：{$core_value}想吸引的粉丝是{$target_audience}，主要变现路径：{$monetize_paths}。
                                        个人高光/逆袭故事：{$highlight_story}。";

                    break;

                case 2:
                    $personaInfo = AiPersonaEnterprise::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        throw new \Exception('企业服务人设不存在');
                    }
                    $brand_name = $personaInfo->brand_name ?? '';
                    $spokesperson = $personaInfo->spokesperson ?? '';
                    if (is_array($spokesperson)) {
                        $spokesperson = implode(',', $spokesperson);
                    }
                    $brand_tone = $personaInfo->brand_tone ?? '';
                    if (is_array($brand_tone)) {
                        $brand_tone = implode(',', $brand_tone);
                    }
                    $main_product = $personaInfo->main_product ?? '';
                    if (is_array($main_product)) {
                        $main_product = implode(',', $main_product);
                    }
                    $target_customer = $personaInfo->target_customer ?? '';
                    if (is_array($target_customer)) {
                        $target_customer = implode(',', $target_customer);
                    }
                    $account_goal = $personaInfo->account_goal ?? '';
                    if (is_array($account_goal)) {
                        $account_goal = implode(',', $account_goal);
                    }
                    $industry_case = $personaInfo->industry_case ?? '';
                    if (is_array($industry_case)) {
                        $industry_case = implode(',', $industry_case);
                    }
                    $coze['keywords'] = "我的企业/品牌名称是{$brand_name}，由{$spokesperson}代表公司出镜，希望以{$brand_tone}的品牌调性生成内容。
                                        主打的产品/解决方案如下：{$main_product}目标客户画像是{$target_customer}，账号核心目的：{$account_goal}。
                                        行业背书/标杆案例：{$industry_case}。";
                    break;
                case 3:
                    $personaInfo = AiPersonaLocal::where('user_id', $userId)->where('persona_id', $persona->id)->findOrEmpty();
                    if ($personaInfo->isEmpty()) {
                        throw new \Exception('本地门店人设不存在');
                    }
                    $store_name = $personaInfo->store_name ?? '';
                    $spokesperson = $personaInfo->spokesperson ?? '';
                    if (is_array($spokesperson)) {
                        $spokesperson = implode(',', $spokesperson);
                    }
                    $store_atmosphere = $personaInfo->store_atmosphere ?? '';
                    if (is_array($store_atmosphere)) {
                        $store_atmosphere = implode(',', $store_atmosphere);
                    }
                    $signature_feature = $personaInfo->signature_feature ?? '';
                    if (is_array($signature_feature)) {
                        $signature_feature = implode(',', $signature_feature);
                    }
                    $target_customer = $personaInfo->target_customer ?? '';
                    if (is_array($target_customer)) {
                        $target_customer = implode(',', $target_customer);
                    }
                    $content_preference = $personaInfo->content_preference ?? '';
                    if (is_array($content_preference)) {
                        $content_preference = implode(',', $content_preference);
                    }
                    $open_story = $personaInfo->open_story ?? '';
                    if (is_array($open_story)) {
                        $open_story = implode(',', $open_story);
                    }
                    $coze['keywords'] = "我的门店及所在商圈是{$store_name}，由{$spokesperson}出镜揽客，希望以{$store_atmosphere}的门店氛围感生成内容。
                                        我们的招牌特色如下：{$signature_feature}主要想吸引进店的客户是{$target_customer}，偏好的引流内容：{$content_preference}。
                                        开店初衷/门店优势：{$open_story}。";
                    break;
                default:
                    throw new \Exception('该设备绑定的人设类型不存在');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }


        Db::startTrans();
        try {
            $auto_type = 1;
            $voice_id = '';
            foreach ($videoTypes as $key => $shanjianType) {
                $typeName = self::getShanjianTypeName($shanjianType);
                $uniqueId = generate_unique_task_id();
                $shanjianVideoSettingData = [
                    'auto_type' => 1,
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
                    'create_type' => 'batch'
                ];
                try {
                    $anchorId = '';
                    if ($shanjianType == 1) {
                        $avatarInfoList = AiPersonaDigitalAvatar::where('user_id', $userId)
                            ->where('persona_id', $persona->id)
                            ->column('third_avatar_id,third_voice_id,cover_url');
                        if (empty($avatarInfoList)) {
                            $shanjianType = 3;
                        } else {
                            $avatar_total = count($avatarInfoList) - 1;
                            $avatarInfo = random_int(0, $avatar_total);
                            $voice_id =  $avatarInfoList[$avatarInfo]['third_voice_id'] ?? '';
                            $anchorId =  $avatarInfoList[$avatarInfo]['third_avatar_id'] ?? '';
                            $pic = $avatarInfoList[$avatarInfo]['cover_url'] ?? '';
                        }
                    } else {
                        $anchorId = '';
                        $voice_id_list =  AiPersonaDigitalVoice::where('user_id', $userId)->where('provider', 'shanjian')->where('persona_id', $persona->id)->column('third_voice_id');
                        $voice_total = count($voice_id_list) - 1;
                        $avatarInfo = random_int(0, $voice_total);
                        $voice_id =  $voice_id_list[$avatarInfo] ?? '';
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
                            $voice_id_list = AiPersonaDigitalVoice::where('user_id', $userId)
                                ->where('persona_id', $persona->id)
                                ->where('provider', 'shanjian')->column('third_voice_id');
                            if (empty($voice_id_list)) {
                                throw new \Exception('该设备绑定的人设没有音色');
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
                            break;
                        default:
                            $typeName = '未知类型';
                            break;
                    }

                    $copywritingResult = AutoDeviceSettingLogic::copywriting($coze, $userId, 4);
                    switch ($shanjianType) {
                        case 1:
                        case 2:
                            $copywritingResult2 = $copywritingResult['content']['0'] ?? '';
                            $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            break;
                        case 3:
                            break;
                        case 4:
                            $copywritingResult4['0']['title'] = $copywritingResult['content']['0'] ?? '';
                            $shanjianVideoSettingData['copywriting'] = json_encode($copywritingResult4, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            $extradata['videoDuration'] = 15;
                            break;

                        default:
                            $typeName = '未知类型';
                            break;
                    }
                } catch (\Exception $e) {
                    // 报错，跳过这个循环
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，文案错误: ' . $e->getMessage();
                    Log::channel('ipVideoSynthesis')->write($msg);
                    continue;
                }

                $selectedMaterials = self::selectAndValidateMaterials($videos, $images, $shanjianType, $deviceCode);

                if (empty($selectedMaterials)) {
                    $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，素材为空';
                    Log::channel('ipVideoSynthesis')->write($msg);
                    continue;
                }


                $taskMsg = $copywritingResult['content']['0'] ?? '';
                //根据文案内容获取标题
                $titlecoze['sn'] = 8;
                $titlecoze['number'] = 1;
                $titlecoze['length'] = 10;
                $titlecoze['keywords'] = $taskMsg;
                $titleResult = AutoDeviceSettingLogic::copywriting($titlecoze, $userId, 4);
                $taskTitle = $titleResult['content']['0'] ?? '';

                // $copywritingContent = '';
                // if (!empty($copywritingData) && isset($copywritingData['content'])) {
                //     $copywritingContent = $copywritingData['content'][0] ?? '';
                // }
                $material_use_log = [];
                foreach ($selectedMaterials as &$item) {
                    if ($shanjianType != 1) {
                        $pic = $item['thumbnail_url'];
                    }
                    $rediskey = 'material_' . $item['id'] . '_device_' . $deviceCode;
                    if (Cache::store('redis')->get($rediskey) < 3) {
                        Cache::store('redis')->inc($rediskey);
                    }
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
                if (in_array($shanjianType, [3, 4])) {
                    $extradata['volume'] = 0.6;
                }
                $shanjianVideoSettingData['name'] = '社媒平台-' . mb_substr($taskTitle, 0, 6) . '-' . date('YmdHis') . '-' . $typeName;
                if ($shanjianType == 4) {
                    $taskTitle = $copywritingResult4['0']['title'];
                }
                $clip_template_id = ShanjianClipTemplate::where('scene', $scene)->where('auto_type', $auto_type)->column('id');
                $clip_template_total = count($clip_template_id) - 1;
                $clip = random_int(0, $clip_template_total);
                $clip_id =  $clip_template_id[$clip];
                $number = random_int(1, 20);
                $music_url = config('app.app_host') . '/static/audio/music/' . $number . '.mp3';
                $shanjianVideoSettingData['material'] = json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                // $shanjianVideoSettingData['copywriting'] = json_encode($copywritingContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $shanjianVideoSettingData['voice'] = $voice_id;
                $setting = new ShanjianVideoSetting();
                $setting->save($shanjianVideoSettingData);
                $settingId = $setting->id;
                $taskId = generate_unique_task_id();
                $taskData = [
                    'shanjian_type' => $shanjianType, // 设置类型
                    'device_code' => $deviceCode,
                    'name' => $shanjianVideoSettingData['name'],
                    'pic' =>  $pic,
                    'task_id' => $taskId,
                    'persona_id' => $persona->id,
                    'status' => 0, // 待处理
                    'audio_type' => 1, // 文案驱动
                    'auto_type' => 1,
                    'user_id' => $userId,
                    'video_setting_id' => $settingId,
                    'anchor_id' => $anchorId,
                    'voice_id' => $voice_id,
                    'card_name' => '',
                    'card_introduced' =>  '',
                    'title' =>  $taskTitle,
                    'msg' =>  $taskMsg,
                    'material' => json_encode($selectedMaterials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'music_url' => $music_url,
                    'clip_id' => $clip_id,
                    'extra' => json_encode($extradata, JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time()
                ];
                $shanjiantask = new ShanjianVideoTask();
                $shanjiantask->save($taskData);
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
            }

            Db::commit();
            return [
                'device' => $device->toArray(),
                'persona' => $persona->toArray(),
                'settings' => $createdSettings,
                'count' => count($createdSettings)
            ];
        } catch (\Exception $e) {
            Db::rollback();
            $msg = '设备号: ' . $deviceCode . '第' . ($key + 1) . '类型视频' . $shanjianType . '，混剪任务创建失败: ' . $e->getMessage();
            Log::channel('ipVideoSynthesis')->write($msg);
        }
    }

    private static function selectAndValidateMaterials(array $videos, array $images, int $shanjianType, string $deviceCode): array
    {
        $videoCount = 0;
        $imageCount = 0;

        switch ($shanjianType) {
            case 1:
                $videoCount = rand(1, 2);
                $imageCount = rand(2, 3);
                break;
            case 2:
                $videoCount = rand(1, 2);
                $imageCount = rand(2, 3);
                break;
            case 3:
                $videoCount = rand(2, 3);
                $imageCount = rand(3, 4);
                break;
            case 4:
                $videoCount = 1;
                $imageCount = rand(2, 3);
                break;
            default:
                $videoCount = 1;
                $imageCount = 2;
        }
        $newVideos = [];
        foreach ($videos as $key => $video) {
            $rediskey = 'material_' . $video['id'] . '_device_' . $deviceCode;
            $device_bind_num = Cache::store('redis')->get($rediskey);
            // Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '，视频' . $video['id'] . '绑定次数: ' . $device_bind_num);
            if ($device_bind_num > 2) {
                continue;
            }
            $newVideos[] = $video;
        }
        $videos = $newVideos;
        $newImages = [];
        foreach ($images as $key => $image) {
            $rediskey = 'material_' . $image['id'] . '_device_' . $deviceCode;
            $device_bind_num = Cache::store('redis')->get($rediskey);
            // Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '，图片' . $image['id'] . '绑定次数: ' . $device_bind_num);
            if ($device_bind_num > 2) {
                continue;
            }
            $newImages[] = $image;
        }
        $images = $newImages;
        // Log::channel('ipVideoSynthesis')->write('设备号: ' . $deviceCode . '，图片ids' . json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));



        shuffle($videos);
        $selectedVideos = array_slice($videos, 0, $videoCount);

        shuffle($images);
        $selectedImages = array_slice($images, 0, $imageCount);

        $mergedMaterials = array_merge($selectedVideos, $selectedImages);
        $materialDuration = 0;
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
            if (isset($value['duration'])) {
                $nowDuration = intval($value['duration']);
            } else {
                $nowDuration = 2;
            }
            $materialDuration += $nowDuration;
            if ($materialDuration > 290 || $nowDuration > 59) {
                unset($mergedMaterials[$key]);
                $materialDuration -= $nowDuration;
            }
        }
        return array_values($mergedMaterials);
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
}
