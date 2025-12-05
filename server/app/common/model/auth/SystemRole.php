<?php


namespace app\common\model\auth;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 角色模型
 * Class Role
 * @package app\common\model
 */
class SystemRole extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $name = 'system_role';

    /**
     * @notes 角色与菜单关联关系
     * @return \think\model\relation\HasMany
     * @author 段誉
     * @date 2022/7/6 11:16
     */
    public function roleMenuIndex()
    {
        return $this->hasMany(SystemRoleMenu::class, 'role_id');
    }
}
