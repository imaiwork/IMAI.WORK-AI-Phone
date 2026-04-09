<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersona extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    // 关联个人IP扩展表
    public function individual()
    {
        return $this->hasOne(AiPersonaIndividual::class, 'persona_id', 'id');
    }

    // 关联企业服务扩展表
    public function enterprise()
    {
        return $this->hasOne(AiPersonaEnterprise::class, 'persona_id', 'id');
    }

    // 关联本地商家扩展表
    public function local()
    {
        return $this->hasOne(AiPersonaLocal::class, 'persona_id', 'id');
    }

    // 获取器：处理报告内容JSON
    public function getReportContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：处理报告内容JSON
    public function setReportContentAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function searchStartTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('create_time', '>=', strtotime($value));
        }
    }

    public function searchEndTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('create_time', '<=', strtotime($value));
        }
    }
}