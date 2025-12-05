<?php

namespace app\adminapi\logic\assistants;

use app\common\logic\BaseLogic;
use app\common\model\chat\ChatLog;

class ChatLogLogic extends BaseLogic
{


    /**
     * @notes 删除聊天记录
     * @param array $data
     * @return bool
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public static function delete(array $data): bool
    {
        try {

            if (is_string($data['id'])) {
                ChatLog::destroy(['id' => $data['id']]);
            } else {
                ChatLog::destroy($data['id']);
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }
}
