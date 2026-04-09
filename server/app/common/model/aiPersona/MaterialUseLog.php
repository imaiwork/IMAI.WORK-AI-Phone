<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MaterialUseLog extends BaseModel
{
    protected $name = 'ai_persona_material_use_log';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const USE_SCENE_AI_GENERATE = 1;
    const USE_SCENE_CONTENT_PUBLISH = 2;
    const USE_SCENE_DEVICE_DISTRIBUTE = 3;

    const USE_STATUS_USING = 0;
    const USE_STATUS_SUCCESS = 1;
    const USE_STATUS_FAILED = 2;

    protected $json = [];
    protected $jsonAssoc = true;
}
