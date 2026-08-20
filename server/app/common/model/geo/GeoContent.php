<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoContent extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_content';
    protected $deleteTime = 'delete_time';
}
