<?php

namespace app\adminapi\logic\knowledge;

use app\common\logic\BaseLogic;
use app\common\model\knowledge\KnowledgeFile;


/**
 * 形象
 */
class FileLogic extends BaseLogic
{


    /**
     * @notes 删除形象
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        try {

            if (is_string($data['id'])) {
                KnowledgeFile::destroy(['id' => $data['id']]);
            } else {
                KnowledgeFile::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
