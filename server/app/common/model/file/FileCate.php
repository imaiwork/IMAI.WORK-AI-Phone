<?php


namespace app\common\model\file;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class FileCate extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
