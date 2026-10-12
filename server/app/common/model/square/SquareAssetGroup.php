<?php

namespace app\common\model\square;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SquareAssetGroup extends BaseModel
{
    use SoftDelete;

    protected $name = 'square_asset_group';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
}