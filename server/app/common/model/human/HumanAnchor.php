<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * HumanAnchor
 * @desc 数字人形象
 * @author dagouzi
 */
class HumanAnchor extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public static function getStatus($status)
    {
        // initialized 初始化；sent 发送给算法；pending 算法排队； processing 算法开始处理； completed 成功； failed 失败；
        $data = [
            0 => '初始化',
            1 => '成功',
            2 => '失败',
            3 => '发送给算法',
            4 => '算法排队',
            5 => '算法开始处理'
        ];
        return $data[$status] ?? '未知';
    }

    public static function transferStatus($status)
    {
        $data = [
            'initialized' => 0,
            'sent' => 3,
            'pending' => 4,
            'processing' => 5,
            'completed' => 1,
            'failed' => 2
        ];
        return $data[$status] ?? 2;
    }
}
