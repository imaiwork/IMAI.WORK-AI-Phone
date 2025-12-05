<?php

namespace app\adminapi\logic\sv;

use app\common\model\sv\SvRobot;
use app\common\logic\BaseLogic;
/**
 * RobotLogic
 * @desc 机器人
 * @author Qasim
 */
class RobotLogic extends BaseLogic
{



    /**
     * @desc 删除机器人
     * @param array $data
     * @return bool
     */
    public static function deleteRobot(array $data)
    {
        try {

            if (is_string($data['id'])) {
                SvRobot::destroy(['id' => $data['id']]);
            } else {
                SvRobot::destroy($data['id']);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
   
}
