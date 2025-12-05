<?php


namespace app\common\model\kb;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * QA数据拆分模型 (利用GPT拆分)
 */
class KbKnowQa extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';
}