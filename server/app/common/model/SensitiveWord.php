<?php


namespace app\common\model;

use think\model\concern\SoftDelete;

/**
 * 敏感词模型类
 */
class SensitiveWord extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';

    /**
     * @notes 敏感词数组
     * @param $value
     * @param $data
     * @return string[]
     * @author ljj
     */
    public function getWordArrAttr($value, $data): array
    {
        unset($value);
        return explode('；',$data['word']);
    }
}