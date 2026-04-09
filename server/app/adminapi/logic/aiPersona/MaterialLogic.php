<?php

namespace app\adminapi\logic\aiPersona;

use app\common\logic\BaseLogic;
use app\common\model\aiPersona\Material;
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

            Db::commit();
            self::$returnData = ['id' => $id];
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
}
