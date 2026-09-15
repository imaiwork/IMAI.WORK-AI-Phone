<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class SvDySearchRecord extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function setCoverUrlsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCoverUrlsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function getAvatarAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    public function setAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public function getStitchUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    public function setStitchUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
