<?php

namespace app\common\model\audio;

use think\Model;
use think\model\concern\SoftDelete;

class AudioKeyWords extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}