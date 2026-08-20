<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoVideoTask extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_video_task';
    protected $deleteTime = 'delete_time';
}
