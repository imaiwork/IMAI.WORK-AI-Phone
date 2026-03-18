<?php

namespace app\common\model\cardcode;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 卡密套餐模型
 * Class CardPackage
 * @package app\common\model\cardcode
 */
class CardPackage extends BaseModel
{
    use SoftDelete;

    protected $name = 'card_package';
    protected string $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = true;
}
