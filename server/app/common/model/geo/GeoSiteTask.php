<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoSiteTask extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_site_task';
    protected $deleteTime = 'delete_time';
}
