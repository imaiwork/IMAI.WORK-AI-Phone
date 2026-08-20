<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoSite extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_site';
    protected $deleteTime = 'delete_time';
}
