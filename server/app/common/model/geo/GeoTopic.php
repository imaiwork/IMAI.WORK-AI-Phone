<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoTopic extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_topic';
    protected $deleteTime = 'delete_time';
}
