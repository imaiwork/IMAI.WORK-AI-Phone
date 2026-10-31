<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaCopywritingLibrary extends BaseModel
{
    use SoftDelete;

    protected $name = 'ai_persona_copywriting_library';
    protected $deleteTime = 'delete_time';

    const LIBRARY_TYPE_VIDEO_DRIVER = 1;
    const LIBRARY_TYPE_PUBLISH = 2;

    const DRIVER_TYPE_NONE = 0;
    const DRIVER_TYPE_NEWS = 1;
    const DRIVER_TYPE_ORAL = 2;
    const DRIVER_TYPE_MATERIAL_MIXCUT = 3;

    const SOURCE_MANUAL = 1;
    const SOURCE_IMPORT = 2;
    const SOURCE_PUBLIC = 3;
    /** 公共文案AI仿写：内容由模型按人设重写，与原模板无版本关系 */
    const SOURCE_AI_REWRITE = 4;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    public static function driverTypeByShanjianType(int $shanjianType): int
    {
        return match ($shanjianType) {
            4 => self::DRIVER_TYPE_NEWS,
            3 => self::DRIVER_TYPE_MATERIAL_MIXCUT,
            default => self::DRIVER_TYPE_ORAL,
        };
    }
}
