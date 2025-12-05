<?php

namespace app\common\model\chat;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class Role
 * @package app\common\modelassistants\
 */
class Scene extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
