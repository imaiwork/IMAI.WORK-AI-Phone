<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoPublish extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_publish';
    protected $deleteTime = 'delete_time';
}
