<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AutoDeviceWechatCircleConfig extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $json = ['video_material', 'image_material','exec_time'];
    protected $jsonAssoc = true;
}
