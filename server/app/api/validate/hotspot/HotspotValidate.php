<?php

namespace app\api\validate\hotspot;

use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\PersonaService;
use app\common\service\hotspot\ScriptService;
use app\common\validate\BaseValidate;

class HotspotValidate extends BaseValidate
{
    protected $rule = [
        // 目前仅开放抖音（与 HotListService::PLATFORMS 同步维护）
        'platform' => 'require|in:douyin',
        'period' => 'in:day,week,rise',
        'day' => 'dateFormat:Y-m-d',
        'limit' => 'integer|between:1,100',
        'topic' => 'require|max:120',
        'app_name' => 'in:aweme',
        'category' => 'max:64',
        'summary' => 'max:20000',
        'core_points' => 'array',
        'persona' => 'array',
        'portrait' => 'max:4000',
        'analysis' => 'array',
        'options' => 'array',
        'title' => 'max:200',
        'script' => 'max:20000',
        'hashtags' => 'array',
        'shots' => 'array',
        'citations' => 'array',
        'id' => 'require|checkTaskNo',
        'persona_id' => 'require|integer|gt:0',
        'page_no' => 'integer|egt:1',
        'page_size' => 'integer|between:1,50',
        'status' => 'in:running,done,fail,wait',
    ];

    protected $message = [
        'platform.require' => '平台不能为空',
        'platform.in' => '不支持的平台',
        'period.in' => 'period 仅支持 day/week/rise',
        'day.dateFormat' => '日期格式必须为 Y-m-d',
        'limit.integer' => 'limit 必须是整数',
        'limit.between' => 'limit 必须在 1 到 100 之间',
        'topic.require' => '话题不能为空',
        'topic.max' => '话题不能超过 120 字',
        'app_name.in' => 'app_name 仅支持 aweme',
        'core_points.array' => 'core_points 必须是数组',
        'persona.array' => 'persona 必须是对象',
        'persona.require' => '人设不能为空',
        'options.array' => 'options 必须是对象',
        'options.require' => '请选择视频类型',
        'script.require' => '口播文案不能为空',
        'id.require' => '任务不存在',
        'persona_id.require' => '人设不能为空',
        'persona_id.integer' => '人设不能为空',
        'persona_id.gt' => '人设不能为空',
        'page_no.integer' => '页码必须是整数',
        'page_no.egt' => '页码必须大于 0',
        'page_size.integer' => '每页数量必须是整数',
        'page_size.between' => '每页数量必须在 1 到 50 之间',
        'status.in' => '任务状态不支持',
    ];

    public function sceneHot()
    {
        return $this->only(['platform', 'period', 'day', 'limit']);
    }

    public function sceneHistoryDates()
    {
        return $this->only(['platform']);
    }

    public function sceneInsight()
    {
        return $this->only(['topic']);
    }

    public function sceneHotWords()
    {
        return $this->only(['app_name']);
    }

    public function sceneResearch()
    {
        return $this->only(['topic', 'platform', 'category']);
    }

    public function sceneLastFlow()
    {
        return $this->only(['topic', 'platform']);
    }

    public function sceneAnalyze()
    {
        return $this->only(['topic', 'platform', 'summary', 'core_points', 'persona', 'portrait'])
            ->append('persona', 'require|checkPersonaIdentity');
    }

    public function sceneScript()
    {
        return $this->only(['topic', 'platform', 'core_points', 'summary', 'persona', 'analysis', 'options']);
    }

    public function sceneTaskAdd()
    {
        // persona 必填：视频合成必须解析人设的形象/音色/素材，无人设任务受理后必然异步失败
        return $this->only(['topic', 'platform', 'title', 'script', 'hashtags', 'shots', 'persona', 'core_points', 'citations', 'analysis', 'options'])
            ->append('script', 'require')
            ->append('persona', 'require|checkPersonaIdentity')
            ->append('options', 'require|checkCreateOptions');
    }

    public function sceneAvatars()
    {
        return $this->only(['persona_id']);
    }

    public function sceneClipMaterials()
    {
        return $this->only(['persona_id', 'page_no', 'page_size']);
    }

    public function sceneTasks()
    {
        return $this->only(['page_no', 'page_size', 'status']);
    }

    public function sceneTaskId()
    {
        return $this->only(['id']);
    }

    protected function checkTaskNo($value)
    {
        if (preg_match('/^HOT_[0-9A-F]{12}$/', (string)$value)) {
            return true;
        }
        return '任务不存在';
    }

    protected function checkPersonaIdentity($value)
    {
        if (!is_array($value)) {
            return '人设不能为空';
        }
        if (!PersonaService::hasIdentity($value)) {
            return '人设不能为空';
        }
        return true;
    }

    protected function checkCreateOptions($value)
    {
        if (!is_array($value)) {
            return 'options 必须是对象';
        }
        try {
            ScriptService::assertCreateOptions($value);
        } catch (HotspotUpstreamException $e) {
            return $e->getMessage();
        }
        return true;
    }
}
