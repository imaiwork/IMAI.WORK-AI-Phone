<?php

namespace app\adminapi\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\api\logic\aiPersona\CopywritingLibraryLogic;
use app\api\logic\aiPersona\SynthesisConfigLogic as ApiSynthesisConfigLogic;
use app\common\logic\BaseLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\aiPersona\SynthesisConfig as AiPersonaSynthesisConfig;
use app\common\service\aiPersona\SynthesisTemplateConfigService;
use Exception;
use think\facade\Db;

class SynthesisConfigLogic extends BaseLogic
{
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $id = intval($params['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('配置ID不能为空');
            }

            $config = AiPersonaSynthesisConfig::where('id', $id)->findOrEmpty();
            if ($config->isEmpty()) {
                throw new Exception('配置不存在');
            }

            $personaId = (int)$config->persona_id;
            $userId = (int)$config->user_id;
            $hasAvatar = self::hasAvatar($personaId, $userId);
            $hasVoice = self::hasVoice($personaId, $userId);

            self::sendSynthesisNotice($userId, $hasAvatar, $hasVoice);

            $saveData = self::getSaveData($params, $config);
            if (empty($saveData)) {
                throw new Exception('保存参数不能为空');
            }
            $saveData['update_time'] = time();

            $shouldResetVideoLibraryUse = CopywritingLibraryLogic::hasVideoLibraryRuleChanged(
                $config,
                array_merge($config->toArray(), $saveData)
            );
            $shouldResetProductUse = ApiSynthesisConfigLogic::hasProductLibraryRuleChanged($config, $saveData);

            $config->save($saveData);
            if (array_key_exists('work_mode', $saveData)) {
                ApiSynthesisConfigLogic::syncPersonaPublishMode($personaId, (int)$saveData['work_mode']);
            }
            if ($shouldResetVideoLibraryUse) {
                CopywritingLibraryLogic::resetVideoDriverUseCounts($personaId);
            }
            if ($shouldResetProductUse) {
                ApiSynthesisConfigLogic::resetProductDirectUseLogs($personaId, $userId);
            }
            $data = $config->refresh()->toArray();
            $data['has_avatar'] = $hasAvatar;
            $data['has_voice'] = $hasVoice;
            $data['publish_mode'] = (int)AiPersona::where('id', $personaId)->value('publish_mode');

            Db::commit();
            self::$returnData = self::appendTemplateConfig($data);
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getByPersonaId(array $params): bool
    {
        Db::startTrans();
        try {
            $personaId = intval($params['persona_id'] ?? 0);
            if ($personaId <= 0) {
                throw new Exception('人设ID是必填项');
            }

            $persona = AiPersona::where('id', $personaId)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new Exception('IP人设不存在');
            }

            $userId = (int)$persona->user_id;
            $config = AiPersonaSynthesisConfig::where('persona_id', $personaId)
                ->where('user_id', $userId)
                ->findOrEmpty();

            $hasAvatar = self::hasAvatar($personaId, $userId);
            $hasVoice = self::hasVoice($personaId, $userId);

            self::sendSynthesisNotice($userId, $hasAvatar, $hasVoice);

            if ($config->isEmpty()) {
                $config = AiPersonaSynthesisConfig::create(
                    ApiSynthesisConfigLogic::buildDefaultConfig(
                        $personaId,
                        $userId,
                        (int)$persona->persona_type,
                        (int)$persona->publish_mode
                    )
                );
            } else {
                $publishMode = (int)$persona->publish_mode;
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
            $data['publish_mode'] = (int)$persona->publish_mode;
            $data = self::appendTemplateConfig($data);

            Db::commit();
            self::$returnData = $data;
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function getSaveData(array $params, AiPersonaSynthesisConfig $config): array
    {
        $fields = [
            'generation_types',
            'template_config',
            'visual_material_source',
            'copywriting_source',
            'library_use_mode',
            'library_reuse_mode',
            'work_mode',
            'product_use_mode',
            'product_reuse_mode',
            'video_cover_source',
            'news_mixcut_duration',
            'pic',
            'music_source',
            'music_volume',
            'speech_rate',
        ];

        $data = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $data[$field] = $params[$field];
            }
        }

        if (array_key_exists('video_cover_type', $params)) {
            $data['video_cover_source'] = $params['video_cover_type'];
        }

        if (array_key_exists('visual_material_source', $data)) {
            $data['visual_material_source'] = self::validateEnum((int)$data['visual_material_source'], [1, 2, 3], '画面素材来源值只能是1、2或3');
        }

        if (array_key_exists('copywriting_source', $data)) {
            $data['copywriting_source'] = self::validateEnum((int)$data['copywriting_source'], [1, 2, 3, 4], '文案来源值只能是1、2、3或4');
        }

        if (array_key_exists('video_cover_source', $data)) {
            $data['video_cover_source'] = self::validateEnum((int)$data['video_cover_source'], [1, 2, 3], '视频封面类型值只能是1、2或3');
        }

        if (array_key_exists('news_mixcut_duration', $data)) {
            $data['news_mixcut_duration'] = self::validateNewsMixcutDuration($data['news_mixcut_duration']);
        }

        if (array_key_exists('library_use_mode', $data)) {
            $data['library_use_mode'] = self::validateEnum((int)$data['library_use_mode'], [1, 2], '文案库使用方式值只能是1或2');
        }

        if (array_key_exists('library_reuse_mode', $data)) {
            $data['library_reuse_mode'] = self::validateEnum((int)$data['library_reuse_mode'], [1, 2], '文案库重复规则值只能是1或2');
        }

        if (array_key_exists('work_mode', $data)) {
            $data['work_mode'] = ApiSynthesisConfigLogic::normalizeWorkMode($data['work_mode']);
        }

        if (array_key_exists('product_use_mode', $data)) {
            $data['product_use_mode'] = ApiSynthesisConfigLogic::normalizeProductUseMode($data['product_use_mode']);
        }

        if (array_key_exists('product_reuse_mode', $data)) {
            $data['product_reuse_mode'] = ApiSynthesisConfigLogic::normalizeProductReuseMode($data['product_reuse_mode']);
        }

        if (array_key_exists('music_source', $data)) {
            $data['music_source'] = AiPersonaSynthesisConfig::normalizeMusicSource($data['music_source']);
        }

        if (array_key_exists('music_volume', $data)) {
            $data['music_volume'] = AiPersonaSynthesisConfig::normalizeMusicVolume($data['music_volume']);
        }

        if (array_key_exists('speech_rate', $data)) {
            $data['speech_rate'] = AiPersonaSynthesisConfig::normalizeSpeechRate($data['speech_rate']);
        }

        if (array_key_exists('template_config', $data)) {
            $enabledGenerationTypes = array_key_exists('generation_types', $data)
                ? (array)$data['generation_types']
                : (array)($config->generation_types ?? []);
            $data['template_config'] = SynthesisTemplateConfigService::validateForSave(
                $data['template_config'],
                $enabledGenerationTypes
            );
        }

        return $data;
    }

    private static function validateEnum(int $value, array $allowed, string $message): int
    {
        if (!in_array($value, $allowed, true)) {
            throw new Exception($message);
        }

        return $value;
    }

    private static function validateNewsMixcutDuration($duration): int
    {
        if (filter_var($duration, FILTER_VALIDATE_INT) === false) {
            throw new Exception('新闻体时长必须是整数');
        }

        $duration = (int)$duration;
        if ($duration < AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_MIN
            || $duration > AiPersonaSynthesisConfig::NEWS_MIXCUT_DURATION_MAX
        ) {
            throw new Exception('新闻体时长范围为5-300秒');
        }

        return $duration;
    }

    private static function hasAvatar(int $personaId, int $userId): bool
    {
        return !AiPersonaDigitalAvatar::availableQuery()
            ->where('ad.persona_id', $personaId)
            ->where('ad.user_id', $userId)
            ->findOrEmpty()
            ->isEmpty();
    }

    private static function hasVoice(int $personaId, int $userId): bool
    {
        return !AiPersonaDigitalVoice::availableQuery()
            ->where('ad.persona_id', $personaId)
            ->where('ad.user_id', $userId)
            ->findOrEmpty()
            ->isEmpty();
    }

    private static function sendSynthesisNotice(int $userId, bool $hasAvatar, bool $hasVoice): void
    {
        if ($hasAvatar && $hasVoice) {
            return;
        }

        $noticeContent = !$hasAvatar && !$hasVoice ? '人设中未配置形象和音色' : (!$hasAvatar ? '人设中未配置形象' : '人设中未配置音色');
        $noticeStatus = $hasAvatar ? '转为新闻体混剪模式' : '转为素材混剪模式';
        ApiLogic::sendNotice([
            'userId' => $userId,
            'content' => $noticeContent,
            'status' => $noticeStatus,
        ], 402);
    }

    private static function appendTemplateConfig(array $data): array
    {
        $data['template_config'] = SynthesisTemplateConfigService::enrich($data['template_config'] ?? []);
        return $data;
    }
}
