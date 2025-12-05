<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 闪剪数字人形象模型
 * 对应表：iw_shanjian_anchor
 */
class ShanjianAnchor extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getVoiceUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     */
    public function setVoiceUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

}


