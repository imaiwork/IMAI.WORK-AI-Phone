<?php
namespace app\common\model\marketing;


use app\common\model\BaseModel;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use think\model\concern\SoftDelete;

class MarketingTemplateSchedule extends BaseModel
{
    public function setPlatformAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getPlatformAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }

    /**
     * 统计人设工作流中可用的发布时段数量（已剔除用户关闭的时段）
     * @param int $personaId 人设ID
     * @param int $scene 场景：5=社媒内容发布，7=朋友圈发布
     */
    public static function getTodayPublishTaskCount(int $personaId, int $scene = 5): int
    {
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return 0;
        }

        $scheduleIds = self::where('template_id', $persona->workflow_template_id)
            ->where('scene', $scene)
            ->column('id');
        if (empty($scheduleIds)) {
            return 0;
        }

        $userRemoveIds = AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
            ->where('template_id', $persona->workflow_template_id)
            ->where('user_id', $persona->user_id)
            ->where('scene', $scene)
            ->where('status', 0)
            ->column('schedule_id');

        foreach ($scheduleIds as $key => $scheduleId) {
            if (in_array($scheduleId, $userRemoveIds)) {
                unset($scheduleIds[$key]);
            }
        }

        return count($scheduleIds);
    }

    /**
     * 统计人设工作流中「需要视频素材」的发布时段数量（已剔除用户关闭的时段）。
     * 与内容发布建单侧同口径：小红书配置图文的纯图文时段走图片池，不计入视频出片配额。
     * @param int $personaId 人设ID
     * @param int $scene 场景：5=社媒内容发布
     */
    public static function getTodayVideoPublishTaskCount(int $personaId, int $scene = 5): int
    {
        $persona = AiPersona::where('id', $personaId)->findOrEmpty();
        if ($persona->isEmpty()) {
            return 0;
        }

        $schedules = self::where('template_id', $persona->workflow_template_id)
            ->where('scene', $scene)
            ->where('id', 'not in', function ($query) use ($persona, $scene) {
                $query->name('ai_persona_workflow_schedule_user')
                    ->where('user_id', $persona->user_id)
                    ->where('persona_id', $persona->id)
                    ->where('template_id', $persona->workflow_template_id)
                    ->where('status', 0)->field('schedule_id');
            })
            ->field('id,platform')
            ->select();

        $count = 0;
        foreach ($schedules as $schedule) {
            if (AiPersona::scheduleNeedsVideoPublish($persona, $schedule->platform)) {
                $count++;
            }
        }

        return $count;
    }

}
