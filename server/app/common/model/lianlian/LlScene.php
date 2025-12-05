<?php

namespace app\common\model\lianlian;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class LlScene
 * @package app\common\modellianlian\
 */
class LlScene extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    public function getTrainingTargetAttr($value)
    {
        if($value){
            return json_decode($value, true);
        }
        return [];
    }

    public function getAnalysisReportConfigAttr($value)
    {   
        if($value){
            return json_decode($value, true);
        }
        return [];
    }

    public function getTipsAttr($value){
        if($value){
            return json_decode($value, true);
        }
        return [];
    }
}
            