<?php

namespace app\common\model\geo;

use app\common\model\BaseModel;

/**
 * GEO 异步任务(执行日志表)。
 * 注意:geo_task 表无 delete_time 列,任务本质是执行日志、无需软删,
 * 故此模型不使用 SoftDelete(否则查询会附加 WHERE delete_time IS NULL 报 Unknown column)。
 */
class GeoTask extends BaseModel
{
    protected $name = 'geo_task';
}
