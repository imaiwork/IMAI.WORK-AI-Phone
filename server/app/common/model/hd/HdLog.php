<?php

namespace app\common\model\hd;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class HdLog extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**
     * @desc ai类型
     * @param $type
     * @return string|string[]
     * @date 2024/7/18 9:24
     * @author dagouzi
     */
    public static function getType($type)
    {
        $data = [
            1 => '商品图',
            2 => 'ai试衣',
        ];
        if (!empty($type)) {
            return $data[$type] ?? '';
        }
        return $data;
    }

    public function image()
    {
        return $this->hasMany(HdImage::class, 'log_id', 'id')->field('id,log_id,image,sub_task_id,task_status,task_completion');
    }
}
