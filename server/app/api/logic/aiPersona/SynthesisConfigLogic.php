<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\service\aiPersona\PersonaRunModeSwitchService;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use think\facade\Db;
use think\facade\Log;

class SynthesisConfigLogic extends ApiLogic
{
    public static function add(array $params): bool
    {
        try {
            self::setError('暂无新增');
            return false;
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            $existConfig = AiPersonaSynthesisConfig::where('user_id', self::$uid)
                ->where('persona_id', $params['persona_id'])
                ->findOrEmpty();
            if (!$existConfig->isEmpty()) {
                self::setError('该人设的配置已存在，请使用更新接口');
                return false;
            }

            $params['user_id'] = self::$uid;
            $params['visual_material_source'] = $params['visual_material_source'] ?? 1;
            $params['copywriting_source'] = $params['copywriting_source'] ?? 2;
            $params['library_use_mode'] = $params['library_use_mode'] ?? AiPersonaSynthesisConfig::LIBRARY_USE_MODE_RANDOM;
            $params['library_reuse_mode'] = $params['library_reuse_mode'] ?? AiPersonaSynthesisConfig::LIBRARY_REUSE_MODE_ONCE;
            $params['hotspot_pick_mode'] = AiPersonaSynthesisConfig::requireValidHotspotPickMode($params['hotspot_pick_mode'] ?? AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_LATEST);
            $params['work_mode'] = self::normalizeWorkMode($params['work_mode'] ?? AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS);
            $params['product_use_mode'] = self::normalizeProductUseMode($params['product_use_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM);
            $params['product_reuse_mode'] = self::normalizeProductReuseMode($params['product_reuse_mode'] ?? AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE);
            $params['template_config'] = SynthesisTemplateConfigService::validateForSave(
                $params['template_config'] ?? [],
                $params['generation_types'] ?? []
            );
            $params['video_cover_type'] = $params['video_cover_type'] ?? 1;
            $params['news_mixcut_duration'] = AiPersonaSynthesisConfig::normalizeNewsMixcutDuration(
                $params['news_mixcut_duration'] ?? AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_DEFAULT
            );
            $params['create_time'] = time();
            $params['update_time'] = time();

            Db::startTrans();
            try {
                $result = AiPersonaSynthesisConfig::create($params);
                self::syncPersonaPublishMode((int)$params['persona_id'], (int)$params['work_mode']);
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }
            self::$returnData = $result->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function update(array $params): bool
    {
        try {
            $config = AiPersonaSynthesisConfig::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($config->isEmpty()) {
                self::setError('配置不存在');
                return false;
            }

            $personaId = $config['persona_id'];

            $hasAvatar = true;
            $hasVoice = true;
            $avatar = AiPersonaDigitalAvatar::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', self::$uid)
                ->findOrEmpty();
            if ($avatar->isEmpty()) {
                $hasAvatar = false;
            }

            $voice = AiPersonaDigitalVoice::availableQuery()
                ->where('ad.persona_id', $personaId)
                ->where('ad.user_id', self::$uid)
                ->findOrEmpty();
            if ($voice->isEmpty()) {
                $hasVoice = false;
            }

            if (!$hasAvatar || !$hasVoice) {
                $noticeContent = !$hasAvatar && !$hasVoice ? '人设中未配置形象和音色' : (!$hasAvatar ? '人设中未配置形象' : '人设中未配置音色');
                $noticeStatus = $hasAvatar ? '转为新闻体混剪模式' : '转为素材混剪模式';
                ApiLogic::sendNotice([
                    'userId' => self::$uid,
                    'content' => $noticeContent,
                    'status' => $noticeStatus
                ], 402);
            }

            $configId = $params['id'];
            unset($params['id']);
            // 运行模式存人设表，不进配置表；无手机模式不允许找爆款仿写（仿写素材靠绑定手机抓取）
            $runMode = null;
            if (array_key_exists('run_mode', $params)) {
                $runMode = AiPersona::requireValidRunMode($params['run_mode']);
                unset($params['run_mode']);
            }
            if (array_key_exists('news_mixcut_duration', $params)) {
                $params['news_mixcut_duration'] = AiPersonaSynthesisConfig::normalizeNewsMixcutDuration($params['news_mixcut_duration']);
            }
            if (array_key_exists('library_use_mode', $params)) {
                $params['library_use_mode'] = in_array((int)$params['library_use_mode'], [1, 2], true)
                    ? (int)$params['library_use_mode']
                    : AiPersonaSynthesisConfig::LIBRARY_USE_MODE_RANDOM;
            }
            if (array_key_exists('library_reuse_mode', $params)) {
                $params['library_reuse_mode'] = in_array((int)$params['library_reuse_mode'], [1, 2], true)
                    ? (int)$params['library_reuse_mode']
                    : AiPersonaSynthesisConfig::LIBRARY_REUSE_MODE_ONCE;
            }
            $nextCopywritingSource = array_key_exists('copywriting_source', $params)
                ? (int)$params['copywriting_source']
                : (int)($config['copywriting_source'] ?? 0);
            if (array_key_exists('hotspot_pick_mode', $params) || $nextCopywritingSource === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_HOTSPOT) {
                $params['hotspot_pick_mode'] = AiPersonaSynthesisConfig::requireValidHotspotPickMode(
                    $params['hotspot_pick_mode'] ?? ($config['hotspot_pick_mode'] ?? AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_LATEST)
                );
            }
            if (array_key_exists('work_mode', $params)) {
                $params['work_mode'] = self::normalizeWorkMode($params['work_mode']);
            }
            // 成品直发没有运行模式可选：规则弹窗里那一节只在「视频剪辑」下渲染，用户根本选不到。
            // 但发布 cron 会当真（AutoDeviceDailyCreateRunner::queryDevices 直接排除无手机模式），
            // 残留的 run_mode=2 会让绑定设备被静默跳过、工作日历里一条成品直发任务都建不出来。
            // 客户端漏传或带着旧值提交都不能信，按最终 work_mode 在写库前归一。
            $nextWorkMode = array_key_exists('work_mode', $params)
                ? (int)$params['work_mode']
                : (int)($config['work_mode'] ?? AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS);
            if ($nextWorkMode === AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT) {
                $runMode = AiPersona::RUN_MODE_PHONE;
            }
            $finalRunMode = $runMode ?? AiPersona::resolveRunMode((int)$personaId);
            if (array_key_exists('product_use_mode', $params)) {
                $params['product_use_mode'] = self::normalizeProductUseMode($params['product_use_mode']);
            }
            if (array_key_exists('product_reuse_mode', $params)) {
                $params['product_reuse_mode'] = self::normalizeProductReuseMode($params['product_reuse_mode']);
            }
            if (array_key_exists('template_config', $params)) {
                $enabledGenerationTypes = array_key_exists('generation_types', $params)
                    ? (array)$params['generation_types']
                    : (array)($config->generation_types ?? []);
                $params['template_config'] = SynthesisTemplateConfigService::validateForSave(
                    $params['template_config'],
                    $enabledGenerationTypes
                );
            }
            if (array_key_exists('material_use_mode', $params)) {
                $params['material_use_mode'] = \app\api\logic\shanjian\ShanjianVideoSettingLogic::normalizeMaterialUseMode(
                    $params['material_use_mode']
                );
            }
            if (array_key_exists('daily_video_count', $params)) {
                $params['daily_video_count'] = AiPersonaSynthesisConfig::normalizeDailyVideoCount($params['daily_video_count']);
                if (!array_key_exists('type_config', $params)) {
                    // 老客户端只传一个总条数：按启用类型重新均分回填，否则库里已显式保存的
                    // type_config 会盖住用户刚改的总条数，云端出片条数对不上。
                    // 总条数为 0（手机模式老客户端恒传 0）时不动分类型配置，免得把已配好的
                    // 每类型音乐/音量一起清空。
                    $rebuilt = self::buildTypeConfigFromTotal($params, $config);
                    if ($rebuilt !== []) {
                        $params['type_config'] = $rebuilt;
                    }
                }
            }
            if (array_key_exists('type_config', $params)) {
                $params = self::applyTypeConfigDerivedFields($params, $config);
            }
            if (array_key_exists('music_source', $params)) {
                $params['music_source'] = AiPersonaSynthesisConfig::normalizeMusicSource($params['music_source']);
            }
            if (array_key_exists('system_music_id', $params) || array_key_exists('music_source', $params)) {
                $nextSource = array_key_exists('music_source', $params)
                    ? (int)$params['music_source']
                    : (int)($config->music_source ?? AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM);
                $pinned = array_key_exists('system_music_id', $params)
                    ? trim((string)$params['system_music_id'])
                    : trim((string)($config->system_music_id ?? ''));
                if ($nextSource !== AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM) {
                    $params['system_music_id'] = '';
                } elseif ($pinned !== '' && !\app\common\service\SystemMusicService::isEnabled($pinned)) {
                    throw new \Exception('这首系统音乐已下架，请重选或改回每次随机');
                } else {
                    $params['system_music_id'] = $pinned;
                }
            }
            if (array_key_exists('music_volume', $params)) {
                $params['music_volume'] = AiPersonaSynthesisConfig::normalizeMusicVolume($params['music_volume']);
            }
            if (array_key_exists('speech_rate', $params)) {
                $params['speech_rate'] = AiPersonaSynthesisConfig::normalizeSpeechRate($params['speech_rate']);
            }
            if (array_key_exists('square_group_ids', $params)) {
                $params['square_group_ids'] = \app\common\service\square\SquareAssetService::normalizeGroupIds($params['square_group_ids']);
            }
            $params = self::applyCopywritingGenerationFields($params, $config);
            $params['update_time'] = time();

            $shouldResetVideoLibraryUse = CopywritingLibraryLogic::hasVideoLibraryRuleChanged($config, array_merge($config->toArray(), $params));
            $shouldResetProductUse = self::hasProductLibraryRuleChanged($config, $params);

            $beforeData = $config->toArray();

            Db::startTrans();
            try {
                $config->save($params);
                if (array_key_exists('work_mode', $params)) {
                    self::syncPersonaPublishMode((int)$personaId, (int)$params['work_mode']);
                }
                if ($runMode !== null) {
                    self::syncPersonaRunMode((int)$personaId, $runMode);
                }
                if (array_key_exists('material_use_mode', $params)) {
                    \app\api\logic\shanjian\ShanjianVideoSettingLogic::syncMaterialUseMode(
                        (int)$personaId,
                        (int)$params['material_use_mode']
                    );
                }
                if ($shouldResetVideoLibraryUse) {
                    CopywritingLibraryLogic::resetVideoDriverUseCounts((int)$personaId);
                }
                if ($shouldResetProductUse) {
                    self::resetProductDirectUseLogs((int)$personaId, (int)self::$uid);
                }
                Db::commit();
            } catch (\Throwable $e) {
                Db::rollback();
                throw $e;
            }

            $afterData = $config->refresh()->toArray();
            Log::channel('synthesis_config')->write(sprintf(
                '[update] id=%d persona_id=%d user_id=%d before=%s after=%s',
                $configId,
                $personaId,
                self::$uid,
                json_encode($beforeData, JSON_UNESCAPED_UNICODE),
                json_encode($afterData, JSON_UNESCAPED_UNICODE)
            ));

            $data = $afterData;
            $data['has_avatar'] = $hasAvatar;
            $data['has_voice'] = $hasVoice;
            $data['publish_mode'] = (int)AiPersona::where('id', $personaId)->value('publish_mode');
            $data['run_mode'] = $finalRunMode;
            self::$returnData = self::appendTemplateConfig($data);
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            \think\facade\Log::channel('auto')->write($th->__toString());
            return false;
        }
    }

    public static function delete(array $ids): bool
    {
        self::setError('暂无删除');
        return false;
        try {
            if (empty($ids)) {
                self::setError('删除ID不能为空');
                return false;
            }

            $configs = AiPersonaSynthesisConfig::where('user_id', self::$uid)
                ->whereIn('id', $ids)
                ->select();

            if ($configs->isEmpty()) {
                self::setError('配置不存在');
                return false;
            }

            $time = time();
            foreach ($configs as $config) {
                $config->delete_time = $time;
                $config->save();
            }

            self::$returnData = ['count' => count($configs)];
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $config = AiPersonaSynthesisConfig::where('id', $params['id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();

            if ($config->isEmpty()) {
                self::setError('配置不存在');
                return false;
            }

            self::$returnData = self::appendTemplateConfig($config->toArray());
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function getByPersonaId(array $params): bool
    {
        try {
            $config = AiPersonaSynthesisConfig::where('persona_id', $params['persona_id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            $hasAvatar =  true;
            $hasVoice =  true;
            $avatar = AiPersonaDigitalAvatar::availableQuery()
                ->where('ad.persona_id', $params['persona_id'])
                ->where('ad.user_id', self::$uid)
                ->findOrEmpty();
            if ($avatar->isEmpty()) {
                $hasAvatar = false;
            }

            $voice = AiPersonaDigitalVoice::availableQuery()
                ->where('ad.persona_id', $params['persona_id'])
                ->where('ad.user_id', self::$uid)
                ->findOrEmpty();
            if ($voice->isEmpty()) {
                $hasVoice = false;
            }

            if (!$hasAvatar || !$hasVoice) {
                $noticeContent = !$hasAvatar && !$hasVoice ? '人设中未配置形象和音色' : (!$hasAvatar ? '人设中未配置形象' : '人设中未配置音色');
                $noticeStatus = $hasAvatar ? '转为新闻体混剪模式' : '转为素材混剪模式';
                ApiLogic::sendNotice([
                    'userId' => self::$uid,
                    'content' => $noticeContent,
                    'status' => $noticeStatus
                ], 402);
            }
            $persona = AiPersona::where('id', $params['persona_id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            if ($config->isEmpty()) {
                $defaultConfig = self::buildDefaultConfig(
                    (int) $persona['id'],
                    (int) $persona['user_id'],
                    (int) $persona['persona_type'],
                    (int) ($persona['publish_mode'] ?? 1)
                );
                $config = AiPersonaSynthesisConfig::create($defaultConfig);
                Log::channel('synthesis_config')->write(sprintf(
                    '[create] persona_id=%d user_id=%d data=%s',
                    $persona['id'],
                    $persona['user_id'],
                    json_encode($defaultConfig, JSON_UNESCAPED_UNICODE)
                ));
            } else {
                // 兼容旧数据：人设已是直发但配置仍为 AI 合成时，回填 work_mode
                $publishMode = (int)($persona['publish_mode'] ?? 1);
                $workMode = (int)($config['work_mode'] ?? AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS);
                if ($publishMode === 2 && $workMode !== AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT) {
                    $beforeData = $config->toArray();
                    $config->save([
                        'work_mode' => AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT,
                        'update_time' => time(),
                    ]);
                    $config = $config->refresh();
                    Log::channel('synthesis_config')->write(sprintf(
                        '[update] persona_id=%d user_id=%d reason=直发模式回填 before_work_mode=%d after_work_mode=%d before=%s after=%s',
                        $persona['id'],
                        $persona['user_id'],
                        $workMode,
                        AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT,
                        json_encode($beforeData, JSON_UNESCAPED_UNICODE),
                        json_encode($config->toArray(), JSON_UNESCAPED_UNICODE)
                    ));
                }
            }
            $data = $config->toArray();
            $data['has_avatar'] = $hasAvatar;
            $data['has_voice'] = $hasVoice;
            $data['publish_mode'] = (int)($persona['publish_mode'] ?? 1);
            $data['run_mode'] = AiPersona::normalizeRunMode($persona['run_mode'] ?? AiPersona::RUN_MODE_PHONE);
            $data = self::appendTemplateConfig($data);
            self::$returnData = $data;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 根据人设类型生成默认合成配置（与 syncSynthesisConfig 保持一致）
     */
    public static function buildDefaultConfig(int $personaId, int $userId, int $personaType, int $publishMode = 1): array
    {
        $time = time();
        $workMode = $publishMode === 2
            ? AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT
            : AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS;
        $config = [
            'user_id'            => $userId,
            'persona_id'         => $personaId,
            'work_mode'          => $workMode,
            'product_use_mode'   => AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM,
            'product_reuse_mode' => AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE,
            'video_cover_source' => 1,
            'news_mixcut_duration' => AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_DEFAULT,
            'library_use_mode' => AiPersonaSynthesisConfig::LIBRARY_USE_MODE_RANDOM,
            'library_reuse_mode' => AiPersonaSynthesisConfig::LIBRARY_REUSE_MODE_ONCE,
            'hotspot_pick_mode' => AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_LATEST,
            'template_config' => SynthesisTemplateConfigService::defaultConfig(),
            'music_source' => AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM,
            'system_music_id' => '',
            'music_volume' => AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT,
            'speech_rate' => AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT,
            'copywriting_generation_type' => AiPersonaSynthesisConfig::COPYWRITING_GENERATION_TYPE_KNOWLEDGE,
            'copywriting_generation_custom' => '',
            'daily_video_count' => AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_UNSET,
            'create_time'        => $time,
            'update_time'        => $time,
        ];

        if ($personaType == 3) {
            // 本地商家：素材混剪(3) + 新闻体混剪(4) | 纯素材库 | AI生成
            $config['generation_types']       = [3, 4];
            $config['visual_material_source'] = 3;
            $config['copywriting_source']     = 2;
        } else {
            // 个人IP(1) & 企业服务(2)：数字人口播(1) + 素材混剪(3) | AI+素材库 | 仿写
            $config['generation_types']       = [1, 3];
            $config['visual_material_source'] = 2;
            $config['copywriting_source']     = 1;
        }

        return $config;
    }

    /**
     * 工作方式同步人设发布模式: 1=根据素材制作视频发送 2=直接发送素材内容
     */
    public static function syncPersonaPublishMode(int $personaId, int $workMode): void
    {
        $publishMode = $workMode === AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT ? 2 : 1;
        AiPersona::where('id', $personaId)->update([
            'publish_mode' => $publishMode,
            'update_time' => time(),
        ]);
    }

    /**
     * 运行模式存人设表: 1=手机模式 2=无手机模式
     */
    public static function syncPersonaRunMode(int $personaId, int $runMode): void
    {
        $oldMode = AiPersona::resolveRunMode($personaId);
        $newMode = AiPersona::normalizeRunMode($runMode);
        AiPersona::where('id', $personaId)->update([
            'run_mode' => $newMode,
            'update_time' => time(),
        ]);
        // 24h 任务的两条生成 cron 都只认手机模式，模式一变当天的任务就得跟着重置
        PersonaRunModeSwitchService::apply($personaId, $oldMode, $newMode);
    }

    public static function applyCopywritingGenerationFields(array $params, $config): array
    {
        if (!array_key_exists('copywriting_generation_type', $params)
            && !array_key_exists('copywriting_generation_custom', $params)
            && !array_key_exists('copywriting_source', $params)
        ) {
            return $params;
        }

        $mergedType = array_key_exists('copywriting_generation_type', $params)
            ? AiPersonaSynthesisConfig::normalizeCopywritingGenerationType($params['copywriting_generation_type'])
            : (int)($config->copywriting_generation_type ?? AiPersonaSynthesisConfig::COPYWRITING_GENERATION_TYPE_KNOWLEDGE);
        $mergedCustom = array_key_exists('copywriting_generation_custom', $params)
            ? AiPersonaSynthesisConfig::normalizeCopywritingGenerationCustom($params['copywriting_generation_custom'])
            : (string)($config->copywriting_generation_custom ?? '');
        $mergedSource = array_key_exists('copywriting_source', $params)
            ? (int)$params['copywriting_source']
            : (int)($config->copywriting_source ?? 0);

        if ($mergedSource === AiPersonaSynthesisConfig::COPYWRITING_SOURCE_AI
            && $mergedType === AiPersonaSynthesisConfig::COPYWRITING_GENERATION_TYPE_CUSTOM
            && $mergedCustom === ''
        ) {
            throw new \Exception('请填写自定义文案生成方向');
        }
        if (array_key_exists('copywriting_generation_type', $params)) {
            $params['copywriting_generation_type'] = $mergedType;
        }
        if (array_key_exists('copywriting_generation_custom', $params)) {
            $params['copywriting_generation_custom'] = $mergedCustom;
        }

        return $params;
    }

    public static function normalizeWorkMode($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS,
            AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT,
        ], true) ? $value : AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS;
    }

    public static function normalizeProductUseMode($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM,
            AiPersonaSynthesisConfig::PRODUCT_USE_MODE_SEQUENCE,
        ], true) ? $value : AiPersonaSynthesisConfig::PRODUCT_USE_MODE_RANDOM;
    }

    public static function normalizeProductReuseMode($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE,
            AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_REPEAT,
        ], true) ? $value : AiPersonaSynthesisConfig::PRODUCT_REUSE_MODE_ONCE;
    }

    /**
     * 成品库使用方式/随机规则是否变更。
     */
    public static function hasProductLibraryRuleChanged($oldConfig, array $newParams): bool
    {
        if (is_object($oldConfig) && method_exists($oldConfig, 'toArray')) {
            $oldConfig = $oldConfig->toArray();
        }
        if (!is_array($oldConfig)) {
            $oldConfig = [];
        }

        $oldUseMode = (int)($oldConfig['product_use_mode'] ?? 0);
        $oldReuseMode = (int)($oldConfig['product_reuse_mode'] ?? 0);
        $newUseMode = array_key_exists('product_use_mode', $newParams)
            ? (int)$newParams['product_use_mode']
            : $oldUseMode;
        $newReuseMode = array_key_exists('product_reuse_mode', $newParams)
            ? (int)$newParams['product_reuse_mode']
            : $oldReuseMode;

        return $oldUseMode !== $newUseMode || $oldReuseMode !== $newReuseMode;
    }

    /**
     * 成品库直发规则变更时，清空该人设内容发布场景的素材使用日志，避免旧次数影响新规则。
     */
    public static function resetProductDirectUseLogs(int $personaId, int $userId = 0): void
    {
        if ($personaId <= 0) {
            return;
        }

        $query = MaterialUseLog::where('persona_id', $personaId)
            ->where('publish_mode', MaterialModel::PUBLISH_MODE_DIRECT_SEND)
            ->where('use_scene', MaterialUseLog::USE_SCENE_CONTENT_PUBLISH);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $logIds = $query->column('id');
        if (!empty($logIds)) {
            MaterialUseLog::destroy($logIds);
        }
    }

    private static function appendTemplateConfig(array $data): array
    {
        $data['template_config'] = SynthesisTemplateConfigService::enrich($data['template_config'] ?? []);
        $pinned = trim((string)($data['system_music_id'] ?? ''));
        $hit = $pinned === '' ? [] : \app\common\service\SystemMusicService::findEnabled($pinned);
        $data['system_music_name'] = (string)($hit['name'] ?? '');
        $data['system_music_enabled'] = $pinned === '' || $hit !== [];
        $data['system_musics'] = \app\common\service\SystemMusicService::publicList();
        $data['system_music_groups'] = \app\common\service\SystemMusicService::allGroups();
        $data['type_config'] = self::appendTypeConfig($data);
        return $data;
    }

    /**
     * 分类型配置回显：三类 key 恒返回，老数据按 daily_video_count 均分条数、
     * 音乐继承顶层值，前端不必自己补算。
     */
    private static function appendTypeConfig(array $data): array
    {
        $counts = AiPersonaSynthesisConfig::resolveCloudTypeCounts($data);
        $result = [];
        foreach (AiPersonaSynthesisConfig::TYPE_CONFIG_KEYS as $type) {
            $music = AiPersonaSynthesisConfig::resolveTypeMusic($data, $type);
            $pinnedMusic = trim((string)$music['pinned_id']);
            $hit = $pinnedMusic === '' ? [] : \app\common\service\SystemMusicService::findEnabled($pinnedMusic);
            $result[$type] = [
                'video_count' => (int)($counts[$type] ?? 0),
                'music_source' => (int)$music['source'],
                'music_group_id' => (int)$music['group_id'],
                'system_music_id' => $pinnedMusic,
                'system_music_name' => (string)($hit['name'] ?? ''),
                'system_music_enabled' => $pinnedMusic === '' || $hit !== [],
                'music_volume' => (float)$music['volume'],
                'speech_rate' => AiPersonaSynthesisConfig::resolveTypeSpeechRate($data, $type),
                // 一镜到底：仅新闻体混剪可开，后端已收敛为 0/1
                'single_shot' => AiPersonaSynthesisConfig::resolveTypeSingleShot($data, $type)
                    ? AiPersonaSynthesisConfig::SINGLE_SHOT_ON
                    : AiPersonaSynthesisConfig::SINGLE_SHOT_OFF,
            ];
        }
        return $result;
    }

    /**
     * 老客户端（只传 daily_video_count、没有 type_config）的分类型回填。
     *
     * 按启用类型顺序把总条数均分、余数从前往后各补 1，与「没有 type_config 时读
     * daily_video_count」的口径完全一致，只是顺手落成显式配置，避免库里的历史
     * type_config 把老客户端刚改的总条数架空。
     *
     * 只改条数：已显式保存过 type_config 时保留各类型自己的音乐来源/分组/指定曲目/音量，
     * 否则老客户端改一次总条数就会把用户配好的每类型 BGM 冲掉。
     * 总条数为 0（手机模式老客户端恒传 0）直接返回空数组，表示不动分类型配置。
     */
    public static function buildTypeConfigFromTotal(array $params, $config): array
    {
        $total = AiPersonaSynthesisConfig::normalizeDailyVideoCount($params['daily_video_count'] ?? 0);
        if ($total <= 0) {
            return [];
        }
        $order = array_key_exists('generation_types', $params)
            ? AiPersonaSynthesisConfig::resolveGenerationOrder(['generation_types' => $params['generation_types']])
            : AiPersonaSynthesisConfig::resolveGenerationOrder($config);
        $counts = AiPersonaSynthesisConfig::splitLegacyVideoCount($total, $order);

        // 已显式保存过分类型配置 → 只覆盖条数，音乐原样保留
        if (AiPersonaSynthesisConfig::hasExplicitTypeConfig($config)) {
            $typeConfig = AiPersonaSynthesisConfig::normalizeTypeConfig(
                AiPersonaSynthesisConfig::configField($config, 'type_config')
            );
            foreach ($counts as $type => $count) {
                $typeConfig[$type]['video_count'] = (int)$count;
            }
            return $typeConfig;
        }

        // 老数据没有分组概念，音乐整体沿用顶层（含指定曲目），保持「三类继承顶层」的旧表现
        $music = [
            'music_source' => $params['music_source']
                ?? AiPersonaSynthesisConfig::configField($config, 'music_source', AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM),
            'music_group_id' => 0,
            'system_music_id' => $params['system_music_id']
                ?? (string)AiPersonaSynthesisConfig::configField($config, 'system_music_id', ''),
            'music_volume' => $params['music_volume']
                ?? AiPersonaSynthesisConfig::configField($config, 'music_volume', AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT),
        ];
        $typeConfig = [];
        foreach ($counts as $type => $count) {
            $typeConfig[$type] = ['video_count' => (int)$count] + $music;
        }
        return $typeConfig;
    }

    /**
     * 保存分类型配置：校验分组与指定曲目，派生总条数、启用类型与顶层音乐兼容字段。
     */
    public static function applyTypeConfigDerivedFields(array $params, $config): array
    {
        $typeConfig = self::normalizeTypeConfigForSave($params['type_config']);

        $sum = 0;
        foreach ($typeConfig as $item) {
            $sum += (int)$item['video_count'];
        }
        $params['type_config'] = $typeConfig;
        // 总条数由三类汇总；它仍是「其它链路」读的兼容值，不再是云端出片的唯一来源
        $params['daily_video_count'] = min($sum, AiPersonaSynthesisConfig::DAILY_VIDEO_COUNT_MAX);

        // 启用类型：提交的为准，条数 > 0 却没列进来的补到末尾，避免配了条数却不出片
        $order = array_key_exists('generation_types', $params)
            ? AiPersonaSynthesisConfig::resolveGenerationOrder(['generation_types' => $params['generation_types']])
            : AiPersonaSynthesisConfig::resolveGenerationOrder($config);
        foreach ($typeConfig as $type => $item) {
            if ((int)$item['video_count'] > 0 && !in_array((int)$type, $order, true)) {
                $order[] = (int)$type;
            }
        }
        if ($order !== []) {
            $params['generation_types'] = $order;
        }

        // 顶层音乐与语速沿用数字人口播的配置，供未分类型的旧链路（爆款复刻等）继续读取
        $voice = $typeConfig[AiPersonaSynthesisConfig::GENERATION_TYPE_DIGITAL_HUMAN];
        $params['music_source'] = (int)$voice['music_source'];
        $params['system_music_id'] = (string)$voice['system_music_id'];
        $params['music_volume'] = (float)$voice['music_volume'];
        $params['speech_rate'] = (float)$voice['speech_rate'];

        return $params;
    }

    /**
     * 结构归一 + 业务校验：
     * - 分组不存在或该组已无可用曲目 → 回落 0（全部随机），不能因为空分组让成片静音；
     * - 指定曲目已下架 → 抛错提示重选（与顶层配置同口径）；
     * - 指定曲目不属于所选分组 → 抛错，避免"选了纯音乐分组却指定了歌曲"。
     */
    public static function normalizeTypeConfigForSave($raw): array
    {
        $normalized = AiPersonaSynthesisConfig::normalizeTypeConfig($raw);
        foreach ($normalized as $type => $item) {
            if ((int)$item['music_source'] !== AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM) {
                $normalized[$type]['music_group_id'] = 0;
                $normalized[$type]['system_music_id'] = '';
                continue;
            }
            $groupId = (int)$item['music_group_id'];
            if ($groupId > 0 && !\app\common\service\SystemMusicService::groupHasMusic($groupId)) {
                $groupId = 0;
            }
            $pinned = trim((string)$item['system_music_id']);
            if ($pinned !== '') {
                $hit = \app\common\service\SystemMusicService::findEnabled($pinned);
                if ($hit === []) {
                    throw new \Exception('这首系统音乐已下架，请重选或改回每次随机');
                }
                if ($groupId > 0 && (int)($hit['group_id'] ?? 0) !== $groupId) {
                    throw new \Exception('所选系统音乐不在当前分组内，请重选或改回组内随机');
                }
            }
            $normalized[$type]['music_group_id'] = $groupId;
            $normalized[$type]['system_music_id'] = $pinned;
        }
        return $normalized;
    }

    public static function getDigitalAvatar(array $params): bool
    {
        try {
            $persona = AiPersona::where('id', $params['persona_id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            $list = AiPersonaDigitalAvatar::availableQuery()
                ->field('ad.*')
                ->where('ad.persona_id', $params['persona_id'])
                ->where('ad.user_id', self::$uid)
                ->order('ad.sort', 'desc')
                ->select()
                ->toArray();

            self::$returnData = $list;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    public static function getDigitalVoice(array $params): bool
    {
        try {
            $persona = AiPersona::where('id', $params['persona_id'])
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('IP人设不存在');
                return false;
            }

            $list = AiPersonaDigitalVoice::availableQuery()
                ->field('ad.*')
                ->where('ad.persona_id', $params['persona_id'])
                ->where('ad.user_id', self::$uid)
                ->order('ad.sort', 'desc')
                ->select()
                ->toArray();

            self::$returnData = $list;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

}
