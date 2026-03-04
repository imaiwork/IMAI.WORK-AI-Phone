<?php

namespace app\common\model\storyboard;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 视频设置模型
 * Class StoryboardVideoSetting
 */
class StoryboardVideoSetting extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
