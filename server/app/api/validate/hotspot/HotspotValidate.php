<?php

namespace app\api\validate\hotspot;

use app\common\service\hotspot\HotspotUpstreamException;
use app\common\service\hotspot\PersonaService;
use app\common\service\hotspot\PoolService;
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
        'ids' => 'checkIdList',
        'status' => 'in:running,done,fail,wait',
        'analysis_id' => 'integer|egt:0',
        'creation_id' => 'integer|egt:0',
        'pool_id' => 'require|integer|gt:0',
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
        'analysis_id.integer' => '分析记录不正确',
        'analysis.requireStockAnalysis' => '分析不能为空',
        'creation_id.integer' => '创作记录不正确',
        'pool_id.require' => '待用记录不存在',
        'pool_id.integer' => '待用记录不存在',
        'pool_id.gt' => '待用记录不存在',
    ];

    public function sceneHot()
    {
        return $this->only(['platform', 'period', 'day', 'limit', 'persona_id'])
            ->remove('persona_id', 'require|gt');
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
        return $this->only(['topic', 'platform', 'persona_id'])
            ->remove('persona_id', 'require|gt');
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
        // persona 的必填与模式相关（不选人设时可空），跨字段判断放 HotspotLogic::add，
        // 这里只保证 persona 传了就必须是对象（基础 array 规则）
        return $this->only(['topic', 'platform', 'title', 'script', 'hashtags', 'shots', 'persona', 'core_points', 'citations', 'analysis', 'options'])
            ->append('script', 'require')
            ->append('options', 'require|checkCreateOptions');
    }

    public function sceneAvatars()
    {
        return $this->only(['persona_id']);
    }

    public function sceneClipMaterials()
    {
        return $this->only(['persona_id', 'page_no', 'page_size', 'ids']);
    }

    public function sceneTasks()
    {
        return $this->only(['page_no', 'page_size', 'status']);
    }

    public function sceneTaskId()
    {
        return $this->only(['id']);
    }

    public function sceneStockLists()
    {
        return $this->only(['persona_id'])->remove('persona_id', 'require|gt');
    }

    public function sceneStockAdd()
    {
        return $this->only([
            'persona_id',
            'topic',
            'platform',
            'title',
            'script',
            'summary',
            'core_points',
            'analysis',
            'analysis_id',
            'creation_id',
        ])->remove('platform', 'require')
            ->remove('persona_id', 'require|gt')
            ->remove('analysis', 'array')
            ->append('analysis', 'requireStockAnalysis');
    }

    public function sceneStockDetail()
    {
        return $this->only(['topic', 'platform', 'persona_id'])
            ->remove('platform', 'require')
            ->remove('persona_id', 'require|gt');
    }

    public function sceneStockDelete()
    {
        return $this->only(['pool_id']);
    }

    /**
     * 入库与更新入库都要带分析内容。analysis_id 有值时交给业务层回读台账，这里先放行。
     */
    protected function requireStockAnalysis($value, $rule = '', array $data = [])
    {
        if (!PoolService::analysisPayloadEmpty($value)) {
            return true;
        }
        if ((int)($data['analysis_id'] ?? 0) > 0) {
            return true;
        }
        return '分析不能为空';
    }

    protected function checkTaskNo($value)
    {
        if (preg_match('/^HOT_[0-9A-F]{12}$/', (string)$value)) {
            return true;
        }
        return '任务不存在';
    }

    /** 素材 id 集合：逗号串或数组，长度设上限免得被拼超长查询 */
    protected function checkIdList($value)
    {
        if (is_array($value)) {
            return count($value) <= PersonaService::CLIP_MATERIAL_ID_LIMIT ? true : 'ids 数量过多';
        }
        if (!is_string($value)) {
            return 'ids 格式不正确';
        }
        if ($value !== '' && !preg_match('/^\d+(,\d+)*$/', $value)) {
            return 'ids 格式不正确';
        }
        return substr_count($value, ',') < PersonaService::CLIP_MATERIAL_ID_LIMIT ? true : 'ids 数量过多';
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
