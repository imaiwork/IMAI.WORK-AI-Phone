<?php

namespace app\common\model\hotspot;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HotspotCreation extends BaseModel
{
    use SoftDelete;

    protected $name = 'hotspot_creation';

    protected $deleteTime = 'delete_time';

    protected $json = [
        'hashtags_json',
        'shots_json',
    ];

    protected $jsonAssoc = true;
}
