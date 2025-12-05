<?php


namespace app\common\model\tools;

use app\common\enum\GeneratorEnum;
use app\common\model\BaseModel;


/**
 * 代码生成器-数据表信息模型
 * Class GenerateTable
 * @package app\common\model\tools
 */
class GenerateTable extends BaseModel
{

    protected $json = ['menu', 'tree', 'relations', 'delete'];

    protected $jsonAssoc = true;

    /**
     * @notes 关联数据表字段
     * @return \think\model\relation\HasMany
     * @author 段誉
     * @date 2022/6/15 10:46
     */
    public function tableColumn()
    {
        return $this->hasMany(GenerateColumn::class, 'table_id', 'id');
    }

    /**
     * @notes 模板类型描述
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 段誉
     * @date 2022/6/14 11:25
     */
    public function getTemplateTypeDescAttr($value, $data)
    {
        return GeneratorEnum::getTemplateTypeDesc($data['template_type']);
    }
}
