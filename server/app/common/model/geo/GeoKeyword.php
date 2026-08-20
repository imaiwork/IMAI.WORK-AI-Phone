<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class GeoKeyword extends BaseModel
{
    use SoftDelete;
    protected $name = 'geo_keyword';
    protected $deleteTime = 'delete_time';
    // 表无 update_time 列:显式关闭自动更新时间戳,防同实例二次 save 时 ORM 带上 update_time 报 1054
    protected $updateTime = false;
}
