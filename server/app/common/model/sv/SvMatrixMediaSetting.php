<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 矩阵媒体设置模型
 * Class SvMatrixMediaSetting
 */
class SvMatrixMediaSetting extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
