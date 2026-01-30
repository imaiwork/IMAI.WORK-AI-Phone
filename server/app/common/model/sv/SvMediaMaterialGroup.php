<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 素材分组模型
 * Class SvMediaMaterialGroup
 * @package app\common\model\sv
 */
class SvMediaMaterialGroup extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**
     * 获取创建时间的格式化
     * @return string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 获取更新时间的格式化
     * @return string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}