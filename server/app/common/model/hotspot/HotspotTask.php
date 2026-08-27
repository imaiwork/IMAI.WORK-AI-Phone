<?php

namespace app\common\model\hotspot;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HotspotTask extends BaseModel
{
    use SoftDelete;

    protected $name = 'hotspot_task';

    protected $deleteTime = 'delete_time';

    protected $json = [
        'persona_json',
        'core_points_json',
        'citations_json',
        'analysis_json',
        'options_json',
        'step_status_json',
    ];

    protected $jsonAssoc = true;
}
