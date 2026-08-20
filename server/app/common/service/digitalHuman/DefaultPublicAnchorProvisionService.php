<?php

namespace app\common\service\digitalHuman;

use app\common\model\aiPersona\AiPersonaDigitalAvatar;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\FileService;
use Exception;
use think\facade\Log;

class DefaultPublicAnchorProvisionService
{
    public static function provisionForPersona(int $userId, int $personaId, int $personaType): void
    {
        if (!DefaultPublicAnchorConfig::isEnabledForPersonaType($personaType)) {
            return;
        }

        if (!DefaultPublicAnchorAssetService::isSynced()) {
            Log::channel('digital')->warning('默认公共形象母版尚未同步，尝试懒同步');
            $result = DefaultPublicAnchorAssetService::syncToStorage();
            if ($result['failed'] !== []) {
                throw new Exception('默认公共形象母版同步失败');
            }
        }

        $dhId = self::resolveOrCloneUserDefaultAnchor($userId);
        self::linkPersonaDigitalAvatar($userId, $personaId, $dhId);
    }

    public static function resolveOrCloneUserDefaultAnchor(int $userId): int
    {
        $config = DefaultPublicAnchorConfig::get();
        $systemRemark = (string)($config['system_remark'] ?? 'system_default');

        $existing = DigitalHumanAnchor::where([
            'user_id' => $userId,
            'remark'  => $systemRemark,
        ])->where('status', 2)->find();

        if ($existing) {
            return (int)$existing['id'];
        }

        $payloads = self::buildCloneInsertPayloads($config, $userId);
        $now = time();

        $dhData = array_merge($payloads['digital_human_anchor'], [
            'user_id'     => $userId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        $dhId = (int)DigitalHumanAnchor::create($dhData)->id;

        $humanAnchorData = array_merge($payloads['human_anchor'], [
            'user_id'     => $userId,
            'dh_id'       => $dhId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        HumanAnchor::create($humanAnchorData);

        $humanVoiceData = array_merge($payloads['human_voice'], [
            'user_id'     => $userId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        HumanVoice::create($humanVoiceData);

        $shanjianData = array_merge($payloads['shanjian_anchor'], [
            'user_id'     => $userId,
            'dh_id'       => $dhId,
            'create_time' => $now,
            'update_time' => $now,
        ]);
        ShanjianAnchor::create($shanjianData);

        return $dhId;
    }

    public static function linkPersonaDigitalAvatar(int $userId, int $personaId, int $dhId): void
    {
        $exists = AiPersonaDigitalAvatar::where([
            'user_id'     => $userId,
            'persona_id'  => $personaId,
            'dh_id'       => $dhId,
            'delete_time' => null,
        ])->find();

        if ($exists) {
            return;
        }

        $avatar = DigitalHumanAnchor::where(['id' => $dhId, 'user_id' => $userId])->where('status', 2)->findOrEmpty();
        if ($avatar->isEmpty()) {
            throw new Exception('默认公共形象状态异常');
        }

        $shanjian = ShanjianAnchor::where('dh_id', $dhId)->where('status', 6)->findOrEmpty();
        if ($shanjian->isEmpty()) {
            throw new Exception('默认公共形象壹传媒记录状态异常');
        }

        $config = DefaultPublicAnchorConfig::get();
        $avatarConfig = $config['persona_digital_avatar'] ?? [];

        AiPersonaDigitalAvatar::create([
            'user_id'           => $userId,
            'persona_id'        => $personaId,
            'dh_id'             => $dhId,
            'avatar_name'       => $avatar['name'],
            'cover_url'         => $avatar['image'] ? FileService::setFileUrl($avatar['image']) : '',
            'video_url'         => $avatar['result_url'] ? FileService::setFileUrl($avatar['result_url']) : '',
            'width'             => $avatar['width'] ?? 0,
            'height'            => $avatar['height'] ?? 0,
            'third_avatar_id'   => $shanjian['anchor_id'] ?? '',
            'third_voice_id'    => $shanjian['voice_id'],
            'is_original_voice' => (int)($avatarConfig['is_original_voice'] ?? 1),
            'voice_name'        => $avatar['name'],
            'voice_url'         => $shanjian['voice_url'],
            'create_time'       => time(),
            'update_time'       => time(),
        ]);
    }

    /**
     * 将母版配置映射为 4 表插入数组（纯函数，便于单测）。
     *
     * @return array{
     *     digital_human_anchor: array<string, mixed>,
     *     human_anchor: array<string, mixed>,
     *     human_voice: array<string, mixed>,
     *     shanjian_anchor: array<string, mixed>
     * }
     */
    public static function buildCloneInsertPayloads(array $config, int $userId): array
    {
        $systemRemark = (string)($config['system_remark'] ?? 'system_default');
        $dhTemplate = $config['digital_human_anchor'] ?? [];
        $templateTaskIds = $dhTemplate['task_ids'] ?? [];
        $taskIdGroup = DefaultPublicTaskIdFactory::forAnchorClone(is_array($templateTaskIds) ? $templateTaskIds : []);

        return [
            'digital_human_anchor' => [
                'name'           => $dhTemplate['name'] ?? '系统默认形象',
                'avatar'         => '',
                'image'          => $dhTemplate['image'] ?? '',
                'task_ids'       => json_encode($taskIdGroup['task_ids'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status'         => (int)($dhTemplate['status'] ?? 2),
                'task_id'        => '',
                'remark'         => $systemRemark,
                'result_url'     => $dhTemplate['result_url'] ?? '',
                'authorized_url' => $dhTemplate['authorized_url'] ?? '',
                'authorized_pic' => $dhTemplate['authorized_pic'] ?? '',
                'width'          => (int)($dhTemplate['width'] ?? 0),
                'height'         => (int)($dhTemplate['height'] ?? 0),
                'ai_type'        => (int)($dhTemplate['ai_type'] ?? 1),
                'model_versions' => null,
            ],
            'human_anchor' => array_merge(
                self::pickTemplateFields($config['human_anchor'] ?? [], [
                    'model_version', 'status', 'pic', 'anchor_id', 'name', 'width', 'height',
                    'gender', 'url', 'preview_result_url', 'preview_audio_url', 'anchor_id_value', 'type', 'remark',
                ]),
                ['task_id' => $taskIdGroup['chanjing']]
            ),
            'human_voice' => array_merge(
                self::pickTemplateFields($config['human_voice'] ?? [], [
                    'model_version', 'status', 'gender', 'name', 'voice_id', 'voice_urls', 'type',
                    'audio_url', 'language', 'demo_text', 'result_task_id', 'remark',
                ]),
                ['task_id' => $taskIdGroup['chanjing']]
            ),
            'shanjian_anchor' => array_merge(
                self::pickTemplateFields($config['shanjian_anchor'] ?? [], [
                    'name', 'status', 'pic', 'anchor_id', 'voice_id', 'voice_model', 'voice_url',
                    'remark', 'token', 'anchor_url', 'authorized_pic', 'authorized_url',
                ]),
                ['task_id' => $taskIdGroup['shanjian']]
            ),
        ];
    }

    /**
     * @param array<string, mixed> $template
     * @param array<int, string> $fields
     * @return array<string, mixed>
     */
    private static function pickTemplateFields(array $template, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $template)) {
                $data[$field] = $template[$field];
            }
        }
        return $data;
    }
}
