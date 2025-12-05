<?php


namespace app\common\model\file;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class File extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
