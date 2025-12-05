<?php

namespace app\adminapi\logic\human;

use app\common\logic\BaseLogic;
use app\common\model\human\HumanAudio;

/**
 * 音频
 */
class HumanAudioLogic extends BaseLogic
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
                HumanAudio::destroy(['id' => $data['id']]);
            } else {
                HumanAudio::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
