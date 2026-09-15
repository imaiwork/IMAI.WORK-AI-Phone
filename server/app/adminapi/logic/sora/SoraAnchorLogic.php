<?php

namespace app\adminapi\logic\sora;

use app\common\logic\BaseLogic;
use app\common\model\sora\SoraAnchor;

class SoraAnchorLogic extends BaseLogic
{
    const SORA_AVATAR = 'soraAvatar';

    public static function update($params)
    {
        try {
            $id     = $params['id'];
            $public = $params['is_public'] ?? 0;

            $anchor = SoraAnchor::where('id', $id)->findOrEmpty();
            if ($anchor->isEmpty()) {
                throw new \Exception('角色不存在');
            }

            if (!empty($params['name'])) {
                $name = $params['name'];
                $same = SoraAnchor::where('name', $name)->findOrEmpty();
                if (!$same->isEmpty()) {
                    self::setError('已存在同名角色，请修改角色名称');
                    return false;
                }
                $anchor->name = $name;
            }

            $anchor->is_public = $public;
            $anchor->save();
            self::$returnData = $anchor->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function delete($id)
    {
        try {
            if (is_string($id)) {
                SoraAnchor::destroy(['id' => $id]);
            } else {
                SoraAnchor::whereIn('id', $id)->select()->delete();
            }
            self::$returnData = [];
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function detail(array $params)
    {
        $model = SoraAnchor::where('id', $params['id'])
                           ->findOrEmpty();
        if ($model->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        self::$returnData = $model->toArray();
        return true;
    }

}


