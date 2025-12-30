<?php

namespace app\adminapi\logic\shanjian;

use app\api\logic\DigitalHumanLogic;
use app\common\logic\BaseLogic;
use app\common\model\shanjian\ShanjianAnchor;

class ShanjianAnchorLogic extends BaseLogic
{

    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                $shanjianAnchor = ShanjianAnchor::findOrEmpty($id);
                if ($shanjianAnchor->isEmpty()) {
                    throw new \Exception('数据不存在');
                }

                if ($shanjianAnchor['dh_id'] !== 0){
                    DigitalHumanLogic::deletePublicAnchor(['id'=>$shanjianAnchor['dh_id']]);
                }else{
                    ShanjianAnchor::destroy(['id' => $id]);
                }
            } else {
                $shanjianAnchors = ShanjianAnchor:: whereIn('id', $id)->select();
                foreach ($shanjianAnchors as $shanjianAnchor) {
                    if ($shanjianAnchor['dh_id'] !== 0){
                        DigitalHumanLogic::deletePublicAnchor(['id'=>$shanjianAnchor['dh_id']]);
                    }else{
                        ShanjianAnchor::destroy(['id' => $shanjianAnchor['id']]);
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


}


