<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvMediaManualSetting extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $json = ['media_url', 'copywriting', 'extra'];

    // 如果 JSON 数据是嵌套数组，设置 JSON 关联属性名（可选）
    protected $jsonAssoc = true;  // 强制返回
    // 这里可以添加模型特有的方法
}