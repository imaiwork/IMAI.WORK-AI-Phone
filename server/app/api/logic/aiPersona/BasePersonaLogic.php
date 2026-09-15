<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\sv\SvDeviceExecutionSchedule;
use app\common\model\sv\SvDeviceExecutionScheduleUser;
use app\common\model\aiPersona\AiPersona;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\service\aiPersona\PersonaWorkflowService;
use Exception;
use think\facade\Db;

class BasePersonaLogic extends ApiLogic
{
    public static function checkScheduleIsCreate(array $payload): bool
    {

        $persona = AiPersona::where('id', $payload['persona_id'])->findOrEmpty();
        if (isset($payload['time_config'])) {
            list($startTime, $endTime) = explode('-', $payload['time_config'][0]);
        } else {
            $startTime = $payload['start_time'];
            $endTime = $payload['end_time'];
        }
        $workflow_template_id =$persona->workflow_template_id;
        $schedule = AiPersonaWorkflowScheduleUser::where('user_id', $payload['user_id'])
            ->where('persona_id', $payload['persona_id'])
            ->where('template_id', $persona->workflow_template_id)
            ->where('scene', $payload['scene'])
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->where('schedule_id', 'in', function ($query) use($workflow_template_id, $payload, $startTime, $endTime){
                $query->name('marketing_template_schedule')->where('template_id', $workflow_template_id)
                    ->where('scene', $payload['scene'])
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->field('id');
            })
            ->limit(1)
            ->findOrEmpty();

        if ($schedule->isEmpty()) {
            return true;
        }

        return (int)$schedule->status == 1 ? true : false;
    }

    public static function getSocialMediaTimesByType(int $personaType)
    {
        $maps = [
            1 => [
                1 => [
                    '08:00-08:30' => '08:02,0', //发布时间，表示用同一个生成的视频
                ]
            ],
            2 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                ]
            ],
            3 => [
                1 => [
                    '08:30-09:00' => '08:32,0',
                    '16:30-17:00' => '16:31,1',
                ]

            ],
        ];
        return $maps[$personaType] ?? [];
    }

    public static function createPersonaExclusiveWorkflow(AiPersona $persona): AiPersona
    {
        return PersonaWorkflowService::ensureExclusiveCustomWorkflow($persona);
    }

    public static function getDefaultPlatform(array $platform): string
    {
        $account = [];
        foreach ($platform as $key => $item) {
            array_push($account, [
                'account_type' => $item,
                'order' => $key + 1,
            ]);
        }
        return json_encode($account, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function getAutoSchedule(AiPersona $persona, int $scene): \think\Collection
    {
        $schedules =  MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
            ->where('scene', $scene)
            ->where('id', 'not in', function ($query) use ($persona) {
                $query->name('ai_persona_workflow_schedule_user')
                    ->where('user_id', $persona->user_id)
                    ->where('persona_id', $persona->id)
                    ->where('template_id', $persona->workflow_template_id)
                    ->where('status', 0)->field('schedule_id');
            })
            ->order('start_time', 'asc')
            ->select();
        return $schedules;
    }
}
