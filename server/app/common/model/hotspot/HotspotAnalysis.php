<?php

namespace app\common\model\hotspot;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HotspotAnalysis extends BaseModel
{
    use SoftDelete;

    protected $name = 'hotspot_analysis';

    protected $deleteTime = 'delete_time';

    protected $json = [
        'persona_json',
        'hooks_json',
        'risks_json',
    ];

    protected $jsonAssoc = true;
}
