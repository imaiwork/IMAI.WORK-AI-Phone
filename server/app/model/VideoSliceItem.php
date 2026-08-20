<?php

namespace app\model;

use app\common\model\BaseModel;

class VideoSliceItem extends BaseModel
{
    protected $name = 'video_slice_items';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;
}
