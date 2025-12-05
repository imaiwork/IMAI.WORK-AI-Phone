<?php


namespace app\common\model\auth;

use app\common\model\BaseModel;

class AdminSession extends BaseModel
{
    /**
     * @notes 关联管理员表
     * @return \think\model\relation\HasOne
     * @author 令狐冲
     * @date 2021/7/5 14:39
     */
    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'admin_id')
            ->field('id,multipoint_login');
    }
}
