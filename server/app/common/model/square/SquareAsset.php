<?php

namespace app\common\model\square;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class SquareAsset extends BaseModel
{
    use SoftDelete;

    protected $name = 'square_asset';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
    protected $json = ['tags'];
    protected $jsonAssoc = true;

    public const KIND_VIDEO = 1;
    public const KIND_IMAGE = 2;

    public function getUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }

    public function setUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public function getCoverUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }

    public function setCoverUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}