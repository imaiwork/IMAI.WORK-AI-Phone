<?php


namespace app\common\model\human;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * HumanVideo
 * @desc 数字人视频
 * @author dagouzi
 */
class HumanVideo extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public static function getStatus($status)
    {
        // 状态 init 初始化；start 发送给算法；pending 算法排队； process 算法开始处理； success 成功； fail 失败；
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
            'init' => 0,
            'start' => 3,
            'pending' => 4,
            'process' => 5,
            'success' => 1,
            'fail' => 2
        ];
        return $data[$status] ?? 2;
    }
}
