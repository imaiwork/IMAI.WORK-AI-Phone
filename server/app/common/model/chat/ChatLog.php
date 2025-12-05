<?php


namespace app\common\model\chat;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class ChatLog extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /**
     * 使用量入库
     * @param array $value
     * @return string
     */
    public function setUsageTokensAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return json_encode([]);
    }

    /**
     * 使用量出库
     * @param string $value
     * @return array
     */
    public function getUsageTokensAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}
