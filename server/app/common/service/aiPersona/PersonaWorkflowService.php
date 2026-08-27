<?php

namespace app\common\service\aiPersona;

use app\common\model\aiPersona\AiPersona;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvDeviceExecutionSchedule;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\auto\AutoTaskSceneScheduleSyncService;
use think\facade\Db;

class PersonaWorkflowService
{
    private const TYPE_EXCLUSIVE = 1;
    private const TYPE_CUSTOM = 2;
    private const CATEGORY_EXCLUSIVE = 1;
    private const CATEGORY_CUSTOM = 2;

    private const PERSONA_TYPE_NAMES = [
        1 => '个人IP',
        2 => '企业服务',
        3 => '本地商家',
    ];

    public static function ensureExclusiveCustomWorkflow(AiPersona $persona): AiPersona
    {
        if ((int)$persona->workflow_template_id > 0) {
            return $persona;
        }

        Db::startTrans();
        try {
            $persona = AiPersona::where('id', $persona->id)->lock(true)->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            if ((int)$persona->workflow_template_id > 0) {
                Db::commit();
                return $persona;
            }

            $customTemplate = self::findReusableCustomTemplate($persona);
            if ($customTemplate === null) {
                $customTemplate = self::createExclusiveWorkflow($persona);
            }

            $persona->workflow_template_id = $customTemplate->id;
            $persona->update_time = time();
            $persona->save();

            Db::commit();
            return $persona;
        } catch (\Throwable $th) {
            Db::rollback();
            throw $th;
        }
    }

    private static function findReusableCustomTemplate(AiPersona $persona): ?MarketingTemplate
    {
        $typedTemplate = self::findTypedCustomTemplate($persona);
        if ($typedTemplate !== null) {
            return $typedTemplate;
        }

        return self::findLegacyCustomTemplate($persona);
    }

    private static function findTypedCustomTemplate(AiPersona $persona): ?MarketingTemplate
    {
        $customTemplates = MarketingTemplate::where('user_id', $persona->user_id)
            ->where('persona_id', $persona->id)
            ->where('persona_type', (int)$persona->persona_type)
            ->where('type', self::TYPE_CUSTOM)
            ->where('category_id', self::CATEGORY_CUSTOM)
            ->where('status', 1)
            ->where('original_id', '>', 0)
            ->order('id', 'desc')
            ->select();

        foreach ($customTemplates as $customTemplate) {
            $originalTemplate = self::findOriginalTemplate($persona, (int)$customTemplate->original_id, (int)$persona->persona_type);
            if ($originalTemplate !== null) {
                return $customTemplate;
            }
        }

        return null;
    }

    private static function findLegacyCustomTemplate(AiPersona $persona): ?MarketingTemplate
    {
        $customTemplates = MarketingTemplate::where('user_id', $persona->user_id)
            ->where('persona_id', $persona->id)
            ->where('persona_type', 0)
            ->where('type', self::TYPE_CUSTOM)
            ->where('category_id', self::CATEGORY_CUSTOM)
            ->where('status', 1)
            ->where('original_id', '>', 0)
            ->order('id', 'desc')
            ->select();

        foreach ($customTemplates as $customTemplate) {
            $originalTemplate = self::findOriginalTemplate($persona, (int)$customTemplate->original_id, 0);
            if ($originalTemplate === null) {
                continue;
            }

            $resolvedType = self::resolvePersonaTypeBySchedule((int)$originalTemplate->id);
            if ($resolvedType !== (int)$persona->persona_type) {
                continue;
            }

            $originalTemplate->persona_type = $resolvedType;
            $originalTemplate->update_time = time();
            $originalTemplate->save();

            $customTemplate->persona_type = $resolvedType;
            $customTemplate->update_time = time();
            $customTemplate->save();

            return $customTemplate;
        }

        return null;
    }

