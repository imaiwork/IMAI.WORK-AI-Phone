<?php
namespace app\common\model\lianlian;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class LlAnalysis
 * @package app\common\modellianlian\
 */
class LlAnalysis extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function getModelResponseAttr($value)  
    {
        if($value){
            return json_decode($value, true);
        }
        return [];
    }
}
            