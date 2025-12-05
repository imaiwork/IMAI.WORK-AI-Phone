<?php

namespace app\common\model\audio;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AudioInfo extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const STATUS = [
        "FAILED"    => '转写失败',
        "ONGOING"   => '转写中',
        "COMPLETED" => '转写成功',
        "INVALID"   => '无效的参数'
    ];

    public function getRemarkAttr($value)
    {   
        if(!$value){
            return '';
        }
        return self::STATUS[$value];
    }

    /**
     * 设置转写结果
     * @param array $value
     * @return string
     */
    public function setResponseAttr($value)
    {
        if(!$value){
            return '';
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?? '';
    }

    /**
     * 获取转写结果
     * @param string $value
     * @return array
     */
    public function getResponseAttr($value)
    {
        if(!$value){

            return [];
        }
        return json_decode($value, true) ?? [];
    }
}
