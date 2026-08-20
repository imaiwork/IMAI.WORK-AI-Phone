<?php
namespace app\common\model\marketing;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MarketingTemplate extends BaseModel
{
    
    public function setDetailImagesAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getDetailImagesAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }

    public function setDetailVideosAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getDetailVideosAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}
