<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaReport extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}