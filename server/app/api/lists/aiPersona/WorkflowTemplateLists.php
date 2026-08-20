<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\marketing\MarketingTemplateUser;
use app\common\model\aiPersona\AiPersona;
use think\db\Query;

class WorkflowTemplateLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['persona_id', 'category_id'],
        ];
    }

    public function where(Query $query): Query
    {
        if (isset($this->params['persona_id']) && is_numeric($this->params['persona_id']) && $this->params['persona_id'] > 0) {
            $personaId = (int)$this->params['persona_id'];
            $query->where(function ($q) use ($personaId) {
                $q->where('persona_id', $personaId)
                    ->whereOr('persona_id', 0);
            });
        }

        if (!empty($this->params['category_id'])) {
            $query->where('category_id', $this->params['category_id']);
        }

        $query->where('status', 1);
        return $query;
    }

    public function lists(): array
    {
        $query = MarketingTemplate::field('*');

        $query = $this->where($query);

        $lists = $query->order('category_id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $allIds = MarketingTemplateSchedule::field('id')
                    ->where('template_id', $item->id)
                    ->column('id');
                $userIds = AiPersonaWorkflowScheduleUser::field('schedule_id')
                    ->where('template_id', $item->id)
                    ->where('status', 0)
                    ->column('schedule_id');

                $item->schedule_count = count(array_diff($allIds, $userIds));
                $item->is_using = AiPersona::where('id', $this->params['persona_id'])->where('workflow_template_id', $item->id)->limit(1)->findOrEmpty()->isEmpty() ? 0 : 1;
            })
            ->toArray();
        return $lists;
    }

    public function count(): int
    {
        $query = MarketingTemplate::field('id');

        $query = $this->where($query);
        return $query->count();
    }
}
