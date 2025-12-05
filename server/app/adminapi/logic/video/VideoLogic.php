<?php

namespace app\adminapi\logic\video;

use app\common\logic\BaseLogic;
use app\common\model\draw\DrawVideo;
use app\common\service\FileService;

/**
 * 图片
 */
class VideoLogic extends BaseLogic
{
    /**
     * @desc 获取详情
     * @param $params
     * @return array
     * @date 2025/7/9 18:12
     * @author Rick
     */
    public static function detail($params): array
    {
        $data = DrawVideo::findOrEmpty($params['id'])->toArray();
        if ($data){
            $data['video_url'] = FileService::getFileUrl($data['video_url']);
        }
        return $data;
    }

    /**
     * @desc 删除图片
     * @param array $data
     * @return bool
     * @date 2025/7/9 18:12
     * @author Rick
     */
    public static function del(array $data)
    {
        try {
            if (is_string($data['id'])) {
                DrawVideo::destroy(['id' => $data['id']]);
            } else {
                DrawVideo::whereIn('id', $data['id'])->select()->delete();
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
