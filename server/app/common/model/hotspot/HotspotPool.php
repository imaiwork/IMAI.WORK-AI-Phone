<?php

namespace app\common\model\hotspot;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HotspotPool extends BaseModel
{
    use SoftDelete;

    protected $name = 'hotspot_pool';

    protected $deleteTime = 'delete_time';

    protected $json = [
        'core_points_json',
        'analysis_json',
    ];

    protected $jsonAssoc = true;

    /** 热点库占用标识，对齐 GET /stock 的 use_status */
    public const USE_STATUS_PENDING = 0;

    public const USE_STATUS_USED = 1;
}
