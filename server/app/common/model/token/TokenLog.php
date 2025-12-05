<?php


namespace app\common\model\article;

use app\common\enum\YesNoEnum;
use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 资讯管理模型
 * Class Article
 * @package app\common\model\article;
 */
class TokenLog extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    /**
     * @desc 获取token消耗类型 1:普通聊天 2:画图 3:会议妙计 4:思维导图 5:音乐 6:场景聊天
     * @param $type
     * @return string|string[]
     * @date 2024/7/29 14:45
     * @author dagouzi
     */
    public static function getType($type = 0)
    {
        $data = [
            1 => '普通聊天',
            2 => '画图',
            3 => '会议妙计',
            4 => '思维导图',
            5 => '音乐',
            6 => '场景聊天',
        ];
        if (!empty($type)) {
            return $data[$type] ?? '';
        }
        return $data;
    }
}
