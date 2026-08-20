<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoMedia extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_media';
    protected $deleteTime = 'delete_time';
}
