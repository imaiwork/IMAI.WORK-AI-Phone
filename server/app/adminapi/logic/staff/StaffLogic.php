<?php

namespace app\adminapi\logic\staff;

use app\common\logic\BaseLogic;
use app\common\model\staff\Staff;
use Exception;


/**
 * logic
 */
class StaffLogic extends BaseLogic
{


    /**
     * 编辑
     * @param array $postData
     * @return bool
     * @author L
     * @data 2024-07-10 09:40:09
     */
    public static function edit(array $postData): bool
    {
        try {
            $info = Staff::findOrEmpty($postData['id']);
            if ($info->isEmpty()) {
                throw new Exception("信息异常");
            }

            $postData['tips'] = json_encode($postData['tips'], JSON_UNESCAPED_UNICODE);

            self::$returnData = Staff::update($postData)->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 详情
     * @param array $getData
     * @return bool
     * @author L
     * @data 2024-07-10 09:40:09
     */
    public static function detail(array $getData): bool
    {
        try {
            self::$returnData = Staff::json(['tips'], true)->findOrEmpty($getData['id'])->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 修改状态
     * @param array $params
     * @return bool
     * @author L
     * @data 2024/7/5 10:25
     */
    public static function changeStatus(array $params):bool
    {
        try {
            $info = Staff::findOrEmpty($params['id']);
            if ($info->isEmpty()) {
                throw new \Exception("信息异常");
            }
            $info->show_status = 1 - $info->show_status;
            $info->save();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
                        