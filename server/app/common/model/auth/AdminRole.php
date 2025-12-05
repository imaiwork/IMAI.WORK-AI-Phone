<?php


namespace app\common\model\auth;

use app\common\model\BaseModel;

class AdminRole extends BaseModel
{

    /**
     * @notes 删除用户关联角色
     * @param $adminId
     * @return bool
     * @author 段誉
     * @date 2022/11/25 14:14
     */
    public static function delByUserId($adminId)
    {
        return self::where(['admin_id' => $adminId])->delete();
    }
}
