<?php

namespace app\common\model\hotspot;

use app\common\model\BaseModel;

class HotspotDailySnapshot extends BaseModel
{
    protected $name = 'hotspot_daily_snapshot';

    protected $json = ['topics_json'];

    protected $jsonAssoc = true;
}
