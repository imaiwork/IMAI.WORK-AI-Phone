<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\service\MemberService;
use think\model\concern\SoftDelete;

/**
 * 用户等级（含订阅配额）
 */
class UserLevel extends BaseModel
{
    use SoftDelete;

    protected $name = 'user_level';
    protected $deleteTime = 'delete_time';
    protected $pk = 'id';

    const CYCLE_NONE  = 0;
    const CYCLE_DAY   = 1;
    const CYCLE_MONTH = 2;
    const CYCLE_YEAR  = 3;

    /**
     * 读取时按本地 models 表补 name,返回 id=>name 供接口展示
     * 落库只存 id 数组,见 setAllowedModelsAttr
     */
    public function getAllowedModelsAttr($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        return MemberService::formatAllowedModels($value);
    }

    /** 只保存模型 id,如 [2,4] */
    public function setAllowedModelsAttr($value)
    {
        $ids = MemberService::parseAllowedModelIds($value);
        return json_encode(array_values($ids));
    }

    /** 兼容旧字段名 name */
    public function getNameAttr($value, $data)
    {
        return $data['level_name'] ?? $value ?? '';
    }
}
