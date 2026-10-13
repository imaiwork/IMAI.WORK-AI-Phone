<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MaterialGroup extends BaseModel
{
    protected $name = 'ai_persona_material_group';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
