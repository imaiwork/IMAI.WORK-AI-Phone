<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * @property int $id 主键ID
 * @property int $user_id 用户ID
 * ...其他字段
 */
class AiPersonaDigitalVoice extends BaseModel
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
     * 关联音色列表
     */
    public function humanVoice()
    {
        return $this->belongsTo(\app\common\model\human\HumanVoice::class, 'voice_id', 'id');
    }
}