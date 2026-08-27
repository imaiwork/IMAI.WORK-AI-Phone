<?php


namespace app\adminapi\logic\marketing;

use app\common\logic\BaseLogic;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\aiPersona\AiPersona;
use app\common\enum\DeviceEnum;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\auto\AutoTaskSceneScheduleSyncService;
use think\facade\Db;

/**
 * 模板管理逻辑
 * Class MarketingTemplateLogic
 * @package app\adminapi\logic\marketing
 */
class MarketingTemplateLogic extends BaseLogic
{
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            $find = MarketingTemplate::where('name', $params['name'])->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('模板名称已存在');
            }
            $params['type'] = 3;
            $params['is_system_generated'] = 0;
            $params['create_time'] = time();
            $params['update_time'] = time();
            $template = MarketingTemplate::create($params);
            $insertData = self::buildScheduleInserts((int)$template->id, 0, 0, $params['schedule'] ?? []);
            if (!empty($insertData)) {
                MarketingTemplateSchedule::insertAll($insertData);
            }
            $template->schedule = MarketingTemplateSchedule::where('template_id', $template->id)->order('start_time', 'asc')->select()->toArray();
            self::$returnData = $template->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params): bool
    {
        try {
            $template = MarketingTemplate::where('id', $params['id'])->findOrEmpty();
            if ($template->isEmpty()) {
                throw new \Exception('该模板不存在');
            }
            $template->schedule = MarketingTemplateSchedule::where('template_id', $template->id)->order('start_time', 'asc')->select()->toArray();
            $data = $template->toArray();
            $data['schedule'] = self::normalizeSchedules($data['schedule'] ?? []);
            self::$returnData = $data;
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            // $exist = MarketingTemplate::where('name', $params['name'])->where('type', 3)->where('id', '<>', $params['id'])->findOrEmpty();
            // if (!$exist->isEmpty()) {
            //     throw new \Exception('模板名称已存在');
            // }

            $find = MarketingTemplate::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该模板不存在');
            }
            $params['update_time'] = time();
            $find->save($params);

            MarketingTemplateSchedule::where('template_id', $find->id)->select()->delete();
            if (isset($params['schedule']) && !empty($params['schedule'])) {
                $insertData = self::buildScheduleInserts(
                    (int)$find->id,
                    (int)$find->user_id,
                    (int)$find->persona_id,
                    $params['schedule']
                );
                if (!empty($insertData)) {
                    MarketingTemplateSchedule::insertAll($insertData);
                }
            }
            $find->schedule = MarketingTemplateSchedule::where('template_id', $find->id)->order('start_time', 'asc')->select()->toArray();


            self::$returnData = $find->toArray();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $find = MarketingTemplate::where('id', $params['id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该模板不存在');
            }
            MarketingTemplateSchedule::where('template_id', $find->id)->select()->delete();

            $personas = AiPersona::where('workflow_template_id', $find->id)->select();
            foreach ($personas as $persona) {
                $template = MarketingTemplate::where('persona_id', $persona->id)->where('type', 1)->findOrEmpty();
                if (!$template->isEmpty()) {
                    $persona->workflow_template_id = $template->id;
                } else {
                    $persona->workflow_template_id = 0;
                }
                $persona->save();
            }
            $find->delete();
            self::$returnData = [];
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 单独修改模板状态
     *
     * @param array $params 只需包含 id 和 status
     * @return bool
     */
    public static function updateStatus(array $params): bool
    {
        try {
            $id     = (int)$params['id'];
            $status = (int)$params['status'];

            $find = MarketingTemplate::where('id', $id)->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该模板不存在');
            }

            $find->save([
                'status'      => $status,
                'update_time' => time(),
            ]);

            if ((int)$status === 0) {
                $personas = AiPersona::where('workflow_template_id', $find->id)->select();
                foreach ($personas as $persona) {
                    $template = MarketingTemplate::where('persona_id', $persona->id)->where('type', 1)->findOrEmpty();
                    if (!$template->isEmpty()) {
                        $persona->workflow_template_id = $template->id;
                    } else {
                        $persona->workflow_template_id = 0;
                    }
                    $persona->save();
                }
            }


            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 剥离已关平台并重算视频发布等锁定结束时间
     *
     * @param array $schedules
     * @return array
     */
    private static function normalizeSchedules(array $schedules): array
    {
        return AutoTaskSceneScheduleSyncService::applyClosedPlatformsToSchedules(
            $schedules,
            AutoTaskSceneScheduleSyncService::collectCurrentlyClosedPlatforms(
                AutoTaskSceneConfigService::getConfigMap()
            )
        );
    }

    /**
     * @param int $templateId
     * @param int $userId
     * @param int $personaId
     * @param array $schedules
     * @return array
     */
    private static function buildScheduleInserts(int $templateId, int $userId, int $personaId, array $schedules): array
    {
        $insertData = [];
        foreach (self::normalizeSchedules($schedules) as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }
            $platforms = $schedule['platform'] ?? [];
            if (!is_array($platforms) || $platforms === []) {
                continue;
            }
            $insertData[] = [
                'user_id' => $userId,
                'persona_id' => $personaId,
                'template_id' => $templateId,
                'start_time' => $schedule['start_time'] ?? '',
                'end_time' => $schedule['end_time'] ?? '',
                'task_category' => DeviceEnum::getTaskSceneDesc($schedule['scene'] ?? 0),
                'scene' => $schedule['scene'] ?? 0,
                'platform' => json_encode($platforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'create_time' => time(),
            ];
        }
        return $insertData;
    }
}
