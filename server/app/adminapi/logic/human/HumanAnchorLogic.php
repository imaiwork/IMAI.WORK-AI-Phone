<?php

namespace app\adminapi\logic\human;

use app\api\logic\DigitalHumanLogic;
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
                $humanAnchor = HumanAnchor::findOrEmpty($data['id']);
                if ($humanAnchor->isEmpty()) {
                    throw new \Exception('数据不存在');
                }
                if ($humanAnchor['dh_id'] !== 0){
                    DigitalHumanLogic::deletePublicAnchor(['id'=>$humanAnchor['dh_id']]);
                }else{
                    HumanAnchor::destroy(['id' => $data['id']]);
                }
            } else {
                $humanAnchors = HumanAnchor::where('id','in', $data['id'])->select();
                if ($humanAnchors->isEmpty()) {
                    throw new \Exception('数据不存在');
                }

                foreach ($humanAnchors as $humanAnchor){
                    if ($humanAnchor['dh_id'] !== 0){
                        DigitalHumanLogic::deletePublicAnchor(['id'=>$humanAnchor['dh_id']]);
                    }else{
                        HumanAnchor::destroy($data['id']);
                    }
                }

            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
