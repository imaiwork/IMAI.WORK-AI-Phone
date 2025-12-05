<?php

namespace app\adminapi\logic\human;

use app\common\logic\BaseLogic;
use app\common\model\human\HumanAnchor;


/**
 * 形象
 */
class HumanAnchorLogic extends BaseLogic
{


    /**
     * @notes 删除形象
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        try {

            if (is_string($data['id'])) {
                HumanAnchor::destroy(['id' => $data['id']]);
            } else {
                HumanAnchor::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
