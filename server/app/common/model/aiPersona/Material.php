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
    const MATERIAL_TYPE_MUSIC = 3;

    /** 背景音乐时长上限（秒）：闪剪合成侧硬限制 0-300 秒，超长会被回调拒绝 */
    const MUSIC_MAX_DURATION = 300;

    const USE_STATUS_DELETED = 0;
    const USE_STATUS_ENABLED = 1;
    const USE_STATUS_DISABLED = 2;

    const PUBLISH_MODE_MAKE_VIDEO = 1;
    const PUBLISH_MODE_DIRECT_SEND = 2;

    const SLICE_STATUS_NONE = 0;
    const SLICE_STATUS_PENDING = 1;
    const SLICE_STATUS_PROCESSING = 2;
    const SLICE_STATUS_SUCCESS = 3;
    const SLICE_STATUS_FAILED = 4;

    protected $json = [];
    protected $jsonAssoc = true;

    public function getFileUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }

  
    public function setFileUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    public function getThumbnailUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }


    public function setThumbnailUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public static function getSliceStatusText(int $status): string
    {
        return match ($status) {
            self::SLICE_STATUS_PENDING => '待分割',
            self::SLICE_STATUS_PROCESSING => '分割中',
            self::SLICE_STATUS_SUCCESS => '分割成功',
            self::SLICE_STATUS_FAILED => '分割失败',
            default => '无需分割',
        };
    }

    public function getSliceStatusTextAttr($value, array $data): string
    {
        return self::getSliceStatusText((int)($data['slice_status'] ?? self::SLICE_STATUS_NONE));
    }
}
