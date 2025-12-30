<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AutoDeviceSetting extends BaseModel {

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $json = ['human_image', 'clip_material', 'image_material'];
    protected $jsonAssoc = true;
}