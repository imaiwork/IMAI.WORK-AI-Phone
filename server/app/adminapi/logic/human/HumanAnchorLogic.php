<?php

namespace app\adminapi\logic\human;

use app\api\logic\DigitalHumanLogic;
use app\common\logic\BaseLogic;
use app\common\model\human\HumanAnchor;
use app\common\service\digitalHuman\PendingAnchorTaskService;


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
            $raw = $data['id'] ?? null;
            $ids = is_array($raw) ? $raw : [$raw];
            $remain = [];
            foreach ($ids as $id) {
                $publicId = PendingAnchorTaskService::publicIdFromVirtual((int)$id);
                if ($publicId > 0) {
                    if (!DigitalHumanLogic::deletePublicAnchor(['id' => $publicId])) {
                        throw new \Exception(DigitalHumanLogic::getError() ?: '数据不存在');
                    }
                    continue;
                }
                $remain[] = $id;
            }
            if ($remain === []) {
                return true;
            }
            $data['id'] = is_array($raw) ? $remain : $remain[0];

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
