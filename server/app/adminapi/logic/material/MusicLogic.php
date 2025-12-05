<?php

namespace app\adminapi\logic\material;

use app\common\logic\BaseLogic;
use app\common\model\material\Music;
use app\common\service\FileService;

class MusicLogic extends BaseLogic
{
    public static function addMusic(array $params)
    {
        try {
            $data = [
                'source_id' => $params['source_id'],
                'source' => 0, // 后台上传
                'style' => $params['style'] ?? 0,
                'name' => $params['name'] ?? '',
                'url' => $params['url'],
                'status' => $params['status'] ?? 0,
                'sort' => $params['sort'] ?? 0,
                'create_time' => time(),
            ];

            $music = new Music();
            $music->save($data);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function updateMusic(array $params)
    {
        try {
            $music = Music::where('id', $params['id'])->where('source',0)->findOrEmpty()->toArray();
            if (!$music) {
                self::setError('音乐不存在');
                return false;
            }
            Music::update($params);
            self::$returnData = $params;
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
                Music::destroy(['id' => $id, 'source' => 0]);
            } else {
                Music::whereIn('id', $id)->where('source', 0)->select()->delete();
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function getMusic(array $params)
    {
        $music = Music::where('id', $params['id'])->where('source', 0)->findOrEmpty()->toArray();
        
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
