<?php

namespace app\common\service\digitalHuman;

use app\common\model\aiPersona\AiPersonaDigitalVoice;
use app\common\model\human\HumanVoice;
use Exception;
use think\facade\Log;

class DefaultPublicVoiceProvisionService
{
    public static function provisionForPersona(int $userId, int $personaId, int $personaType): void
    {
        if (!DefaultPublicVoiceConfig::isEnabledForPersonaType($personaType)) {
            return;
        }

        if (!DefaultPublicVoiceAssetService::isSynced()) {
            Log::channel('digital')->warning('默认公共音色母版尚未同步，尝试懒同步');
            $result = DefaultPublicVoiceAssetService::syncToStorage();
            if ($result['failed'] !== []) {
                throw new Exception('默认公共音色母版同步失败');
            }
        }

        $humanVoiceId = self::resolveOrCloneUserDefaultVoice($userId);
        self::linkPersonaDigitalVoice($userId, $personaId, $humanVoiceId);
    }

    public static function resolveOrCloneUserDefaultVoice(int $userId): int
    {
        $existing = self::findUserDefaultVoice($userId);
        if ($existing) {
            return (int)$existing['id'];
        }

        $config = DefaultPublicVoiceConfig::get();
        $systemRemark = (string)($config['system_remark'] ?? 'system_default_voice');
        $template = $config['human_voice'] ?? [];
        $now = time();

        $data = self::buildCloneInsertPayload($template, $systemRemark);
        $data['user_id'] = $userId;
        $data['create_time'] = $now;
        $data['update_time'] = $now;

        return (int)HumanVoice::create($data)->id;
    }

    public static function linkPersonaDigitalVoice(int $userId, int $personaId, int $humanVoiceId): void
    {
        $exists = AiPersonaDigitalVoice::where([
            'user_id'     => $userId,
            'persona_id'  => $personaId,
            'voice_id'    => $humanVoiceId,
            'delete_time' => null,
        ])->find();

        if ($exists) {
            return;
        }

        $humanVoice = HumanVoice::where(['id' => $humanVoiceId, 'user_id' => $userId])->findOrEmpty();
        if ($humanVoice->isEmpty()) {
            throw new Exception('默认公共音色不存在');
        }
        if ((int)$humanVoice['status'] !== 1) {
            throw new Exception('默认公共音色状态异常');
        }

        $config = DefaultPublicVoiceConfig::get();
        $voiceConfig = $config['persona_digital_voice'] ?? [];

        AiPersonaDigitalVoice::create([
            'user_id'           => $userId,
            'persona_id'        => $personaId,
            'voice_id'          => $humanVoiceId,
            'voice_name'        => $humanVoice['name'] ?? '',
            'provider'          => (string)($voiceConfig['provider'] ?? 'shanjian'),
            'preview_audio_url' => $humanVoice['voice_urls'] ?? '',
            'third_voice_id'    => $humanVoice['voice_id'] ?? '',
            'create_time'       => time(),
            'update_time'       => time(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findUserDefaultVoice(int $userId): ?array
    {
        $config = DefaultPublicVoiceConfig::get();
        $systemRemark = (string)($config['system_remark'] ?? 'system_default_voice');

        $existing = HumanVoice::where([
            'user_id' => $userId,
            'remark'  => $systemRemark,
        ])->order('id', 'asc')->find();

        return $existing ? $existing->toArray() : null;
    }

    /**
     * @param array<string, mixed> $template
     * @return array<string, mixed>
     */
    public static function buildCloneInsertPayload(array $template, string $systemRemark): array
    {
        $fields = [
            'model_version', 'status', 'gender', 'name', 'voice_id', 'voice_urls', 'type',
            'audio_url', 'language', 'demo_text', 'result_task_id',
        ];
        $data = [
            'remark'  => $systemRemark,
            'task_id' => DefaultPublicTaskIdFactory::forVoiceClone(),
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $template)) {
                $data[$field] = $template[$field];
            }
        }
        return $data;
    }
}
