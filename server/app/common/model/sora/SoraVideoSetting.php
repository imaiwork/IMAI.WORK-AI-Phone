<?php

namespace app\common\model\sora;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 视频设置模型
 * Class ShanjianVideoSetting
 */
class SoraVideoSetting extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
