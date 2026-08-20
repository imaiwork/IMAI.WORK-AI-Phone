<?php


namespace app\common\model\tutorial;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 教程分类管理模型
 * Class TutorialCategory
 * @package app\common\model\tutorial;
 */
class TutorialCategory extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    public function getIsShowDescAttr($value, $data)
    {
        return $data['is_show'] ? '启用' : '停用';
    }
}
