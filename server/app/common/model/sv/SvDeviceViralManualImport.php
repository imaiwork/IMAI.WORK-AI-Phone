<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 爆款库手动导入待执行
 */
class SvDeviceViralManualImport extends BaseModel
{
    use SoftDelete;

    protected $name = 'sv_device_viral_manual_import';
    protected $deleteTime = 'delete_time';

    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAIL = 3;
    public const STATUS_PARTIAL = 4;

    public const MAX_RETRY = 3;
    public const STALE_SECONDS = 1800;
    public const REMARK_QUEUED = '已排队，将于每日00:00-03:00按人设下全部设备解析入库';
    public const REMARK_PROCESSING = '手动解析中';

    public function setResultJsonAttr(mixed $value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value;
    }

    public function getResultJsonAttr(mixed $value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setParsedPayloadAttr(mixed $value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $value;
    }

    public function getParsedPayloadAttr(mixed $value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
