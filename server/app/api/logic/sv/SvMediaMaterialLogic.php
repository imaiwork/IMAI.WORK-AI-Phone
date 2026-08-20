<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvMediaMaterial;
use app\common\service\FileService;

class SvMediaMaterialLogic extends SvBaseLogic
{
    public static function addSvMediaMaterial(array $params)
    {
        // 添加素材逻辑
        try {
            $data = [
                'user_id' => self::$uid,
                'content' =>  $params['content'],
                'name' =>  $params['name'],
                'pic' =>  $params['pic'] ?? '',
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
                'm_type'=> $params['m_type'],
                'type'=>$params['type'],
                'size'=>$params['size'] ?? 0,
                'group_id'=>$params['group_id'] ?? 0,
                'duration'=>$params['duration'] ?? 0,
            ];

            $result = SvMediaMaterial::create($data);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateSvMediaMaterial(array $params)
    {
        // 更新素材逻辑
        try {
            $material = SvMediaMaterial::where('id',$params['id'])->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $data['name'] = $params['name'];
            $data['id'] = $params['id'];
            $data['pic'] = $params['pic'] ?? '';
            $data['group_id'] = $params['group_id'] ?? 0;
            $material->update($data);
            self::$returnData = $material->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteSvMediaMaterial($id)
    {
        // 删除素材逻辑
        try {
            $ids = is_array($id) ? $id : [$id];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($ids)) {
                self::setError('请选择要删除的素材');
                return false;
            }

            $materials = SvMediaMaterial::whereIn('id', $ids)
                ->where('user_id', self::$uid)
                ->select();
            if ($materials->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }

            $materials->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getSvMediaMaterial(array $params)
    {
        $material = SvMediaMaterial::where('id',$params['id'])->where('user_id', self::$uid)
            ->findOrEmpty()->toArray();
        if (!$material) {
            self::setError('素材不存在');
            return false;
        }
        $material['content'] = $material['content'] ? FileService::getFileUrl($material['content']) : '';
        self::$returnData = $material;
        return true;
    }


    public static function batchUpdateSvMediaMaterial(array $params)
    {
        // 批量更新素材逻辑
        try {
            if (!isset($params['ids']) || !is_array($params['ids'])) {
                self::setError('请选择要操作的素材');
                return false;
            }
            $ids = $params['ids'];
            $material = SvMediaMaterial::whereIn('id', $ids)->where('user_id', self::$uid)
                ->select();
            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            $updateData = [
                'group_id' => $params['group_id'] ?? 0,
            ];
            SvMediaMaterial::whereIn('id', $ids)->where('user_id', self::$uid)->update($updateData);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getByGroupId(array $params)
    {
        // 根据分组id获取素材列表
        try {
            $material = SvMediaMaterial::where('group_id',$params['group_id'])->where('user_id', self::$uid)
                ->select();
            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            self::$returnData = $material->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function batchUpdateDate(array $params)
    {
        try {
           
            $ids = array_column($params, 'id');
            $ids = array_unique(array_filter($ids));
            if (empty($ids)) {
                self::setError('请选择要操作的素材');
                return false;
            }
            $material = SvMediaMaterial::whereIn('id', $ids)->where('user_id', self::$uid)
                ->select();
            if ($material->isEmpty()) {
                self::setError('素材不存在');
                return false;
            }
            
            $materialIds = $material->column('id');
            $updateDate = array_values(array_filter($params, function($item) use ($materialIds) {
                return in_array($item['id'], $materialIds);
            }));
          
            $SvMediaMaterialModel = new SvMediaMaterial;
            $SvMediaMaterialModel->saveAll($updateDate);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

}
