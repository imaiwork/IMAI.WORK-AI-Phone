<?php

namespace app\api\logic\aiPersona;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
use app\common\model\sv\SvDeviceViralRecord;
use think\facade\Db;

/**
 * 人设内容记录 - 图片仿写记录逻辑
 */
class ImageRecordLogic extends ApiLogic
{
    /**
     * 批量删除图片仿写记录
     */
    public static function batchDelete(array $ids): bool
    {
        Db::startTrans();
        try {
            $ids = self::normalizeIds($ids);
            if (empty($ids)) {
                self::setError('请选择要删除的记录');
                Db::rollback();
                return false;
            }

            $delIds = SvDeviceViralRecord::where('user_id', self::$uid)
                ->where('publish_media_type', AiPersona::PUBLISH_MEDIA_TYPE_IMAGE_TEXT)
                ->whereIn('id', $ids)
                ->column('id');
            if (empty($delIds)) {
                self::setError('记录不存在');
                Db::rollback();
                return false;
            }

            AiPersonaSynthesisCopywriting::where('user_id', self::$uid)
                ->whereIn('sv_device_viral_record_id', $delIds)
                ->select()
                ->delete();

            SvDeviceViralRecord::where('user_id', self::$uid)
                ->whereIn('id', $delIds)
                ->delete();

            Db::commit();
            self::$returnData = [
                'ids' => $delIds,
                'count' => count($delIds),
            ];
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            self::setError($th->getMessage());
            return false;
        }
    }

    private static function normalizeIds($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }
        if (!is_array($ids)) {
            return [];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        return array_values(array_unique($ids));
    }
}
