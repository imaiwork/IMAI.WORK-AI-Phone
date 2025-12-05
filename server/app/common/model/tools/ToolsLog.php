<?php

namespace app\common\model\tools;

use think\Model;
use think\model\concern\SoftDelete;

class ToolsLog extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}