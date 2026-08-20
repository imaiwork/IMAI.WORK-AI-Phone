<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoProject extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_project';
    protected $deleteTime = 'delete_time';
}
