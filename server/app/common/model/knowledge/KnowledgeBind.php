<?php


namespace app\common\model\knowledge;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


/**
 * KnowledgeBind模型
 * Class KnowledgeBind
 * @package app\common\model\knowledge
 */
class KnowledgeBind extends BaseModel
{
    use SoftDelete;
    protected $name = 'knowledge_bind';
    protected $deleteTime = 'delete_time';

    
}