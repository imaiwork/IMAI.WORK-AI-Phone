<?php

namespace app\adminapi\logic\hd;

use app\common\logic\BaseLogic;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;

class HdPuzzleLogic extends BaseLogic
{


    
    /**
     * @notes 删除拼图任务
     * @param array $params
     * @return bool
     */
    public static function delete(array $params): bool
    {
       try {
            $id = $params['id'];
            // 删除任务
            HdPuzzle::destroy($id);
            return true;
       } catch (\Exception $e) {
           self::setError($e->getMessage());
           return false;
       }
    }

}