<?php

namespace app\adminapi\logic\hd;

use app\common\logic\BaseLogic;
use app\common\model\hd\HdImage;
use app\common\model\hd\HdLog;
use app\common\service\FileService;

/**
 * 图片
 */
class HdImageLogic extends BaseLogic
{


    /**
     * @notes 删除图片
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                HdLog::destroy(['id' => $data['id']]);
                HdImage::where('log_id', $data['id'])->select()->delete();
            } else {
                HdLog::destroy($data['id']);
                HdImage::whereIn('log_id', $data['id'])->select()->delete();
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 获取详情
     * @param $params
     * @return array
     * @date 2024/5/23 11:33
     * @author dagouzi
     */
    public static function detail($params): array
    {
        $data = HdLog::findOrEmpty($params['id'])->toArray();
        if ($data){
            $data['params'] = json_decode( $data['params']);
            $image = HdImage::where('log_id', $data['id'])->value('image');
            $data['image']  = '';
            if ($image){
                $data['image'] = FileService::getFileUrl($image);
            }
        }
        return $data;
    }
}
