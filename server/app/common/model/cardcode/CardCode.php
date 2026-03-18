<?php

namespace app\common\model\cardcode;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * Class CardCode
 * @package app\common\model\cardcode
 */
class CardCode extends BaseModel
{
    use SoftDelete;
    protected string $deleteTime = 'delete_time';

}