<?php

namespace app\api\logic\sv;

use app\common\model\sv\SvMediaMaterialGroup;

class SvMediaMaterialGroupLogic extends SvBaseLogic
{
    public static function addSvMediaMaterialGroup(array $params)
    {
        // 添加素材分组逻辑
        try {
            $data = [
                'user_id' => self::$uid,
                'name' =>  $params['name'],
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
                'update_time' => time(),
            ];

            $result = SvMediaMaterialGroup::create($data);
            self::$returnData = $result->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateSvMediaMaterialGroup(array $params)
    {
        // 更新素材分组逻辑
        try {
            $group = SvMediaMaterialGroup::where('id', $params['id'])->where('user_id', self::$uid)
                ->findOrEmpty();
            if (!$group) {
                self::setError('素材分组不存在');
                return false;
            }
            $data = [
                'name' => $params['name'],
                'sort' => $params['sort'] ?? 0,
                'update_time' => time(),
            ];
            $group->save($data);
            self::$returnData = $group->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteSvMediaMaterialGroup($id)
    {
        // 删除素材分组逻辑
        try {
            if (is_string($id)) {
                SvMediaMaterialGroup::destroy(['id' => $id, 'user_id' => self::$uid]);
            } else {
                SvMediaMaterialGroup::whereIn('id', $id)->where('user_id', self::$uid)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getSvMediaMaterialGroup(array $params)
    {
        $group = SvMediaMaterialGroup::where('id', $params['id'])->where('user_id', self::$uid)
            ->findOrEmpty()->toArray();
        if (!$group) {
            self::setError('素材分组不存在');
            return false;
        }
        self::$returnData = $group;
        return true;
    }
}