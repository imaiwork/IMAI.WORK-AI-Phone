<?php


namespace app\common\model\chat;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class Assistants extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
