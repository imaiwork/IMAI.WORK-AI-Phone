<?php


namespace app\api\logic\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\marketing\MarketingCategory;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\service\auto\AutoTaskSceneConfigService;
use app\common\service\auto\AutoTaskSceneScheduleSyncService;
use think\facade\Db;


/**
 * 自动流程逻辑
 * Class WorkflowLogic    
 * @package app\api\logic\aiPersona
 */
class WorkflowLogic extends BasePersonaLogic
{
    /**
     * @notes 人设正在使用的模板详情
     *
     * @param array $params 人设ID
     * @return bool
     */
    public static function detail(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }
            if ((int)$persona->workflow_template_id === 0) {
                //根据人设类型绑定专属计划
                //创建专属模板和专属计划
                $persona = self::createPersonaExclusiveWorkflow($persona);

                 MarketingTemplate::where('persona_id', $persona->id)->where('persona_type', 0)->where('category_id', 1)->select()->each(function ($item) {
                    MarketingTemplateSchedule::where('template_id', $item->id)->where('persona_id', $item->persona_id)->select()->delete();
                })->delete();
            }

            $template = MarketingTemplate::where('id', $persona->workflow_template_id)->findOrEmpty();
            if ($template->isEmpty()) {
                self::setError('该人设未配置工作流模板');
                return false;
            }
            $template->schedule = MarketingTemplateSchedule::where('scene', 'not in', [16, 17])->where('template_id', $template->id)->order('start_time', 'asc')->select()->each(function ($item) use ($persona, $template) {
                $item->status = 1;
                $find = AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
                    ->where('template_id', $template->id)
                    ->where('scene', $item->scene)
                    ->where('start_time', $item->start_time)
                    ->where('end_time', $item->end_time)
                    ->where('schedule_id', $item->id)
                    ->order('id', 'desc')
                    ->limit(1)
                    ->findOrEmpty();
                if ($find->isEmpty()) {
                    $item->status = 1;
                } else {
                    $item->status = $find->status;
                }
                $item->is_default = 0;
            });
            $template = $template->toArray();
            $template['schedule'] = AutoTaskSceneScheduleSyncService::sanitizeSchedulesForDisplay($template['schedule'] ?? []);
            $template['schedule'] = array_merge($template['schedule'], DeviceEnum::getDefaultScheduleScene($persona->id));
            $key = array_column($template['schedule'], 'start_time');
            array_multisort($key, SORT_ASC, $template['schedule']);

