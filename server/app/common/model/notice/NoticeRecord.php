<?php

namespace app\common\model\notice;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 通知记录模型
 * Class Notice
 * @package app\common\model
 */
class NoticeRecord extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';
}
