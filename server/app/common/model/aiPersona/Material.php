<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class Material extends BaseModel
{
    protected $name = 'ai_persona_material';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const MATERIAL_TYPE_VIDEO = 1;
    const MATERIAL_TYPE_IMAGE = 2;

    const USE_STATUS_DELETED = 0;
    const USE_STATUS_ENABLED = 1;
    const USE_STATUS_DISABLED = 2;

    const PUBLISH_MODE_MAKE_VIDEO = 1;
    const PUBLISH_MODE_DIRECT_SEND = 2;

    protected $json = [];
    protected $jsonAssoc = true;

    public function getFileUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

  
    public function setFileUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    public function getThumbnailUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }


    public function setThumbnailUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
