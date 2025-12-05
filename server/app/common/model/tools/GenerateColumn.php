<?php


namespace app\common\model\tools;


use app\common\model\BaseModel;


/**
 * 代码生成器-数据表字段信息模型
 * Class GenerateColumn
 * @package app\common\model\tools
 */
class GenerateColumn extends BaseModel
{

    /**
     * @notes 关联table表
     * @return \think\model\relation\BelongsTo
     * @author 段誉
     * @date 2022/6/15 18:59
     */
    public function generateTable()
    {
        return $this->belongsTo(GenerateTable::class, 'id', 'table_id');
    }
}
