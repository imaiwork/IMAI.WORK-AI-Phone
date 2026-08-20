<?php

namespace app\common\model\map;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class MapLeadRecord extends BaseModel
{
    use SoftDelete;

    protected $name = 'map_lead_record';
    protected $deleteTime = 'delete_time';

    public function getRawDataAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }
}
