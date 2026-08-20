<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoAuthAccount extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_auth_account';
    protected $deleteTime = 'delete_time';
}
