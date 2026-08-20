<?php


namespace app\api\logic\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\marketing\MarketingCategory;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\service\auto\AutoTaskSceneConfigService;
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
            self::$returnData = $template->toArray();
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

            $find = MarketingTemplateSchedule::where('persona_id', $persona->id)
                ->where('template_id', $params['template_id'])
                ->where('user_id', self::$uid)
                ->where('start_time', $params['start_time'])
                ->where('end_time', $params['end_time'])
                ->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('该时段已存在节点，请重新选择时段');
            }

            if (!AutoTaskSceneConfigService::canAdd((int)$params['scene'])) {
                throw new \Exception('该任务类型暂未开放添加');
            }

            $schedule = MarketingTemplateSchedule::create([
                'user_id' => self::$uid,
                'persona_id' => $persona->id,
                'template_id' => $params['template_id'],
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'task_category' => AutoTaskSceneConfigService::getSceneName((int)$params['scene']),
                'scene' => $params['scene'],
                'platform' => $params['platform'],
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

            $schedules = MarketingTemplateSchedule::where('template_id', $originalTemplate->id)->select();
            $insertData = [];
            foreach ($schedules as $schedule) {
                array_push($insertData, [
                    'user_id' => self::$uid,
                    'persona_id' => $persona->id,
                    'template_id' => $template->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'task_category' => $schedule->task_category,
                    'scene' => $schedule->scene,
                    'platform' => json_encode($schedule->platform),
                    'remark' => $schedule->remark,
                    'create_time' => time(),
                ]);
            }
            MarketingTemplateSchedule::insertAll($insertData);
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
            foreach ($params['schedule'] as $schedule) {
                if (in_array((int)$schedule['scene'], [16, 17], true)) {
                    continue;
                }
                // 后台已关闭「允许添加」的场景一律不写入（含模板原有关闭类型）
                if (!AutoTaskSceneConfigService::canAdd((int)$schedule['scene'], $configMap)) {
                    continue;
                }
                foreach ($schedule['platform'] as $platform) {
                    if (!in_array((int)$platform['account_type'], [1, 2, 3, 4, 5], true)) {
                        throw new \Exception("时段{$schedule['start_time']}至{$schedule['end_time']}存在无效的账号类型");
                    }
                }

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
                    'platform' => json_encode($schedule['platform']),
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

            $originalTemplate = MarketingTemplate::where('id', $params['template_id'])->where('type', 3)->findOrEmpty();
            if ($originalTemplate->isEmpty()) {
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
            $schedules = MarketingTemplateSchedule::where('template_id', $originalTemplate->id)->order('start_time', 'asc')->select();
            $insertData = [];
            foreach ($schedules as $schedule) {
                array_push($insertData, [
                    'user_id' => self::$uid,
                    'persona_id' => $persona->id,
                    'template_id' => $template->id,
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'task_category' => $schedule['task_category'] ?? AutoTaskSceneConfigService::getSceneName((int)$schedule['scene']),
                    'scene' => $schedule['scene'],
                    'platform' => json_encode($schedule['platform']),
                    'remark' => $schedule['remark'] ?? '',
                    'create_time' => time(),
                ]);
            }
            MarketingTemplateSchedule::insertAll($insertData);
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
}
