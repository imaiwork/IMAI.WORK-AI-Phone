<?php

namespace app\adminapi\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoPublish;

/**
 * GEO 发布记录管理。
 * 只允许删除终态记录:pending 记录可能还有未退的投递扣费/回执待回填,
 * 后台删除会造成资金与台账不一致,统一拒绝。
 */
class GeoPublishAdminLogic extends BaseLogic
{
    public static function delete(array $params): bool
    {
        try {
            $row = GeoPublish::where('id', (int)$params['id'])->findOrEmpty();
            if ($row->isEmpty()) {
                throw new \Exception('记录不存在');
            }
            if ($row->status === 'pending') {
                throw new \Exception('待发布记录不可删除,请等待发布完成或失败后再操作');
            }
            $row->delete();
            return true;
        } catch (\Throwable $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