    private static function findOriginalTemplate(AiPersona $persona, int $templateId, int $personaType): ?MarketingTemplate
    {
        $query = MarketingTemplate::where('id', $templateId)
            ->where('user_id', $persona->user_id)
            ->where('persona_id', $persona->id)
            ->where('type', self::TYPE_EXCLUSIVE)
            ->where('category_id', self::CATEGORY_EXCLUSIVE)
            ->where('status', 1)
            ->where('is_system_generated', 1);

        if ($personaType > 0) {
            $query->where('persona_type', $personaType);
        } else {
            $query->where('persona_type', 0);
        }

        $template = $query->findOrEmpty();
        return $template->isEmpty() ? null : $template;
    }

    private static function createExclusiveWorkflow(AiPersona $persona): MarketingTemplate
    {
        $personaType = (int)$persona->persona_type;
        if (!isset(self::PERSONA_TYPE_NAMES[$personaType])) {
            throw new \Exception('IP人设类型错误');
        }

        $schedules = SvDeviceExecutionSchedule::where('persona_type', $personaType)
            ->where('scene', 'not in', [16, 17])
            ->order('start_time', 'asc')
            ->select();
        if ($schedules->isEmpty()) {
            throw new \Exception('不存在当前人设类型的专属工作流');
        }

        // 跳过后台已关闭「允许添加」的场景与已关闭的平台，避免新建人设写入不会生成任务的节点
        $configMap = AutoTaskSceneConfigService::getConfigMap();
        $addableSchedules = [];
        foreach ($schedules as $schedule) {
            $scene = (int)$schedule->scene;
            if (!AutoTaskSceneConfigService::canAdd($scene, $configMap)) {
                continue;
            }
            $platform = self::filterOpenPlatforms(self::normalizePlatform($schedule->platform), $scene, $configMap);
            if ($platform === '[]') {
                continue;
            }
            $addableSchedules[] = ['schedule' => $schedule, 'platform' => $platform];
        }
        if (empty($addableSchedules)) {
            throw new \Exception('当前开放的任务类型为空，无法生成专属工作流');
        }

        $now = time();
        $template = MarketingTemplate::create([
            'user_id' => $persona->user_id,
            'persona_id' => $persona->id,
            'persona_type' => $personaType,
            'name' => $persona->persona_name . self::PERSONA_TYPE_NAMES[$personaType] . '专属工作流',
            'type' => self::TYPE_EXCLUSIVE,
            'category_id' => self::CATEGORY_EXCLUSIVE,
            'operation_preference' => 1,
            'description' => '系统根据您当前配置的IP人设自动生成的专属任务流，保证基础运营效果，不可更改。',
            'status' => 1,
            'detail_content' => '',
            'detail_task_types' => '',
            'detail_users' => '',
            'detail_images' => [],
            'detail_videos' => [],
            'is_system_generated' => 1,
            'create_time' => $now,
        ]);

        $customTemplate = MarketingTemplate::create([
            'user_id' => $persona->user_id,
            'persona_id' => $persona->id,
            'persona_type' => $personaType,
            'name' => $persona->persona_name . self::PERSONA_TYPE_NAMES[$personaType] . '专属自定义工作流',
            'type' => self::TYPE_CUSTOM,
            'category_id' => self::CATEGORY_CUSTOM,
            'operation_preference' => 1,
            'description' => '系统根据您当前配置的IP人设自动生成的专属自定义任务流，保证基础运营效果，可更改任务。',
            'status' => 1,
            'detail_content' => '',
            'detail_task_types' => '',
            'detail_users' => '',
            'detail_images' => [],
            'detail_videos' => [],
            'is_system_generated' => 0,
            'original_id' => $template->id,
            'create_time' => $now,
        ]);

        $insertData = [];
        $customInsertData = [];
        foreach ($addableSchedules as $addable) {
            $schedule = $addable['schedule'];
            $synced = AutoTaskSceneScheduleSyncService::syncLockedEndTime([
                'scene' => (int)$schedule->scene,
                'start_time' => (string)$schedule->start_time,
                'end_time' => (string)$schedule->end_time,
                'platform' => $addable['platform'],
            ]);
            $row = [
                'user_id' => $persona->user_id,
                'persona_id' => $persona->id,
                'start_time' => $synced['start_time'] ?? $schedule->start_time,
                'end_time' => $synced['end_time'] ?? $schedule->end_time,
                'task_category' => $schedule->task_category,
                'scene' => $schedule->scene,
                'platform' => $addable['platform'],
                'remark' => $schedule->remark,
                'create_time' => $now,
            ];

            $insertData[] = array_merge($row, ['template_id' => $template->id]);
            $customInsertData[] = array_merge($row, ['template_id' => $customTemplate->id]);
        }

        MarketingTemplateSchedule::insertAll($insertData);
        MarketingTemplateSchedule::insertAll($customInsertData);

        return $customTemplate;
    }

