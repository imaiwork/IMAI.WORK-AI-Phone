<?php

namespace app\adminapi\logic\hd;

use app\common\logic\BaseLogic;
use app\common\model\hd\HdPuzzleSetting;
use app\common\model\hd\HdPuzzle;

class HdPuzzleSettingLogic extends BaseLogic
{

    
    /**
     * @notes 删除拼图设置
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
        try {
            $id = $params['id'];
            // 删除拼图设置
            HdPuzzleSetting::destroy($id);
            // 删除关联的拼图任务
            HdPuzzle::whereIn('puzzle_setting_id', $id)->select()->delete();
            
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
    

}