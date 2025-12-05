<?php

namespace app\adminapi\logic\mindMap;

use app\common\logic\BaseLogic;
use app\common\model\mindMap\MindMap;

/**
 * 思维导图
 */
class MindMapLogic extends BaseLogic
{


    /**
     * @notes 删除思维导图
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        
        try {

            if (is_string($data['id'])) {
                MindMap::destroy(['id' => $data['id']]);
            } else {
                MindMap::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
