<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaSynthesisCopywriting extends BaseModel
{
    protected $name = 'ai_persona_synthesis_copywriting';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    //关键词获取结果: 1-失败, 2成功
    const STATUS_FAILED = 1;
    const STATUS_SUCCESS = 2;

    //使用状态: 0-未使用, 1使用中 2已使用
    const USE_STATE_UNUSED = 0;
    const USE_STATE_USING = 1;
    const USE_STATE_USED = 2;
}
