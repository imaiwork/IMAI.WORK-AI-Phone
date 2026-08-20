<?php

namespace app\adminapi\validate\user;

use app\common\model\user\UserLevel;
use app\common\validate\BaseValidate;

/**
 * 用户等级验证
 * Class UserLevelValidate
 * @package app\adminapi\validate\user
 */
class UserLevelValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkLevel',
        'level_name' => 'require|max:50',
        'sort' => 'require|number',
    ];

    protected $message = [
        'id.require' => '请选择用户等级id',
        'level_name.require' => '请填写等级名称',
        'level_name.max' => '等级名称最多50个字符',
        'sort.require' => '请填写排序权重',
        'sort.number' => '排序权重必须为数字',
    ];

    /**
     * @notes 详情场景
     * @return UserLevelValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 新增场景
     * @return UserLevelValidate
     */
    public function sceneCreate()
    {
        return $this->only(['level_name', 'sort']);
    }

    /**
     * @notes 编辑场景
     * @return UserLevelValidate
     */
    public function sceneEdit()
    {
        return $this->only(['id', 'level_name', 'sort']);
    }

    /**
     * @notes 删除场景
     * @return UserLevelValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 校验用户等级是否存在（支持批量）
     * @param $value
     * @return bool|string
     */
    public function checkLevel($value)
    {
        $ids = is_array($value) ? $value : [$value];

        foreach ($ids as $id) {
            $level = UserLevel::find($id);
            if (!$level) {
                return '用户等级id' . $id . '不存在';
            }
        }
        return true;
    }

   
}
