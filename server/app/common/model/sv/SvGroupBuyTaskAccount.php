<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class SvGroupBuyTaskAccount extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function getAvatarAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    public function setAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}