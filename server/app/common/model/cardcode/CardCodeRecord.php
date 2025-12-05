<?php

namespace app\common\model\cardcode;
use app\common\model\BaseModel;

class CardCodeRecord extends BaseModel
{
    protected $json = ['package_snapshot'];

    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

}