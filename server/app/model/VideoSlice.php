<?php

namespace app\model;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class VideoSlice extends BaseModel
{
    use SoftDelete;

    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED = 3;

    public const MODE_LOCAL = 'local';
    public const MODE_OSS = 'oss';

    public const BILL_NONE = 0;
    public const BILL_HELD = 1;
    public const BILL_CONFIRMED = 2;
    public const BILL_REFUNDED = 3;

    protected $name = 'video_slices';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    public function items()
    {
        return $this->hasMany(VideoSliceItem::class, 'slice_id', 'id')->order('sequence asc');
    }

    public function getStatusTextAttr($value, array $data): string
    {
        return match ((int)($data['status'] ?? self::STATUS_PENDING)) {
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
            default => '待处理',
        };
    }
}
