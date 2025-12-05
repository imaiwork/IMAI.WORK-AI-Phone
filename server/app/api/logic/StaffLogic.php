<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;

class StaffLogic extends BaseLogic
{


    /**
     * 详情
     * @param string $id
     * @return bool
     * @author L
     * @data 2024/6/29 10:30
     */
    public static function detail(string $id): bool
    {
        try {
            $staff =  Staff::where('id', $id)->findOrEmpty();

            if ($staff->isEmpty()) {

                throw new \Exception('AI员工不存在');
            }

            $staff = $staff->toArray();

            $staff['tips'] = json_decode($staff['tips']);

            self::$returnData = $staff;
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}