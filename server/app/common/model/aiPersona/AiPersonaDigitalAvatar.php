<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaDigitalAvatar extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

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