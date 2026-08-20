<?php

namespace app\adminapi\logic\aiPersona;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use Exception;
use think\facade\Db;

class MaterialLogic extends BaseLogic
{
    public static function update(array $params): bool
    {
        Db::startTrans();
        try {
            $id = intval($params['id']);
            $material = Material::where(['id' => $id])->findOrEmpty();
            if ($material->isEmpty()) {
                throw new Exception('素材不存在');
            }

            $updateData = [
                'material_name' => $params['material_name'] ?? $material->material_name,
                'use_status' => isset($params['use_status']) ? intval($params['use_status']) : $material->use_status,
                'update_time' => time()
            ];

            Material::update($updateData, ['id' => $id]);

            Db::commit();
            self::$returnData = ['id' => $id];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        Db::startTrans();
        try {
            $material = Material::where(['id' => $id])->findOrEmpty();
            if ($material->isEmpty()) {
                throw new Exception('素材不存在');
            }

            Material::destroy($id);
            self::deleteUseLogs([$id]);

            Db::commit();
            self::$returnData = ['id' => $id];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function batchDelete(array $ids): bool
    {
        Db::startTrans();
        try {
            $ids = self::normalizeIds($ids);
            if (empty($ids)) {
                throw new Exception('请选择要删除的素材');
            }

            $delIds = Material::whereIn('id', $ids)->column('id');
            if (empty($delIds)) {
                throw new Exception('素材不存在');
            }

            Material::destroy($delIds);
            self::deleteUseLogs($delIds);

            Db::commit();
            self::$returnData = ['id' => $delIds];
            return true;
        } catch (Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(int $id): bool
    {
        try {
            $material = Material::where(['id' => $id])->findOrEmpty();
            if ($material->isEmpty()) {
                throw new Exception('素材不存在');
            }

            self::$returnData = $material->toArray();
            return true;
        } catch (Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    private static function normalizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    private static function deleteUseLogs(array $materialIds): void
    {
        $logIds = MaterialUseLog::whereIn('material_id', $materialIds)->column('id');
        if (!empty($logIds)) {
            MaterialUseLog::destroy($logIds);
        }
    }
}
