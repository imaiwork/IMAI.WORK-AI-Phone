<?php

namespace app\adminapi\logic\audio;

use app\common\logic\BaseLogic;
use app\common\model\audio\AudioInfo;
use app\common\service\FileService;

/**
 * logic
 */
class AudioLogic extends BaseLogic
{

    /**
     * 删除
     * @param array $data
     * @return bool
     * @author L
     * @data 2024-07-10 09:40:09
     */
    public static function delete(array $data): bool
    {
        try {
            if (is_string($data['id'])) {
                AudioInfo::destroy(['id' => $data['id']]);
            } else {
                AudioInfo::destroy($data['id']);
            }
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


     /**
     * 详情
     * @param int $id
     * @return bool
     * @author L
     * @data 2024-07-10 09:40:09
     */
    public static function detail(int $id): bool
    {
        try {
            $audioInfo = AudioInfo::where('id', $id)->findOrEmpty();

            if ($audioInfo->isEmpty()) {
                throw new \Exception('任务不存在');
            }

            $audioInfo->url     = FileService::getFileUrl($audioInfo->url);
            $audioInfo->ws_url  = $audioInfo->ws_url ?: '';
            $audioInfo->text    = $audioInfo->text ?: '';
            self::$returnData   = $audioInfo->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
