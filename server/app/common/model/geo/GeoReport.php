<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoReport extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_report';
    protected $deleteTime = 'delete_time';
}
