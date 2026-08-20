<?php

namespace app\common\model\map;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MapLeadConversation extends BaseModel
{
    use SoftDelete;

    protected $name = 'map_lead_conversation';
    protected $deleteTime = 'delete_time';
}

