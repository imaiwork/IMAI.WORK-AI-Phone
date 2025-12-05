<?php

namespace app\adminapi\logic\meeting;

use app\common\logic\BaseLogic;
use app\common\model\audio\Audio;
use app\common\model\audio\AudioInfo;

/**
 * 会议
 */
class MeetingLogic extends BaseLogic
{


    /**
     * @notes 删除会议
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
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
}
