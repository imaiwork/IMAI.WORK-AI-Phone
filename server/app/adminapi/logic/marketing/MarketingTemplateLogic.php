<?php


namespace app\adminapi\logic\marketing;

use app\common\logic\BaseLogic;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\aiPersona\AiPersona;
use app\common\enum\DeviceEnum;
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
            $insertData = array();
            foreach ($params['schedule'] as $schedule) {
                $insertData[] = [
                    'user_id' => 0,
                    'persona_id' => 0,
                    'template_id' => $template->id,
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'task_category' => DeviceEnum::getTaskSceneDesc($schedule['scene']),
                    'scene' => $schedule['scene'],
                    'platform' => json_encode($schedule['platform'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'create_time' => time(),
                ];
            }
            MarketingTemplateSchedule::insertAll($insertData);
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
            self::$returnData = $template->toArray();
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
                $insertData = array();
                foreach ($params['schedule'] as $schedule) {
                    $insertData[] = [
                        'user_id' => $find->user_id,
                        'persona_id' => $find->persona_id,
                        'template_id' => $find->id,
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'task_category' => DeviceEnum::getTaskSceneDesc($schedule['scene']),
                        'scene' => $schedule['scene'],
                        'platform' => json_encode($schedule['platform'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'create_time' => time(),
                    ];
                }
                MarketingTemplateSchedule::insertAll($insertData);
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
}
