<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaDigitalAvatar extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**
     * 可用于视频合成的人设形象：人设绑定记录、公共形象、闪剪形象都必须有效。
     */
    public static function availableQuery(string $alias = 'ad')
    {
        return self::alias($alias)
            ->join('digital_human_anchor dha', "{$alias}.dh_id = dha.id")
            ->join('shanjian_anchor sa', "{$alias}.dh_id = sa.dh_id")
            ->leftJoin('human_voice hv', "{$alias}.third_voice_id = hv.voice_id AND {$alias}.user_id = hv.user_id")
            ->whereNull("{$alias}.delete_time")
            ->whereNull('dha.delete_time')
            ->where('dha.status', '=', 2)
            ->whereNull('sa.delete_time')
            ->where('sa.status', '=', 6)
            ->where("{$alias}.third_avatar_id", '<>', '')
            ->where("{$alias}.third_voice_id", '<>', '')
            ->whereRaw("(({$alias}.is_original_voice = 1 AND sa.voice_id = {$alias}.third_voice_id) OR ({$alias}.is_original_voice = 0 AND hv.delete_time IS NULL AND hv.status = 1))");
    }

    /**
     * 关联人设主表
     */
    public function persona()
    {
        return $this->belongsTo(\app\common\model\aiPersona\AiPersona::class, 'persona_id', 'id');
    }

    /**
     * 关联公共数字人形象
     */
    public function humanAnchor()
    {
        return $this->belongsTo(\app\common\model\digitalHuman\DigitalHumanAnchor::class, 'dh_id', 'id');
    }
}
