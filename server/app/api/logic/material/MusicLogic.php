<?php

namespace app\api\logic\material;

use app\api\logic\ApiLogic;
use app\common\model\material\Music;
use app\common\service\FileService;

class MusicLogic extends ApiLogic
{
    public static function addMusic(array $params)
    {
        try {
            $data = [
                'source_id' => self::$uid,
                'source' => 1, // 用户上传
                'style' => $params['style'] ?? 0,
                'name' => $params['name'] ?? '',
                'url' => $params['url'],
                'status' => $params['status'] ?? 0,
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
            ];

            $music = new Music();
            $music->save($data);
            self::$returnData = $data;
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateMusic(array $params)
    {
        try {
            $music = Music::where('id', $params['id'])
                ->where('source', 1)
                ->where('source_id', self::$uid)
                ->findOrEmpty();
            
            if ($music->isEmpty()) {
                self::setError('音乐不存在');
                return false;
            }
            // 只允许改白名单字段，且走模型实例保存：防止 params 带 source / source_id 改归属或改到别人的行，
            // 同时保留 setUrlAttr 去域名与 update_time 自动写入
            $data = array_intersect_key($params, array_flip(['style', 'name', 'url', 'status', 'sort']));
            if ($data) {
                $music->save($data);
            }
            self::$returnData = Music::where('id', $music->id)->findOrEmpty()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteMusic($id)
    {
        try {
            if (is_string($id)) {
                Music::destroy(['id' => $id, 'source_id' => self::$uid, 'source' => 1]);
            } else {
                Music::whereIn('id', $id)->where('source_id', self::$uid)->where('source', 1)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getMusic(array $params)
    {
        $music = Music::where('id', $params['id'])
            ->where('source_id', self::$uid)
            ->where('source',1)
            ->findOrEmpty()->toArray();
        
        if (!$music) {
            self::setError('音乐不存在');
            return false;
        }
        $music['uri'] = $music['url'];
        $music['url'] = $music['url'] ? FileService::getFileUrl($music['url']) : '';
        $music['style_text'] = Music::getStyleText($music['style']);
        self::$returnData = $music;
        return true;
    }
}
