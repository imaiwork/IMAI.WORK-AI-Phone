<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\Material as MaterialModel;
use app\common\model\aiPersona\MaterialUseLog;
use app\common\model\shanjian\ShanjianClipTemplate;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use app\common\model\sv\SvDevice;
use think\facade\Cache;
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

            unset($params['id']);
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
            if (array_key_exists('work_mode', $params)) {
                $params['work_mode'] = self::normalizeWorkMode($params['work_mode']);
            }
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
            if (array_key_exists('music_source', $params)) {
                $params['music_source'] = AiPersonaSynthesisConfig::normalizeMusicSource($params['music_source']);
            }
            if (array_key_exists('music_volume', $params)) {
                $params['music_volume'] = AiPersonaSynthesisConfig::normalizeMusicVolume($params['music_volume']);
            }
            if (array_key_exists('speech_rate', $params)) {
                $params['speech_rate'] = AiPersonaSynthesisConfig::normalizeSpeechRate($params['speech_rate']);
            }
            $params['update_time'] = time();

            $shouldResetVideoLibraryUse = CopywritingLibraryLogic::hasVideoLibraryRuleChanged($config, array_merge($config->toArray(), $params));
            $shouldResetProductUse = self::hasProductLibraryRuleChanged($config, $params);

            Db::startTrans();
            try {
                $config->save($params);
                if (array_key_exists('work_mode', $params)) {
                    self::syncPersonaPublishMode((int)$personaId, (int)$params['work_mode']);
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

            $data = $config->refresh()->toArray();
            $data['has_avatar'] = $hasAvatar;
            $data['has_voice'] = $hasVoice;
            $data['publish_mode'] = (int)AiPersona::where('id', $personaId)->value('publish_mode');
            self::$returnData = self::appendTemplateConfig($data);
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
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
                $config = AiPersonaSynthesisConfig::create(
                    self::buildDefaultConfig(
                        (int) $persona['id'],
                        (int) $persona['user_id'],
                        (int) $persona['persona_type'],
                        (int) ($persona['publish_mode'] ?? 1)
                    )
                );
            } else {
                // 兼容旧数据：人设已是直发但配置仍为 AI 合成时，回填 work_mode
                $publishMode = (int)($persona['publish_mode'] ?? 1);
                $workMode = (int)($config['work_mode'] ?? AiPersonaSynthesisConfig::WORK_MODE_AI_SYNTHESIS);
                if ($publishMode === 2 && $workMode !== AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT) {
                    $config->save([
                        'work_mode' => AiPersonaSynthesisConfig::WORK_MODE_PRODUCT_DIRECT,
                        'update_time' => time(),
                    ]);
                    $config = $config->refresh();
                }
            }
            $data = $config->toArray();
            $data['has_avatar'] = $hasAvatar;
            $data['has_voice'] = $hasVoice;
            $data['publish_mode'] = (int)($persona['publish_mode'] ?? 1);
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
            'template_config' => SynthesisTemplateConfigService::defaultConfig(),
            'music_source' => AiPersonaSynthesisConfig::MUSIC_SOURCE_SYSTEM,
            'music_volume' => AiPersonaSynthesisConfig::MUSIC_VOLUME_DEFAULT,
            'speech_rate' => AiPersonaSynthesisConfig::SPEECH_RATE_DEFAULT,
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
        return $data;
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