    private static function resolvePersonaTypeBySchedule(int $templateId): int
    {
        $templateSignature = self::getTemplateScheduleSignature($templateId);
        if (empty($templateSignature)) {
            return 0;
        }

        $matchedTypes = [];
        foreach (array_keys(self::PERSONA_TYPE_NAMES) as $personaType) {
            $defaultSignature = self::getDefaultScheduleSignature((int)$personaType);
            if (!empty($defaultSignature) && $templateSignature === $defaultSignature) {
                $matchedTypes[] = (int)$personaType;
            }
        }

        return count($matchedTypes) === 1 ? $matchedTypes[0] : 0;
    }

    private static function getTemplateScheduleSignature(int $templateId): array
    {
        $schedules = MarketingTemplateSchedule::where('template_id', $templateId)
            ->order('start_time', 'asc')
            ->select();

        return self::buildScheduleSignature($schedules);
    }

    private static function getDefaultScheduleSignature(int $personaType): array
    {
        $schedules = SvDeviceExecutionSchedule::where('persona_type', $personaType)
            ->where('scene', 'not in', [16, 17])
            ->order('start_time', 'asc')
            ->select();

        return self::buildScheduleSignature($schedules);
    }

    private static function buildScheduleSignature($schedules): array
    {
        $signature = [];
        foreach ($schedules as $schedule) {
            $signature[] = implode('|', [
                (string)$schedule->start_time,
                (string)$schedule->end_time,
                (int)$schedule->scene,
                trim((string)$schedule->task_category),
                self::normalizePlatform($schedule->platform),
            ]);
        }

        sort($signature, SORT_STRING);
        return $signature;
    }

    /**
     * 剔除已关闭的平台并重排 order；平台全关返回 '[]'
     * 仅用于写入节点，签名比对仍用未过滤的 normalizePlatform
     *
     * @param string $platformJson normalizePlatform 的返回值
     * @param int $scene
     * @param array $configMap
     * @return string
     */
    private static function filterOpenPlatforms(string $platformJson, int $scene, array $configMap): string
    {
        $decoded = json_decode($platformJson, true);
        if (!is_array($decoded)) {
            return '[]';
        }

        $result = [];
        foreach ($decoded as $item) {
            $accountType = (int)($item['account_type'] ?? 0);
            if ($accountType <= 0 || !AutoTaskSceneConfigService::canAdd($scene, $accountType, $configMap)) {
                continue;
            }
            $result[] = [
                'account_type' => $accountType,
                'order' => count($result) + 1,
            ];
        }

        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function normalizePlatform($platform): string
    {
        if (is_string($platform)) {
            $decoded = json_decode($platform, true);
            $platform = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($platform)) {
            $platform = [];
        }

        $account = [];
        foreach ($platform as $key => $item) {
            if (is_array($item)) {
                $account[] = [
                    'account_type' => (int)($item['account_type'] ?? $item['app_type'] ?? $item['type'] ?? 0),
                    'order' => (int)($item['order'] ?? $key + 1),
                ];
            } else {
                $account[] = [
                    'account_type' => (int)$item,
                    'order' => $key + 1,
                ];
            }
        }

        usort($account, function ($left, $right) {
            if ($left['order'] === $right['order']) {
                return $left['account_type'] <=> $right['account_type'];
            }

            return $left['order'] <=> $right['order'];
        });

        return json_encode($account, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
