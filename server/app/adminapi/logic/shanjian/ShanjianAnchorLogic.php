<?php

namespace app\adminapi\logic\shanjian;

use app\common\logic\BaseLogic;
use app\common\model\shanjian\ShanjianAnchor;

class ShanjianAnchorLogic extends BaseLogic
{

    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                ShanjianAnchor::destroy(['id' => $id]);
            } else {
                ShanjianAnchor:: whereIn('id', $id)   ->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


}


