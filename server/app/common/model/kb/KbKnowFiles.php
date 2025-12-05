<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 知识库文件模型
 */
class KbKnowFiles extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}