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
                ->findOrEmpty()->toArray();
            
            if (!$music) {
                self::setError('音乐不存在');
                return false;
            }
            Music::update($params);
            self::$returnData = Music::where('id', $params['id'])->findOrEmpty()->toArray();
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
