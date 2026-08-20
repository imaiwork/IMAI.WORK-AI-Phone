<?php


namespace app\common\model\minimax;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * Minimax
 * @desc Minimax音色
 * @author dagouzi
 */
class MinimaxShanjianTask extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}