            self::$returnData = $template;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }
    /**
     * @notes 详情模板
     * @return bool
     */
    public static function detailTemplate(array $params): bool
    {
        try {
            $template = MarketingTemplate::where('id', $params['id'])->findOrEmpty();
            if ($template->isEmpty()) {
                self::setError('该模板不存在');
                return false;
            }
            $template->schedule = MarketingTemplateSchedule::where('template_id', $template->id)->order('start_time', 'asc')->select();
            $data = $template->toArray();
            $data['schedule'] = AutoTaskSceneScheduleSyncService::sanitizeSchedulesForDisplay($data['schedule'] ?? []);
            self::$returnData = $data;
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @notes 可添加的任务场景列表（添加任务节点选择器用）
     * @return bool
     */
    public static function sceneLists(): bool
    {
        try {
            self::$returnData = AutoTaskSceneConfigService::getAddableSceneList();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    /**
     * @notes 模板
     *
     * @param array $params 人设ID
     * @return bool
     */
    public static function category(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }
            $default = [
                [
                    'id' => 1,
                    'name' => '专属',
                ],
                [
                    'id' => 2,
                    'name' => '自定义',
                ],
            ];
            $categories = MarketingCategory::field('id, name')->where('status', 1)->order('sort', 'desc')->select();

            self::$returnData = array_merge($default, $categories->toArray());
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @notes 添加
     *
     * @param array $params 人设ID、模板ID
     * @return bool
     */
    public static function add(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }

            $template = MarketingTemplate::create([
                'user_id' => self::$uid,
                'persona_id' => $persona->id,
                'name' => $params['name'] ?? '自定义工作流' . date('YmdHis'),
                'type' => 2,
                'category_id' => 2,
                'operation_preference' => $params['operation_preference'] ?? 1,
                'description' => $params['description'] ?? '自定义工作流',
                'status' => 1,
                'detail_content' => $params['detail_content'] ?? '',
                'detail_task_types' => $params['detail_task_types'] ?? '',
                'detail_users' => $params['detail_users'] ?? '',
                'detail_images' => $params['detail_images'] ?? [],
                'detail_videos' => $params['detail_videos'] ?? [],
                'is_system_generated' => 0,
                'create_time' => time(),
            ]);

            self::$returnData = $template->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @notes 更新
     *
     * @param array $params 人设ID、模板ID
     * @return bool
     */
    public static function update(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                self::setError('人设不存在');
                return false;
            }

            $template = MarketingTemplate::where('id', $params['id'])->findOrEmpty();
            if ($template->isEmpty()) {
                self::setError('该工作流模板不存在');
                return false;
            }

            $template->save($params);

            self::$returnData = $template->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除
     *
     * @param array $params 人设ID、模板ID
     * @return bool
     */
    public static function delete(array $params): bool
    {
        Db::startTrans();
        try {
            $template = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($template->isEmpty()) {
                throw new \Exception('该工作流模板不存在');
            }
            if ($template->type !== 2) {
                throw new \Exception('非自定义工作流模板不能删除');
            }

            $find = AiPersona::where('user_id', self::$uid)->where('workflow_template_id', $params['template_id'])->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('该工作流模板正在使用中不能删除');
            }

            MarketingTemplate::where('id', $params['template_id'])->where('user_id', self::$uid)->select()->delete();
            MarketingTemplateSchedule::where('template_id', $params['template_id'])->where('user_id', self::$uid)->select()->delete();
            AiPersonaWorkflowScheduleUser::where('template_id', $params['template_id'])->where('user_id', self::$uid)->select()->delete();

            Db::commit();
            self::$returnData = [];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * @notes 添加节点
     *
     * @param array $params 人设ID、模板ID
     * @return bool
     */
    public static function addNode(array $params): bool
    {
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $configMap = AutoTaskSceneConfigService::getConfigMap();
            if (!AutoTaskSceneConfigService::canAdd((int)$params['scene'], $configMap)) {
                throw new \Exception('该任务类型暂未开放添加');
            }
            $rawPlatforms = $params['platform'] ?? [];
            $beforeCount = is_array($rawPlatforms) ? count($rawPlatforms) : 0;
            $platforms = self::resolveAddablePlatforms((int)$params['scene'], $rawPlatforms, $configMap);
            $synced = AutoTaskSceneScheduleSyncService::syncLockedEndTime([
                'scene' => (int)$params['scene'],
                'start_time' => $params['start_time'] ?? '',
                'end_time' => $params['end_time'] ?? '',
                'platform' => $platforms,
            ], $beforeCount);
            $params['end_time'] = $synced['end_time'] ?? $params['end_time'];

            $find = MarketingTemplateSchedule::where('persona_id', $persona->id)
                ->where('template_id', $params['template_id'])
                ->where('user_id', self::$uid)
                ->where('start_time', $params['start_time'])
                ->where('end_time', $params['end_time'])
                ->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('该时段已存在节点，请重新选择时段');
            }

            $schedule = MarketingTemplateSchedule::create([
                'user_id' => self::$uid,
                'persona_id' => $persona->id,
                'template_id' => $params['template_id'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'task_category' => AutoTaskSceneConfigService::getSceneName((int)$params['scene'], $configMap),
                'scene' => $params['scene'],
                'platform' => $platforms,
                'create_time' => time(),
            ]);

            self::$returnData = $schedule->toArray();
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    /**
     *  只对引用系统模板有效
     *
     * @param array $params
     * @return boolean
     */
    public static function reset(array $params): bool
    {
        Db::startTrans();
        try {
            // 删除模板，计划以及用户计划配置
            // 复制原始id模板
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $template = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($template->isEmpty()) {
                throw new \Exception('该工作流模板不存在');
            }
            if ($template->original_id === 0) {
                throw new \Exception('该工作流模板不是引用系统模板，不能重置');
            }

            $originalTemplate = MarketingTemplate::where('id', $template->original_id)->findOrEmpty();
            if ($originalTemplate->isEmpty()) {
                throw new \Exception('原始模板不存在');
            }

            MarketingTemplateSchedule::where('template_id', $params['template_id'])->select()->delete();
            AiPersonaWorkflowScheduleUser::where('template_id', $params['template_id'])->select()->delete();

            $template->type = 2;
            $template->user_id = self::$uid;
            $template->persona_id = $persona->id;
            $template->name = $originalTemplate->name;
            $template->category_id = 2;
            $template->operation_preference = $originalTemplate->operation_preference;
            $template->description = $originalTemplate->description;
            $template->detail_content = $originalTemplate->detail_content;
            $template->status = 1;
            $template->is_system_generated = 0;
            $template->original_id = $originalTemplate->id;
            $template->update_time = time();
            $template->save();

            $configMap = AutoTaskSceneConfigService::getConfigMap();
            $schedules = MarketingTemplateSchedule::where('template_id', $originalTemplate->id)->select()->toArray();
            $insertData = self::buildSanitizedScheduleInserts(
                $schedules,
                $configMap,
                self::$uid,
                (int)$persona->id,
                (int)$template->id
            );
            if (!empty($insertData)) {
                MarketingTemplateSchedule::insertAll($insertData);
            }
            $template->schedule = MarketingTemplateSchedule::where('template_id', $template->id)->order('start_time', 'asc')->select();


            Db::commit();
            self::$returnData = $template->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     *  只对引用系统模板有效
     *
     * @param array $params
     * @return boolean
     */
    public static function updateNode(array $params): bool
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $template = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($template->isEmpty()) {
                throw new \Exception('该工作流模板不存在');
            }
            if (isset($params['operation_preference']) && $params['operation_preference'] > 0) {
                $template->operation_preference = $params['operation_preference'] ?? 0;
                $template->update_time = time();
                $template->save();
            }

            $oldSchedules = MarketingTemplateSchedule::where('template_id', $params['template_id'])->select()->toArray();
            $oldFingerprints = [];
            foreach ($oldSchedules as $old) {
                $oldFingerprints[self::buildScheduleFingerprint($old)] = (int)$old['scene'];
            }

            $configMap = AutoTaskSceneConfigService::getConfigMap();
            $newFingerprints = [];
            $insertData = [];
            foreach (self::sanitizeSchedulesForUpdate($params['schedule'] ?? [], $oldSchedules, $configMap) as $schedule) {
                $fp = self::buildScheduleFingerprint($schedule);
                $newFingerprints[$fp] = (int)$schedule['scene'];
                $insertData[] = [
                    'user_id' => self::$uid,
                    'persona_id' => $persona->id,
                    'template_id' => $template->id,
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'task_category' => $schedule['task_category'] ?? AutoTaskSceneConfigService::getSceneName((int)$schedule['scene'], $configMap),
                    'scene' => $schedule['scene'],
                    'platform' => json_encode($schedule['platform'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'remark' => $schedule['remark'] ?? '',
                    'create_time' => time(),
                ];
            }

            self::assertScheduleScenePermission($oldFingerprints, $newFingerprints, $configMap);

            MarketingTemplateSchedule::where('template_id', $params['template_id'])->select()->delete();
            if (!empty($insertData)) {
                MarketingTemplateSchedule::insertAll($insertData);
            }

            $schedules = MarketingTemplateSchedule::where('template_id', $params['template_id'])->order('start_time', 'asc')->select();

            foreach ($schedules as $schedule) {
                $find = AiPersonaWorkflowScheduleUser::where('template_id', $schedule->template_id)
                    ->where('scene', $schedule->scene)
                    ->where('start_time', $schedule->start_time)
                    ->where('end_time', $schedule->end_time)
                    ->order('id', 'desc')
                    ->limit(1)
                    ->findOrEmpty();
                if (!$find->isEmpty()) {
                    $find->schedule_id = $schedule->id;
                    $find->save();
                }
            }

            $template->schedule = $schedules;

            Db::commit();
            self::$returnData = $template->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     *  只对引用系统模板有效
     *
     * @param array $params
     * @return boolean
     */
    public static function copyTemplate(array $params): bool
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $originalTemplate = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($originalTemplate->isEmpty() || !self::canCopyTemplateType((int)$originalTemplate->type)) {
                throw new \Exception('该系统模板不存在');
            }

            $template = MarketingTemplate::create([
                'user_id' => self::$uid,
                'persona_id' => $persona->id,
                'name' => $params['name'] ?? $originalTemplate->name . '-复制',
                'category_id' => 2,
                'type' => 2,
                'operation_preference' => $originalTemplate->operation_preference,
                'description' => $originalTemplate->description,
                'detail_content' => $originalTemplate->detail_content,
                'status' => 1,
                'is_system_generated' => 0,
                'original_id' => $originalTemplate->id,
                'create_time' => time(),
            ]);
            $configMap = AutoTaskSceneConfigService::getConfigMap();
            $schedules = MarketingTemplateSchedule::where('template_id', $originalTemplate->id)->order('start_time', 'asc')->select()->toArray();
            $insertData = self::buildSanitizedScheduleInserts(
                $schedules,
                $configMap,
                self::$uid,
                (int)$persona->id,
                (int)$template->id
            );
            if (!empty($insertData)) {
                MarketingTemplateSchedule::insertAll($insertData);
            }
            $template->schedule = MarketingTemplateSchedule::where('template_id', $template->id)->order('start_time', 'asc')->select();
            Db::commit();
            self::$returnData = $template->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function changeStatusNode(array $params): bool
    {
        Db::startTrans();
        try {
            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $template = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($template->isEmpty()) {
                throw new \Exception('该系统模板不存在');
            }

            $schedule = MarketingTemplateSchedule::where('id', $params['schedule_id'])->findOrEmpty();
            if ($schedule->isEmpty()) {
                throw new \Exception('该任务计划不存在');
            }
            $find = AiPersonaWorkflowScheduleUser::where('user_id', self::$uid)
                ->where('persona_id', $persona->id)
                ->where('template_id', $template->id)
                ->where('scene', $schedule->scene)
                ->where('start_time', $schedule->start_time)
                ->where('end_time', $schedule->end_time)
                ->order('id', 'desc')
                ->limit(1)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $find = AiPersonaWorkflowScheduleUser::create([
                    'user_id' => self::$uid,
                    'persona_id' => $persona->id,
                    'template_id' => $template->id,
                    'schedule_id' => $params['schedule_id'],
                    'scene' => $schedule->scene,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => $params['status'],
                    'create_time' => time(),
                ]);
            } else {
                $find->schedule_id = $params['schedule_id'];
                $find->status = $params['status'];
                $find->save();
            }

            Db::commit();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function use(array $params): bool
    {
        Db::startTrans();
        try {

            $persona = AiPersona::where('user_id', self::$uid)->where('id', $params['persona_id'])->findOrEmpty();
            if ($persona->isEmpty()) {
                throw new \Exception('人设不存在');
            }

            $find = MarketingTemplate::where('id', $params['template_id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('该模板不存在');
            }
            $persona->workflow_template_id = $find->id;
            $persona->save();
            $devices = \app\common\model\sv\SvDevice::field('*')
                ->where('persona_id', $persona->id)
                ->where('user_id', self::$uid)
                ->select();
            foreach ($devices as $device) {
                $device->is_first = 1;
                $device->save();
                self::deleteOldPersonaTask($device, '设备人设解绑，任务取消');
            }
            Db::commit();
            self::$returnData = $persona->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    /**
     * 仅允许克隆 IP 专属(type=1) 与系统模板(type=3)
     *
     * @param int $type
     * @return bool
     */
    public static function canCopyTemplateType(int $type): bool
    {
        return in_array($type, [1, 3], true);
    }

    /**
     * 编辑保存：未改节点也剥离已关平台；关闭类型的已有节点保留；不补回重开平台。
     *
     * @param array $schedules
     * @param array $oldSchedules
     * @param array $configMap
     * @return array
     * @throws \Exception
     */
    public static function sanitizeSchedulesForUpdate(array $schedules, array $oldSchedules, array $configMap): array
    {
        $oldSceneSet = [];
        $oldBySlot = [];
        foreach ($oldSchedules as $old) {
            if (!is_array($old)) {
                continue;
            }
            $scene = (int)($old['scene'] ?? 0);
            if ($scene > 0) {
                $oldSceneSet[$scene] = true;
            }
            $oldBySlot[self::buildScheduleSlotKey($old)] = $old;
        }

        $result = [];
        $keptSlots = [];
        foreach ($schedules as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }
            $scene = (int)($schedule['scene'] ?? 0);
            if (in_array($scene, [16, 17], true)) {
                continue;
            }
            $isExistingScene = isset($oldSceneSet[$scene]);
            if (!AutoTaskSceneConfigService::canAdd($scene, $configMap) && !$isExistingScene) {
                continue;
            }

            $platforms = $schedule['platform'] ?? [];
            if (is_string($platforms)) {
                $decoded = json_decode($platforms, true);
                $platforms = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            if (!is_array($platforms)) {
                $platforms = [];
            }
            foreach ($platforms as $platform) {
                if (!is_array($platform)) {
                    continue;
                }
                if (!in_array((int)($platform['account_type'] ?? 0), [1, 2, 3, 4, 5], true)) {
                    throw new \Exception("时段{$schedule['start_time']}至{$schedule['end_time']}存在无效的账号类型");
                }
            }

            $beforeCount = count($platforms);
            $platforms = self::filterAddablePlatforms($scene, $platforms, $configMap);
            if ($platforms === [] && !$isExistingScene) {
                continue;
            }

            $row = $schedule;
            $row['scene'] = $scene;
            $row['platform'] = $platforms;
            $row['task_category'] = $schedule['task_category'] ?? AutoTaskSceneConfigService::getSceneName($scene, $configMap);
            $keptSlots[self::buildScheduleSlotKey($row)] = true;
            $result[] = AutoTaskSceneScheduleSyncService::syncLockedEndTime($row, $beforeCount);
        }

        foreach ($oldBySlot as $slot => $old) {
            if (isset($keptSlots[$slot])) {
                continue;
            }
            $scene = (int)($old['scene'] ?? 0);
            if (in_array($scene, [16, 17], true)) {
                continue;
            }
            if (AutoTaskSceneConfigService::canAdd($scene, $configMap)) {
                continue;
            }
            $platforms = $old['platform'] ?? [];
            if (is_string($platforms)) {
                $decoded = json_decode($platforms, true);
                $platforms = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            $rawPlatforms = is_array($platforms) ? $platforms : [];
            $row = $old;
            $row['scene'] = $scene;
            $row['platform'] = self::filterAddablePlatforms($scene, $rawPlatforms, $configMap);
            $row['task_category'] = $old['task_category'] ?? AutoTaskSceneConfigService::getSceneName($scene, $configMap);
            $result[] = AutoTaskSceneScheduleSyncService::syncLockedEndTime($row, count($rawPlatforms));
        }

        return $result;
    }

    /**
     * 按当前开放配置清洗待写入节点：关闭类型跳过，关闭平台剔除，全空跳过
     *
     * @param array $schedules
     * @param array $configMap
     * @return array
     */
    public static function sanitizeSchedulesForWrite(array $schedules, array $configMap): array
    {
        $result = [];
        foreach ($schedules as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }
            $scene = (int)($schedule['scene'] ?? 0);
            if (in_array($scene, [16, 17], true)) {
                continue;
            }
            if (!AutoTaskSceneConfigService::canAdd($scene, $configMap)) {
                continue;
            }
            $platforms = $schedule['platform'] ?? [];
            if (is_string($platforms)) {
                $decoded = json_decode($platforms, true);
                $platforms = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            if (!is_array($platforms)) {
                $platforms = [];
            }
            $beforeCount = count($platforms);
            $platforms = self::filterAddablePlatforms($scene, $platforms, $configMap);
            if (empty($platforms)) {
                continue;
            }
            $row = $schedule;
            $row['scene'] = $scene;
            $row['platform'] = $platforms;
            $row['task_category'] = $schedule['task_category'] ?? AutoTaskSceneConfigService::getSceneName($scene, $configMap);
            $result[] = AutoTaskSceneScheduleSyncService::syncLockedEndTime($row, $beforeCount);
        }
        return $result;
    }

    /**
     * 手动 addNode：剔除未开放平台后仍有剩余则返回；否则抛暂未开放
     *
     * @param int $scene
     * @param mixed $platforms
     * @param array $configMap
     * @return array
     * @throws \Exception
     */
    public static function resolveAddablePlatforms(int $scene, $platforms, array $configMap): array
    {
        $filtered = self::filterAddablePlatforms($scene, $platforms, $configMap);
        if (!empty($filtered)) {
            return $filtered;
        }
        self::assertPlatformsAddable($scene, $platforms, $configMap);
        throw new \Exception('该任务类型暂未开放添加');
    }

    /**
     * 将清洗后的节点转成 insertAll 行
     *
     * @param array $schedules
     * @param array $configMap
     * @param int $userId
     * @param int $personaId
     * @param int $templateId
     * @return array
     */
    private static function buildSanitizedScheduleInserts(
        array $schedules,
        array $configMap,
        int $userId,
        int $personaId,
        int $templateId
    ): array {
        $insertData = [];
        foreach (self::sanitizeSchedulesForWrite($schedules, $configMap) as $schedule) {
            $insertData[] = [
                'user_id' => $userId,
                'persona_id' => $personaId,
                'template_id' => $templateId,
                'start_time' => $schedule['start_time'] ?? '',
                'end_time' => $schedule['end_time'] ?? '',
                'task_category' => $schedule['task_category'] ?? AutoTaskSceneConfigService::getSceneName((int)$schedule['scene'], $configMap),
                'scene' => $schedule['scene'],
                'platform' => json_encode($schedule['platform'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'remark' => $schedule['remark'] ?? '',
                'create_time' => time(),
            ];
        }
        return $insertData;
    }

    /**
     * 时段槽位：场景+起止时间，用于识别已有节点（不含平台，避免剥离后对不上）
     *
     * @param array $schedule
     * @return string
     */
    private static function buildScheduleSlotKey(array $schedule): string
    {
        return (int)($schedule['scene'] ?? 0)
            . '|' . (string)($schedule['start_time'] ?? '')
            . '|' . (string)($schedule['end_time'] ?? '');
    }

    /**
     * 构建节点指纹，用于判断节点是否变更
     *
     * @param array $schedule
     * @return string
     */
    private static function buildScheduleFingerprint(array $schedule): string
    {
        $platform = $schedule['platform'] ?? [];
        if (is_string($platform)) {
            $decoded = json_decode($platform, true);
            $platform = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($platform)) {
            $platform = [];
        }
        // 规范化平台数组，避免键序导致误判
        $normalized = array_values(array_map(static function ($item) {
            return [
                'account_type' => (int)($item['account_type'] ?? 0),
                'order' => (int)($item['order'] ?? 0),
            ];
        }, $platform));
        usort($normalized, static function ($a, $b) {
            return [$a['order'], $a['account_type']] <=> [$b['order'], $b['account_type']];
        });

        return (int)($schedule['scene'] ?? 0)
            . '|' . (string)($schedule['start_time'] ?? '')
            . '|' . (string)($schedule['end_time'] ?? '')
            . '|' . md5(json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 校验 updateNode：仅拦截「全新场景类型」的新增；已有类型始终允许调整
     *
     * @param array $oldFingerprints [fingerprint => scene]
     * @param array $newFingerprints [fingerprint => scene]
     * @param array $configMap
     * @return void
     * @throws \Exception
     */
    private static function assertScheduleScenePermission(array $oldFingerprints, array $newFingerprints, array $configMap): void
    {
        $oldScenes = array_values(array_unique(array_values($oldFingerprints)));
        $oldSceneSet = array_fill_keys($oldScenes, true);

        foreach ($newFingerprints as $fp => $scene) {
            if (isset($oldFingerprints[$fp])) {
                continue;
            }

            // 已有该场景类型：视为编辑/同类型调整，放行
            if (isset($oldSceneSet[$scene])) {
                continue;
            }

            if (!AutoTaskSceneConfigService::canAdd($scene, $configMap)) {
                $name = AutoTaskSceneConfigService::getSceneName($scene, $configMap);
                throw new \Exception("任务类型「{$name}」暂未开放添加");
            }
        }
    }

    /**
     * 剔除未开放的平台（含历史数据里该类型根本不支持的平台）
     *
     * @param int $scene
     * @param mixed $platforms [{account_type, order}]
     * @param array $configMap
     * @return array
     */
    private static function filterAddablePlatforms(int $scene, $platforms, array $configMap): array
    {
        if (!is_array($platforms)) {
            return [];
        }
        $result = [];
        foreach ($platforms as $platform) {
            $accountType = (int)($platform['account_type'] ?? 0);
            if ($accountType <= 0 || !AutoTaskSceneConfigService::canAdd($scene, $accountType, $configMap)) {
                continue;
            }
            $result[] = $platform;
        }
        return array_values($result);
    }

    /**
     * 校验任务节点选择的平台是否开放（单节点新增用，addNode）
     *
     * @param int $scene
     * @param mixed $platforms [{account_type, order}]
     * @param array $configMap
     * @return void
     * @throws \Exception
     */
    private static function assertPlatformsAddable(int $scene, $platforms, array $configMap): void
    {
        if (!is_array($platforms)) {
            return;
        }
        foreach ($platforms as $platform) {
            $accountType = (int)($platform['account_type'] ?? 0);
            if ($accountType <= 0) {
                continue;
            }
            if (AutoTaskSceneConfigService::canAdd($scene, $accountType, $configMap)) {
                continue;
            }
            $sceneName = AutoTaskSceneConfigService::getSceneName($scene, $configMap);
            $platformName = DeviceEnum::getAccountTypeDesc($accountType);
            if ($platformName === '') {
                $platformName = '平台' . $accountType;
            }
            throw new \Exception("任务类型「{$sceneName}」在{$platformName}暂未开放");
        }
    }
}
